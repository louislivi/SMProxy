<?php

namespace SMProxy\Parser\Util;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午9:48.
 */
class CharTypes
{
    public static function isIdentifierChar(int $c2)
    {
        $identifierFlags = new \SplFixedArray(256);
        for ($c = 0; $c < count($identifierFlags); ++$c) {
            if ($c >= 'A' && $c <= 'Z') {
                $identifierFlags[$c] = true;
            } elseif ($c >= 'a' && $c <= 'z') {
                $identifierFlags[$c] = true;
            } elseif ($c >= '0' && $c <= '9') {
                $identifierFlags[$c] = true;
            }
        }
        //  identifierFlags['`'] = true;
        $identifierFlags['_'] = true;
        $identifierFlags['$'] = true;

        return $c2 > count($identifierFlags) || $identifierFlags[$c2];
    }
}
