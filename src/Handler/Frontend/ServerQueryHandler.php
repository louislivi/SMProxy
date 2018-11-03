<?php

namespace SMProxy\Handler\Frontend;

use SMProxy\Parser\ServerParse;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午10:48
 */
class ServerQueryHandler implements FrontendQueryHandler
{


    public function query(String $sql)
    {
        $rs = ServerParse::parse($sql);
        switch ($rs & 0xff) {
            case ServerParse::EXPLAIN:
                // ExplainHandler.handle(sql, c, rs >>> 8);
                break;
            case ServerParse::SET:
//                SetHandler.handle(sql, source, rs >>> 8);
                break;
            case ServerParse::SHOW:
                // todo data source
//                ShowHandler.handle(sql, source, rs >>> 8);
                break;
            case ServerParse::SELECT:
//                SelectHandler.handle(sql, source, rs >>> 8);
                break;
            case ServerParse::START:
//                StartHandler.handle(sql, source, rs >>> 8);
                break;
            case ServerParse::BEGIN:
//                BeginHandler.handle(sql, source );
                break;
            case ServerParse::SAVEPOINT:
//                SavepointHandler.handle(sql, source);
                break;
            case ServerParse::KILL:
//                KillHandler.handle(sql, rs >>> 8, source);
                break;
            case ServerParse::KILL_QUERY:
//                source.writeErrMessage(ErrorCode.ER_UNKNOWN_COM_ERROR, "Unsupported command");
                break;
            case ServerParse::USE:
//                UseHandler.handle(sql, source, rs >>> 8);
                break;
            case ServerParse::COMMIT:
//                source.commit();
                break;
            case ServerParse::ROLLBACK:
//                source.rollback();
                break;
            default:
//                source.execute(sql, rs);
        }
        return $rs & 0xff;
    }
}