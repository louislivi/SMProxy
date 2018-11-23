<?php

namespace SMProxy\Handler\Frontend;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午10:42.
 */
interface FrontendQueryHandler
{
    public function query(string $sql);
}
