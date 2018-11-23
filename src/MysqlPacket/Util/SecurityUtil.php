<?php

namespace SMProxy\MysqlPacket\Util;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 下午2:18.
 */
class SecurityUtil
{
    public static function scramble411(string $pass, array $seed)
    {
        $pass1 = getBytes(sha1($pass, true));
        $pass2 = getBytes(sha1(getString($pass1), true));
        $pass3 = getBytes(sha1(getString($seed) . getString($pass2), true));
        for ($i = 0; $i < count($pass3); ++$i) {
            $pass3[$i] = ($pass3[$i] ^ $pass1[$i]);
        }

        return $pass3;
    }
}
