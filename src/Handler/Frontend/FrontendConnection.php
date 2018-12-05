<?php

namespace SMProxy\Handler\Frontend;

use SMProxy\MysqlPacket\BinaryPacket;
use SMProxy\MysqlPacket\MySQLMessage;
use SMProxy\MysqlPool\MySQLException;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/3
 * Time: 上午10:18.
 */
class FrontendConnection
{
    protected $queryHandler;

    public function __construct()
    {
        $this->setQueryHandler(new ServerQueryHandler());
    }

    public function setQueryHandler(FrontendQueryHandler $queryHandler)
    {
        $this->queryHandler = $queryHandler;
    }

    /**
     * @param BinaryPacket $bin
     *
     * @return mixed
     * @throws MySQLException
     */
    public function query(BinaryPacket $bin)
    {
        // 取得语句
        $mm = new MySQLMessage($bin->data);
        $mm->position(5);
        $sql = $mm->readString();
        if (null == $sql || 0 == strlen($sql)) {
            throw new MySQLException('Empty SQL');
        }
        // 执行查询
        return $this->queryHandler->query($sql);
    }
}
