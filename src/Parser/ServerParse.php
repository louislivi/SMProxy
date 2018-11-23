<?php

namespace SMProxy\Parser;

use SMProxy\Parser\Util\ParseUtil;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/2
 * Time: 下午4:08.
 */
final class ServerParse
{
    const OTHER = -1;
    const BEGIN = 1;
    const COMMIT = 2;
    const DELETE = 3;
    const INSERT = 4;
    const REPLACE = 5;
    const ROLLBACK = 6;
    const SELECT = 7;
    const SET = 8;
    const SHOW = 9;
    const START = 10;
    const UPDATE = 11;
    const KILL = 12;
    const SAVEPOINT = 13;
    const USE = 14;
    const EXPLAIN = 15;
    const KILL_QUERY = 16;
    const MODEL = 17;

    public static function parse(string $stmt)
    {
        for ($i = 0, $stmtLen = strlen($stmt); $i < $stmtLen; ++$i) {
            switch ($stmt[$i]) {
                case ' ':
                case '\t':
                case '\r':
                case '\n':
                    continue;
                case '/':
                case '#':
                    $i = ParseUtil::comment($stmt, $i);
                    continue;
                case 'B':
                case 'b':
                    return self::beginCheck($stmt, $i);
                case 'C':
                case 'c':
                    return self::commitCheck($stmt, $i);
                case 'D':
                case 'd':
                    return self::deleteCheck($stmt, $i);
                case 'E':
                case 'e':
                    return self::explainCheck($stmt, $i);
                case 'I':
                case 'i':
                    return self::insertCheck($stmt, $i);
                case 'R':
                case 'r':
                    return self::rCheck($stmt, $i);
                case 'S':
                case 's':
                    return self::sCheck($stmt, $i);
                case 'U':
                case 'u':
                    return self::uCheck($stmt, $i);
                case 'K':
                case 'k':
                    return self::killCheck($stmt, $i);
                default:
                    return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // EXPLAIN' '
    public static function explainCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + strlen('XPLAIN ')) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            $c7 = $stmt[++$offset];
            if (('X' == $c1 || 'x' == $c1) && ('P' == $c2 || 'p' == $c2) && ('L' == $c3 || 'l' == $c3)
                && ('A' == $c4 || 'a' == $c4) && ('I' == $c5 || 'i' == $c5) && ('N' == $c6 || 'n' == $c6)
                && (' ' == $c7 || '\t' == $c7 || '\r' == $c7 || '\n' == $c7)) {
                return ($$offset << 8) | self::EXPLAIN;
            }
        }

        return self::OTHER;
    }

    // KILL' '
    public static function killCheck(string $stmt, int $offset)
    {
        $stmtLen = strlen($stmt);
        if ($stmtLen > $offset + strlen('ILL ')) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (('I' == $c1 || 'i' == $c1) && ('L' == $c2 || 'l' == $c2) && ('L' == $c3 || 'l' == $c3)
                && (' ' == $c4 || '\t' == $c4 || '\r' == $c4 || '\n' == $c4)) {
                while ($stmtLen > ++$offset) {
                    switch ($stmt[$offset]) {
                        case ' ':
                        case '\t':
                        case '\r':
                        case '\n':
                            continue;
                        case 'Q':
                        case 'q':
                            return self::killQueryCheck($stmt, $offset);
                        default:
                            return ($offset << 8) | self::KILL;
                    }
                }

                return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // KILL QUERY' '
    public static function killQueryCheck(string $stmt, int $offset)
    {
        $stmtLen = strlen($stmt);
        if ($stmtLen > $offset + strlen('UERY ')) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (('U' == $c1 || 'u' == $c1) && ('E' == $c2 || 'e' == $c2) && ('R' == $c3 || 'r' == $c3)
                && ('Y' == $c4 || 'y' == $c4) && (' ' == $c5 || '\t' == $c5 || '\r' == $c5 || '\n' == $c5)) {
                while ($stmtLen > ++$offset) {
                    switch ($stmt[$offset]) {
                        case ' ':
                        case '\t':
                        case '\r':
                        case '\n':
                            continue;
                        default:
                            return ($offset << 8) | self::KILL_QUERY;
                    }
                }

                return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // BEGIN
    public static function beginCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (('E' == $c1 || 'e' == $c1) && ('G' == $c2 || 'g' == $c2) && ('I' == $c3 || 'i' == $c3)
                && ('N' == $c4 || 'n' == $c4) && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt . [$offset]))) {
                return self::BEGIN;
            }
        }

        return self::OTHER;
    }

    // COMMIT
    public static function commitCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 5) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (('O' == $c1 || 'o' == $c1) && ('M' == $c2 || 'm' == $c2) && ('M' == $c3 || 'm' == $c3)
                && ('I' == $c4 || 'i' == $c4) && ('T' == $c5 || 't' == $c5)
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
                return self::COMMIT;
            }
        }

        return self::OTHER;
    }

    // DELETE' '
    public static function deleteCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (('E' == $c1 || 'e' == $c1) && ('L' == $c2 || 'l' == $c2) && ('E' == $c3 || 'e' == $c3)
                && ('T' == $c4 || 't' == $c4) && ('E' == $c5 || 'e' == $c5)
                && (' ' == $c6 || '\t' == $c6 || '\r' == $c6 || '\n' == $c6)) {
                return self::DELETE;
            }
        }

        return self::OTHER;
    }

    // INSERT' '
    public static function insertCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (('N' == $c1 || 'n' == $c1) && ('S' == $c2 || 's' == $c2) && ('E' == $c3 || 'e' == $c3)
                && ('R' == $c4 || 'r' == $c4) && ('T' == $c5 || 't' == $c5)
                && (' ' == $c6 || '\t' == $c6 || '\r' == $c6 || '\n' == $c6)) {
                return self::INSERT;
            }
        }

        return self::OTHER;
    }

    public static function rCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > ++$offset) {
            switch ($stmt[$offset]) {
                case 'E':
                case 'e':
                    return self::replaceCheck($stmt, $offset);
                case 'O':
                case 'o':
                    return self::rollabckCheck($stmt, $offset);
                default:
                    return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // REPLACE' '
    public static function replaceCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (('P' == $c1 || 'p' == $c1) && ('L' == $c2 || 'l' == $c2) && ('A' == $c3 || 'a' == $c3)
                && ('C' == $c4 || 'c' == $c4) && ('E' == $c5 || 'e' == $c5)
                && (' ' == $c6 || '\t' == $c6 || '\r' == $c6 || '\n' == $c6)) {
                return self::REPLACE;
            }
        }

        return self::OTHER;
    }

    // ROLLBACK
    public static function rollabckCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (('L' == $c1 || 'l' == $c1) && ('L' == $c2 || 'l' == $c2) && ('B' == $c3 || 'b' == $c3)
                && ('A' == $c4 || 'a' == $c4) && ('C' == $c5 || 'c' == $c5) && ('K' == $c6 || 'k' == $c6)
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
                return self::ROLLBACK;
            }
        }

        return self::OTHER;
    }

    public static function sCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > ++$offset) {
            switch ($stmt[$offset]) {
                case 'A':
                case 'a':
                    return self::SAVEPOINTCheck($stmt, $offset);
                case 'E':
                case 'e':
                    return self::seCheck($stmt, $offset);
                case 'H':
                case 'h':
                    return self::showCheck($stmt, $offset);
                case 'T':
                case 't':
                    return self::startCheck($stmt, $offset);
                default:
                    return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // SAVEPOINT
    public static function savepointCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 8) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            $c7 = $stmt[++$offset];
            $c8 = $stmt[++$offset];
            if (('V' == $c1 || 'v' == $c1) && ('E' == $c2 || 'e' == $c2) && ('P' == $c3 || 'p' == $c3)
                && ('O' == $c4 || 'o' == $c4) && ('I' == $c5 || 'i' == $c5) && ('N' == $c6 || 'n' == $c6)
                && ('T' == $c7 || 't' == $c7) && (' ' == $c8 || '\t' == $c8 || '\r' == $c8 || '\n' == $c8)) {
                return self::SAVEPOINT;
            }
        }

        return self::OTHER;
    }

    public static function seCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > ++$offset) {
            switch ($stmt[$offset]) {
                case 'L':
                case 'l':
                    return self::selectCheck($stmt, $offset);
                case 'T':
                case 't':
                    if (strlen($stmt) > ++$offset) {
                        $c = $stmt[$offset];
                        if (' ' == $c || '\r' == $c || '\n' == $c || '\t' == $c || '/' == $c || '#' == $c) {
                            return ($offset << 8) | self::SET;
                        }
                    }

                    return self::OTHER;
                default:
                    return self::OTHER;
            }
        }

        return self::OTHER;
    }

    // SELECT' '
    public static function selectCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (('E' == $c1 || 'e' == $c1) && ('C' == $c2 || 'c' == $c2) && ('T' == $c3 || 't' == $c3)
                && (' ' == $c4 || '\t' == $c4 || '\r' == $c4 || '\n' == $c4 || '/' == $c4 || '#' == $c4)) {
                return ($offset << 8) | self::SELECT;
            }
        }

        return self::OTHER;
    }

    // SHOW' '
    public static function showCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            if (('O' == $c1 || 'o' == $c1) && ('W' == $c2 || 'w' == $c2)
                && (' ' == $c3 || '\t' == $c3 || '\r' == $c3 || '\n' == $c3)) {
                return ($offset << 8) | self::SHOW;
            }
        }

        return self::OTHER;
    }

    // START' '
    public static function startCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (('A' == $c1 || 'a' == $c1) && ('R' == $c2 || 'r' == $c2) && ('T' == $c3 || 't' == $c3)
                && (' ' == $c4 || '\t' == $c4 || '\r' == $c4 || '\n' == $c4)) {
                return ($offset << 8) | self::START;
            }
        }

        return self::OTHER;
    }

    // UPDATE' ' | USE' '
    public static function uCheck(string $stmt, int $offset, bool $has_Space = true)
    {
        if (strlen($stmt) > ++$offset) {
            switch ($stmt[$offset]) {
                case 'P':
                case 'p':
                    if (strlen($stmt) > $offset + 5) {
                        $c1 = $stmt[++$offset];
                        $c2 = $stmt[++$offset];
                        $c3 = $stmt[++$offset];
                        $c4 = $stmt[++$offset];
                        $c5 = $stmt[++$offset];
                        if (('D' == $c1 || 'd' == $c1) && ('A' == $c2 || 'a' == $c2) && ('T' == $c3 || 't' == $c3)
                            && ('E' == $c4 || 'e' == $c4) && ($has_Space ? (' ' == $c5 || '\t' == $c5 || '\r' == $c5 || '\n' == $c5) : true)) {
                            return self::UPDATE;
                        }
                    }
                    break;
                case 'S':
                case 's':
                    if (strlen($stmt) > $offset + 2) {
                        $c1 = $stmt[++$offset];
                        $c2 = $stmt[++$offset];
                        if (('E' == $c1 || 'e' == $c1) && (' ' == $c2 || '\t' == $c2 || '\r' == $c2 || '\n' == $c2)) {
                            return ($offset << 8) | self::USE;
                        }
                    }
                    break;
                default:
                    return self::OTHER;
            }
        }

        return self::OTHER;
    }
}
