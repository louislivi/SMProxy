<?php

namespace SMProxy\Parser\Util;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午9:30.
 */
final class ParseUtil
{
    public static function isEOF($c)
    {
        return ' ' == $c || "\t" == $c || "\n" == $c || "\r" == $c || ';' == $c;
    }

    public static function getSQLId(string $stmt)
    {
        $offset = strpos($stmt, '=');
        if (-1 != $offset && strlen($stmt) > ++$offset) {
            $id = trim(substr($stmt, $offset));

            return $id;
        }

        return 0;
    }

    /**
     * <code>'abc'</code>.
     *
     * @param string $stmt
     * @param int offset stmt.charAt(offset) == first <code>'</code>
     *
     * @return string
     */
    private static function parsestring(string $stmt, int $offset)
    {
        $sb = '';
        $stmtLen = strlen($stmt);
        for (++$offset; $offset < $stmtLen; ++$offset) {
            $c = $stmt[$offset];
            if ('\\' == $c) {
                switch ($c = $stmt[++$offset]) {
                    case '0':
                        $sb .= "\0";
                        break;
                    case 'b':
                        $sb .= "\b";
                        break;
                    case 'n':
                        $sb .= "\n";
                        break;
                    case 'r':
                        $sb .= "\r";
                        break;
                    case 't':
                        $sb .= "\t";
                        break;
                    case 'Z':
                        $sb .= chr(26);
                        break;
                    default:
                        $sb .= $c;
                }
            } elseif ('\'' == $c) {
                if ($offset + 1 < strlen($stmt) && '\'' == $stmt[$offset + 1]) {
                    ++$offset;
                    $sb .= '\'';
                } else {
                    break;
                }
            } else {
                $sb .= $c;
            }
        }

        return $sb;
    }

    /**
     * <code>"abc"</code>.
     *
     * @param string $stmt
     * @param int offset stmt.charAt(offset) == first <code>"</code>
     *
     * @return string
     */
    private static function parsestring2(string $stmt, int $offset)
    {
        $sb = '';
        $stmtLen = strlen($stmt);
        for (++$offset; $offset < $stmtLen; ++$offset) {
            $c = $stmt = [$offset];
            if ('\\' == $c) {
                switch ($c = $stmt[++$offset]) {
                    case '0':
                        $sb .= "\0";
                        break;
                    case 'b':
                        $sb .= "\b";
                        break;
                    case 'n':
                        $sb .= "\n";
                        break;
                    case 'r':
                        $sb .= "\r";
                        break;
                    case 't':
                        $sb .= "\t";
                        break;
                    case 'Z':
                        $sb .= chr(26);
                        break;
                    default:
                        $sb .= $c;
                }
            } elseif ('"' == $c) {
                if ($offset + 1 < strlen($stmt) && '"' == $stmt[$offset + 1]) {
                    ++$offset;
                    $sb .= '"';
                } else {
                    break;
                }
            } else {
                $sb .= $c;
            }
        }

        return $sb;
    }

    /**
     * <code>AS `abc`</code>.
     *
     * @param string $stmt
     * @param int    $offset
     *
     * @return string
     */
    private static function parseIdentifierEscape(string $stmt, int $offset)
    {
        $sb = '';
        for (++$offset,$stemLen = strlen($stmt); $offset < $stemLen; ++$offset) {
            $c = $stmt[$offset];
            if ('`' == $c) {
                if ($offset + 1 < strlen($stmt) && '`' == $stmt[$offset + 1]) {
                    ++$offset;
                    $sb .= '`';
                } else {
                    break;
                }
            } else {
                $sb .= $c;
            }
        }

        return $sb;
    }

    /**
     * @param string $stmt
     * @param int    $aliasIndex
     * @return bool|null|string
     */
    public static function parseAlias(string $stmt, int $aliasIndex)
    {
        if ($aliasIndex < 0 || $aliasIndex >= strlen($stmt)) {
            return null;
        }
        switch ($stmt[$aliasIndex]) {
            case '\'':
                return self::parsestring($stmt, $aliasIndex);
            case '"':
                return self::parsestring2($stmt, $aliasIndex);
            case '`':
                return self::parseIdentifierEscape($stmt, $aliasIndex);
            default:
                $offset = $aliasIndex;
                $stmtLen = strlen($stmt);
                while ($offset < $stmtLen && CharTypes::isIdentifierChar($stmt[$offset])) {
                    $offset++;
                }

                return substr($stmt, $aliasIndex, $offset);
        }
    }

    public static function comment(string $stmt, int $offset)
    {
        $len = strlen($stmt);
        $n = $offset;
        switch ($stmt[$n]) {
            case '/':
                if ($len > ++$n && '*' == $stmt[$n++] && $len > $n + 1 && '!' != $stmt[$n]) {
                    for ($i = $n; $i < $len; ++$i) {
                        if ('*' == $stmt[$i]) {
                            $m = $i + 1;
                            if ($len > $m && '/' == $stmt[$m]) {
                                return $m;
                            }
                        }
                    }
                }
                break;
            case '#':
                for ($i = $n + 1; $i < $len; ++$i) {
                    if ("\n" == $stmt[$i]) {
                        return $i;
                    }
                }
                break;
        }

        return $offset;
    }

    public static function currentCharIsSep(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset) {
            switch ($stmt[$offset]) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    return true;
                default:
                    return false;
            }
        }

        return true;
    }

    /*****
     * 检查下一个字符是否为分隔符，并把偏移量加1
     * @param string $stmt
     * @param int    $offset
     * @return bool
     */
    public static function nextCharIsSep(string $stmt, int $offset)
    {
        return self::currentCharIsSep($stmt, ++$offset);
    }

    /*****
     * 检查下一个字符串是否为期望的字符串，并把偏移量移到从offset开始计算，expectValue之后的位置
     *
     * @param string $stmt 被解析的sql
     * @param int $offset 被解析的sql的当前位置
     * @param string $nextExpectedstring 在stmt中准备查找的字符串
     * @param bool $checkSepChar 当找到expectValue值时，是否检查其后面字符为分隔符号
     *
     * @return int 如果包含指定的字符串，则移动相应的偏移量，否则返回值=offset
     */
    public static function nextstringIsExpectedWithIgnoreSepChar(
        string $stmt,
        int $offset,
        string $nextExpectedstring,
        bool $checkSepChar
    ) {
        if (null == $nextExpectedstring || strlen($nextExpectedstring) < 1) {
            return $offset;
        }
        $i = $offset;
        $index = 0;
        for ($stmtLen = strlen($stmt); $i < $stmtLen && $index < strlen($nextExpectedstring); ++$i) {
            if (0 == $index) {
                $isSep = self::currentCharIsSep($stmt, $i);
                if ($isSep) {
                    continue;
                }
            }
            $actualChar = $stmt[$i];
            $expectedChar = $nextExpectedstring[$index++];
            if ($actualChar != $expectedChar) {
                return $offset;
            }
        }
        if ($index == strlen($nextExpectedstring)) {
            $ok = true;
            if ($checkSepChar) {
                $ok = self::nextCharIsSep($stmt, $i);
            }
            if ($ok) {
                return $i;
            }
        }

        return $offset;
    }

    const JSON = 'json';
    const EQ = '=';

    //private static final string WHERE = "where";
    //private static final string SET = "set";

    /**********
     * 检查下一个字符串是否json= *
     *
     * @param string $stmt 被解析的sql
     * @param int    $offset
     * @return int 如果包含指定的字符串，则移动相应的偏移量，否则返回值=offset
     */
    public static function nextstringIsJsonEq(string $stmt, int $offset)
    {
        $i = $offset;

        // / drds 之后的符号
        if (!self::currentCharIsSep($stmt, ++$i)) {
            return $offset;
        }

        // json 串
        $k = self::nextstringIsExpectedWithIgnoreSepChar($stmt, $i, self::JSON, false);
        if ($k <= $i) {
            return $offset;
        }
        $i = $k;

        // 等于符号
        $k = self::nextstringIsExpectedWithIgnoreSepChar($stmt, $i, self::EQ, false);
        if ($k <= $i) {
            return $offset;
        }

        return $i;
    }

    public static function move(string $stmt, int $offset, int $length)
    {
        $stmtLen = strlen($stmt);
        for ($i = $offset; $i < $stmtLen; ++$i) {
            switch ($stmt[$i]) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    continue 2;
                case '/':
                case '#':
                    $i = self::comment($stmt, $i);
                    continue 2;
                default:
                    return $i + $length;
            }
        }

        return $i;
    }

    public static function compare(string $s, int $offset, $keyword)
    {
        if (strlen($s) >= $offset + count($keyword)) {
            for ($i = 0; $i < count($keyword); ++$i, ++$offset) {
                if (strtoupper($s[$offset]) != $keyword[$i]) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
