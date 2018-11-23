<?php

namespace SMProxy\Handler\Frontend;

use SMProxy\Parser\ServerParse;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午10:48.
 */
class ServerQueryHandler implements FrontendQueryHandler
{
    public function query(string $sql)
    {
        $rs = ServerParse::parse($sql);

        return $rs & 0xff;
    }
}
