<?php

namespace SMProxy;
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/30
 * Time: 上午11:06
 */
class Base extends Context
{
    /**
     * 携程执行处理异常
     *
     * @param $function
     */
    protected function go($function)
    {
        if (\Swoole\Coroutine::getuid() !== -1) {
            $pool = self::$pool[\Swoole\Coroutine::getuid()]??false;
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
                print_r($SMProxyException->getMessage() . "\n");
            }
        });
    }

    /**
     * 格式化配置项
     *
     * @param int $server_port
     *
     * @return array
     * @throws SMProxyException
     */
    public function parseDbConfig(int $server_port)
    {
        $config = CONFIG['databases'];
        if (!array_key_exists('db.' . $server_port . '.write.host', $config)) {
            throw new SMProxyException('database key ' . 'db.' . $server_port . '.write.host' . ' is not exists.');
        }
        if (!array_key_exists('db.' . $server_port . '.write.port', $config)) {
            throw new SMProxyException('database key ' . 'db.' . $server_port . '.write.port' . ' is not exists.');
        }
        $now_config = [];
        foreach ($config as $key => $value) {
            $key_data = explode('.', $key);
            if (isset($key_data[1]) && is_numeric($key_data[1]) && count($key_data) === 4) {
                if ($key_data[1] == $server_port) {
                    $now_config[$key_data[2]][$key_data[3]] = $value;
                }
            } else {
                throw new SMProxyException('database ' . $key . ' error.');
            }
        }
        return $now_config;
    }


}