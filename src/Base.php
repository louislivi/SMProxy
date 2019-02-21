<?php

namespace SMProxy;

use SMProxy\Log\Log;
use Swoole\Coroutine\Channel;
use SMProxy\MysqlPool\MySQLException;
use SMProxy\MysqlPacket\ErrorPacket;
use function SMProxy\Helper\array_iconv;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/30
 * Time: 上午11:06.
 */
class Base extends Context
{
    /**
     * 携程执行处理异常.
     *
     * @param $function
     */
    protected static function go(\Closure $function)
    {
        if (-1 !== \Swoole\Coroutine::getuid()) {
            $pool = self::$pool[\Swoole\Coroutine::getuid()] ?? false;
        } else {
            $pool = false;
        }
        go(function () use ($function, $pool) {
            try {
                if ($pool) {
                    self::$pool[\Swoole\Coroutine::getuid()] = $pool;
                }
                $function();
                if ($pool) {
                    unset(self::$pool[\Swoole\Coroutine::getuid()]);
                }
            } catch (SMProxyException $SMProxyException) {
                self::writeErrorMessage($SMProxyException, 'system');
            } catch (MySQLException $MySQLException) {
                self::writeErrorMessage($MySQLException, 'mysql');
            }
        });
    }

    /**
     * 写入日志
     *
     * @param $exception
     * @param string $tag
     */
    protected static function writeErrorMessage($exception, string $tag = 'mysql')
    {
        $log = Log::getLogger($tag);
        $errLevel = $exception ->getCode() ? array_search($exception ->getCode(), Log::$levels) : 'warning';
        $log->$errLevel($exception->errorMessage());
        if (CONFIG['server']['swoole']['daemonize'] != true) {
            echo  '[' . ucfirst($errLevel) . '] ', $exception->errorMessage(), PHP_EOL;
        }
    }

    /**
     * 格式化配置项.
     *
     * @param array $_config
     *
     * @return array
     *
     * @throws \SMProxy\SMProxyException
     */
    public function parseDbConfig(array $_config)
    {
        $config = $_config['database'] ?? [];
        foreach ($config['databases'] as $key => $database) {
            if (isset($config['serverInfo'][$database['serverInfo']])) {
                $config['databases'][$key]['maxConns']      = floor(
                    eval('return ' . $config['databases'][$key]['maxConns'] . ';') / CONFIG['server']['swoole']['worker_num']
                );
                $config['databases'][$key]['maxSpareConns'] = floor(
                    eval('return ' . $config['databases'][$key]['maxSpareConns'] . ';') / CONFIG['server']['swoole']['worker_num']
                );
                $config['databases'][$key]['startConns']    = eval('return ' . $config['databases'][$key]['startConns'] . ';');
                $config['databases'][$key]['maxSpareExp']   = eval('return ' . $config['databases'][$key]['maxSpareExp'] . ';');
                foreach ($config['serverInfo'][$database['serverInfo']] as $s_key => $value) {
                    if (isset($config['account'][$value['account']])) {
                        $host = &$config['serverInfo'][$database['serverInfo']][$s_key]['host'];
                        if (is_array($host)) {
                            $host = $host[array_rand($host)];
                        }
                        if (!isset($config['databases'][$s_key])) {
                            $config['databases'][$s_key] = $config['databases'][$key];
                            $config['databases'][$s_key]['serverInfo'] =
                                $config['serverInfo'][$database['serverInfo']][$s_key];
                            $config['databases'][$s_key]['serverInfo']['account'] =
                                $config['account'][$value['account']];
                        }
                        $config['databases'][$s_key . DB_DELIMITER . $key] = $config['databases'][$key];
                        $config['databases'][$s_key . DB_DELIMITER . $key]['serverInfo'] =
                            $config['serverInfo'][$database['serverInfo']][$s_key];
                        $config['databases'][$s_key . DB_DELIMITER . $key]['serverInfo']['account'] =
                            $config['account'][$value['account']];
                    } else {
                        throw new SMProxyException('Config serverInfo->' . $s_key . '->account is not exists!');
                    }
                }
            } else {
                throw new SMProxyException('Config serverInfo key ' . $database['serverInfo'] . 'is not exists!');
            }
            unset($config['databases'][$key]);
        }
        return $config['databases'];
    }

    /**
     * 协程pop
     *
     * @param $chan
     * @param int $timeout
     *
     * @return bool
     */
    protected static function coPop(Channel $chan, int $timeout = 0)
    {
        if (version_compare(swoole_version(), '4.0.3', '>=')) {
            return $chan->pop($timeout);
        } else {
            if (0 == $timeout) {
                return $chan->pop();
            } else {
                $writes = [];
                $reads = [$chan];
                $result = $chan->select($reads, $writes, $timeout);
                if (false === $result || empty($reads)) {
                    return false;
                }
                $readChannel = $reads[0];
                return $readChannel->pop();
            }
        }
    }

    protected static function writeErrMessage(int $id, string $msg, int $errno = 0, $sqlState = 'HY000')
    {
        $err = new ErrorPacket();
        $err->packetId = $id;
        if ($errno) {
            $err->errno = $errno;
        }
        $err->sqlState = $sqlState;
        $err->message  = array_iconv($msg);

        return $err->write();
    }
}
