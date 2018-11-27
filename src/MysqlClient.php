<?php

namespace SMProxy;

use SMProxy\Log\Log;
use Swoole\Coroutine\Client;

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
        $this->client = new Client(CONFIG['server']['swoole_client_sock_setting']['sock_type'] ?? 1);
        $this->client->set(CONFIG['server']['swoole_client_setting'] ?? []);
    }

    /**
     * connect.
     *
     * @param string $host
     * @param int    $port
     * @param float  $timeout
     * @param int    $flag
     *
     * @return \Swoole\Coroutine\Client
     *
     * @throws SMProxyException
     */
    public function connect(string $host, int $port, float $timeout = 0.1, int $flag = 0)
    {
        if (!$this->client->connect($host, $port, $timeout = 0.1, $flag = 0)) {
            $this->onClientError($this ->client);
            $mysql_log = Log::getLogger('mysql');
            $mysql_log->error("connect {$host}:{$port} failed. Error: {$this->client->errCode}\n");
            throw new SMProxyException("connect {$host}:{$port} failed. Error: {$this->client->errCode}\n");
        } else {
            $this->go(function () {
                while (true) {
                    if (version_compare(swoole_version(), '2.1.2', '>=')) {
                        $data = $this->client->recv(-1);
                    } else {
                        $data = $this->client->recv();
                    }
                    if (!$data) {
                        $this ->onClientClose($this ->client);
                        break;
                    }
                    $this ->onClientReceive($this ->client, $data);
                }
            });
            return $this->client;
        }
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
