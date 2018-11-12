<?php

namespace SMProxy;

use SMProxy\MysqlPacket\ErrorPacket;
use Swoole\Coroutine;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:40
 */
abstract class BaseServer extends Base
{
    protected $connectReadState = [];
    protected $connectHasTransaction = [];
    protected $server;

    /**
     * BaseServer constructor.
     *
     * @throws \ErrorException
     */
    public function __construct()
    {
        try {
            if (!(CONFIG['server']['swoole'] ?? false)) {
                throw new SMProxyException('config [swoole] is not found !');
            }
            if ((CONFIG['server']['port'] ?? false)) {
                $ports = explode(',', CONFIG['server']['port']);
            } else {
                $ports = [3366];
            }
            $this->server = new \swoole_server(CONFIG['server']['host'] ?? '0.0.0.0',
                $ports[0], CONFIG['server']['mode'], CONFIG['server']['sock_type']);
            if (count($ports) > 1) {
                for ($i = 1; $i < count($ports); $i++) {
                    $this->server->addListener(CONFIG['server']['host'] ?? '0.0.0.0',
                        $ports[$i], CONFIG['server']['sock_type']);
                }
            }
            $this->server->set(CONFIG['server']['swoole']);
            $this->server->on('connect', [$this, 'onConnect']);
            $this->server->on('receive', [$this, 'onReceive']);
            $this->server->on('close', [$this, 'onClose']);
            $result = $this->server->start();
            if ($result){
                print_r('server start success!'. "\n");
            }else{
                print_r('server start error!'. "\n");
            }
        } catch (\Swoole\Exception | \ErrorException | SMProxyException $exception) {
            print_r('ERROR:'.$exception->getMessage() . "\n");
        }
    }

    protected function onConnect($server, $fd)
    {
    }

    protected function onReceive($server, $fd, $reactor_id, $data)
    {
    }

    /**
     * 关闭连接 销毁携程变量
     *
     * @param $server
     * @param $fd
     */
    protected function onClose($server, $fd)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0 && isset(self::$pool[$cid])) {
            unset(self::$pool[$cid]);
        }

    }


    protected function writeErrMessage($id,String $msg,int $errno = 0)
    {
        $err = new ErrorPacket();
        $err->packetId = $id;
        if ($errno){
            $err->errno = $errno;
        }
        $err->message = array_iconv($msg);
        return $err->write();
    }
}