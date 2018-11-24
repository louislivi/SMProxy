<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午1:11.
 */

namespace SMProxy\Command;

class ServerCommand
{
    public $logo = <<<'LOGO'

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
    protected $version = 'SMProxy version: ' . SMPROXY_VERSION ;
    public $desc = <<<'DESC'
Options and arguments (and corresponding environment variables):
start   : start server
stop    : stop server
restart : restart server
status  : view service status
reload  : graceful restart
-h      : print this help message and exit (also --help)
-v      : print server version
DESC;
    public $serverSetting = [];

    public function __construct()
    {
        $this->logo = $this->logo . $this->version;
        $this->desc = $this->logo . $this->desc;
    }

    /**
     * 启动服务
     *
     * @throws \ErrorException
     * @throws \SMProxy\SMProxyException
     */
    public function start()
    {
        // 是否正在运行
        if ($this->isRunning()) {
            print_r("The server have been running!(PID: {$this->serverSetting['masterPid']})\n");

            return;
        }
        print_r($this->logo . 'server starting ...' . "\n");
        new \SMProxy\SMProxyServer();
    }

    /**
     * 停止服务.
     */
    public function stop()
    {
        if (!$this->isRunning()) {
            print_r('The server is not running! cannot stop!' . "\n");

            return;
        }
        print_r('SMProxy is stopping ...' . "\n");

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
            print_r('SMProxy stop fail' . "\n");

            return;
        }
        //删除pid文件
        @unlink(CONFIG['server']['swoole']['pid_file']);

        print_r('SMProxy stop success!' . "\n");
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
            print_r('The server is not running! cannot reload' . "\n");

            return;
        }

        print_r('Server is reloading...' . "\n");
        posix_kill($this->serverSetting['managerPid'], SIGUSR1);
        print_r('Server reload success' . "\n");
    }

    /**
     * 服务状态
     */
    public function status()
    {
        // 是否已启动
        if ($this->isRunning()) {
            print_r('The server is running' . "\n");
        } else {
            print_r('The server is not running' . "\n");
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
            $masterIsLive = $this->serverSetting['masterPid'] && @posix_kill($this->serverSetting['managerPid'], 0);
        }

        return $masterIsLive;
    }
}
