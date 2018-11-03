<?php

namespace SMProxy\MysqlPacket;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/29
 * Time: 下午4:11
 */
class ErrorPacket extends MySQLPacket
{
    static $FIELD_COUNT = 255;
    public $marker = '#';
    public $sqlstate = "HY000";
    public $errno = [21, 4];
    public $message;

    public function read(BinaryPacket $bin)
    {
        $this->packetLength = $bin->packetLength;
        $this->packetId = $bin->packetId;
        $mm = new MySQLMessage($bin->data);
        $mm->move(4);
        $this->fieldCount = $mm->read();
        $this->errno = $mm->readUB2();
        if ($mm->hasRemaining() && ($mm->read($mm->position()) == $this->sqlstate)) {
            $mm->read();
            $this->sqlState = getString($mm->readBytes(5));
        }
        $this->message = getString($mm->readBytes());
        return $this;
    }

    public function write()
    {
        $data = [];
        $size = $this->calcPacketSize();
        $data = array_merge($data, $size);
        $data[] = $this->packetId;
        $data[] = self::$FIELD_COUNT;
        $data = array_merge($data, $this->errno);
        $data[] = ord($this->marker);
        $data = array_merge($data, getBytes($this->sqlstate));
        if ($this->message != null) {
            $data = array_merge($data, getBytes($this->message));
        }
        return $data;
    }


    public function calcPacketSize()
    {
        $size = 9;
        if ($this->message != null) {
            $sizeData = getMysqlPackSize($size + strlen($this->message));
        } else {
            $sizeData[] = $size;
            $sizeData[] = 0;
            $sizeData[] = 0;
        }
        return $sizeData;
    }

    protected function getPacketInfo()
    {
        return "MySQL Error Packet";
    }

}