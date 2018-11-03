<?php

namespace SMProxy;

use Swoole\Coroutine;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:40
 */
abstract class BaseServer extends Base
{
    protected $mysqlConnect = [];
    protected $connectReadState = [];
    protected $server;

    /**
     * BaseServer constructor.
     *
     * @throws \ErrorException
     */
    public function __construct()
    {
        try {
            if (!(CONFIG['swoole']??false)) {
                throw new SMProxyException('config [swoole] is not found !');
            }
            if ((CONFIG['server']['port']??false)) {
                $ports = explode(',', CONFIG['server']['port']);
            } else {
                $ports = [3366];
            }
            $this->server = new \swoole_server(CONFIG['server']['host'] ?? '0.0.0.0',
                $ports[0], CONFIG['server']['mode'], CONFIG['server']['sock_type']);
            if (count($ports) > 1) {
                for ($i = 1; $i < count($ports); $i++) {
                    $this->server->addListener(CONFIG['server']['host'] ?? '0.0.0.0',
                        $ports[$i], CONFIG['server']['sock_type']);
                }
            }
            $this->server->set(CONFIG['swoole']);
            $this->server->on('connect', [$this, 'onConnect']);
            $this->server->on('receive', [$this, 'onReceive']);
            $this->server->on('close', [$this, 'onClose']);
            $this->server->start();
        } catch (\Swoole\Exception | \ErrorException | SMProxyException $exception) {
            print_r($exception->getMessage() . "\n");
        }
    }

    protected function onConnect($server, $fd)
    {
    }

    protected function onReceive($server, $fd, $reactor_id, $data)
    {
    }

    /**
     * 关闭连接 销毁携程变量
     *
     * @param $server
     * @param $fd
     */
    protected function onClose($server, $fd)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0 && isset(self::$pool[$cid])) {
            unset(self::$pool[$cid]);
        }

    }

    /**
     * 初始化mysql代理客户端
     *
     * @param int $fd
     *
     * @throws SMProxyException
     */
    protected function initMysqlProxyClient(int $fd)
    {
        $this->initReadMysqlClient($fd);
        $this->initWriteMysqlClient($fd);
        print_r("init mysql client\n");
    }

    /**
     * 初始化读库
     *
     * @param int $fd
     */
    public function initReadMysqlClient(int $fd)
    {
        $this->go(function () use ($fd) {
            $config = self::get('mysql_config');
            if (isset($config['read']['host'])) {
                $mysqlReadProxyClient = new MysqlReadProxy($this->server);
                $mysqlReadProxyClient->serverFd = $fd;
                $mysqlReadProxyClient->connect($config['read']['host'], $config['read']['port'] ?? 3306, $config['read']['timeout'] ?? 0.1, $config['read']['flag'] ?? 0);
                $this->putClient($fd, $mysqlReadProxyClient ?? null, 'readClient');
                $this->putClient($fd, 0, 'readStep');
            }
        });
    }

    /**
     * 初始化写库
     *
     * @param int $fd
     */
    public function initWriteMysqlClient(int $fd)
    {
        $this->go(function () use ($fd) {
            $config = self::get('mysql_config');
            if (isset($config['write']['host'])) {
                $mysqlWriteProxyClient = new MysqlWriteProxy($this->server);
                $mysqlWriteProxyClient->serverFd = $fd;
                $mysqlWriteProxyClient->connect($config['write']['host'], $config['write']['port'] ?? 3306, $config['write']['timeout'] ?? 0.1, $config['write']['flag'] ?? 0);
                $this->putClient($fd, $mysqlWriteProxyClient, 'client');
                $this->putClient($fd, 0, 'step');
            } else {
                throw new SMProxyException('database config not has write host.');
            }
        });
    }

    /**
     * 获取客户端链接
     *
     * @param int $fd
     * @param string $key
     *
     * @return bool
     */
    public function getClient(int $fd, string $key = '', $model = 'write')
    {
        if ($key) {
            return $this->mysqlConnect[$model][$fd % self::get('mysql_config')['read']['max_connect']][$key] ?? false;
        } else {
            return $this->mysqlConnect[$model][$fd % self::get('mysql_config')['read']['max_connect']] ?? false;
        }
    }

    /**
     * 存储客户端链接
     *
     * @param int $fd
     * @param $data
     * @param string $key
     *
     * @return bool|mixed
     */
    public function putClient(int $fd, $data, string $key = '', $model = 'write')
    {
        if ($key) {
            return $this->mysqlConnect[$model][$fd % self::get('mysql_config')['read']['max_connect']][$key] = $data;
        } else {
            return $this->mysqlConnect[$model][$fd % self::get('mysql_config')['read']['max_connect']] = $data;
        }
    }
}