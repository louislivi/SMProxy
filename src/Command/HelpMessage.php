<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/28
 * Time: 下午2:14
 */

namespace SMProxy\Command;

class HelpMessage
{
    public static $logo = <<<'LOGO'

  /$$$$$$  /$$      /$$ /$$$$$$$                                        
 /$$__  $$| $$$    /$$$| $$__  $$                                       
| $$  \__/| $$$$  /$$$$| $$  \ $$ /$$$$$$   /$$$$$$  /$$   /$$ /$$   /$$
|  $$$$$$ | $$ $$/$$ $$| $$$$$$$//$$__  $$ /$$__  $$|  $$ /$$/| $$  | $$
 \____  $$| $$  $$$| $$| $$____/| $$  \__/| $$  \ $$ \  $$$$/ | $$  | $$
 /$$  \ $$| $$\  $ | $$| $$     | $$      | $$  | $$  >$$  $$ | $$  | $$
|  $$$$$$/| $$ \/  | $$| $$     | $$      |  $$$$$$/ /$$/\  $$|  $$$$$$$
 \______/ |__/     |__/|__/     |__/       \______/ |__/  \__/ \____  $$
                                                               /$$  | $$
                                                              |  $$$$$$/
                                                               \______/
                                                               

LOGO;

    public static $version = 'SMProxy version: ' . SMPROXY_VERSION . PHP_EOL;

    public static $usage      = <<<'USAGE'
Usage:
  SMProxy [ start | stop | restart | status | reload ] [ -c | --config <configuration_path> | --console ]
  SMProxy -h | --help
  SMProxy -v | --version

USAGE;

    public static $desc       = <<<'DESC'
Options:
  start                            Start server
  stop                             Shutdown server
  restart                          Restart server
  status                           Show server status
  reload                           Reload configuration
  -h --help                        Display help
  -v --version                     Display version
  -c --config <configuration_path> Specify configuration path
  --console                        Front desk operation

DESC;

    public static $status     = <<<'STATUS'
SMProxy[${version}] - ${uname}
Host: ${host}, Port: ${port}, PHPVerison: ${php_version}
SwooleVersion: ${swoole_version}, WorkerNum: ${worker_num}
STATUS;
}
