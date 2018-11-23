<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/12
 * Time: 上午9:56.
 */

namespace SMProxy\Log;

/**
 * 日志类.
 *
 * Description:
 * 1.自定义日志根目录及日志文件名称。
 * 2.使用日期时间格式自定义日志目录。
 * 3.自动创建不存在的日志目录。
 * 4.记录不同分类的日志，例如信息日志，警告日志，错误日志。
 * 5.可自定义日志配置，日志根据标签调用不同的日志配置。
 *
 * Func
 * public  static set_config 设置配置
 * public  static get_logger 获取日志类对象
 * public  info              写入信息日志
 * public  warn              写入警告日志
 * public  error             写入错误日志
 * private add               写入日志
 * private create_log_path   创建日志目录
 * private get_log_file      获取日志文件名称
 */
class Log
{
    // 日志根目录
    private $_log_path = '.';

    // 日志文件
    private $_log_file = 'system.log';

    // 日志自定义目录
    private $_format = 'Y/m/d';

    // 日志标签
    private $_tag = 'system';

    // 总配置设定
    private static $_CONFIG;

    public static $open = true;

    /**
     * 设置配置.
     *
     * @param array $config 总配置设定
     * @param bool  $open   日志开关
     */
    public static function set_config(array $config = [], bool $open = true)
    {
        self::$_CONFIG = $config;
        self::$open = $open;
    }

    /**
     * 获取日志类对象
     *
     * @param array $config 总配置设定
     */
    public static function get_logger(string $tag = 'system')
    {
        // 根据tag从总配置中获取对应设定，如不存在使用system设定
        $config = isset(self::$_CONFIG[$tag]) ? self::$_CONFIG[$tag] : (isset(self::$_CONFIG['system']) ? self::$_CONFIG['system'] : []);

        // 设置标签
        $config['tag'] = '' != $tag && 'system' != $tag ? $tag : '-';

        // 返回日志类对象
        return new Log($config);
    }

    /**
     * 初始化.
     *
     * @param array $config 配置设定
     */
    public function __construct(array $config = [])
    {
        // 日志根目录
        if (isset($config['log_path'])) {
            $this->_log_path = $config['log_path'];
        }

        // 日志文件
        if (isset($config['log_file'])) {
            $this->_log_file = $config['log_file'];
        }

        // 日志自定义目录
        if (isset($config['format'])) {
            $this->_format = $config['format'];
        }

        // 日志标签
        if (isset($config['tag'])) {
            $this->_tag = $config['tag'];
        }
    }

    /**
     * 写入信息日志.
     *
     * @param string $data 信息数据
     *
     * @return bool
     */
    public function info(string $data)
    {
        return $this->add('INFO', $data);
    }

    /**
     * 写入警告日志.
     *
     * @param string $data 警告数据
     *
     * @return bool
     */
    public function warn(string $data)
    {
        return $this->add('WARN', $data);
    }

    /**
     * 写入错误日志.
     *
     * @param string $data 错误数据
     *
     * @return bool
     */
    public function error(string $data)
    {
        return $this->add('ERROR', $data);
    }

    /**
     * 写入日志.
     *
     * @param string $type 日志类型
     * @param string $data 日志数据
     *
     * @return bool
     */
    private function add(string $type, string $data)
    {
        if (self::$open) {
            // 获取日志文件
            $log_file = $this->get_log_file();

            // 创建日志目录
            $is_create = $this->create_log_path(dirname($log_file));

            // 创建日期时间对象
            $dt = new \DateTime();

            // 日志内容
            $log_data = sprintf('[%s] %-5s %s %s' . PHP_EOL, $dt->format('Y-m-d H:i:s'), $type, $this->_tag, $data);

            // 写入日志文件
            if ($is_create) {
                try {
                    return \Swoole\Coroutine::writeFile($log_file, $log_data, FILE_APPEND);
                } catch (\Exception $exception) {
                    return file_put_contents($log_file, $log_data, FILE_APPEND);
                }
            }
        }

        return false;
    }

    /**
     * 创建日志目录.
     *
     * @param string $log_path 日志目录
     *
     * @return bool
     */
    private function create_log_path(string $log_path)
    {
        if (!is_dir($log_path)) {
            return mkdir($log_path, 0777, true);
        }

        return true;
    }

    /**
     * 获取日志文件名称.
     *
     * @return string
     */
    private function get_log_file()
    {
        // 创建日期时间对象writeFile
        $dt = new \DateTime();
        // 计算日志目录格式
        return sprintf('%s/%s/%s', $this->_log_path, $dt->format($this->_format), $this->_log_file);
    }
}
