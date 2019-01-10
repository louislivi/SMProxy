<?php

namespace SMProxy\Parser;

use function SMProxy\Helper\startsWith;
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
    const EXPLAIN2 = 151;
    const KILL_QUERY = 16;
    const HELP = 17;
    const MYSQL_CMD_COMMENT = 18;
    const MYSQL_COMMENT = 19;
    const CALL = 20;
    const DESCRIBE = 21;
    const LOCK = 22;
    const UNLOCK = 23;
    const LOAD_DATA_INFILE_SQL = 99;
    const DDL = 100;


    const MIGRATE = 203;
    private static $pattern = "(load)+\s+(data)+\s+\w*\s*(infile)+";
    private static $callPattern = "\w*\;\s*\s*(call)+\s+\w*\s*";

    public static function parse(string $stmt)
    {
        $length = strlen($stmt);
        //FIX BUG FOR SQL SUCH AS /XXXX/SQL
        $rt = -1;
        for ($i = 0; $i < $length; ++$i) {
            switch ($stmt[$i]) {
                case ' ':
                case '\t':
                case '\r':
                case '\n':
                    continue 2;
                case '/':
                    // such as /*!40101 SET character_set_client = @saved_cs_client
                    // */;
                    if ($i == 0 && $stmt[1] == '*' && $stmt[2] == '!' && $stmt[$length - 2] == '*'
                        && $stmt[$length - 1] == '/') {
                        return self::MYSQL_CMD_COMMENT;
                    }
                // no break
                case '#':
                    $i = ParseUtil::comment($stmt, $i);
                    if ($i + 1 == $length) {
                        return self::MYSQL_COMMENT;
                    }
                    continue 2;
                case 'A':
                case 'a':
                    $rt = self::aCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'B':
                case 'b':
                    $rt = self::beginCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'C':
                case 'c':
                    $rt = self::commitOrCallCheckOrCreate($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'D':
                case 'd':
                    $rt = self::deleteOrdCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'E':
                case 'e':
                    $rt = self::explainCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'I':
                case 'i':
                    $rt = self::insertCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'M':
                case 'm':
                    $rt = self::migrateCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'R':
                case 'r':
                    $rt = self::rCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'S':
                case 's':
                    $rt = self::sCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'T':
                case 't':
                    $rt = self::tCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'U':
                case 'u':
                    $rt = self::uCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'K':
                case 'k':
                    $rt = self::killCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'H':
                case 'h':
                    $rt = self::helpCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                case 'L':
                case 'l':
                    $rt = self::lCheck($stmt, $i);
                    if ($rt != self::OTHER) {
                        return $rt;
                    }
                    continue 2;
                default:
                    continue 2;
            }
        }
        return self::OTHER;
    }


    public static function lCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            if (($c1 == 'O' || $c1 == 'o') && ($c2 == 'A' || $c2 == 'a')
                && ($c3 == 'D' || $c3 == 'd')) {
                return preg_match(self::$pattern, $stmt) ? self::LOAD_DATA_INFILE_SQL : self::OTHER;
            } elseif (($c1 == 'O' || $c1 == 'o') && ($c2 == 'C' || $c2 == 'c')
                && ($c3 == 'K' || $c3 == 'k')) {
                return self::LOCK;
            }
        }

        return self::OTHER;
    }

    private static function migrateCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 7) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];


            if (($c1 == 'i' || $c1 == 'I')
                && ($c2 == 'g' || $c2 == 'G')
                && ($c3 == 'r' || $c3 == 'R')
                && ($c4 == 'a' || $c4 == 'A')
                && ($c5 == 't' || $c5 == 'T')
                && ($c6 == 'e' || $c6 == 'E')) {
                return self::MIGRATE;
            }
        }
        return self::OTHER;
    }

    //truncate
    private static function tCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 7) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            $c7 = $stmt[++$offset];
            $c8 = $stmt[++$offset];

            if (($c1 == 'R' || $c1 == 'r')
                && ($c2 == 'U' || $c2 == 'u')
                && ($c3 == 'N' || $c3 == 'n')
                && ($c4 == 'C' || $c4 == 'c')
                && ($c5 == 'A' || $c5 == 'a')
                && ($c6 == 'T' || $c6 == 't')
                && ($c7 == 'E' || $c7 == 'e')
                && ($c8 == ' ' || $c8 == '\t' || $c8 == '\r' || $c8 == '\n')) {
                return self::DDL;
            }
        }
        return self::OTHER;
    }

    //alter table/view/...
    private static function aCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (($c1 == 'L' || $c1 == 'l')
                && ($c2 == 'T' || $c2 == 't')
                && ($c3 == 'E' || $c3 == 'e')
                && ($c4 == 'R' || $c4 == 'r')
                && ($c5 == ' ' || $c5 == '\t' || $c5 == '\r' || $c5 == '\n')) {
                return self::DDL;
            }
        }
        return self::OTHER;
    }

    //create table/view/...
    private static function createCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 5) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'R' || $c1 == 'r')
                && ($c2 == 'E' || $c2 == 'e')
                && ($c3 == 'A' || $c3 == 'a')
                && ($c4 == 'T' || $c4 == 't')
                && ($c5 == 'E' || $c5 == 'e')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
                return self::DDL;
            }
        }
        return self::OTHER;
    }

    //drop
    private static function dropCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'R' || $c1 == 'r')
                && ($c2 == 'O' || $c2 == 'o')
                && ($c3 == 'P' || $c3 == 'p')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
                return self::DDL;
            }
        }
        return self::OTHER;
    }

    // delete or drop
    public static function deleteOrdCheck(string $stmt, int $offset)
    {
        $sqlType = self::OTHER;
        switch ($stmt[$offset + 1]) {
            case 'E':
            case 'e':
                $sqlType = self::dCheck($stmt, $offset);
                break;
            case 'R':
            case 'r':
                $sqlType = self::dropCheck($stmt, $offset);
                break;
            default:
                $sqlType = self::OTHER;
        }
        return $sqlType;
    }

    // HELP' '
    public static function helpCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'P' || $c3 == 'p')) {
                return ($offset << 8) | self::HELP;
            }
        }
        return self::OTHER;
    }

    // EXPLAIN' '
    public static function explainCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            $c7 = $stmt[++$offset];
            if (($c1 == 'X' || $c1 == 'x') && ($c2 == 'P' || $c2 == 'p')
                && ($c3 == 'L' || $c3 == 'l') && ($c4 == 'A' || $c4 == 'a')
                && ($c5 == 'I' || $c5 == 'i') && ($c6 == 'N' || $c6 == 'n')
                && ($c7 == ' ' || $c7 == '\t' || $c7 == '\r' || $c7 == '\n')) {
                return ($offset << 8) | self::EXPLAIN;
            }
        }
        if ($stmt != null && startsWith(strtolower($stmt), "explain2")) {
            return ($offset << 8) | self::EXPLAIN2;
        }
        return self::OTHER;
    }

    // KILL' '
    public static function killCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'I' || $c1 == 'i') && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'L' || $c3 == 'l')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
                while (strlen($stmt) > ++$offset) {
                    switch ($stmt[$offset]) {
                        case ' ':
                        case '\t':
                        case '\r':
                        case '\n':
                            continue 2;
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
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            if (($c1 == 'U' || $c1 == 'u') && ($c2 == 'E' || $c2 == 'e')
                && ($c3 == 'R' || $c3 == 'r') && ($c4 == 'Y' || $c4 == 'y')
                && ($c5 == ' ' || $c5 == '\t' || $c5 == '\r' || $c5 == '\n')) {
                while (strlen($stmt) > ++$offset) {
                    switch ($stmt[$offset]) {
                        case ' ':
                        case '\t':
                        case '\r':
                        case '\n':
                            continue 2;
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
            if (($c1 == 'E' || $c1 == 'e')
                && ($c2 == 'G' || $c2 == 'g')
                && ($c3 == 'I' || $c3 == 'i')
                && ($c4 == 'N' || $c4 == 'n')
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
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
            if (($c1 == 'O' || $c1 == 'o')
                && ($c2 == 'M' || $c2 == 'm')
                && ($c3 == 'M' || $c3 == 'm')
                && ($c4 == 'I' || $c4 == 'i')
                && ($c5 == 'T' || $c5 == 't')
                && (strlen($stmt) == ++$offset || ParseUtil::isEOF($stmt[$offset]))) {
                return self::COMMIT;
            }
        }

        return self::OTHER;
    }

    // CALL
    public static function callCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 3) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            if (($c1 == 'A' || $c1 == 'a') && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'L' || $c3 == 'l')) {
                return self::CALL;
            }
        }

        return self::OTHER;
    }

    public static function commitOrCallCheckOrCreate(string $stmt, int $offset)
    {
        $sqlType = self::OTHER;
        switch ($stmt[$offset + 1]) {
            case 'O':
            case 'o':
                $sqlType = self::commitCheck($stmt, $offset);
                break;
            case 'A':
            case 'a':
                $sqlType = self::callCheck($stmt, $offset);
                break;
            case 'R':
            case 'r':
                $sqlType = self::createCheck($stmt, $offset);
                break;
            default:
                $sqlType = self::OTHER;
        }
        return $sqlType;
    }

    // DESCRIBE or desc or DELETE' '
    public static function dCheck(string $stmt, int $offset)
    {
        if (strlen($stmt) > $offset + 4) {
            $res = self::describeCheck($stmt, $offset);
            if ($res == self::DESCRIBE) {
                return $res;
            }
        }
        // continue check
        if (strlen($stmt) > $offset + 6) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            $c5 = $stmt[++$offset];
            $c6 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'E' || $c3 == 'e') && ($c4 == 'T' || $c4 == 't')
                && ($c5 == 'E' || $c5 == 'e')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
                return self::DELETE;
            }
        }
        return self::OTHER;
    }

    // DESCRIBE' ' 或 desc' '
    public static function describeCheck(string $stmt, int $offset)
    {
        //desc
        if (strlen($stmt) > $offset + 4) {
            $c1 = $stmt[++$offset];
            $c2 = $stmt[++$offset];
            $c3 = $stmt[++$offset];
            $c4 = $stmt[++$offset];
            if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'S' || $c2 == 's')
                && ($c3 == 'C' || $c3 == 'c')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
                return self::DESCRIBE;
            }
            //describe
            if (strlen($stmt) > $offset + 4) {
                $c5 = $stmt[++$offset];
                $c6 = $stmt[++$offset];
                $c7 = $stmt[++$offset];
                $c8 = $stmt[++$offset];
                if (($c1 == 'E' || $c1 == 'e') && ($c2 == 'S' || $c2 == 's')
                    && ($c3 == 'C' || $c3 == 'c') && ($c4 == 'R' || $c4 == 'r')
                    && ($c5 == 'I' || $c5 == 'i') && ($c6 == 'B' || $c6 == 'b')
                    && ($c7 == 'E' || $c7 == 'e')
                    && ($c8 == ' ' || $c8 == '\t' || $c8 == '\r' || $c8 == '\n')) {
                    return self::DESCRIBE;
                }
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
            if (($c1 == 'N' || $c1 == 'n') && ($c2 == 'S' || $c2 == 's')
                && ($c3 == 'E' || $c3 == 'e') && ($c4 == 'R' || $c4 == 'r')
                && ($c5 == 'T' || $c5 == 't')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
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
            if (($c1 == 'P' || $c1 == 'p') && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'A' || $c3 == 'a') && ($c4 == 'C' || $c4 == 'c')
                && ($c5 == 'E' || $c5 == 'e')
                && ($c6 == ' ' || $c6 == '\t' || $c6 == '\r' || $c6 == '\n')) {
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
            if (($c1 == 'L' || $c1 == 'l')
                && ($c2 == 'L' || $c2 == 'l')
                && ($c3 == 'B' || $c3 == 'b')
                && ($c4 == 'A' || $c4 == 'a')
                && ($c5 == 'C' || $c5 == 'c')
                && ($c6 == 'K' || $c6 == 'k')
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
                    return self::savepointCheck($stmt, $offset);
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
            if (($c1 == 'V' || $c1 == 'v') && ($c2 == 'E' || $c2 == 'e')
                && ($c3 == 'P' || $c3 == 'p') && ($c4 == 'O' || $c4 == 'o')
                && ($c5 == 'I' || $c5 == 'i') && ($c6 == 'N' || $c6 == 'n')
                && ($c7 == 'T' || $c7 == 't')
                && ($c8 == ' ' || $c8 == '\t' || $c8 == '\r' || $c8 == '\n')) {
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
                        //支持一下语句
                        //  /*!smproxy: sql=SELECT * FROM test where id=99 */set @pin=1;
//                    call p_test(@pin,@pout);
//                    select @pout;
                        if (startsWith($stmt, "/*!smproxy:") || startsWith($stmt, "/*#smproxy:") || startsWith($stmt, "/*smproxy:")) {
                            if (preg_match(self::$callPattern, $stmt)) {
                                return self::CALL;
                            }
                        }

                        $c = $stmt[$offset];
                        if ($c == ' ' || $c == '\r' || $c == '\n' || $c == '\t'
                            || $c == '/' || $c == '#') {
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
            if (($c1 == 'E' || $c1 == 'e')
                && ($c2 == 'C' || $c2 == 'c')
                && ($c3 == 'T' || $c3 == 't')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n'
                    || $c4 == '/' || $c4 == '#')) {
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
            if (($c1 == 'O' || $c1 == 'o') && ($c2 == 'W' || $c2 == 'w')
                && ($c3 == ' ' || $c3 == '\t' || $c3 == '\r' || $c3 == '\n')) {
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
            if (($c1 == 'A' || $c1 == 'a') && ($c2 == 'R' || $c2 == 'r')
                && ($c3 == 'T' || $c3 == 't')
                && ($c4 == ' ' || $c4 == '\t' || $c4 == '\r' || $c4 == '\n')) {
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
                        if (($c1 == 'D' || $c1 == 'd')
                            && ($c2 == 'A' || $c2 == 'a')
                            && ($c3 == 'T' || $c3 == 't')
                            && ($c4 == 'E' || $c4 == 'e')
                            && ($has_Space ? (' ' == $c5 || '\t' == $c5 || '\r' == $c5 || '\n' == $c5) : true)) {
                            return self::UPDATE;
                        }
                    }
                    break;
                case 'S':
                case 's':
                    if (strlen($stmt) > $offset + 2) {
                        $c1 = $stmt[++$offset];
                        $c2 = $stmt[++$offset];
                        if (($c1 == 'E' || $c1 == 'e')
                            && ($c2 == ' ' || $c2 == '\t' || $c2 == '\r' || $c2 == '\n')) {
                            return ($offset << 8) | self::USE;
                        }
                    }
                    break;
                case 'N':
                case 'n':
                    if (strlen($stmt) > $offset + 5) {
                        $c1 = $stmt[++$offset];
                        $c2 = $stmt[++$offset];
                        $c3 = $stmt[++$offset];
                        $c4 = $stmt[++$offset];
                        $c5 = $stmt[++$offset];
                        if (($c1 == 'L' || $c1 == 'l')
                            && ($c2 == 'O' || $c2 == 'o')
                            && ($c3 == 'C' || $c3 == 'c')
                            && ($c4 == 'K' || $c4 == 'k')
                            && ($c5 == ' ' || $c5 == '\t' || $c5 == '\r' || $c5 == '\n')) {
                            return self::UNLOCK;
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
