<?php

namespace SMProxy;

use SMProxy\Handler\Backend\BackendAuthenticator;
use SMProxy\Log\Log;
use SMProxy\MysqlPacket\AuthPacket;
use SMProxy\MysqlPacket\BinaryPacket;
use SMProxy\MysqlPacket\ErrorPacket;
use SMProxy\MysqlPacket\HandshakePacket;
use SMProxy\MysqlPacket\OkPacket;
use SMProxy\MysqlPacket\Util\CharsetUtil;
use SMProxy\MysqlPacket\Util\ErrorCode;
use SMProxy\MysqlPacket\Util\SecurityUtil;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:51
 */
class MysqlProxy extends MysqlClient
{
    protected $server;
    public $serverFd;
    public $charset;
    public $account;
    public $auth = false;
    public $chan;

    public function __construct($server,$fd,$chan)
    {
        parent::__construct();
        $this->server = $server;
        $this->serverFd = $fd;
        $this->chan = $chan;
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
            if (!$this->auth) {
                $handshakePacket = (new HandshakePacket())->read($binaryPacket);
                $salt = array_merge($handshakePacket->seed, $handshakePacket->restOfScrambleBuff);
                $password = SecurityUtil::scramble411($this ->account['password'], $salt);
                $authPacket = new AuthPacket();
                $authPacket->packetId = 1;
                $authPacket->clientFlags = BackendAuthenticator::getClientFlags();
                $authPacket->maxPacketSize = CONFIG['server']['swoole_client_setting']['package_max_length']??16777216;
                $authPacket->charsetIndex = CharsetUtil::getIndex($this ->charset??'utf-8');
                $authPacket->user = $this ->account['user'];
                $authPacket->password = $password;
                $authPacket->database = $this ->database??0;
                $this->auth = true;
                if ($cli ->isConnected()){
                    $cli->send(getString($authPacket->write()));
                }
            } else {
                $send = true;
                switch ($binaryPacket->data[4]) {
                    case OkPacket::$FIELD_COUNT:
                        if (!array_diff_assoc($binaryPacket->data,OkPacket::$AUTH_OK)){
                            $send = false;
                            $this->chan ->push($this);
                        }
                        break;
                    case ErrorPacket::$FIELD_COUNT:
                        $errorPacket = new ErrorPacket();
                        $errorPacket ->read($binaryPacket);
                        $errorPacket ->errno = ErrorCode::ER_SYNTAX_ERROR;
                        $mysql_log = Log::get_logger('mysql');
                        $mysql_log ->error($errorPacket ->errno.':'.$errorPacket->message);
                        $data = getString($errorPacket ->write());
//                        switch ($errorPacket ->sqlState){
//                            case 28000:
//                                throw new \ErrorException($errorPacket->message);
//                                break;
//                        }
                        break;
                }
                if ($send && $this->server->exist($fd)){
                    $this->server->send($fd, $data);
                }
            }
        });
    }

    public function onClientError($cli)
    {
    }

    public function onClientClose($cli)
    {
//        echo "mysql proxy connection close\n";
    }
}