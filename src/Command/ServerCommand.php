<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午1:11.
 */

namespace SMProxy\Command;

use SMProxy\Base;
use function SMProxy\Helper\initConfig;
use function SMProxy\Helper\smproxy_error;
use SMProxy\MysqlPool\MySQLException;
use Swoole\Coroutine;

class ServerCommand extends Base
{
    public $logo;
    public $desc;
    public $usage;
    public $serverSetting = [];
    const  SMPROXY_VERSION = 'v1.2.9';

    public function __construct()
    {
        $this->logo = HelpMessage::$logo . PHP_EOL . HelpMessage::$version;
        $this->desc = $this->logo . PHP_EOL . HelpMessage::$usage . PHP_EOL . HelpMessage::$desc;
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
        if (file_exists(CONFIG['server']['swoole']['pid_file'])) {
            @unlink(CONFIG['server']['swoole']['pid_file']);
        }

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
     *
     * @throws \SMProxy\SMProxyException
     */
    public function status()
    {
        // 是否已启动
        if ($this->isRunning()) {
            self::go(function () {
                //显示基础信息
                echo str_replace([
                    '${version}',
                    '${uname}',
                    '${php_version}',
                    '${worker_num}',
                    '${host}',
                    '${port}',
                    '${swoole_version}',
                ], [
                    self::SMPROXY_VERSION,
                    php_uname(),
                    PHP_VERSION,
                    CONFIG['server']['swoole']['worker_num'],
                    CONFIG['server']['host'],
                    CONFIG['server']['port'],
                    swoole_version(),
                ], HelpMessage::$status), PHP_EOL;
                $dbConfig = $this->parseDbConfig(initConfig(CONFIG_PATH));
                $serverClient = new Coroutine\Client(SWOOLE_SOCK_TCP);
                $serverClient->connect(CONFIG['server']['host'], CONFIG['server']['port'], 0.5);
                $serverClient->recv();
                $serverClient->send("status");
                $result = '';
                while ($nowData = $serverClient->recv()) {
                    $result .= $nowData;
                };
                $result = json_decode(base64_decode($result), true);
                $serverClient->close();
                $clients = [];
                foreach ($dbConfig as $key => $value) {
                    $database = explode(DB_DELIMITER, $key)[1] ?? false;
                    $model = explode(DB_DELIMITER, $key)[0];
                    if ($database && !isset($clients[$key])) {
                        $threadId = "";
                        foreach ($result as $index => $item) {
                            $indexes = explode(DB_DELIMITER, $index);
                            if (($indexes[0] . DB_DELIMITER . $indexes[1] == $key) || (count($indexes) == 2 && $indexes[0] == $model)) {
                                $threadId .= $item['threadId'] . ",";
                            }
                        }
                        if (empty($threadId)) {
                            continue;
                        }
                        $threadId = substr($threadId, 0, strlen($threadId) - 1);
                        $mysql = new Coroutine\MySQL();
                        $mysql->connect([
                            'host'     => CONFIG['server']['host'],
                            'user'     => CONFIG['server']['user'],
                            'port'     => CONFIG['server']['port'],
                            'password' => CONFIG['server']['password'],
                            'database' => $database,
                        ]);
                        $mysql->setDefer();
                        switch ($model) {
                            case 'read':
                                $mysql->query('/*SMProxy processlist sql*/select * from information_schema.processlist where id in (' . $threadId . ') order by id asc');
                                break;
                            case 'write':
                                $mysql->query('/** smproxy:db_type=write *//*SMProxy processlist sql*/select * from information_schema.processlist where id in (' . $threadId . ') order by id asc');
                                break;
                        }
                        $clients[$key] = $mysql;
                    }
                }
                //绘制表格数据
                $table = new Table();
                $table->setHeader(["ID", "USER", "HOST", "DB", "COMMAND", "TIME", "STATE", "INFO", "SERVER_VERSION", "PLUGIN_NAME", "SERVER_STATUS", "SERVER_KEY"]);
                $processlist = [];
                foreach ($clients as $key => $client) {
                    $model = explode(DB_DELIMITER, $key)[0];
                    $data = $client->recv() ?: [];
                    foreach ($data as $process) {
                        $processlist[$process["COMMAND"]] = ($processlist[$process["COMMAND"]] ?? 0) + 1;
                        foreach ($result as $index => $item) {
                            $indexes = explode(DB_DELIMITER, $index);
                            if ($process["ID"] == $item["threadId"]) {
                                $process["SERVER_VERSION"] = $item["serverVersion"];
                                $process["PLUGIN_NAME"] = $item["pluginName"];
                                $process["SERVER_STATUS"] = $item["serverStatus"];
                                if ($indexes[0] . DB_DELIMITER . $indexes[1] == $key) {
                                    $process["SERVER_KEY"] = $key;
                                } else if (count($indexes) == 2 && $indexes[0] == $model) {
                                    $process["SERVER_KEY"] = $model;
                                }
                            }
                        }
                        if (strpos($process["INFO"], "/*SMProxy processlist sql*/") !== false) {
                            $process["INFO"] = "/*SMProxy processlist sql*/";
                        }
                        $table->addRow(array_values($process));
                    }
                    if ($client->errno) {
                        throw new MySQLException($client->error);
                    }
                    $client->close();
                }
                $processlistDetails = '';
                foreach ($processlist as $key => $value) {
                    $processlistDetails .= ',  ' . $value . ' ' . strtolower($key);
                }
                echo 'Process :  ' . $table->count() . ' total' . $processlistDetails, PHP_EOL;
                echo $table->render(), PHP_EOL;
            });
        } else {
            echo 'The Server is not running', PHP_EOL;
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
