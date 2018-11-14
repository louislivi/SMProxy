<?php

namespace SMProxy;

use SMProxy\Handler\Frontend\FrontendAuthenticator;
use SMProxy\Handler\Frontend\FrontendConnection;
use SMProxy\Log\Log;
use SMProxy\MysqlPacket\AuthPacket;
use SMProxy\MysqlPacket\MySqlPacketDecoder;
use SMProxy\MysqlPacket\MySQLPacket;
use SMProxy\MysqlPacket\OkPacket;
use SMProxy\MysqlPacket\Util\ErrorCode;
use SMProxy\MysqlPool\MySQLException;
use SMProxy\MysqlPool\MySQLPool;
use SMProxy\Parser\ServerParse;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午6:32
 */
class SMProxyServer extends BaseServer
{
    public $source;
    public $mysqlClient;

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
        // 生成认证数据
        $Authenticator = new FrontendAuthenticator();
        $this->source[$fd] = $Authenticator;
        $server->send($fd, $Authenticator->getHandshakePacket($fd));
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
            $packages = $this ->packageSplit($data,$this->source[$fd]->auth);
            foreach ($packages as $package){
                $data = $package;
                $this ->go(function () use($server, $fd, $reactor_id, $data){
                    $bin = (new MySqlPacketDecoder())->decode($data);
                    $dbConfig = $this->parseDbConfig();
                    MySQLPool::init($dbConfig);
                    Log::set_config(CONFIG['server']['logs']['config'],CONFIG['server']['logs']['open']);
                    if (!$this->source[$fd]->auth) {
//                var_dump($data);
//                var_dump(implode(',',getBytes($data)));
                        $authPacket = new AuthPacket();
                        $authPacket->read($bin);
                        $checkPassword = $this->source[$fd]->checkPassword($authPacket->password, CONFIG['server']['password']);
                        if (CONFIG['server']['user'] != $authPacket->user || !$checkPassword) {
                            $message = "Access denied for user '" . $authPacket->user . "'";
                            $errMessage = $this->writeErrMessage(2,$message
                                , ErrorCode::ER_NO_SUCH_USER);
                            $mysql_log = Log::get_logger('mysql');
                            $mysql_log ->error($message);
                            $server->send($fd, getString($errMessage));
                            return null;
                        } else {
                            $server->send($fd, getString(OkPacket::$AUTH_OK));
                            $this->source[$fd]->auth = true;
                            $this->source[$fd]->database = $authPacket->database;
                        }
                    } else {
                        switch ($bin->data[4]) {
                            case MySQLPacket::$COM_INIT_DB:
                                // just init the frontend
                                break;
                            case MySQLPacket::$COM_QUERY:
                            case MySQLPacket::$COM_STMT_PREPARE:
                                $connection = new FrontendConnection();
                                $queryType = $connection->query($bin);
                                if ($queryType == ServerParse::SELECT ||
                                    $queryType == ServerParse::SHOW ||
                                    $queryType == ServerParse::SET ||
                                    $queryType == ServerParse::USE
                                ) {
                                    if (!isset($this ->connectHasTransaction[$fd]) || !$this ->connectHasTransaction[$fd]){
                                        $this->connectReadState[$fd] = true;
                                    }
                                }else if ($queryType == ServerParse::START){
                                    $this ->connectHasTransaction[$fd] = true;
                                    $this->connectReadState[$fd] = false;
                                }else if ($queryType == ServerParse::COMMIT || $queryType == ServerParse::ROLLBACK){
                                    $this ->connectHasTransaction[$fd] = false;
                                }else{
                                    $this->connectReadState[$fd] = false;
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
                            case MySQLPacket::$COM_STMT_EXECUTE:
                                break;
                            case MySQLPacket::$COM_STMT_CLOSE:
                                if (substr($data, -5) == getString([1, 0, 0, 0, 1])) {
                                    $data = substr($data, 0, strlen($data) - 5);
                                }
                                break;
                            case MySQLPacket::$COM_HEARTBEAT:
                                break;
                            default:
                                break;
                        }
                        if (isset($this->connectReadState[$fd]) && $this->connectReadState[$fd] === true) {
                            $model = 'read';
                            $key = $this->source[$fd]->database?$model . '_' . $this->source[$fd]->database:$model;
                            //如果没有读库 默认用写库
                            if (!array_key_exists($key, $dbConfig)){
                                $model = 'write';
                            }
                        } else {
                            $model = 'write';
                        }
                        if (isset($this->mysqlClient[$fd][$model])) {
                            $client = $this->mysqlClient[$fd][$model];
                            if ($data && $client->client->isConnected()) {
                                $client->client->send($data);
                            }
                        } else {
                            $key = $this->source[$fd]->database?$model . '_' . $this->source[$fd]->database:$model;
                            if (array_key_exists($key, $dbConfig)) {
                                $client = MySQLPool::fetch($key,
                                    $server, $fd);
                                $this->mysqlClient[$fd][$model] = $client;
                                if ($data && $client->client->isConnected()) {
                                    $client->client->send($data);
                                }
                            } else {
                                $message = 'database config ' . ($this->source[$fd]->database?:'') . ' ' . $model . ' is not exists!';
                                $errMessage = $this->writeErrMessage(1,$message
                                    ,ErrorCode::ER_SYNTAX_ERROR);
                                $mysql_log = Log::get_logger('mysql');
                                $mysql_log ->error($message);
                                $server->send($fd, getString($errMessage));
                            }
                        }
                    }
                });
            }
        });
    }


    /**
     * 客户端断开连接
     *
     * @param $server
     * @param $fd
     *
     * @throws MySQLException
     */
    public function onClose($server, $fd)
    {
        if (isset($this->source[$fd])){
            unset($this->source[$fd]);
        }
        if (isset($this->mysqlClient[$fd])){
            foreach ($this->mysqlClient[$fd] as $mysqlClient){
                MySQLPool::recycle($mysqlClient);
            }
            unset($this->mysqlClient[$fd]);
        }
        if (isset($this->connectReadState[$fd])) {
            unset($this->connectReadState[$fd]);
        }
        parent::onClose($server, $fd);
//        echo "connection close: {$fd}\n";
    }

    /**
     * 处理粘包问题
     * @param string $data
     * @param bool $auth 是否通过认证
     *
     * @return array
     */
    protected function packageSplit(string $data,bool $auth)
    {
        if (strlen($data) == $this ->getPackageLength($data,0,$auth?4:3)){
            return [$data];
        }
        $packages = [];
        $split = function ($data,&$packages,$step = 0) use(&$split){
            if (isset($data[$step]) && ord($data[$step]) != 0){
                $packageLength = $this ->getPackageLength($data,$step,4);
                $packages[] = substr($data,$step,$packageLength);
                $split($data,$packages,$step+$packageLength);
            }
        };
        if ($auth){
            $split($data,$packages);
        } else{
            $packageLength = $this ->getPackageLength($data,0,3);
            $packages[] = substr($data,0,$packageLength);
        }
        return $packages;
    }

    /**
     * 获取包长
     *
     * @param string $data
     * @param int $step
     * @param int $offset
     *
     * @return int
     */
    private function getPackageLength(string $data,int $step,int $offset)
    {
        $i  = ord($data[$step]);
        $i |= ord($data[$step+1])  << 8;
        $i |= ord($data[$step+2])  << 16;
        if ($offset >= 4){
            $i |= ord($data[$step+3])  << 24;
        }
        return $i+$offset;
    }
}