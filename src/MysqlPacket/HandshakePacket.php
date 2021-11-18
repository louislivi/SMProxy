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
        // 写入消息头长度
        BufferUtil::writeUB3($buffer, $this->calcPacketSize());
        // 写入序号 -- 消息头的
        $buffer[] = $this->packetId;
        // 写入协议版本号
        $buffer[] = $this->protocolVersion;
        // 写入服务器版本信息
        BufferUtil::writeWithNull($buffer, getBytes($this->serverVersion));
        // 写入服务器线程ID
        BufferUtil::writeUB4($buffer, $this->threadId);
        // 挑战随机数 9个字节 包含一个填充值
        BufferUtil::writeWithNull($buffer, $this->seed);
        // 服务器权能标识
        BufferUtil::writeUB2($buffer, $this->serverCapabilities);
        // 1字节 字符编码
        $buffer[] = $this->serverCharsetIndex;
        BufferUtil::writeUB2($buffer, $this->serverStatus);
        if ($this ->serverCapabilities & Capabilities::CLIENT_PLUGIN_AUTH) {
            // 服务器权能标志 16位
            BufferUtil::writeUB2($buffer, $this->serverCapabilities);
            // 挑战长度+填充值+挑战随机数
            $buffer[] = max(13, count($this->seed) + count($this->restOfScrambleBuff) + 1);
            $buffer = array_merge($buffer, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        } else {
            // 10字节填充数
            $buffer = array_merge($buffer, self::$FILLER_13);
        }
        // +12字节挑战随机数
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
