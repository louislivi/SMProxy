<?php
namespace SMProxy\Parser\Util;
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午9:48
 */
class CharTypes
{
    public static function isIdentifierChar($c)
    {
        $identifierFlags = new \SplFixedArray(256);
        for ($c = 0; $c < count($identifierFlags); ++$c) {
            if ($c >= 'A' && $c <= 'Z') {
                $identifierFlags[$c] = true;
            } else if ($c >= 'a' && $c <= 'z') {
                $identifierFlags[$c] = true;
            } else if ($c >= '0' && $c <= '9') {
                $identifierFlags[$c] = true;
            }
        }
        //  identifierFlags['`'] = true;
        $identifierFlags['_'] = true;
        $identifierFlags['$'] = true;
        return $c > count($identifierFlags) || $identifierFlags[$c];
    }
}