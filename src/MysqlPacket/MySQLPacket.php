<?php

namespace SMProxy\MysqlPacket;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/25
 * Time: 下午6:26.
 */

/**
 * MySqlPacket.
 *
 * @Author Louis Livi <574747417@qq.com>
 */
abstract class MySQLPacket
{
    /**
     * none, this is an internal thread state.
     */
    public static $COM_SLEEP = 0;

    /**
     * mysql_close.
     */
    public static $COM_QUIT = 1;

    /**
     * mysql_select_db.
     */
    public static $COM_INIT_DB = 2;

    /**
     * mysql_real_query.
     */
    public static $COM_QUERY = 3;

    /**
     * mysql_list_fields.
     */
    public static $COM_FIELD_LIST = 4;

    /**
     * mysql_create_db (deprecated).
     */
    public static $COM_CREATE_DB = 5;

    /**
     * mysql_drop_db (deprecated).
     */
    public static $COM_DROP_DB = 6;

    /**
     * mysql_refresh.
     */
    public static $COM_REFRESH = 7;

    /**
     * mysql_shutdown.
     */
    public static $COM_SHUTDOWN = 8;

    /**
     * mysql_stat.
     */
    public static $COM_STATISTICS = 9;

    /**
     * mysql_list_processes.
     */
    public static $COM_PROCESS_INFO = 10;

    /**
     * none, this is an internal thread state.
     */
    public static $COM_CONNECT = 11;

    /**
     * mysql_kill.
     */
    public static $COM_PROCESS_KILL = 12;

    /**
     * mysql_dump_debug_info.
     */
    public static $COM_DEBUG = 13;

    /**
     * mysql_ping.
     */
    public static $COM_PING = 14;

    /**
     * none, this is an internal thread state.
     */
    public static $COM_TIME = 15;

    /**
     * none, this is an internal thread state.
     */
    public static $COM_DELAYED_INSERT = 16;

    /**
     * mysql_change_user.
     */
    public static $COM_CHANGE_USER = 17;

    /**
     * used by slave server mysqlbinlog.
     */
    public static $COM_BINLOG_DUMP = 18;

    /**
     * used by slave server to get master table.
     */
    public static $COM_TABLE_DUMP = 19;

    /**
     * used by slave to log connection to master.
     */
    public static $COM_CONNECT_OUT = 20;

    /**
     * used by slave to register to master.
     */
    public static $COM_REGISTER_SLAVE = 21;

    /**
     * mysql_stmt_prepare.
     */
    public static $COM_STMT_PREPARE = 22;

    /**
     * mysql_stmt_execute.
     */
    public static $COM_STMT_EXECUTE = 23;

    /**
     * mysql_stmt_send_long_data.
     */
    public static $COM_STMT_SEND_LONG_DATA = 24;

    /**
     * mysql_stmt_close.
     */
    public static $COM_STMT_CLOSE = 25;

    /**
     * mysql_stmt_reset.
     */
    public static $COM_STMT_RESET = 26;

    /**
     * mysql_set_server_option.
     */
    public static $COM_SET_OPTION = 27;

    /**
     * mysql_stmt_fetch.
     */
    public static $COM_STMT_FETCH = 28;

    /**
     * cobar heartbeat.
     */
    public static $COM_HEARTBEAT = 64;

    /**
     * MORE RESULTS.
     */
    public static $SERVER_MORE_RESULTS_EXISTS = 8;

    public $packetLength;
    public $packetId = 1;

    /**
     * 把数据包写到buffer中，如果buffer满了就把buffer通过前端连接写出。
     *
     * @throws \Exception
     */
    public function write()
    {
        throw new \Exception();
    }

    /**
     * @param $buffer
     * @param $ctx
     *
     * @throws \Exception
     */
    public function writeBuf($buffer, $ctx)
    {
        throw new \Exception();
    }

    /**
     * 计算数据包大小，不包含包头长度。
     */
    abstract public function calcPacketSize();

    /**
     * 取得数据包信息.
     */
    abstract protected function getPacketInfo();

    protected function toString()
    {
        return $this->getPacketInfo() . '{length=' . $this->packetLength . ',id='
            . $this->packetId . '}';
    }
}
