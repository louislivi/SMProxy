<?php

namespace SMProxy;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:45.
 */
abstract class MysqlClient extends Base
{
    public $client;
    public $database;

    /**
     * MysqlClient constructor.
     *
     * @throws SMProxyException
     */
    public function __construct()
    {
        $this->client = new \swoole_client(CONFIG['swoole_client_sock_setting']['sock_type'] ?? 1,
            CONFIG['swoole_client_sock_setting']['sync_type'] ?? 1);
        $this->client->set(CONFIG['swoole_client_setting'] ?? []);
        $this->client->on('connect', [$this, 'onClientConnect']);
        $this->client->on('receive', [$this, 'onClientReceive']);
        $this->client->on('error', [$this, 'onClientError']);
        $this->client->on('close', [$this, 'onClientClose']);
    }

    public function connect(string $host, int $port, float $timeout = 0.1, int $flag = 0)
    {
        if (!$this->client->connect($host, $port, $timeout = 0.1, $flag = 0)) {
            throw new SMProxyException("connect {$host}:{$port} failed. Error: {$this->client->errCode}\n");
        } else {
            return $this->client;
        }
    }

    public function onClientConnect(\swoole_client $cli)
    {
    }

    public function onClientReceive(\swoole_client $cli, string $data)
    {
    }

    public function onClientError(\swoole_client $cli)
    {
    }

    public function onClientClose(\swoole_client $cli)
    {
    }
}
