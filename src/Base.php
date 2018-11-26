<?php

namespace SMProxy;

use SMProxy\Log\Log;

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
    protected function go(\Closure $function)
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
                $system_log->warning($SMProxyException->errorMessage());
            }
        });
    }

    /**
     * 格式化配置项.
     *
     * @param int $server_port
     *
     * @return array
     *
     * @throws SMProxyException
     */
    public function parseDbConfig(array $_config)
    {
        $config = $_config['database'] ?? [];
        foreach ($config['databases'] as $key => $database) {
            if (isset($config['serverInfo'][$database['serverInfo']])) {
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
                        $config['databases'][$s_key . '_' . $key] = $config['databases'][$key];
                        $config['databases'][$s_key . '_' . $key]['serverInfo'] =
                            $config['serverInfo'][$database['serverInfo']][$s_key];
                        $config['databases'][$s_key . '_' . $key]['serverInfo']['account'] =
                            $config['account'][$value['account']];
                    } else {
                        $mysql_log = Log::getLogger('system');
                        $mysql_log->error('config serverInfo->' . $s_key .
                            '->account is not exists!');
                        throw new SMProxyException('config serverInfo->' . $s_key . '->account is not exists!');
                    }
                }
            } else {
                $mysql_log = Log::getLogger('system');
                $mysql_log->error('config serverInfo key ' . $database['serverInfo'] . 'is not exists!');
                throw new SMProxyException('config serverInfo key ' . $database['serverInfo'] . 'is not exists!');
            }
            unset($config['databases'][$key]);
        }

        return $config['databases'];
    }
}
