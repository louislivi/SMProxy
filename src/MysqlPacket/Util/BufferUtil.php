<?php

namespace SMProxy\MysqlPacket\Util;
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/25
 * Time: 下午7:22
 */

/**
 * @Author lizhuyang
 */
class BufferUtil
{
    public static function writeUB2(&$buffer, $i)
    {
        $buffer [] = $i & 0xff;
        $buffer [] = shr16($i & 0xff << 8,8);
    }

    public static function writeUB3(&$buffer, $i)
    {
        $buffer [] = $i & 0xff;
        $buffer [] = shr16($i & 0xff << 8,8);
        $buffer [] = shr16($i & 0xff << 16,16);
    }

    public static function writeInt(&$buffer, $i)
    {
        $buffer [] = $i & 0xff;
        $buffer [] = shr16($i & 0xff << 8,8);
        $buffer [] = shr16($i & 0xff << 16,16);
        $buffer [] = shr16($i & 0xff << 24,24);
    }

    public static function writeFloat(&$buffer, $f)
    {
        self::writeInt($buffer, (int)($f));
    }

    public static function writeUB4(&$buffer, $l)
    {
        $buffer [] = $l & 0xff;
        $buffer [] = shr16($l & 0xff << 8,8);
        $buffer [] = shr16($l & 0xff << 16,16);
        $buffer [] = shr16($l & 0xff << 24,24);
    }

    public static function writeLong(&$buffer, $l)
    {
        $buffer [] = $l & 0xff;
        $buffer [] = shr16($l & 0xff << 8,8);
        $buffer [] = shr16($l & 0xff << 16,16);
        $buffer [] = shr16($l & 0xff << 24,24);
        $buffer [] = shr16($l & 0xff << 32,32);
        $buffer [] = shr16($l & 0xff << 40,40);
        $buffer [] = shr16($l & 0xff << 48,48);
        $buffer [] = shr16($l & 0xff << 56,56);
    }

    public static function writeDouble(&$buffer, $d)
    {
        self::writeLong($buffer, (float)($d));
    }

    public static function writeLength(&$buffer, $l)
    {
        if ($l < 251) {
            $buffer [] = $l;
        } else if ($l < 0x10000) {
            $buffer [] = 252;
            self::writeUB2($buffer, (int)$l);
        } else if ($l < 0x1000000) {
            $buffer [] = 253;
            self::writeUB3($buffer, (int)$l);
        } else {
            $buffer [] = 254;

            self::writeLong($buffer, $l);
        }
    }

    public static function writeWithNull(&$buffer, $src)
    {
        $src = is_array($src) ?$src: [$src];
        $buffer = array_merge($buffer, $src);
        $buffer [] = 0;
    }

    public static function writeWithLength(&$buffer, $src, $nullValue = 0)
    {
        if ($src == null) {
            $buffer[] = $nullValue;
        } else {
            $length = count($src);
            if ($length < 251) {
                $buffer[] = $length;
            } else if ($length < 0x10000) {
                $buffer[] = 252;
                self::writeUB2($buffer, $length);
            } else if ($length < 0x1000000) {
                $buffer[] = 253;
                self::writeUB3($buffer, $length);
            } else {
                $buffer[] = 254;
                self::writeLong($buffer, $length);
            }
            $buffer = array_merge($buffer, $src);
        }

    }

    public static function getLength($length)
    {
        if (is_array($length)) {
            $length = count($length);
            if ($length < 251) {
                return 1 + $length;
            } else if ($length < 0x10000) {
                return 3 + $length;
            } else if ($length < 0x1000000) {
                return 4 + $length;
            } else {
                return 9 + $length;
            }
        } else {
            if ($length < 251) {
                return 1;
            } else if ($length < 0x10000) {
                return 3;
            } else if ($length < 0x1000000) {
                return 4;
            } else {
                return 9;
            }
        }
    }
}