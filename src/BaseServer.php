<?php

namespace SMProxy;

use SMProxy\Helper\ProcessHelper;
use function SMProxy\Helper\packageLengthSetting;
use function SMProxy\Helper\smproxy_error;
use SMProxy\Log\Log;
use Swoole\Coroutine;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:40.
 */
abstract class BaseServer extends Base
{
    protected $connectReadState = [];
    protected $connectHasTransaction = [];
    protected $connectHasAutoCommit = [];
    protected $server;

    /**
     * BaseServer constructor.
     *
     */
    public function __construct()
    {
        try {
            if (!(CONFIG['server']['swoole'] ?? false)) {
                $system_log = Log::getLogger('system');
                $system_log->error('config [swoole] is not found !');
                throw new SMProxyException('config [swoole] is not found !');
            }
            if ((CONFIG['server']['port'] ?? false)) {
                $ports = explode(',', CONFIG['server']['port']);
            } else {
                $ports = [3366];
            }
            $this->server = new \swoole_server(
                CONFIG['server']['host'],
                $ports[0],
                CONFIG['server']['mode'],
                CONFIG['server']['sock_type']
            );
            if (count($ports) > 1) {
                for ($i = 1; $i < count($ports); ++$i) {
                    $this->server->addListener(
                        CONFIG['server']['host'],
                        $ports[$i],
                        CONFIG['server']['sock_type']
                    );
                }
            }
            $this->server->set(CONFIG['server']['swoole']);
            $this->server->on('connect', [$this, 'onConnect']);
            $this->server->on('receive', [$this, 'onReceive']);
            $this->server->on('close', [$this, 'onClose']);
            $this->server->on('start', [$this, 'onStart']);
            $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
            $this->server->on('ManagerStart', [$this, 'onManagerStart']);
            $this->server->set(packageLengthSetting());
            $result = $this->server->start();
            if ($result) {
                smproxy_error('WARNING: Server is shutdown!');
            } else {
                smproxy_error('ERROR: Server start failed!');
            }
        } catch (\Swoole\Exception $exception) {
            smproxy_error('ERROR:' . $exception->getMessage());
        } catch (\ErrorException $exception) {
            smproxy_error('ERROR:' . $exception->getMessage());
        } catch (SMProxyException $exception) {
            smproxy_error('ERROR:' . $exception->errorMessage());
        }
    }

    protected function onConnect(\swoole_server $server, int $fd)
    {
    }

    protected function onReceive(\swoole_server $server, int $fd, int $reactor_id, string $data)
    {
    }

    protected function onWorkerStart(\swoole_server $server, int $worker_id)
    {
    }

    public function onStart(\swoole_server $server)
    {
        \file_put_contents(CONFIG['server']['swoole']['pid_file'], $server->master_pid . ',' . $server->manager_pid);
        ProcessHelper::setProcessTitle('SMProxy master  process');
    }

    public function onManagerStart(\swoole_server $server)
    {
        ProcessHelper::setProcessTitle('SMProxy manager process');
    }

    /**
     * 关闭连接 销毁协程变量.
     *
     * @param $server
     * @param $fd
     */
    protected function onClose(\swoole_server $server, int $fd)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0 && isset(self::$pool[$cid])) {
            unset(self::$pool[$cid]);
        }
    }
}
