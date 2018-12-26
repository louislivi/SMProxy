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
        return sprintf('%s (%s:%s)', trim($this->getMessage()), $this->getFile(), $this->getLine());
    }
}
