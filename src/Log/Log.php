<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/12
 * Time: 上午9:56.
 */

namespace SMProxy\Log;

use Swoole\Coroutine;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * 日志类.
 */
class Log extends AbstractLogger
{
    // 日志根目录
    private $logPath = '.';

    // 日志文件
    private $logFile = 'system.log';

    // 日志自定义目录
    private $format = 'Y/m/d';

    // 日志标签
    private $tag = 'system';

    // 总配置设定
    private static $CONFIG = [];

    public static $open = true;

    public static $levels = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private $minLevelIndex;

    /**
     * Log constructor.
     *
     * @param array $config
     * @param null $minLevel
     */
    public function __construct(array $config, $minLevel = null)
    {
        // 日志根目录
        if (isset($config['log_path'])) {
            $this->logPath = $config['log_path'];
        }

        // 日志文件
        if (isset($config['log_file'])) {
            $this->logFile = $config['log_file'];
        }

        // 日志自定义目录
        if (isset($config['format'])) {
            $this->format = $config['format'];
        }

        // 日志标签
        if (isset($config['tag'])) {
            $this->tag = $config['tag'];
        }

        if (null === $minLevel) {
            $minLevel = LogLevel::WARNING;

            if (isset($_ENV['SHELL_VERBOSITY']) || isset($_SERVER['SHELL_VERBOSITY'])) {
                switch ((int) (isset($_ENV['SHELL_VERBOSITY']) ? $_ENV['SHELL_VERBOSITY'] : $_SERVER['SHELL_VERBOSITY'])) {
                    case -1:
                        $minLevel = LogLevel::ERROR;
                        break;
                    case 1:
                        $minLevel = LogLevel::NOTICE;
                        break;
                    case 2:
                        $minLevel = LogLevel::INFO;
                        break;
                    case 3:
                        $minLevel = LogLevel::DEBUG;
                        break;
                }
            }
        }

        if (!isset(self::$levels[$minLevel])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $minLevel));
        }

        $this->minLevelIndex = self::$levels[$minLevel];
    }

    /**
     * 添加日志。
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        if (self::$open) {
            if (!isset(self::$levels[$level])) {
                throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
            }

            if (self::$levels[$level] < $this->minLevelIndex) {
                return;
            }

            $log_data = $this ->format($level, $message, $context);

            // 获取日志文件
            $log_file = $this->getLogFile();

            // 创建日志目录
            $is_create = $this->createLogPath(dirname($log_file));
            // 写入日志文件
            if ($is_create) {
                if (Coroutine::getuid() > 0) {
                    // 协程写
                    $this->coWrite($log_file, $log_data);
                } else {
                    $this->syncWrite($log_file, $log_data);
                }
            }
        }
    }

    /**
     * 格式化输出信息。
     *
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return string
     */
    private function format(string $level, string $message, array $context): string
    {
        if (false !== strpos($message, '{')) {
            $replacements = [];
            foreach ($context as $key => $val) {
                if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                    $replacements["{{$key}}"] = $val;
                } elseif ($val instanceof \DateTimeInterface) {
                    $replacements["{{$key}}"] = $val->format('Y-m-d H:i:s');
                } elseif (\is_object($val)) {
                    $replacements["{{$key}}"] = '[object ' . \get_class($val) . ']';
                } else {
                    $replacements["{{$key}}"] = '[' . \gettype($val) . ']';
                }
            }

            $message = strtr($message, $replacements);
        }

        return sprintf('%s [%s] %s', date('Y-m-d H:i:s'), $level, $message) . \PHP_EOL;
    }

    /**
     * 获取日志类对象。
     *
     * @param string $tag
     *
     * @return Log
     */
    public static function getLogger(string $tag = 'system')
    {
        if (!is_array(self::$CONFIG) || empty(self::$CONFIG)) {
            self::$CONFIG = CONFIG['server']['logs']['config'];
            self::$open = CONFIG['server']['logs']['open'];
        }

        // 根据tag从总配置中获取对应设定，如不存在使用system设定
        $config = isset(self::$CONFIG[$tag]) ? self::$CONFIG[$tag] :
            (isset(self::$CONFIG['system']) ? self::$CONFIG['system'] : []);

        // 设置标签
        $config['tag'] = '' != $tag && 'system' != $tag ? $tag : '-';

        // 返回日志类对象
        return new Log($config, LogLevel::DEBUG);
    }

    /**
     * 创建日志目录.
     *
     * @param string $log_path 日志目录
     *
     * @return bool
     */
    private function createLogPath(string $log_path)
    {
        $dirs = explode("/", $log_path);
        $current_dir = "";
        foreach ($dirs as $dir) {
            $current_dir .= $dir;
            if (file_exists($current_dir) && !is_dir($current_dir)) {
                @unlink($current_dir);
            }
            $current_dir .= "/";
            if (!file_exists($current_dir)) {
                @mkdir($current_dir, 0755);
            }
        }
        return true;
    }

    /**
     * 获取日志文件名称.
     *
     * @return string
     */
    private function getLogFile()
    {
        // 创建日期时间对象writeFile
        $dt = new \DateTime();
        // 计算日志目录格式
        return sprintf('%s/%s/%s', $this->logPath, $dt->format($this->format), $this->logFile);
    }

    /**
     * 协程写文件
     *
     * @param string $logFile     日志路径
     * @param string $messageText 文本信息
     */
    private function coWrite(string $logFile, string $messageText)
    {
        go(function () use ($logFile, $messageText) {
            $res = Coroutine::writeFile($logFile, $messageText, FILE_APPEND);
            if ($res === false) {
                throw new \InvalidArgumentException("Unable to append to log file: {$this->logFile}");
            }
        });
    }

    /**
     * 同步写文件
     *
     * @param string $logFile     日志路径
     * @param string $messageText 文本信息
     */
    private function syncWrite(string $logFile, string $messageText)
    {
        $fp = fopen($logFile, 'a');
        if ($fp === false) {
            throw new \InvalidArgumentException("Unable to append to log file: {$this->logFile}");
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $messageText);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
