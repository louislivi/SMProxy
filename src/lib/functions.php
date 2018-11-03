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
