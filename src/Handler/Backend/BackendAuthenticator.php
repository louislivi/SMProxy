<?php

namespace SMProxy\Handler\Backend;

use SMProxy\MysqlPacket\Util\Capabilities;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 下午4:07.
 */
class BackendAuthenticator
{
    /**
     * 与MySQL连接时的一些特性指定.
     */
    public static function getClientFlags()
    {
        $flag = 0;

        $flag |= Capabilities::CLIENT_LONG_PASSWORD;
        $flag |= Capabilities::CLIENT_FOUND_ROWS;
        $flag |= Capabilities::CLIENT_LONG_FLAG;
        $flag |= Capabilities::CLIENT_CONNECT_WITH_DB;
//         flag |= Capabilities::CLIENT_NO_SCHEMA;
//         flag |= Capabilities::CLIENT_COMPRESS;
        $flag |= Capabilities::CLIENT_ODBC;
        // flag |= Capabilities::CLIENT_LOCAL_FILES;
        $flag |= Capabilities::CLIENT_IGNORE_SPACE;
        $flag |= Capabilities::CLIENT_PROTOCOL_41;
        $flag |= Capabilities::CLIENT_INTERACTIVE;
        // flag |= Capabilities::CLIENT_SSL;
        $flag |= Capabilities::CLIENT_IGNORE_SIGPIPE;
        $flag |= Capabilities::CLIENT_TRANSACTIONS;
        // flag |= Capabilities::CLIENT_RESERVED;
        $flag |= Capabilities::CLIENT_SECURE_CONNECTION;
        $flag |= Capabilities::CLIENT_PLUGIN_AUTH;
        // client extension
        // 不允许MULTI协议
        // flag |= Capabilities::CLIENT_MULTI_STATEMENTS;
        // flag |= Capabilities::CLIENT_MULTI_RESULTS;
        return $flag;
    }
}
