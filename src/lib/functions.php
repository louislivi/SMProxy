<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:33.
 */

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
 * @param $data
 *
 * @return string
 */
function getString(array $bytes)
{
    $string = '';
    $count = count($bytes);
    for ($i = 0; $i < $count; ++$i) {
        $str = chr($bytes[$i]);
        $string .= $str;
    }

    return $string;
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
    $newArray = [];
    for ($i = 0; $i < $len; ++$i) {
        $newArray[] = $array[$start + $i];
    }

    return $newArray;
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
    if ($size <= 255) {
        $sizeData[] = $size;
        $sizeData[] = 0;
        $sizeData[] = 0;
    } else {
        $sizeData[] = 255;
        if ($size - 255 > 0) {
            $sizeData[] = $size - 255;
        } else {
            $sizeData[] = $size;
        }
        if ($size - 255 - 255 > 0) {
            $sizeData[] = $size - 255 - 255;
        } else {
            $sizeData[] = $size;
        }
    }

    return $sizeData;
}

/**
 * 无符号16位右移.
 *
 * @param int $x    要进行操作的数字
 * @param int $bits 右移位数
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
    if (is_dir($dir)) {
        //打开
        if ($dh = @opendir($dir)) {
            //读取
            while (false !== ($file = readdir($dh))) {
                if ('.' != $file && '..' != $file && !is_dir($file) &&
                    '.json' == substr($file, -5, 5)) {
                    $file_config = json_decode(file_get_contents($dir . $file), true);
                    if (is_array($file_config)) {
                        $config = array_merge($config, $file_config);
                    }
                }
            }
            //关闭
            closedir($dh);
        }
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

    if (isset($config['server']['swoole_client_sock_setting']['sync_type'])) {
        replace_constant($config['server']['swoole_client_sock_setting']['sync_type'], SWOOLE_SOCK_ASYNC);
    } else {
        $config['server']['swoole_client_sock_setting']['sync_type'] = SWOOLE_SOCK_ASYNC;
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
 * @param string       $output 转换后的编码
 */
function array_iconv($data, string $output = 'utf-8')
{
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
    $tagInfo = \SMProxy\Helper\ProcessHelper::run('cd ' . ROOT . ' && git describe --tags HEAD')[1];
    if (preg_match('/^(?<tag>.+)(-\d+-g(?<hash>[a-f0-9]{7}))?$/', $tagInfo, $matches)) {
        return $matches['tag'] . (isset($matches['hash']) ? '@' . $matches['hash'] : '');
    } else {
        throw new \RuntimeException('Could not absorb version from git.');
    }
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
