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
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function run(array $argv)
    {
        $serverCommand = new ServerCommand();
        if (!isset($argv[1]) || '-h' == $argv[1] || '--help' == $argv[1]) {
            echo $serverCommand->desc, PHP_EOL;

            return;
        }
        if ('-v' == $argv[1] || '--version' == $argv[1]) {
            echo $serverCommand->logo, PHP_EOL;

            return;
        }
        if (!method_exists($serverCommand, $argv[1])) {
            smproxy_error("ERROR: Unknown option \"{$argv[1]}\"" . PHP_EOL . "Try `server -h' for more information.");

            return;
        }
        PhpHelper::call([$serverCommand, $argv[1]], ...array_copy($argv, 1, count($argv) - 2));
    }
}
