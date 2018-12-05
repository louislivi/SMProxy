<?php

namespace SMProxy\MysqlPool;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/6
 * Time: 上午10:53.
 */
class MySQLException extends \Exception
{
    public function errorMessage()
    {
        return sprintf('ERROR: %s (%s:%s)', $this->getMessage(), $this->getFile(), $this->getLine());
    }
}
