<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:33.
 */

namespace SMProxy\Helper;

use SMProxy\Command\ServerCommand;
use SMProxy\Log\Log;
use SMProxy\MysqlPacket\Util\CharsetUtil;

/**
 * 获取bytes 数组.
 *
 * @param $data
 *
 * @return array
 */
function getBytes(string $data)
{
    $bytes = [];
    $count = strlen($data);
    for ($i = 0; $i < $count; ++$i) {
        $byte = ord($data[$i]);
        $bytes[] = $byte;
    }

    return $bytes;
}

/**
 * 获取 string.
 *
 * @param array $bytes
 *
 * @return string
 */
function getString(array $bytes)
{
    return implode(array_map('chr', $bytes));
}

/**
 * 数组复制.
 *
 * @param $array
 * @param $start
 * @param $len
 *
 * @return array
 */
function array_copy(array $array, int $start, int $len)
{
    return array_slice($array, $start, $len);
}

/**
 * 计算mysql包的大小.
 *
 * @param $size
 *
 * @return array
 */
function getMysqlPackSize(int $size)
{
    $sizeData[] = $size & 0xff;
    $sizeData[] = shr16($size & 0xff << 8, 8);
    $sizeData[] = shr16($size & 0xff << 16, 16);
    return $sizeData;
}

/**
 * 无符号16位右移.
 *
 * @param int $x    要进行操作的数字
 * @param int $bits 右移位数
 *
 * @return int
 */
function shr16(int $x, int $bits)
{
    return ((2147483647 >> ($bits - 1)) & ($x >> $bits)) > 255 ? 255 : ((2147483647 >> ($bits - 1)) & ($x >> $bits));
}

/**
 * 初始化配置文件.
 *
 * @param string $dir
 *
 * @return array
 *
 * @throws \SMProxy\SMProxyException
 */
function initConfig(string $dir)
{
    $config = [];

    $dir = realpath($dir);
    if (!is_dir($dir)) {
        throw new \RuntimeException('Cannot find config dir.');
    }

    $paths = glob($dir . '/*.json');

    foreach ($paths as $path) {
        $item = json_decode(file_get_contents($path), true);
        if (is_array($item)) {
            $config = array_merge($config, $item);
        } else {
            throw new \InvalidArgumentException('Invalid config.');
        }
    }

    if (!isset($config['server']['host'])) {
        $config['server']['host'] = '0.0.0.0';
    }

    if (!isset($config['server']['port'])) {
        $config['server']['port'] = 3366;
    }

    //是否为前端运行
    if (CONSOLE) {
        $config['server']['swoole']['daemonize'] = false;
    }

    //计算worker_num
    if (isset($config['server']['swoole']['worker_num'])) {
        $config['server']['swoole']['worker_num'] = eval('return ' . $config['server']['swoole']['worker_num'] . ';');
    } else {
        $config['server']['swoole']['worker_num'] = 1;
    }

    //替换swoole 常量
    if (isset($config['server']['mode'])) {
        replace_constant($config['server']['mode'], SWOOLE_PROCESS);
    } else {
        $config['server']['mode'] = SWOOLE_PROCESS;
    }

    if (isset($config['server']['sock_type'])) {
        replace_constant($config['server']['sock_type'], SWOOLE_SOCK_TCP);
    } else {
        $config['server']['sock_type'] = SWOOLE_SOCK_TCP;
    }

    if (isset($config['server']['swoole_client_sock_setting']['sock_type'])) {
        replace_constant($config['server']['swoole_client_sock_setting']['sock_type'], SWOOLE_SOCK_TCP);
    } else {
        $config['server']['swoole_client_sock_setting']['sock_type'] = SWOOLE_SOCK_TCP;
    }

    //生成日志目录
    if (isset($config['server']['logs']['config']['system']['log_path'])) {
        mk_log_dir($config['server']['logs']['config']['system']['log_path']);
    } else {
        throw new \SMProxy\SMProxyException('ERROR:server.logs.config.system.log_path 配置项不存在!');
    }
    if (isset($config['server']['logs']['config']['mysql']['log_path'])) {
        mk_log_dir($config['server']['logs']['config']['mysql']['log_path']);
    } else {
        throw new \SMProxy\SMProxyException('ERROR:server.logs.config.mysql.log_path 配置项不存在!');
    }
    if (isset($config['server']['swoole']['log_file'])) {
        mk_log_dir($config['server']['swoole']['log_file']);
    } else {
        throw new \SMProxy\SMProxyException('ERROR:server.swoole.log_file 配置项不存在!');
    }
    if (isset($config['server']['swoole']['pid_file'])) {
        mk_log_dir($config['server']['swoole']['pid_file']);
    } else {
        throw new \SMProxy\SMProxyException('ERROR:server.swoole.pid_file 配置项不存在!');
    }

    return $config;
}

/**
 * 替换常量值
 *
 * @param string $const
 * @param string $default
 */
function replace_constant(string &$const, string $default = '')
{
    if (defined($const)) {
        $const = constant($const);
    } else {
        $const = $default;
    }
}

/**
 * 创建日志目录.
 *
 * @param string $path
 */
function mk_log_dir(string &$path)
{
    $path = str_replace('ROOT', ROOT, $path);
    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
}

/**
 * 对数据进行编码转换.
 *
 * @param array/string $data   数组
 * @param string $output 转换后的编码
 *
 * @return array|null|string|string[]
 */
function array_iconv($data, string $output = 'utf-8')
{
    $output = CharsetUtil::charsetToEncoding($output);
    $encode_arr = ['UTF-8', 'ASCII', 'GBK', 'GB2312', 'BIG5', 'JIS', 'eucjp-win', 'sjis-win', 'EUC-JP'];
    $encoded = mb_detect_encoding($data, $encode_arr);

    if (!is_array($data)) {
        return mb_convert_encoding($data, $output, $encoded);
    } else {
        foreach ($data as $key => $val) {
            $key = array_iconv($key, $output);
            if (is_array($val)) {
                $data[$key] = array_iconv($val, $output);
            } else {
                $data[$key] = mb_convert_encoding($data, $output, $encoded);
            }
        }

        return $data;
    }
}

/**
 * get version.
 *
 * @return string
 */
function absorb_version_from_git()
{
    $defaultVersion = ServerCommand::SMPROXY_VERSION;
    $hasGit = \SMProxy\Helper\ProcessHelper::run('type git >/dev/null 2>&1 || { echo >&2 "false" ;}', ROOT)[2];
    if ($hasGit !== "false") {
        $tagInfo = \SMProxy\Helper\ProcessHelper::run('git describe --tags HEAD', ROOT)[1];
        if (preg_match('/^(?<tag>.+)-\d+-g(?<hash>[a-f0-9]{7})$/', $tagInfo, $matches)) {
            return sprintf('%s@%s', $matches['tag'], $matches['hash']);
        } elseif ($tagInfo) {
            return $tagInfo;
        }
    }
    return $defaultVersion;
}

/**
 * error.
 *
 * @param $message
 * @param int $exitCode
 */
function smproxy_error($message, $exitCode = 0)
{
    $parts = explode(':', $message, 2);

    $parts[0] = strtoupper($parts[0]);

    $prefixExists = in_array($parts[0], [
        'ERROR', 'WARNING', 'NOTICE',
    ]);

    if ($prefixExists) {
        $message = $parts[0] . ': ' . trim($parts[1]);
    } else {
        $message = 'ERROR: ' . $message;
    }

    error_log($message);

    if (!$prefixExists || 'ERROR' == $parts[0]) {
        exit($exitCode);
    }
}

/**
 * 处理粘包问题.
 *
 * @param string $data
 * @param bool $auth
 * @param int $headerLength 是否认证通过
 * @param bool $isClient 是否是客户端
 * @param string $halfPack 半包体
 *
 * @return array
 */
function packageSplit(string $data, bool $auth, int $headerLength = 4, bool $isClient = false, string &$halfPack = '')
{
    if ($halfPack !== '') {
        $data = $halfPack . $data;
    }
    $dataLen = strlen($data);
    if ($headerLength == 3) {
        $dataLen -= 1;
    }
    if ($dataLen < 4) {
        return [];
    }
    $packageLen = getPackageLength($data, 0, $headerLength);
    if ($dataLen == $packageLen) {
        $halfPack = '';
        return [$data];
    } elseif ($dataLen < $packageLen) {
        $halfPack = $data;
        return [];
    } else {
        $halfPack = '';
    }
    $packages = [];
    $split = function ($data, &$packages, $step = 0) use (&$split, $headerLength, $isClient) {
        if (isset($data[$step]) && 0 != ord($data[$step])) {
            $packageLength = getPackageLength($data, $step, $headerLength);
            if ($isClient) {
                $packageLength ++;
            }
            $packages[] = substr($data, $step, $packageLength);
            $split($data, $packages, $step + $packageLength);
        }
    };
    if ($auth) {
        $split($data, $packages);
    } else {
        $packageLength = getPackageLength($data, 0, 3) + 1;
        $packages[] = substr($data, 0, $packageLength);
        if (isset($data[$packageLength]) && 0 != ord($data[$packageLength])) {
            $split($data, $packages, $packageLength);
        }
    }

    return $packages;
}

/**
 * 获取包长
 *
 * @param string $data
 * @param int    $step
 * @param int    $offset
 *
 * @return int
 */
function getPackageLength(string $data, int $step, int $offset)
{
    $i = ord($data[$step]);
    $i |= ord($data[$step + 1]) << 8;
    $i |= ord($data[$step + 2]) << 16;
    if ($offset >= 4) {
        $i |= ord($data[$step + 3]) << 24;
    }

    return $i + $offset;
}

/**
 * 处理异常
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function _error_handler(int $errno, string $errstr, string $errfile, int $errline)
{
    $errCode = strlen($errstr) > 3 ? substr($errstr, -4, 3) : 0;
    $errMethod = explode(': ', $errstr)[0] ?? '';
    if (strrpos($errMethod, 'Swoole\Coroutine\Client') === false && $errCode != '110' && $errCode != '111') {
        $system_log = Log::getLogger('system');
        $message = sprintf('%s (%s:%s)', $errstr, $errfile, $errline);
        $errLevel = $errno ? (array_search($errno + 1, Log::$levels) ?: 'error') : 'error';
        $system_log->$errLevel($message);
        if (CONFIG['server']['swoole']['daemonize'] != true) {
            echo '[' . ucfirst($errLevel) . '] ', trim($message), PHP_EOL;
        }
    }
}

function startsWith($haystack, $needle)
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle)
{
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}
