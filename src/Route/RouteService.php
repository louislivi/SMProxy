<?php

namespace SMProxy\Route;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/12/10
 * Time: 下午4:11
 */
class RouteService
{
    const HINT_SPLIT = '=';
    const SMPROXY_HINT_TYPE = "_smproxyHintType";

    public static function route(string $stmt)
    {
        $hintLength = self::isHintSql($stmt);
        if ($hintLength != -1) {
            $endPos = strpos($stmt, "*/");
            if ($endPos > 0) {
                $hint = trim(substr($stmt, $hintLength, $endPos - $hintLength));
                $firstSplitPos = strpos($hint, self::HINT_SPLIT);
                if ($firstSplitPos > 0) {
                    $hintArr = self::parseHint($hint);
                    return $hintArr;
                }
            }
        }
        return [];
    }

    public static function isHintSql(string $sql)
    {
        $j = 0;
        $len = strlen($sql);
        if ($sql[$j++] == '/' && $sql[$j++] == '*') {
            $c = $sql[$j];
            // 过滤掉 空格 和 * 两种字符, 支持： "/** !smproxy: */" 和 "/** #smproxy: */" 形式的注解
            while ($j < $len && $c != '!' && $c != '#' && ($c == ' ' || $c == '*')) {
                $c = $sql[++$j];
            }
            if ($sql[$j] == 's') {
                $j--;
            }
            if ($j + 6 >= $len) {
                return -1;
            }
            if ($sql[++$j] == 's' && $sql[++$j] == 'm' && $sql[++$j] == 'p'
                && $sql[++$j] == 'r' && $sql[++$j] == 'o' && $sql[++$j] == 'x' && $sql[++$j] == 'y' && ($sql[++$j] == ':' || $sql[++$j] == '#')) {
                return $j + 1;    // true，同时返回注解部分的长度
            }
        }
        return -1;    // false
    }

    private static function parseHint(string $sql)
    {
        $arr = [];
        $y = 0;
        $begin = 0;
        for ($i = 0; $i < strlen($sql); $i++) {
            $cur = $sql[$i];
            if ($cur == ',' && $y % 2 == 0) {
                $substring = substr($sql, $begin, $i);

                self::parseKeyValue($arr, $substring);
                $begin = $i + 1;
            } elseif ($cur == '\'') {
                $y++;
            }
            if ($i == strlen($sql) - 1) {
                self::parseKeyValue($arr, substr($sql, $begin));
            }
        }
        return $arr;
    }

    private static function parseKeyValue(array &$arr, string $substring)
    {
        $indexOf = strpos($substring, '=');
        if ($indexOf != -1) {
            $key = strtolower(trim(substr($substring, 0, $indexOf)));
            $value = substr($substring, $indexOf + 1, strlen($substring));
            if (\SMProxy\Helper\endsWith($value, "'") && \SMProxy\Helper\startsWith($value, "'")) {
                $value = substr($value, 1, strlen($value) - 1);
            }
            if ($value == '') {
                $arr[self::SMPROXY_HINT_TYPE] = $key;
            }
            $arr[$key] = trim($value);
        }
    }
}
