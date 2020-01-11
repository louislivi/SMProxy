<?php

namespace SMProxy;

use function SMProxy\Helper\getBytes;
use function SMProxy\Helper\getString;
use function SMProxy\Helper\getMysqlPackSize;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Client;
use SMProxy\MysqlPacket\AuthPacket;
use SMProxy\MysqlPacket\BinaryPacket;
use SMProxy\MysqlPacket\ErrorPacket;
use SMProxy\MysqlPacket\HandshakePacket;
use SMProxy\MysqlPacket\MySQLMessage;
use SMProxy\MysqlPacket\OkPacket;
use SMProxy\MysqlPacket\Util\Capabilities;
use SMProxy\MysqlPacket\Util\CharsetUtil;
use SMProxy\MysqlPacket\Util\SecurityUtil;
use SMProxy\MysqlPool\MySQLException;
use SMProxy\MysqlPool\MySQLPool;
use function SMProxy\Helper\packageLengthSetting;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:51.
 */
class MysqlProxy extends MysqlClient
{
    private $isDuplex;
    public $server;
    public $serverFd;
    public $charset;
    public $account;
    public $auth = false;
    public $chan;
    public $serverPublicKey;
    public $salt;
    public $connected = false;
    public $timeout = 0.1;
    public $mysqlClient;
    public $mysqlServer;

    /**
     * MysqlClient constructor.
     *
     */
    public function __construct(\swoole_server $server, int $fd, \Swoole\Coroutine\Channel $chan)
    {
        $this->server = $server;
        $this->serverFd = $fd;
        $this->chan = $chan;
        $this->client = new Client(CONFIG['server']['swoole_client_sock_setting']['sock_type'] ?? SWOOLE_SOCK_TCP);
        $this->client->set(CONFIG['server']['swoole_client_setting'] ?? []);
        $this->client->set(packageLengthSetting());
        $this->isDuplex = version_compare(SWOOLE_VERSION, '4.2.13', '>=');
        if (!$this->isDuplex) {
            $this->mysqlClient = new Channel(1);
        }
    }

    /**
     * connect.
     *
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param int $tryStep
     *
     * @return bool|Client
     */
    public function connect(string $host, int $port, float $timeout = 0.1, int $tryStep = 0)
    {
        $this->timeout = $timeout;
        if (!$this->client->connect($host, $port, $timeout)) {
            if ($tryStep < 3) {
                $this->client->close();
                return $this->connect($host, $port, $timeout, ++$tryStep);
            } else {
                $this->onClientError($this->client);
                return false;
            }
        } else {
            if (!$this->isDuplex) {
                $this->mysqlClient->push($this->client);
            }
            self::go(function () {
                $remain = '';
                while (true) {
                    $data = $this->recv($remain);
                    if ($data === '' || $data === false) {
                        break;
                    }
                }
            });
            return $this->client;
        }
    }

    /**
     * mysql 客户端消息转发.
     *
     * @param $cli
     * @param $data
     */
    public function onClientReceive(\Swoole\Coroutine\Client $cli, string $data)
    {
        self::go(function () use ($cli, $data) {
            $fd = $this->serverFd;
            $binaryPacket = new BinaryPacket();
            $binaryPacket->data = getBytes($data);
            $binaryPacket->packetLength = $binaryPacket->calcPacketSize();
            if (isset($binaryPacket->data[4])) {
                $send = true;
                //ERROR Packet
                if ($binaryPacket->data[4] == ErrorPacket::$FIELD_COUNT) {
                    $errorPacket = new ErrorPacket();
                    $errorPacket->read($binaryPacket);
                    //$errorPacket->errno = ErrorCode::ER_SYNTAX_ERROR;
                    $data = getString($errorPacket->write());
                } elseif (!$this->connected) {
                    //OK Packet
                    if ($binaryPacket->data[4] == OkPacket::$FIELD_COUNT) {
                        $send = false;
                        $this->connected = true;
                        $this->chan->push($this);
                        # 快速认证
                    } elseif ($binaryPacket->data[4] == 0x01) {
                        # 请求公钥
                        if ($binaryPacket->packetLength == 6) {
                            if ($binaryPacket->data[$binaryPacket->packetLength - 1] == 4) {
                                $data = getString(array_merge(getMysqlPackSize(1), [3, 2]));
                                $this->send($data);
                            }
                        } else {
                            $this->serverPublicKey = substr($data, 5, strlen($data) - 2);
                            $encryptData = SecurityUtil::sha2RsaEncrypt($this->account['password'], $this->salt, $this->serverPublicKey);
                            $data = getString(array_merge(getMysqlPackSize(strlen($encryptData)), [5])) . $encryptData;
                            $this->send($data);
                        }
                        $send = false;
                        //EOF Packet
                    } elseif ($binaryPacket->data[4] == 0xfe) {
                        $mm = new MySQLMessage($binaryPacket->data);
                        $mm->move(5);
                        $pluginName = $mm->readStringWithNull();
                        $this->salt = $mm->readBytesWithNull();
                        $password = $this->processAuth($pluginName ?: 'mysql_native_password');
                        $this->send(getString(array_merge(getMysqlPackSize(count($password)), [3], $password)));
                        $send = false;
                        //未授权
                    } elseif (!$this->auth) {
                        $handshakePacket = (new HandshakePacket())->read($binaryPacket);
                        $this->mysqlServer = $handshakePacket;
                        $this->salt = array_merge($handshakePacket->seed, $handshakePacket->restOfScrambleBuff);
                        $password = $this->processAuth($handshakePacket->pluginName);
                        $clientFlag = Capabilities::CLIENT_CAPABILITIES;
                        $authPacket = new AuthPacket();
                        $authPacket->pluginName = $handshakePacket->pluginName;
                        $authPacket->packetId = 1;
                        if (isset($this->database) && $this->database) {
                            $authPacket->database = $this->database;
                        } else {
                            $authPacket->database = 0;
                        }
                        if ($authPacket->database) {
                            $clientFlag |= Capabilities::CLIENT_CONNECT_WITH_DB;
                        }
                        if (version_compare($handshakePacket->serverVersion, '5.0', '>=')) {
                            $clientFlag |= Capabilities::CLIENT_MULTI_RESULTS;
                        }
                        $authPacket->clientFlags = $clientFlag;
                        $authPacket->serverCapabilities = $handshakePacket->serverCapabilities;
                        $authPacket->maxPacketSize =
                            CONFIG['server']['swoole_client_setting']['package_max_length'] ?? 16777215;
                        $authPacket->charsetIndex = CharsetUtil::getIndex($this->charset ?? 'utf8mb4');
                        $authPacket->user = $this->account['user'];
                        $authPacket->password = $password;
                        $this->auth = true;
                        $this->send(getString($authPacket->write()));
                        $send = false;
                    }
                }
                if ($send && $this->server->exist($fd)) {
                    $this->server->send($fd, $data);
                }
            }
        });
    }

    /**
     * 认证
     *
     * @param string $pluginName
     *
     * @return array
     * @throws MySQLException
     */
    public function processAuth(string $pluginName)
    {
        switch ($pluginName) {
            case 'mysql_native_password':
                $password = SecurityUtil::scramble411($this->account['password'], $this->salt);
                break;
            case 'caching_sha2_password':
                $password = SecurityUtil::scrambleSha256($this->account['password'], $this->salt);
                break;
            case 'sha256_password':
                throw new MySQLException('Sha256_password plugin is not supported yet');
                break;
            case 'mysql_old_password':
                throw new MySQLException('mysql_old_password plugin is not supported yet');
                break;
            case 'mysql_clear_password':
                $password = array_merge(getBytes($this->account['password']), [0]);
                break;
            default:
                $password = SecurityUtil::scramble411($this->account['password'], $this->salt);
                break;
        }
        return $password;
    }

    /**
     * send.
     *
     * @param mixed ...$data
     *
     * @return bool
     */
    public function send(...$data)
    {
        if ($this->isDuplex) {
            if ($this->client->isConnected()) {
                return $this->client->send(...$data);
            } else {
                return false;
            }
        } else {
            $client = self::coPop($this->mysqlClient);
            if ($client === false) {
                //连接关闭或超时
                return false;
            }
            if ($client->isConnected()) {
                $result = $client->send(...$data);
                $this->mysqlClient->push($client);
                return $result;
            }
            return false;
        }
    }

    /**
     * recv.
     *
     * @return mixed
     */
    public function recv(&$remain)
    {
        if ($this->isDuplex) {
            $client = $this->client;
            $data = $client->recv(-1);
        } else {
            $client = self::coPop($this->mysqlClient, $this->timeout);
            if ($client === false) {
                //连接关闭或超时
                return false;
            }
            if ($client->isConnected()) {
                $data = $client->recv($this->timeout / 500);
            } else {
                $data = '';
            }
            $this->mysqlClient->push($client);
            if ($data === false && $client->errCode == 110) {
                //如果连接超时则不处理
                $data = true;
            }
            if ($data === '' || $data === false) {
                $this->mysqlClient->close();
            }
        }
        if ($data === '' || $data === false) {
            $this->onClientClose($client);
        } elseif (is_string($data)) {
            $this->onClientReceive($client, $data);
        }
        return $data;
    }

    /**
     * close.
     *
     * @param Client $cli
     */
    public function onClientClose(\Swoole\Coroutine\Client $cli)
    {
        MySQLPool::destruct($cli, $this->connName);
    }

    public function onClientError(\Swoole\Coroutine\Client $cli)
    {
    }
}
