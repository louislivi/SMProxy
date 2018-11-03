<?php
namespace SMProxy\MysqlPacket\Util;
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 下午4:13
 */
class CharsetUtil
{
    private static $INDEX_TO_CHARSET = [];
    private static $CHARSET_TO_INDEX = [];

    static public function init()
    {
        // index --> charset
        self::$INDEX_TO_CHARSET[1] = "big5";
        self::$INDEX_TO_CHARSET[2] = "czech";
        self::$INDEX_TO_CHARSET[3] = "dec8";
        self::$INDEX_TO_CHARSET[4] = "dos";
        self::$INDEX_TO_CHARSET[5] = "german1";
        self::$INDEX_TO_CHARSET[6] = "hp8";
        self::$INDEX_TO_CHARSET[7] = "koi8_ru";
        self::$INDEX_TO_CHARSET[8] = "latin1";
        self::$INDEX_TO_CHARSET[9] = "latin2";
        self::$INDEX_TO_CHARSET[10] = "swe7";
        self::$INDEX_TO_CHARSET[11] = "usa7";
        self::$INDEX_TO_CHARSET[12] = "ujis";
        self::$INDEX_TO_CHARSET[13] = "sjis";
        self::$INDEX_TO_CHARSET[14] = "cp1251";
        self::$INDEX_TO_CHARSET[15] = "danish";
        self::$INDEX_TO_CHARSET[16] = "hebrew";
        self::$INDEX_TO_CHARSET[18] = "tis620";
        self::$INDEX_TO_CHARSET[19] = "euc_kr";
        self::$INDEX_TO_CHARSET[20] = "estonia";
        self::$INDEX_TO_CHARSET[21] = "hungarian";
        self::$INDEX_TO_CHARSET[22] = "koi8_ukr";
        self::$INDEX_TO_CHARSET[23] = "win1251ukr";
        self::$INDEX_TO_CHARSET[24] = "gb2312";
        self::$INDEX_TO_CHARSET[25] = "greek";
        self::$INDEX_TO_CHARSET[26] = "win1250";
        self::$INDEX_TO_CHARSET[27] = "croat";
        self::$INDEX_TO_CHARSET[28] = "gbk";
        self::$INDEX_TO_CHARSET[29] = "cp1257";
        self::$INDEX_TO_CHARSET[30] = "latin5";
        self::$INDEX_TO_CHARSET[31] = "latin1_de";
        self::$INDEX_TO_CHARSET[32] = "armscii8";
        self::$INDEX_TO_CHARSET[33] = "utf8";
        self::$INDEX_TO_CHARSET[34] = "win1250ch";
        self::$INDEX_TO_CHARSET[35] = "ucs2";
        self::$INDEX_TO_CHARSET[36] = "cp866";
        self::$INDEX_TO_CHARSET[37] = "keybcs2";
        self::$INDEX_TO_CHARSET[38] = "macce";
        self::$INDEX_TO_CHARSET[39] = "macroman";
        self::$INDEX_TO_CHARSET[40] = "pclatin2";
        self::$INDEX_TO_CHARSET[41] = "latvian";
        self::$INDEX_TO_CHARSET[42] = "latvian1";
        self::$INDEX_TO_CHARSET[43] = "maccebin";
        self::$INDEX_TO_CHARSET[44] = "macceciai";
        self::$INDEX_TO_CHARSET[45] = "maccecias";
        self::$INDEX_TO_CHARSET[46] = "maccecsas";
        self::$INDEX_TO_CHARSET[47] = "latin1bin";
        self::$INDEX_TO_CHARSET[48] = "latin1cias";
        self::$INDEX_TO_CHARSET[49] = "latin1csas";
        self::$INDEX_TO_CHARSET[50] = "cp1251bin";
        self::$INDEX_TO_CHARSET[51] = "cp1251cias";
        self::$INDEX_TO_CHARSET[52] = "cp1251csas";
        self::$INDEX_TO_CHARSET[53] = "macromanbin";
        self::$INDEX_TO_CHARSET[54] = "macromancias";
        self::$INDEX_TO_CHARSET[55] = "macromanciai";
        self::$INDEX_TO_CHARSET[56] = "macromancsas";
        self::$INDEX_TO_CHARSET[57] = "cp1256";
        self::$INDEX_TO_CHARSET[63] = "binary";
        self::$INDEX_TO_CHARSET[64] = "armscii";
        self::$INDEX_TO_CHARSET[65] = "ascii";
        self::$INDEX_TO_CHARSET[66] = "cp1250";
        self::$INDEX_TO_CHARSET[67] = "cp1256";
        self::$INDEX_TO_CHARSET[68] = "cp866";
        self::$INDEX_TO_CHARSET[69] = "dec8";
        self::$INDEX_TO_CHARSET[70] = "greek";
        self::$INDEX_TO_CHARSET[71] = "hebrew";
        self::$INDEX_TO_CHARSET[72] = "hp8";
        self::$INDEX_TO_CHARSET[73] = "keybcs2";
        self::$INDEX_TO_CHARSET[74] = "koi8r";
        self::$INDEX_TO_CHARSET[75] = "koi8ukr";
        self::$INDEX_TO_CHARSET[77] = "latin2";
        self::$INDEX_TO_CHARSET[78] = "latin5";
        self::$INDEX_TO_CHARSET[79] = "latin7";
        self::$INDEX_TO_CHARSET[80] = "cp850";
        self::$INDEX_TO_CHARSET[81] = "cp852";
        self::$INDEX_TO_CHARSET[82] = "swe7";
        self::$INDEX_TO_CHARSET[83] = "utf8";
        self::$INDEX_TO_CHARSET[84] = "big5";
        self::$INDEX_TO_CHARSET[85] = "euckr";
        self::$INDEX_TO_CHARSET[86] = "gb2312";
        self::$INDEX_TO_CHARSET[87] = "gbk";
        self::$INDEX_TO_CHARSET[88] = "sjis";
        self::$INDEX_TO_CHARSET[89] = "tis620";
        self::$INDEX_TO_CHARSET[90] = "ucs2";
        self::$INDEX_TO_CHARSET[91] = "ujis";
        self::$INDEX_TO_CHARSET[92] = "geostd8";
        self::$INDEX_TO_CHARSET[93] = "geostd8";
        self::$INDEX_TO_CHARSET[94] = "latin1";
        self::$INDEX_TO_CHARSET[95] = "cp932";
        self::$INDEX_TO_CHARSET[96] = "cp932";
        self::$INDEX_TO_CHARSET[97] = "eucjpms";
        self::$INDEX_TO_CHARSET[98] = "eucjpms";

        // charset --> index
        for ($i = 0; $i < count(self::$INDEX_TO_CHARSET); $i++) {
            $charset = self::$INDEX_TO_CHARSET[$i]??null;
            if ($charset != null && (self::$CHARSET_TO_INDEX[$charset] ?? null) == null) {
                self::$CHARSET_TO_INDEX[$i] = $charset;
            }
        }
        self::$CHARSET_TO_INDEX['iso-8859-1'] = 14;
        self::$CHARSET_TO_INDEX['utf8mb4'] = 45;
        self::$CHARSET_TO_INDEX['utf-8'] = 33;
        self::$CHARSET_TO_INDEX['utf8'] = 33;
    }

    public static function getCharset(int $index)
    {
        self::init();
        return self::$INDEX_TO_CHARSET[$index];
    }

    public static function getIndex(string $charset)
    {
        self::init();
        if ($charset == null || strlen($charset) == 0) {
            return 0;
        } else {
            $i = self::$CHARSET_TO_INDEX[strtolower($charset)];
            return ($i == null) ? 0 : $i;
        }
    }

}