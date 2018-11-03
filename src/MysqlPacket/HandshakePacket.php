<?php
namespace SMProxy\MysqlPacket;

/**
 * MySql握手包
 *
 * @Author lizhuyang
 */
class HandshakePacket extends MySQLPacket
{

    private static $FILLER_13 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    public $protocolVersion;
    public $serverVersion;
    public $threadId;
    public $seed;
    public $serverCapabilities;
    public $serverCharsetIndex;
    public $serverStatus;
    public $restOfScrambleBuff;

    public function read(BinaryPacket $bin)
    {
        $this->packetLength = $bin->packetLength;
        $this->packetId = $bin->packetId;
        $mm = new MySQLMessage($bin->data);
        $mm->length = $this->packetLength;
        $mm->move(4);
        $this->protocolVersion = $mm->read();
        $this->serverVersion = $mm->readBytesWithNull();
        $this->threadId = $mm->readUB4();
        $this->seed = $mm->readBytesWithNull();
        $this->serverCapabilities = $mm->readUB2();
        $this->serverCharsetIndex = $mm->read();
        $this->serverStatus = $mm->readUB2();
        $mm->move(13);
        $this->restOfScrambleBuff = $mm->readBytesWithNull();
        return $this;
    }

    /**
     *
     */
    public function write()
    {
        // default init 256,so it can avoid buff extract
        $buffer = '';
        BufferUtil::writeUB3($buffer, $this->calcPacketSize());
        $buffer .= $this->packetId;
        $buffer .= $this->protocolVersion;
        BufferUtil::writeWithNull($buffer, $this->serverVersion);
        BufferUtil::writeUB4($buffer, $this->threadId);
        BufferUtil::writeWithNull($buffer, $this->seed);
        BufferUtil::writeUB2($buffer, $this->serverCapabilities);
        $buffer .= $this->serverCharsetIndex;
        BufferUtil::writeUB2($buffer, $this->serverStatus);
        $buffer .= implode('',self::$FILLER_13);
        BufferUtil::writeWithNull($buffer, $this->restOfScrambleBuff);
        return $buffer;
    }

    public function calcPacketSize()
    {
        $size = 1;
        $size += count($this->serverVersion);// n
        $size += 5;// 1+4
        $size += count($this->seed);// 8
        $size += 19;// 1+2+1+2+13
        $size += count($this->restOfScrambleBuff);// 12
        $size += 1;// 1
        return $size;
    }


    protected function getPacketInfo()
    {
        return "MySQL Handshake Packet";
    }
}