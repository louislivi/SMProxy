<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午1:11.
 */

namespace SMProxy\Command;

use function SMProxy\Helper\smproxy_error;

class ServerCommand
{
    public $logo;
    public $desc;
    public $usage;
    public $serverSetting = [];
    const  SMPROXY_VERSION = 'v1.2.7';

    public function __construct()
    {
        $this->logo  = HelpMessage::$logo . PHP_EOL . HelpMessage::$version;
        $this->desc  = $this->logo . PHP_EOL . HelpMessage::$usage . PHP_EOL . HelpMessage::$desc;
        $this->usage = $this->logo . PHP_EOL . HelpMessage::$usage;
    }

    /**
     * 启动服务
     *
     * @throws \ErrorException
     */
    public function start()
    {
        // 是否正在运行
        if ($this->isRunning()) {
            smproxy_error("The server have been running! (PID: {$this->serverSetting['masterPid']})");
        }

        echo $this->logo, PHP_EOL;
        echo 'Server starting ...', PHP_EOL;
        new \SMProxy\SMProxyServer();
    }

    /**
     * 停止服务.
     */
    public function stop()
    {
        if (!$this->isRunning()) {
            smproxy_error('ERROR: The server is not running! cannot stop!');
        }

        echo 'SMProxy is stopping ...', PHP_EOL;

        $result = function () {
            // 获取master进程ID
            $masterPid = $this->serverSetting['masterPid'];
            // 使用swoole_process::kill代替posix_kill
            \swoole_process::kill($masterPid);
            $timeout = 60;
            $startTime = time();
            while (true) {
                // Check the process status
                if (\swoole_process::kill($masterPid, 0)) {
                    // 判断是否超时
                    if (time() - $startTime >= $timeout) {
                        return false;
                    }
                    usleep(10000);
                    continue;
                }

                return true;
            }
        };

        // 停止失败
        if (!$result()) {
            smproxy_error('SMProxy shutting down failed!');
        }

        // 删除pid文件
        @unlink(CONFIG['server']['swoole']['pid_file']);

        echo 'SMProxy has been shutting down.', PHP_EOL;
    }

    /**
     * 重启服务
     *
     * @throws \ErrorException
     * @throws \SMProxy\SMProxyException
     */
    public function restart()
    {
        // 是否已启动
        if ($this->isRunning()) {
            $this->stop();
        }

        // 重启默认是守护进程
        $this->start();
    }

    /**
     * 平滑重启.
     */
    public function reload()
    {
        // 是否已启动
        if (!$this->isRunning()) {
            echo 'The server is not running! cannot reload', PHP_EOL;

            return;
        }

        echo 'Server is reloading...', PHP_EOL;
        \swoole_process::kill($this->serverSetting['managerPid'], SIGUSR1);
        echo 'Server reload success', PHP_EOL;
    }

    /**
     * 服务状态
     */
    public function status()
    {
        // 是否已启动
        if ($this->isRunning()) {
            echo 'The server is running', PHP_EOL;
        } else {
            echo 'The server is not running', PHP_EOL;
        }
    }

    /**
     * 判断服务是否在运行中.
     *
     * @return bool
     */
    public function isRunning()
    {
        $masterIsLive = false;
        $pFile = CONFIG['server']['swoole']['pid_file'];

        // 判断pid文件是否存在
        if (file_exists($pFile)) {
            // 获取pid文件内容
            $pidFile = file_get_contents($pFile);
            $pids = explode(',', $pidFile);

            $this->serverSetting['masterPid'] = $pids[0];
            $this->serverSetting['managerPid'] = $pids[1];
            $masterIsLive = $this->serverSetting['masterPid'] && @\swoole_process::kill($this->serverSetting['managerPid'], 0);
        }

        return $masterIsLive;
    }
}
