<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午1:04.
 */

namespace SMProxy\Command;

use function SMProxy\Helper\initConfig;
use SMProxy\Helper\PhpHelper;
use function SMProxy\Helper\smproxy_error;

class Command
{
    /**
     * 运行
     *
     * @param array $argv
     *
     * @throws \SMProxy\SMProxyException
     */
    public function run(array $argv)
    {
        $command = count($argv) >= 2 ? $argv[1] : false;
        $this ->settingConfig($argv);
        $this ->commandHandler($command);
    }

    /**
     * 设置配置文件
     *
     * @param array $argv
     *
     * @throws \SMProxy\SMProxyException
     */
    protected function settingConfig(array $argv)
    {
        //指定配置文件
        $configPath = ROOT . '/conf/';
        $configKey  = array_search('-c', $argv) ?: array_search('--config', $argv);
        if ($configKey) {
            if (!isset($argv[$configKey + 1])) {
                echo HelpMessage::$version . PHP_EOL . HelpMessage::$usage;
                exit(0);
            }
            $configPath = $argv[$configKey + 1];
        }

        if (file_exists($configPath)) {
            define('CONFIG_PATH', realpath($configPath) . '/');
            define('CONFIG', initConfig(realpath(CONFIG_PATH) . '/'));
        } else {
            smproxy_error('ERROR: ' . $configPath . ' No such file or directory!');
        }
    }

    /**
     * 处理命令
     *
     * @param string $command
     * @param string $command2
     */
    protected function commandHandler(string $command)
    {
        $serverCommand = new ServerCommand();

        if ('-h' == $command || '--help' == $command) {
            echo $serverCommand->desc, PHP_EOL;

            return;
        }

        if ('-v' == $command || '--version' == $command) {
            echo $serverCommand->logo, PHP_EOL;

            return;
        }

        if (!$command || !method_exists($serverCommand, $command)) {
            echo $serverCommand->usage, PHP_EOL;

            return;
        }

        PhpHelper::call([$serverCommand, $command]);
    }
}
