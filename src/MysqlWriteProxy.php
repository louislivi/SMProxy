<?php

namespace SMProxy;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:51
 */
class MysqlWriteProxy extends MysqlClient
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
            if (!$this->handshake) {
                $this->handshake = $data;
            }
            if ($this->server->exist($fd))
                $this->server->send($fd, $data);
        });

    }

    public function onClientError($cli)
    {
        $fd = $this->serverFd;
    }

    public function onClientClose($cli)
    {
        $fd = $this->serverFd;
        echo "write client connection close $fd\n";
    }
}