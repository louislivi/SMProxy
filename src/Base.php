<?php

namespace SMProxy;

use SMProxy\Log\Log;
use Swoole\Coroutine\Channel;
use SMProxy\MysqlPool\MySQLException;
use SMProxy\MysqlPacket\ErrorPacket;
use function SMProxy\Helper\array_iconv;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/30
 * Time: 上午11:06.
 */
class Base extends Context
{
    /**
     * 协程执行处理异常.
     *
     * @param $function
     */
    protected static function go(\Closure $function)
    {
        if (-1 !== \Swoole\Coroutine::getuid()) {
            $pool = self::$pool[\Swoole\Coroutine::getuid()] ?? false;
        } else {
            $pool = false;
        }
        go(function () use ($function, $pool) {
            try {
                if ($pool) {
                    self::$pool[\Swoole\Coroutine::getuid()] = $pool;
                }
                $function();
                if ($pool) {
                    unset(self::$pool[\Swoole\Coroutine::getuid()]);
                }
            } catch (SMProxyException $SMProxyException) {
                self::writeErrorMessage($SMProxyException, 'system');
            } catch (MySQLException $MySQLException) {
                self::writeErrorMessage($MySQLException, 'mysql');
            }
        });
    }

    /**
     * 写入日志
     *
     * @param $exception
     * @param string $tag
     */
    protected static function writeErrorMessage($exception, string $tag = 'mysql')
    {
        $log = Log::getLogger($tag);
        $errLevel = $exception->getCode() ? array_search($exception->getCode(), Log::$levels) : 'warning';
        $log->$errLevel($exception->errorMessage());
        if (CONFIG['server']['swoole']['daemonize'] != true) {
            echo  '[' . ucfirst($errLevel) . '] ', $exception->errorMessage(), PHP_EOL;
        }
    }

    /**
     * 格式化配置项.
     *
     * @param array $_config
     *
     * @return array
     *
     * @throws \SMProxy\SMProxyException
     */
    public function parseDbConfig(array $_config)
    {
        $config = $_config['database'] ?? [];
        foreach ($config['databases'] as $key => $database) {
            if (isset($config['serverInfo'][$database['serverInfo']])) {
                //处理连接池参数
                $this ->setConnPoolParams($config['databases'][$key], $config['databases'][$key]);
                foreach ($config['serverInfo'][$database['serverInfo']] as $s_key => $value) {
                    $database_result = &$config['databases'][$s_key . DB_DELIMITER . $key];
                    //处理连接参数
                    if (isset($config['account'][$value['account']])) {
                        $host = &$config['serverInfo'][$database['serverInfo']][$s_key]['host'];
                        if (is_array($host)) {
                            $host = $host[array_rand($host)];
                        }
                        $database_result = $config['databases'][$key];
                        $database_result['serverInfo'] =
                            $config['serverInfo'][$database['serverInfo']][$s_key];
                        $database_result['serverInfo']['account'] =
                            $config['account'][$value['account']];
                        //重载连接池参数
                        $this ->setConnPoolParams($value, $database_result, $database_result['serverInfo']);
                        if (!isset($config['databases'][$s_key])) {
                            $config['databases'][$s_key] = $config['databases'][$key];
                            $config['databases'][$s_key]['serverInfo'] = $database_result['serverInfo'];
                        }
                    } else {
                        throw new SMProxyException('Config serverInfo->' . $s_key . '->account is not exists!');
                    }
                }
            } else {
                throw new SMProxyException('Config serverInfo key ' . $database['serverInfo'] . 'is not exists!');
            }
            unset($config['databases'][$key]);
        }
        return $config['databases'];
    }

    /**
     * 设置连接池参数
     *
     * @param array $value      需要设置的值
     * @param array $new_params 被设置的参数
     * @param array $old_params 删除旧参数
     *
     */
    private function setConnPoolParams(array $value, array &$new_params, array &$old_params = [])
    {
        if (isset($value['maxConns'])) {
            $new_params['maxConns']      = $this ->evalConfigParam($value['maxConns'], true);
            if (!empty($old_params)) {
                unset($old_params['maxConns']);
            }
        }
        if (isset($value['maxSpareConns'])) {
            $new_params['maxSpareConns'] = $this ->evalConfigParam($value['maxSpareConns'], true);
            if (!empty($old_params)) {
                unset($old_params['maxSpareConns']);
            }
        }
        if (isset($value['startConns'])) {
            $new_params['startConns'] = $this ->evalConfigParam($value['startConns']);
            if (!empty($old_params)) {
                unset($old_params['startConns']);
            }
        }
        if (isset($value['maxSpareExp'])) {
            $new_params['maxSpareExp'] = $this ->evalConfigParam($value['maxSpareExp']);
            if (!empty($old_params)) {
                unset($old_params['maxSpareExp']);
            }
        }
    }

    /**
     * 计算配置参数
     *
     * @param string $value
     * @param bool $floor_worker_num
     *
     * @return float|mixed
     */
    private function evalConfigParam(string $value, bool $floor_worker_num = false)
    {
        if ($floor_worker_num) {
            $param = floor(
                eval('return ' . $value . ';') / CONFIG['server']['swoole']['worker_num']
            );
        } else {
            $param = eval('return ' . $value . ';');
        }
        return $param;
    }

    /**
     * 协程pop
     *
     * @param $chan
     * @param int $timeout
     *
     * @return bool
     */
    protected static function coPop(Channel $chan, int $timeout = 0)
    {
        if (version_compare(swoole_version(), '4.0.3', '>=')) {
            return $chan->pop($timeout);
        } else {
            if (0 == $timeout) {
                return $chan->pop();
            } else {
                $writes = [];
                $reads = [$chan];
                $result = $chan->select($reads, $writes, $timeout);
                if (false === $result || empty($reads)) {
                    return false;
                }
                $readChannel = $reads[0];
                return $readChannel->pop();
            }
        }
    }

    protected static function writeErrMessage(int $id, string $msg, int $errno = 0, $sqlState = 'HY000')
    {
        $err = new ErrorPacket();
        $err->packetId = $id;
        if ($errno) {
            $err->errno = $errno;
        }
        $err->sqlState = $sqlState;
        $err->message  = array_iconv($msg);

        return $err->write();
    }
}
