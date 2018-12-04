<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/9
 * Time: 上午9:41.
 */

namespace SMProxy\MysqlPacket\Util;

use function SMProxy\Helper\shr16;

/**
 * 随机数类.
 *
 * Class RandomUtil
 */
class RandomUtil
{
    private static $bytes = [
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '0',
        'q',
        'w',
        'e',
        'r',
        't',
        'y', 'u', 'i', 'o', 'p', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm',
        'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X',
        'C', 'V', 'B', 'N', 'M',
    ];
    private static $multiplier = 0x5DEECE66D;
    private static $addend = 0xB;
    private static $mask = (1 << 48) - 1;
    private static $integerMask = (1 << 33) - 1;
    private static $seedUniquifier = 8682522807148012;

    private static $seed;

    public function __construct()
    {
        $s = self::$seedUniquifier + system('date +%s%N');
        $s = ($s ^ self::$multiplier) & self::$mask;
        $this->seed = $s;
    }

    public static function randomBytes(int $size)
    {
        $bb = self::$bytes;
        $ab = new \SplFixedArray($size);
        for ($i = 0; $i < $size; ++$i) {
            $ab[$i] = array_rand($bb);
        }

        return $ab->toArray();
    }

    private static function randomByte(array $b)
    {
        $ran = (int) (shr16((self::next() & self::$integerMask) & 0xff << 16, 16));

        return $b[$ran % count($b)];
    }

    private static function next()
    {
        $oldSeed = self::$seed;
        $nextSeed = 0;
        do {
            $nextSeed = ($oldSeed * self::$multiplier + self::$addend) & self::$mask;
        } while ($oldSeed == $nextSeed);
        self::$seed = $nextSeed;

        return $nextSeed;
    }
}
