<?php

namespace SMProxy\MysqlPacket;

use function SMProxy\Helper\getBytes;
use SMProxy\MysqlPacket\Util\BufferUtil;
use SMProxy\MysqlPacket\Util\Capabilities;

/**
 * MySql握手包.
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
    public $pluginName = 'mysql_native_password';
    public $authDataLength;

    public function read(BinaryPacket $bin)
    {
        $this->packetLength = $bin->packetLength;
        $this->packetId = $bin->packetId;
        $mm = new MySQLMessage($bin->data);
        $mm->length = $this->packetLength;
        $mm->move(4);
        $this->protocolVersion = $mm->read();
        $this->serverVersion = $mm->readStringWithNull();
        $this->threadId = $mm->readUB4();
        $this->seed = $mm->readBytesWithNull();
        $this->serverCapabilities = $mm->readUB2();
        $this->serverCharsetIndex = $mm->read();
        $this->serverStatus = $mm->readUB2();
        $this->serverCapabilities |= $mm->readUB2();
        $this->authDataLength = $mm->read();
        $mm->move(10);
        if ($this ->serverCapabilities & Capabilities::CLIENT_SECURE_CONNECTION) {
            $this->restOfScrambleBuff = $mm->readBytesWithNull();
        }
        $this->pluginName             = $mm->readStringWithNull() ?: $this->pluginName;
        return $this;
    }

    public function write()
    {
        // default init 256,so it can avoid buff extract
        $buffer = [];
        BufferUtil::writeUB3($buffer, $this->calcPacketSize());
        $buffer[] = $this->packetId;
        $buffer[] = $this->protocolVersion;
        BufferUtil::writeWithNull($buffer, getBytes($this->serverVersion));
        BufferUtil::writeUB4($buffer, $this->threadId);
        BufferUtil::writeWithNull($buffer, $this->seed);
        BufferUtil::writeUB2($buffer, $this->serverCapabilities);
        $buffer[] = $this->serverCharsetIndex;
        BufferUtil::writeUB2($buffer, $this->serverStatus);
        if ($this ->serverCapabilities & Capabilities::CLIENT_PLUGIN_AUTH) {
            BufferUtil::writeUB2($buffer, $this->serverCapabilities);
            $buffer[] = max(13, count($this->seed) + count($this->restOfScrambleBuff) + 1);
            $buffer = array_merge($buffer, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        } else {
            $buffer = array_merge($buffer, self::$FILLER_13);
        }
        if ($this ->serverCapabilities & Capabilities::CLIENT_SECURE_CONNECTION) {
            BufferUtil::writeWithNull($buffer, $this->restOfScrambleBuff);
        }
        if ($this ->serverCapabilities & Capabilities::CLIENT_PLUGIN_AUTH) {
            BufferUtil::writeWithNull($buffer, getBytes($this->pluginName));
        }
        return $buffer;
    }

    public function calcPacketSize()
    {
        $size = 1;
        $size += strlen($this->serverVersion); // n
        $size += 5; // 1+4
        $size += count($this->seed); // 8
        $size += 19; // 1+2+1+2+13
        if ($this ->serverCapabilities & Capabilities::CLIENT_SECURE_CONNECTION) {
            $size += count($this->restOfScrambleBuff); // 12
            ++$size; // 1
        }
        if ($this ->serverCapabilities & Capabilities::CLIENT_PLUGIN_AUTH) {
            $size += strlen($this->pluginName);
            ++$size; // 1
        }
        return $size;
    }

    protected function getPacketInfo()
    {
        return 'MySQL Handshake Packet';
    }
}
