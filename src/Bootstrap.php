<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:27.
 */

namespace SMProxy;

class Bootstrap
{
    /**
     * @throws SMProxyException
     */
    public function bootstrap()
    {
        // 判断php版本
        if (PHP_VERSION < 7.0) {
            smproxy_error('ERROR: PHP version must be greater than 7.0!');
        }

        // 判断swoole版本
        if (defined('SWOOLE_VERSION')) {
            if (SWOOLE_VERSION < 2.1) {
                smproxy_error('ERROR: Swoole version must be greater than 2.1!');
            }
        } else {
            exit('ERROR: Swoole not installed!' );
        }

        // 读取配置文件
        $configName = ROOT . '/conf/';
        if (file_exists($configName)) {
            define('CONFIG', initConfig($configName));
        } else {
            smproxy_error("ERROR: $configName No such file or directory!");
        }
    }
}
