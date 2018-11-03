<?php

namespace SMProxy;

use SMProxy\Handler\Frontend\FrontendConnection;
use SMProxy\MysqlPacket\AuthPacket;
use SMProxy\MysqlPacket\MySqlPacketDecoder;
use SMProxy\MysqlPacket\ErrorPacket;
use SMProxy\MysqlPacket\MySQLPacket;
use SMProxy\MysqlPacket\OkPacket;
use SMProxy\Parser\ServerParse;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午6:32
 */
class SMProxyServer extends BaseServer
{

    /**
     * 连接
     *
     * @param $server
     * @param $fd
     *
     * @throws SMProxyException
     */
    public function onConnect($server, $fd)
    {
//        print_r('connect ' . $fd . "\n");
        $this->initMysqlClient($fd);
    }

    /**
     * 接收消息
     *
     * @param $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    public function onReceive($server, $fd, $reactor_id, $data)
    {
        $this->go(function () use ($server, $fd, $reactor_id, $data) {
            $dbConfig = $this->parseDbConfig($this->server
                ->connection_info($fd)['server_port']);
            $this->initMysqlClientConfig($fd);
            $client = $this->getClient($fd, 'client');
            $readClient = $this->getClient($fd, 'readClient');
            if ($client) {
                if (!$client->client->isConnected()) {
                    $this->initWriteMysqlClient($fd);
                    $client = $this->getClient($fd, 'client');
                }
                $bin = (new MySqlPacketDecoder())->decode($data);
                $type = $bin->data[4];
                if (strpos($data, 'mysql_native_password') > 0 && $type != 3) {
                    $authPacket = new AuthPacket();
                    $authPacket->read($bin);
                    $authPacket->database = $authPacket->database ?: ($client
                        ->database ?: $dbConfig['write']['database']);
                    $readClient->database = $authPacket->database;
                    $client->database = $authPacket->database;
                    $data = getString($authPacket->write());
                    $auth_data = substr($data, 36);
                    //判断是第几次使用链接
                    if ($this->getClient($fd, 'step') == 0) {
                        $this->putClient($fd, $auth_data, 'mysql_native_password');
                        //首次连接发送验证包
                        $err = $client->client->send($data);
                    } else {
                        //第二次登录 验证密码正确性
                        if ($this->getClient($fd, 'mysql_native_password') === $auth_data) {
                            //返回ok包
                            $send_data = getString(OkPacket::$AUTH_OK);
                        } else {
                            //返回error包
                            $errPacket = new ErrorPacket();
                            $errPacket->sqlstate = '28000';
                            $errPacket->packetId = 2;
                            $errPacket->message = 'Access denied for user (using password: YES)';
                            $message = $errPacket->write();
                            $send_data = getString($message);
                        }
                        if ($server->exist($fd)) {
                            //手动发送数据包
                            $server->send($fd, $send_data);
                        }

                    }
                } else {
                    $this->connectReadState[$fd] = false;
                    switch ($type) {
                        case MySQLPacket::$COM_INIT_DB:
                            // just init the frontend
                            break;
                        case MySQLPacket::$COM_QUERY:
                            $connection = new FrontendConnection();
                            $queryType = $connection->query($bin);
                            if ($queryType == ServerParse::SELECT ||
                                $queryType == ServerParse::SHOW ||
                                $queryType == ServerParse::SET ||
                                $queryType == ServerParse::USE
                            ) {
                                $this->connectReadState[$fd] = true;
                            }
                            break;
                        case MySQLPacket::$COM_PING:
                            break;
                        case MySQLPacket::$COM_QUIT:
                            //禁用客户端退出
                            $data = '';
                            break;
                        case MySQLPacket::$COM_PROCESS_KILL:
                            break;
                        case MySQLPacket::$COM_STMT_PREPARE:
                            $this->connectReadState[$fd] = true;
                            break;
                        case MySQLPacket::$COM_STMT_EXECUTE:
                            $this->connectReadState[$fd] = true;
                            break;
                        case MySQLPacket::$COM_STMT_CLOSE:
                            $this->connectReadState[$fd] = true;
                            if (substr($data, -5) == getString([1, 0, 0, 0, 1])) {
                                $data = substr($data, 0, strlen($data) - 5);
                            }
                            break;
                        case MySQLPacket::$COM_HEARTBEAT:
                            break;
                        default:
                            break;
                    }
                    //发送除断开连接的请求转发
                    if ($data) {
                        if (isset($this->connectReadState[$fd]) && $this->connectReadState[$fd] === true) {
                            if (isset($dbConfig['read']['host'])) {
                                $client = $readClient;
                                if (!$client->client->isConnected()) {
                                    $this->initReadMysqlClient($fd);
                                    $client = $this->getClient($fd, 'readClient');
                                }
                                $this->putClient($fd, $this->getClient($fd, 'readStep') + 1, 'readStep');
                            }
                        } else {
                            $this->putClient($fd, $this->getClient($fd, 'step') + 1, 'step');
                        }
                        if (!$this->getClient($fd, 'step')) {
                            $this->putClient($fd, $this->getClient($fd, 'step') + 1, 'step');
                        }
                        if (!$this->getClient($fd, 'readStep')) {
                            $this->putClient($fd, $this->getClient($fd, 'readStep') + 1, 'readStep');
                        }
                        $err = $client->client->send($data);
                    }
                }
                if (isset($err) && !$err) {
                    print_r($client->client->errCode . "\n");
                }
            } else {
                $server->close($fd);
            }
        });
    }


    /**
     * 客户端断开连接
     *
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        parent::onClose($server, $fd);
//        echo "connection close: {$fd}\n";
    }

    /**
     * 初始化mysql配置文件
     *
     * @param int $fd
     *
     * @throws SMProxyException
     */
    public function initMysqlClientConfig(int $fd)
    {
        $config = $this->parseDbConfig($this->server
            ->connection_info($fd)['server_port']);
        self::put('mysql_config', $config ?? 20);
        return $config;
    }

    /**
     * 初始化mysql客户端
     *
     * @param int $fd
     *
     * @throws SMProxyException
     */
    public function initMysqlClient(int $fd)
    {
        $this->go(function () use ($fd) {
            $this->initMysqlClientConfig($fd);
            $client = $this->getClient($fd, 'client');
            $readClient = $this->getClient($fd, 'readClient');
            if (!$client) {
                $this->initMysqlProxyClient($fd);
            } else {
                if (!$readClient->client->isConnected()) {
                    $this->initReadMysqlClient($fd);
                }
                if (!$client->client->isConnected()) {
                    $this->initWriteMysqlClient($fd);
                } else {
                    $client->serverFd = $fd;
                    $readClient->serverFd = $fd;
                    if ($this->server->exist($fd)) {
                        //手动发送mysql验证包
                        $err = $this->server->send($fd, $client->handshake);
                        if (!$err) {
                            print_r($this->server->errCode . "\n");
                        }
                    }
                }
            }
        });
    }

}