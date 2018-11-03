<?php

namespace SMProxy;

use SMProxy\Handler\Backend\BackendAuthenticator;
use SMProxy\MysqlPacket\AuthPacket;
use SMProxy\MysqlPacket\BinaryPacket;
use SMProxy\MysqlPacket\HandshakePacket;
use SMProxy\MysqlPacket\OkPacket;
use SMProxy\MysqlPacket\Util\CharsetUtil;
use SMProxy\MysqlPacket\Util\SecurityUtil;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:51
 */
class MysqlReadProxy extends MysqlClient
{
    protected $server;
    public $handshake = '';
    public $serverFd;

    public function __construct($server)
    {
        parent::__construct();
        $this->server = $server;
    }

    public function onClientConnect($cli)
    {
    }

    /**
     * mysql 客户端消息转发
     *
     * @param $cli
     * @param $data
     */
    public function onClientReceive($cli, $data)
    {
        $this->go(function () use ($cli, $data) {
            $fd = $this->serverFd;
            $binaryPacket = new BinaryPacket();
            $binaryPacket->data = getBytes($data);
            $binaryPacket->packetLength = $binaryPacket->calcPacketSize();
            if (!$this->handshake) {
                $readConfig = $this->parseDbConfig($this->server
                    ->connection_info($this ->serverFd)['server_port'])['read'];
                $handshakePacket = (new HandshakePacket())->read($binaryPacket);
                $salt = array_merge($handshakePacket->seed, $handshakePacket->restOfScrambleBuff);
                $password = SecurityUtil::scramble411($readConfig['password'], $salt);
                $authPacket = new AuthPacket();
                $authPacket->packetId = 1;
                $authPacket->clientFlags = BackendAuthenticator::getClientFlags();
                $authPacket->maxPacketSize = 16777216;
                $authPacket->charsetIndex = CharsetUtil::getIndex($readConfig['charset']??'utf-8');
                $authPacket->user = $readConfig['user'];
                $authPacket->password = $password;
                $authPacket->database = $readConfig['database']??($this->database?:0);
                $this->handshake = $data;
                $cli->send(getString($authPacket->write()));
            } else {
                if (array_diff_assoc($binaryPacket->data,OkPacket::$AUTH_OK)){
                    if ($this->server->exist($fd))
                        $this->server->send($fd, $data);
                }
//                switch ($binaryPacket->data[4]) {
//                    case OkPacket::$FIELD_COUNT:
//
//                        break;
//                    case ErrorPacket::$FIELD_COUNT:
//                        $fd = $this->serverFd;
//                        if ($this->server->exist($fd))
//                            $this->server->send($fd, $data);
////                        $errorPacket = new ErrorPacket();
////                        throw new SMProxyException('read only connect error:' . $errorPacket
////                                ->read($binaryPacket) ->message);
//                        break;
//                    default:
//                        $fd = $this->serverFd;
//                        if ($this->server->exist($fd))
//                            $this->server->send($fd, $data);
//                        break;
//                }
            }
        });

    }

    public function onClientError($cli)
    {
        $fd = $this->serverFd;
    }

    public function onClientClose($cli)
    {
        $fd = $this ->serverFd;
        echo "read client connection close $fd\n";
    }
}