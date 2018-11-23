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
        //判断php版本
        if (PHP_VERSION < 7.0) {
            print_r('ERROR:PHP version must be greater than 7.0!' . "\n");

            return;
        }

        //判断swoole版本
        if (defined('SWOOLE_VERSION')) {
            if (SWOOLE_VERSION < 2.1) {
                print_r('ERROR:Swoole version must be greater than 2.1!' . "\n");

                return;
            }
        } else {
            print_r('ERROR:Swoole not installed!' . "\n");

            return;
        }
        //读取配置文件
        $configName = ROOT . '/conf/';
        if (file_exists($configName)) {
            define('CONFIG', initConfig($configName));
        } else {
            throw new \SMProxy\SMProxyException('Error:config conf/ No such file or directory!');
        }
    }
}
