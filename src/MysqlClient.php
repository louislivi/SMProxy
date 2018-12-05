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
    public $model;
    public $ssl = false;

    public function connect(string $host, int $port, float $timeout = 0.1)
    {
    }

    public function onClientReceive(\Swoole\Coroutine\Client $cli, string $data)
    {
    }

    public function onClientClose(\Swoole\Coroutine\Client $cli)
    {
    }

    public function onClientError(\Swoole\Coroutine\Client $cli)
    {
    }
}
