<?php

namespace SMProxy;

use Psr\Log\LogLevel;
use SMProxy\Log\Log;
use SMProxy\MysqlPool\MySQLException;

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
                $system_log = Log::getLogger('system');
                $errLevel = $SMProxyException ->getCode() ? array_search($SMProxyException ->getCode(), Log::$levels) : 'error';
                $system_log->$errLevel($SMProxyException->errorMessage());
                if (CONFIG['server']['swoole']['daemonize'] != true) {
                    echo $SMProxyException->errorMessage(), PHP_EOL;
                }
            } catch (MySQLException $MySQLException) {
                $mysql_log = Log::getLogger('mysql');
                $errLevel = $MySQLException ->getCode() ? array_search($MySQLException ->getCode(), Log::$levels) : 'warning';
                $mysql_log->$errLevel($MySQLException->errorMessage());
                if (CONFIG['server']['swoole']['daemonize'] != true) {
                    echo $MySQLException->errorMessage(), PHP_EOL;
                }
            }
        });
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
                        throw new SMProxyException('config serverInfo->' . $s_key . '->account is not exists!');
                    }
                }
            } else {
                throw new SMProxyException('config serverInfo key ' . $database['serverInfo'] . 'is not exists!');
            }
            unset($config['databases'][$key]);
        }
        return $config['databases'];
    }
}
