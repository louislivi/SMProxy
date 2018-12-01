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
use SMProxy\MysqlPool\MySQLPool;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:51.
 */
class MysqlProxy extends MysqlClient
{
    public $server;
    public $serverFd;
    public $charset;
    public $account;
    public $auth = false;
    public $chan;

    public function __construct(\swoole_server $server, int $fd, \Swoole\Coroutine\Channel $chan)
    {
        parent::__construct();
        $this->server = $server;
        $this->serverFd = $fd;
        $this->chan = $chan;
    }

    /**
     * mysql 客户端消息转发.
     *
     * @param $cli
     * @param $data
     */
    public function onClientReceive(\Swoole\Coroutine\Client $cli, string $data)
    {
        $this->go(function () use ($cli, $data) {
            $fd = $this->serverFd;
            $binaryPacket = new BinaryPacket();
            $binaryPacket->data = getBytes($data);
            $binaryPacket->packetLength = $binaryPacket->calcPacketSize();
            if (isset($binaryPacket->data[4])) {
                $send = true;
                if ($binaryPacket->data[4] == ErrorPacket::$FIELD_COUNT) {
                    $errorPacket = new ErrorPacket();
                    $errorPacket->read($binaryPacket);
                    $errorPacket->errno = ErrorCode::ER_SYNTAX_ERROR;
                    $mysql_log = Log::getLogger('mysql');
                    $mysql_log->error($errorPacket->errno . ':' . $errorPacket->message);
                    $data = getString($errorPacket->write());
                } else {
                    if (!$this->auth) {
                        $handshakePacket = (new HandshakePacket())->read($binaryPacket);
                        $salt = array_merge($handshakePacket->seed, $handshakePacket->restOfScrambleBuff);
                        $password = SecurityUtil::scramble411($this->account['password'], $salt);
                        $authPacket = new AuthPacket();
                        $authPacket->packetId = 1;
                        $authPacket->clientFlags = BackendAuthenticator::getClientFlags();
                        $authPacket->maxPacketSize =
                            CONFIG['server']['swoole_client_setting']['package_max_length'] ?? 16777216;
                        $authPacket->charsetIndex = CharsetUtil::getIndex($this->charset ?? 'utf-8');
                        $authPacket->user = $this->account['user'];
                        $authPacket->password = $password;
                        $authPacket->database = $this->database ?? 0;
                        $this->auth = true;
                        if ($cli->isConnected()) {
                            $cli->send(getString($authPacket->write()));
                        }
                        $send = false;
                    }
                    if ($binaryPacket->data[4] == OkPacket::$FIELD_COUNT) {
                        if (!array_diff_assoc($binaryPacket->data, OkPacket::$AUTH_OK)) {
                            $send = false;
                            $this->chan->push($this);
                        }
                    }
                }
                if ($send && $this->server->exist($fd)) {
                    $this->server->send($fd, $data);
                }
            }
        });
    }

    /**
     * close.
     *
     * @param \Swoole\Coroutine\Client $cli
     *
     * @throws \SMProxy\MysqlPool\MySQLException
     * @throws SMProxyException
     */
    public function onClientClose(\Swoole\Coroutine\Client $cli)
    {
        MySQLPool::destruct($cli, $this->model . '_smproxy_' . $this->database);
    }

    public function onClientError(\Swoole\Coroutine\Client $cli)
    {
    }
}
