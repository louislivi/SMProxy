<?php

namespace SMProxy\MysqlPool;

use SMProxy\Base;
use function SMProxy\Helper\getString;
use SMProxy\MysqlPacket\Util\ErrorCode;
use SMProxy\MysqlProxy;
use Swoole\Coroutine\Client;

/**
 * MySql连接池管理
 *
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/6
 * Time: 上午10:52.
 */
class MySQLPool extends Base
{
    //是否初始化
    protected static $init = false;
    //空闲连接
    protected static $spareConns = [];
    //正在使用中的连接
    protected static $busyConns  = [];
    //配置项
    protected static $connsConfig;
    //所有的连接
    protected static $connsNameMap = [];
    //等待中的连接
    protected static $pendingFetchCount = [];
    //需要恢复的连接
    protected static $resumeFetchCount  = [];
    //等待连接对应的协程通道
    protected static $yieldChannel  = [];
    //初始连接数
    protected static $initConnCount = [];
    //连接最后交互时间
    protected static $lastConnsTime = [];
    //MySql服务端
    protected static $mysqlServer;

    /**
     * 初始化连接池
     *
     * @param array $connsConfig
     *
     * @throws MySQLException
     */
    public static function init(array $connsConfig, &$mysqlServer)
    {
        if (self::$init) {
            return;
        }
        self::$connsConfig = $connsConfig;
        foreach ($connsConfig as $name => $config) {
            self::$spareConns[$name] = [];
            self::$busyConns[$name] = [];
            self::$pendingFetchCount[$name] = 0;
            self::$resumeFetchCount[$name]  = 0;
            self::$initConnCount[$name] = 0;
            if ($config['maxSpareConns'] <= 0 || $config['maxConns'] <= 0) {
                throw new MySQLException("Invalid maxSpareConns or maxConns in {$name}");
            }
        }
        self::$mysqlServer = $mysqlServer;
        self::$init = true;
        // 启动定时回收空闲连接
        self::tickRecycle();
    }

    /**
     * 定时回收空闲连接
     */
    private static function tickRecycle()
    {
        \Swoole\Timer::tick(2000, function () {
            foreach (self::$spareConns as $connName => &$connsPool) {
                foreach ($connsPool as $key => &$conn) {
                    $id = spl_object_hash($conn);
                    if (((count($connsPool) + self::$initConnCount[$connName]) > self::$connsConfig[$connName]['maxSpareConns']) &&
                        ((microtime(true) - self::$lastConnsTime[$id]) >= ((self::$connsConfig[$connName]['maxSpareExp']) ?? 0))
                    ) {
                        unset($connsPool[$key]);
                        if ($conn->client->isConnected()) {
                            $conn->client->close();
                        }
                        self::delConns($connName, $conn->mysqlServer->threadId, $id);
                    }
                    unset($conn);
                }
                unset($connsPool);
            }
        });
    }

    /**
     * 回收连接
     *
     * @param MysqlProxy $conn
     * @param bool       $busy
     *
     */
    public static function recycle(MysqlProxy $conn, bool $busy = true)
    {
        self::go(function () use ($conn, $busy) {
            if (!self::$init) {
                throw new MySQLException('Should call MySQLPool::init.');
            }
            $id = spl_object_hash($conn);
            $connName = self::$connsNameMap[$id];
            if ($busy) {
                if (isset(self::$busyConns[$connName][$id])) {
                    unset(self::$busyConns[$connName][$id]);
                } else {
                    throw new MySQLException('Unknow MySQL connection.');
                }
            }
            $connsPool = &self::$spareConns[$connName];
            if (((count($connsPool) + self::$initConnCount[$connName]) > self::$connsConfig[$connName]['maxSpareConns']) &&
                ((microtime(true) - self::$lastConnsTime[$id]) >= ((self::$connsConfig[$connName]['maxSpareExp']) ?? 0))
            ) {
                if ($conn->client->isConnected()) {
                    $conn->client->close();
                }
                self::delConns($connName, $conn->mysqlServer->threadId, $id);
            } else {
                if (!$conn->client->isConnected()) {
                    self::delConns($connName, $conn->mysqlServer->threadId, $id);
                    $conn = self::initConn($conn->server, $conn->serverFd, $connName);
                    $id = spl_object_hash($conn);
                }
                $connsPool[] = $conn;
                if (self::$pendingFetchCount[$connName] > 0) {
                    ++self::$resumeFetchCount[$connName];
                    self::$yieldChannel[$connName]->push($id);
                }
            }
        });
    }

    /**
     * 删除连接
     *
     * @param $connName
     * @param $threadId
     * @param $id
     */
    private static function delConns($connName, $threadId, $id)
    {
        $threadName = $connName . DB_DELIMITER . $threadId;
        if (self::$mysqlServer->exist($threadName)) {
            self::$mysqlServer->del($threadName);
        }
        unset(self::$connsNameMap[$id]);
        unset($threadName);
    }

    /**
     * 获取连接.
     *
     * @param $connName
     * @param \swoole_server $server
     * @param $fd
     *
     * @return bool|mixed|MysqlProxy
     *
     * @throws MySQLException
     * @throws \SMProxy\SMProxyException
     */
    public static function fetch(string $connName, \swoole_server $server, int $fd)
    {
        if (!self::$init) {
            throw new MySQLException('Should call MySQLPool::init!');
        }
        if (!isset(self::$connsConfig[$connName])) {
            throw new MySQLException("Unvalid connName: {$connName}.");
        }
        $connsPool = &self::$spareConns[$connName];
        if (!empty($connsPool) && count($connsPool) > self::$resumeFetchCount[$connName]) {
            $conn = array_pop($connsPool);
            if (!$conn->client->isConnected()) {
                return self::reconnect($server, $fd, $conn, $connName);
            } else {
                $conn->serverFd = $fd;
                $id = spl_object_hash($conn);
                self::$busyConns[$connName][$id] = $conn;
                self::$lastConnsTime[$id] = microtime(true);

                return $conn;
            }
        }
        if ((count(self::$busyConns[$connName]) + count($connsPool) + self::$pendingFetchCount[$connName] +
                self::$initConnCount[$connName]) >= self::$connsConfig[$connName]['maxConns']) {
            if (!isset(self::$yieldChannel[$connName])) {
                self::$yieldChannel[$connName] = new \Swoole\Coroutine\Channel(1);
            }
            ++self::$pendingFetchCount[$connName];
            $client = self::coPop(self::$yieldChannel[$connName], self::$connsConfig[$connName]['serverInfo']['timeout']);
            if (false === $client) {
                --self::$pendingFetchCount[$connName];
                $message = 'SMProxy@Reach max connections! Cann\'t pending fetch!';
                $errMessage = self::writeErrMessage(1, $message, ErrorCode::ER_HAS_GONE_AWAY);
                if ($server->exist($fd)) {
                    $server->send($fd, getString($errMessage));
                }
                throw new MySQLException($message);
            }
            --self::$resumeFetchCount[$connName];
            if (!empty($connsPool)) {
                $conn = array_pop($connsPool);
                if (!$conn->client->isConnected()) {
                    $conn = self::reconnect($server, $fd, $conn, $connName);
                    --self::$pendingFetchCount[$connName];

                    return $conn;
                } else {
                    $conn->serverFd = $fd;
                    $id = spl_object_hash($conn);
                    self::$busyConns[$connName][$id] = $conn;
                    self::$lastConnsTime[$id] = microtime(true);
                    --self::$pendingFetchCount[$connName];

                    return $conn;
                }
            } else {
                return false; //should not happen
            }
        }

        return self::initConn($server, $fd, $connName);
    }

    /**
     * 初始化链接.
     *
     * @param \swoole_server $server
     * @param int            $fd
     * @param string         $connName
     *
     * @return mixed
     *
     * @throws MySQLException
     * @throws \SMProxy\SMProxyException
     */
    public static function initConn(\swoole_server $server, int $fd, string $connName, $tryStep = 0)
    {
        ++self::$initConnCount[$connName];
        $chan = new \Swoole\Coroutine\Channel(1);
        $conn = new MysqlProxy($server, $fd, $chan);
        $serverInfo = self::$connsConfig[$connName]['serverInfo'];
        if (false == strpos($connName, DB_DELIMITER)) {
            $conn->database = 0;
            $conn->model    = $connName;
        } else {
            $conn->database = self::$connsConfig[$connName]['databaseName'] ?? substr($connName, strpos($connName, DB_DELIMITER) + strlen(DB_DELIMITER));
            $conn->model    = substr($connName, 0, strpos($connName, DB_DELIMITER));
        }

        $conn->account  = $serverInfo['account'];
        $conn->connName = $connName;
        $conn->charset  = self::$connsConfig[$connName]['charset'];
        if (false == $conn->connect($serverInfo['host'], $serverInfo['port'], $serverInfo['timeout'] ?? 0.1)) {
            --self::$initConnCount[$connName];
            $message = 'SMProxy@MySQL server has gone away';
            $errMessage = self::writeErrMessage(1, $message, ErrorCode::ER_HAS_GONE_AWAY);
            if ($server->exist($fd)) {
                $server->send($fd, getString($errMessage));
            }
            throw new MySQLException($message);
        }
        $client = self::coPop($chan, $serverInfo['timeout'] * 3);
        if ($client === false) {
            --self::$initConnCount[$connName];
            if ($tryStep < 3) {
                return self::initConn($server, $fd, $connName, ++$tryStep);
            } else {
                $message = 'SMProxy@Connection ' . $serverInfo['host'] . ':' . $serverInfo['port'] .
                    ' waiting timeout, timeout=' . $serverInfo['timeout'];
                $errMessage = self::writeErrMessage(1, $message, ErrorCode::ER_HAS_GONE_AWAY);
                if ($server->exist($fd)) {
                    $server->send($fd, getString($errMessage));
                }
                throw new MySQLException($message);
            }
        }
        $id = spl_object_hash($client);
        self::$connsNameMap[$id] = $connName;
        self::$busyConns[$connName][$id] = $client;
        self::$lastConnsTime[$id] = microtime(true);
        --self::$initConnCount[$connName];
        //保存服务信息
        $threadName = $connName . DB_DELIMITER . $conn->mysqlServer->threadId;
        self::$mysqlServer->set($threadName, [
            "threadId"      => $client->mysqlServer->threadId,
            "serverVersion" => $client->mysqlServer->serverVersion,
            "pluginName"    => $client->mysqlServer->pluginName,
            "serverStatus"  => $client->mysqlServer->serverStatus,
        ]);
        unset($threadName);
        return $client;
    }

    /**
     * 销毁连接。
     *
     * @param Client $cli
     * @param string $connName
     *
     */
    public static function destruct(Client $cli, string $connName)
    {
        self::go(function () use ($cli, $connName) {
            if ($cli->isConnected()) {
                $cli ->close();
            }
            $proxyConn = false;
            foreach (self::$spareConns[$connName] as $key => $conn) {
                if (spl_object_hash($conn ->client) == spl_object_hash($cli)) {
                    $proxyConn = $conn;
                    unset(self::$spareConns[$connName][$key]);
                    break;
                }
            }
            if ($proxyConn) {
                self::recycle($proxyConn, false);
            }
        });
    }

    /**
     * 断重链.
     *
     * @param \swoole_server      $server
     * @param int                 $fd
     * @param \SMProxy\MysqlProxy $conn
     * @param string              $connName
     *
     * @return mixed
     *
     * @throws \SMProxy\MysqlPool\MySQLException
     * @throws \SMProxy\SMProxyException
     */
    public static function reconnect(\swoole_server $server, int $fd, MysqlProxy $conn, string $connName)
    {
        if ($conn->client->isConnected()) {
            $conn->client->close();
        }
        $old_id = spl_object_hash($conn);
        unset(self::$busyConns[$connName][$old_id]);
        unset(self::$connsNameMap[$old_id]);
        self::$lastConnsTime[$old_id] = 0;

        return self::initConn($server, $fd, $connName);
    }
}
