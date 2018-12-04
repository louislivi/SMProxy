<?php

namespace SMProxy\MysqlPacket;

use function SMProxy\Helper\getBytes;
use function SMProxy\Helper\getMysqlPackSize;
use function SMProxy\Helper\getString;
use SMProxy\MysqlPacket\Util\BufferUtil;
use SMProxy\MysqlPacket\Util\ErrorCode;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/29
 * Time: 下午4:11.
 */
class ErrorPacket extends MySQLPacket
{
    public static $FIELD_COUNT = 255;
    public $marker = '#';
    public $sqlState = 'HY000';
    public $errno = ErrorCode::ER_NO_SUCH_USER;
    public $message;

    public function read(BinaryPacket $bin)
    {
        $this->packetLength = $bin->packetLength;
        $this->packetId = $bin->packetId;
        $mm = new MySQLMessage($bin->data);
        $mm->move(4);
        $this->fieldCount = $mm->read();
        $this->errno = $mm->readUB2();
        if ($mm->hasRemaining() && chr($mm->read($mm->position()) == $this->marker)) {
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
        BufferUtil::writeUB2($data, $this->errno);
        $data[] = ord($this->marker);
        $data = array_merge($data, getBytes($this->sqlState));
        if (null != $this->message) {
            $data = array_merge($data, getBytes($this->message));
        }

        return $data;
    }

    public function calcPacketSize()
    {
        $size = 9;
        if (null != $this->message) {
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
        return 'MySQL Error Packet';
    }
}
