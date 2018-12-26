<?php

namespace SMProxy\MysqlPacket;

use function SMProxy\Helper\array_copy;
use function SMProxy\Helper\getString;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/25
 * Time: 下午6:42.
 */

/**
 * For netty MySql.
 *
 * @author lizhuyang
 */
class MySQLMessage
{
    public static $NULL_LENGTH = -1;
    private static $EMPTY_BYTES = 0;

    private $data;
    public $length;
    private $position;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->length = count($data);
        $this->position = 0;
    }

    public function length()
    {
        return $this->length;
    }

    public function position(int $i = 0)
    {
        if ($i) {
            $this->position = $i;
        } else {
            return $this->position;
        }
    }

    public function bytes()
    {
        return $this->data;
    }

    public function move(int $i)
    {
        $this->position += $i;
    }

    public function hasRemaining()
    {
        return $this->length > $this->position;
    }

    public function read(int $i = 0)
    {
        if ($i) {
            return $this->data[$i];
        }

        return $this->data[$this->position++];
    }

    public function readUB2()
    {
        $b = $this->data;
        $i = $b[$this->position++];
        $i |= ($b[$this->position++]) << 8;

        return $i;
    }

    public function readUB3()
    {
        $b = $this->data;
        $i = $b[$this->position++];
        $i |= ($b[$this->position++]) << 8;
        $i |= ($b[$this->position++]) << 16;

        return $i;
    }

    public function readUB4()
    {
        $b = $this->data;
        $l = $b[$this->position++];
        $l |= $b[$this->position++] << 8;
        $l |= $b[$this->position++] << 16;
        $l |= $b[$this->position++] << 24;

        return $l;
    }

    public function readInt()
    {
        $b = $this->data;
        $i = $b[$this->position++];
        $i |= ($b[$this->position++]) << 8;
        $i |= ($b[$this->position++]) << 16;
        $i |= ($b[$this->position++]) << 24;

        return $i;
    }

    public function readFloat()
    {
        return (float) ($this->readInt());
    }

    public function readLong()
    {
        $b = $this->data;
        $l = $b[$this->position++];
        $l |= $b[$this->position++] << 8;
        $l |= $b[$this->position++] << 16;
        $l |= $b[$this->position++] << 24;
        $l |= $b[$this->position++] << 32;
        $l |= $b[$this->position++] << 40;
        $l |= $b[$this->position++] << 48;
        $l |= $b[$this->position++] << 56;

        return $l;
    }

    public function readDouble()
    {
        return $this->readLong();
    }

    public function readLength()
    {
        $length = ($this->data[$this->position++] ?? 0) & 0xff;
        switch ($length) {
            case 251:
                return self::$NULL_LENGTH;
            case 252:
                return $this->readUB2();
            case 253:
                return $this->readUB3();
            case 254:
                return $this->readLong();
            default:
                return $length;
        }
    }

    public function readBytes(int $length = 0)
    {
        if ($length) {
            return array_copy($this->data, $this->position, $length);
        } else {
            if ($this->position >= $this->length) {
                return self::$EMPTY_BYTES;
            }

            return array_copy($this->data, $this->position, $this->length - $this->position);
        }
    }

    public function readBytesWithNull()
    {
        $b = $this->data;
        if ($this->position >= $this->length) {
            return self::$EMPTY_BYTES;
        }
        $offset = -1;
        for ($i = $this->position; $i < $this->length; ++$i) {
            if (0 == $b[$i]) {
                $offset = $i;
                break;
            }
        }
        switch ($offset) {
            case -1:
                $ab1 = array_copy($b, $this->position, $this->length - $this->position);
                $this->position = $this->length;

                return $ab1;
            case 0:
                $this->position++;

                return self::$EMPTY_BYTES;
            default:
                $ab2 = array_copy($b, $this->position, $offset - $this->position);
                $this->position = $offset + 1;

                return $ab2;
        }
    }

    public function readBytesWithLength()
    {
        $length = (int) $this->readLength();
        if ($length <= 0) {
            return [self::$EMPTY_BYTES];
        }
        $ab = array_copy($this->data, $this->position, $length);
        $this->position += $length;

        return $ab;
    }

    public function readStringWithNull(string $charset = '')
    {
        $b = $this->data;
        if ($this->position >= $this->length) {
            return null;
        }
        $offset = -1;
        for ($i = $this->position; $i < $this->length; ++$i) {
            if (0 == $b[$i]) {
                $offset = $i;
                break;
            }
        }
        if ($charset) {
            switch ($offset) {
                case -1:
                    $s1 = getString(array_copy($b, $this->position, $this->length - $this->position));
                    $this->position = $this->length;

                    return $s1;
                case 0:
                    $this->position++;

                    return null;
                default:
                    $s2 = getString(array_copy($b, $this->position, $offset - $this->position));
                    $this->position = $offset + 1;

                    return $s2;
            }
        } else {
            if (-1 == $offset) {
                $s = getString(array_copy($b, $this->position, $this->length - $this->position));
                $this->position = $this->length;

                return $s;
            }
            if ($offset > $this->position) {
                $s = getString(array_copy($b, $this->position, $offset - $this->position));
                $this->position = $offset + 1;

                return $s;
            } else {
                ++$this->position;

                return null;
            }
        }
    }

    public function readString()
    {
        if ($this->position >= $this->length) {
            return null;
        }
        $s = getString(array_copy($this->data, $this->position, $this->length - $this->position));
        $this->position = $this->length;

        return $s;
    }
}
