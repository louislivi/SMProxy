<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/26
 * Time: 下午5:33
 */

/**
 * 获取bytes 数组
 *
 * @param $data
 *
 * @return array
 */
function getBytes($data)
{
    $bytes = [];
    for ($i = 0; $i < strlen($data); $i++) {
        $byte = ord($data[$i]);
        $bytes[] = $byte;
    }
    return $bytes;
}

/**
 * 获取 string
 *
 * @param $data
 *
 * @return string
 */
function getString($bytes)
{
    $string = '';
    for ($i = 0; $i < count($bytes); $i++) {
        $str = chr($bytes[$i]);
        $string .= $str;
    }
    return $string;
}

/**
 * 数组复制
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
    for ($i = 0; $i <= $len - 1; $i++) {
        $newArray[] = $array[$start + $i];
    }
    return $newArray;
}

/**
 * 计算mysql包的大小
 *
 * @param $size
 *
 * @return array
 */
function getMysqlPackSize($size)
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
 * 无符号16位右移
 *
 * @param int $x 要进行操作的数字
 * @param int $bits 右移位数
 *
 */
function shr16($x, $bits)
{
    return ((2147483647 >> ($bits - 1)) & ($x >> $bits)) > 255?255:((2147483647 >> ($bits - 1)) & ($x >> $bits));
}

/**
 * 初始化配置文件
 *
 * @param $dir
 */
function initConfig($dir)
{
    $config = [];
    if(is_dir($dir)){
        //打开
        if($dh = @opendir($dir)){
            //读取
            while(($file = readdir($dh)) !== false){
                if($file != '.' && $file != '..' && !is_dir($file) &&
                    substr($file,-5,5) == '.json'){
                    $file_config = json_decode(file_get_contents($dir.$file),true);
                    if (is_array($file_config)){
                        $config = array_merge($config,$file_config);
                    }
                }
            }
            //关闭
            closedir($dh);
        }
    }
    $config = json_decode(str_replace('ROOT',ROOT,json_encode($config)),true);
    return $config;
}


/**
 * 对数据进行编码转换
 * @param array/string $data       数组
 * @param string $output    转换后的编码
 */
function array_iconv($data,  $output = 'utf-8') {
    $encode_arr = array('UTF-8','ASCII','GBK','GB2312','BIG5','JIS','eucjp-win','sjis-win','EUC-JP');
    $encoded = mb_detect_encoding($data, $encode_arr);

    if (!is_array($data)) {
        return mb_convert_encoding($data, $output, $encoded);
    }
    else {
        foreach ($data as $key=>$val) {
            $key = array_iconv($key, $output);
            if(is_array($val)) {
                $data[$key] = array_iconv($val, $output);
            } else {
                $data[$key] = mb_convert_encoding($data, $output, $encoded);
            }
        }
        return $data;
    }
}

