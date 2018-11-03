<?php

namespace SMProxy\Parser;

use SMProxy\Parser\Util\ParseUtil;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/2
 * Time: 下午4:08
 */
final class ServerParse
{
    public const OTHER = -1;
    public const BEGIN = 1;
    public const COMMIT = 2;
    public const DELETE = 3;
    public const INSERT = 4;
    public const REPLACE = 5;
    public const ROLLBACK = 6;
    public const SELECT = 7;
    public const SET = 8;
    public const SHOW = 9;
    public const START = 10;
    public const UPDATE = 11;
    public const KILL = 12;
    public const SAVEPOINT = 13;
    public const USE = 14;
    public const EXPLAIN = 15;
    public const KILL_QUERY = 16;

    public static function parse(String $stmt)
    {
        for ($i = 0; $i < strlen($stmt); ++$i) {
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
    static function explainCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + strlen("XPLAIN ")) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            $c7 = $stmt[++$offset];
            if (($c1 == 'X' || $c1 == 'x') && ($c2 == 'P' || $c2 == 'p') && ($c3 == 'L' || $c3 == 'l')
                && ($c4 == 'A' || $c4 == 'a') && ($c5 == 'I' || $c5 == 'i') && ($c6 == 'N' || $c6 == 'n')
                && ($c7 == ' ' || $c7 == '\t' || $c7 == '\r' || $c7 == '\n')) {
                return ($$offset << 8) | self::EXPLAIN;
            }
        }
        return self::OTHER;
    }

    // KILL' '
    static function killCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + strlen("ILL ")) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'I' || $c1 == 'i') && ($c2 == 'L' || $c2 == 'l') && ($c3 == 'L' || $c3 == 'l')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
                while (strlen($stmt) > ++$offset) {
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
                            return ($$offset << 8) | self::KILL;
                    }
                }
                return self::OTHER;
            }
        }
        return self::OTHER;
    }

    // KILL QUERY' '
    static function killQueryCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + strlen("UERY ")) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (($c1 == 'U' || $c1 == 'u') && ($c2 == 'E' || $c2 == 'e') && ($c3 == 'R' || $c3 == 'r')
                && ($c4 == 'Y' || $c4 == 'y') && ($c5 == ' ' || $c5 == '\t' || $c5 == '\r' || $c5 == '\n')) {
                while (strlen($stmt) > ++$offset) {
                    switch ($stmt[$offset]) {
                        case ' ':
                        case '\t':
                        case '\r':
                        case '\n':
                            continue;
                        default:
                            return ($$offset << 8) | self::KILL_QUERY;
                    }
                }
                return self::OTHER;
            }
        }
        return self::OTHER;
    }

    // BEGIN
    static function beginCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'G' || $c2 == 'g') && ($c3 == 'I' || $c3 == 'i')
                && ($c4 == 'N' || $c4 == 'n') && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt . [$offset]))) {
                return self::BEGIN;
            }
        }
        return self::OTHER;
    }

    // COMMIT
    static function commitCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 5) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (($c1 == 'O' || $c1 == 'o') && ($c2 == 'M' || $c2 == 'm') && ($c3 == 'M' || $c3 == 'm')
                && ($c4 == 'I' || $c4 == 'i') && ($c5 == 'T' || $c5 == 't')
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
                return self::COMMIT;
            }
        }
        return self::OTHER;
    }

    // DELETE' '
    static function deleteCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'L' || $c2 == 'l') && ($c3 == 'E' || $c3 == 'e')
                && ($c4 == 'T' || $c4 == 't') && ($c5 == 'E' || $c5 == 'e')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
                return self::DELETE;
            }
        }
        return self::OTHER;
    }

    // INSERT' '
    static function insertCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'N' || $c1 == 'n') && ($c2 == 'S' || $c2 == 's') && ($c3 == 'E' || $c3 == 'e')
                && ($c4 == 'R' || $c4 == 'r') && ($c5 == 'T' || $c5 == 't')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
                return self::INSERT;
            }
        }
        return self::OTHER;
    }

    static function rCheck(String $stmt, int $offset)
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
    static function replaceCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'P' || $c1 == 'p') && ($c2 == 'L' || $c2 == 'l') && ($c3 == 'A' || $c3 == 'a')
                && ($c4 == 'C' || $c4 == 'c') && ($c5 == 'E' || $c5 == 'e')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
                return self::REPLACE;
            }
        }
        return self::OTHER;
    }

    // ROLLBACK
    static function rollabckCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'L' || $c1 == 'l') && ($c2 == 'L' || $c2 == 'l') && ($c3 == 'B' || $c3 == 'b')
                && ($c4 == 'A' || $c4 == 'a') && ($c5 == 'C' || $c5 == 'c') && ($c6 == 'K' || $c6 == 'k')
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
                return self::ROLLBACK;
            }
        }
        return self::OTHER;
    }

    static function sCheck(String $stmt, int $offset)
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
    static function savepointCheck(String $stmt, int $offset)
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
            if (($c1 == 'V' || $c1 == 'v') && ($c2 == 'E' || $c2 == 'e') && ($c3 == 'P' || $c3 == 'p')
                && ($c4 == 'O' || $c4 == 'o') && ($c5 == 'I' || $c5 == 'i') && ($c6 == 'N' || $c6 == 'n')
                && ($c7 == 'T' || $c7 == 't') && ($c8 == ' ' || $c8 == '\t' || $c8 == '\r' || $c8 == '\n')) {
                return self::SAVEPOINT;
            }
        }
        return self::OTHER;
    }

    static function seCheck(String $stmt, int $offset)
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
                        if ($c == ' ' || $c == '\r' || $c == '\n' || $c == '\t' || $c == '/' || $c == '#') {
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
    static function selectCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'C' || $c2 == 'c') && ($c3 == 'T' || $c3 == 't')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n' || $c4 == '/' || $c4 == '#')) {
                return ($offset << 8) | self::SELECT;
            }
        }
        return self::OTHER;
    }

    // SHOW' '
    static function showCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            if (($c1 == 'O' || $c1 == 'o') && ($c2 == 'W' || $c2 == 'w')
                && ($c3 == ' ' || $c3 == '\t' || $c3 == '\r' || $c3 == '\n')) {
                return ($offset << 8) | self::SHOW;
            }
        }
        return self::OTHER;
    }

    // START' '
    static function startCheck(String $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'A' || $c1 == 'a') && ($c2 == 'R' || $c2 == 'r') && ($c3 == 'T' || $c3 == 't')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
                return ($offset << 8) | self::START;
            }
        }
        return self::OTHER;
    }

    // UPDATE' ' | USE' '
    static function uCheck(String $stmt, int $offset)
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
                        if (($c1 == 'D' || $c1 == 'd') && ($c2 == 'A' || $c2 == 'a') && ($c3 == 'T' || $c3 == 't')
                            && ($c4 == 'E' || $c4 == 'e') && ($c5 == ' ' || $c5 == '\t' || $c5 == '\r' || $c5 == '\n')) {
                            return self::UPDATE;
                        }
                    }
                    break;
                case 'S':
                case 's':
                    if (strlen($stmt) > $offset + 2) {
                        $c1 = $stmt[++$offset];
                        $c2 = $stmt[++$offset];
                        if (($c1 == 'E' || $c1 == 'e') && ($c2 == ' ' || $c2 == '\t' || $c2 == '\r' || $c2 == '\n')) {
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