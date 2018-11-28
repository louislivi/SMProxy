<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午1:04.
 */

namespace SMProxy\Command;

use SMProxy\Helper\PhpHelper;

class Command
{
    /**
     * @param array $argv
     *
     * @throws \SMProxy\SMProxyException
     */
    public function run(array $argv)
    {

        $command = count($argv) >= 2 ? $argv[count($argv) - 1] : false;
        $configPath = ROOT . '/conf/';
        foreach ($argv as $key => $value) {
            switch ($value) {
                case '-c':
                case '--config':
                    // 读取配置文件
                    $configPath = $argv[$key + 1];
                    break;
            }
        }

        if (file_exists($configPath)) {
            define('CONFIG_PATH', realpath($configPath) . '/');
            define('CONFIG', initConfig(realpath(CONFIG_PATH) . '/'));
        } else {
            smproxy_error('ERROR: ' . $configPath . ' No such file or directory!');
        }

        $serverCommand = new ServerCommand();
        if (!$command || '-h' == $command || '--help' == $command) {
            echo $serverCommand->desc, PHP_EOL;

            return;
        }
        if ('-v' == $command || '--version' == $command) {
            echo $serverCommand->logo, PHP_EOL;

            return;
        }
        if (!method_exists($serverCommand, $command)) {
            smproxy_error("ERROR: Unknown option \"{$command}\"" . PHP_EOL . "Try `server -h' for more information.");

            return;
        }
        PhpHelper::call([$serverCommand, $command]);
    }
}
