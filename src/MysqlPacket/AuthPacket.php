<?php

namespace SMProxy\MysqlPacket;

use SMProxy\MysqlPacket\Util\BufferUtil;
use SMProxy\MysqlPacket\Util\Capabilities;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 上午10:32
 */
class AuthPacket extends MySQLPacket
{
    private static $FILLER = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; //23位array

    public $clientFlags;
    public $maxPacketSize;
    public $charsetIndex;
    public $extra;// from FILLER(23)
    public $user;
    public $password;
    public $database = 0;

    public function read(BinaryPacket $bin)
    {
        $this->packetLength = $bin->packetLength;
        $this->packetId = $bin->packetId;
        $mm = new MySQLMessage($bin->data);
        $mm->move(4);
        $this->clientFlags = $mm->readUB4();
        $this->maxPacketSize = $mm->readUB4();
        $this->charsetIndex = ($mm->read() & 0xff);
        $current = $mm->position();
        $len = (int)$mm->readLength();
        if ($len > 0 && $len < count(self::$FILLER)) {
            $this->extra = array_copy($mm->bytes(), $mm->position(), $len);
        }
        $mm->position($current + count(self::$FILLER));
        $this->user = $mm->readStringWithNull();
        $this->password = $mm->readBytesWithLength();
        if ((($this->clientFlags & Capabilities::CLIENT_CONNECT_WITH_DB) != 0) && $mm->hasRemaining()) {
            $this->database = $mm->readStringWithNull();
        }

        return $this;
    }


    public function write()
    {
        $data = getMysqlPackSize($this->calcPacketSize());
        $data[] = $this->packetId;
        BufferUtil::writeUB4($data, $this->clientFlags);
        BufferUtil::writeUB4($data, $this->maxPacketSize);
        $data[] = $this->charsetIndex;
        $data = array_merge($data,self::$FILLER);
        if ($this->user == null) {
            $data[] = 0;
        } else {
            $userData = getBytes($this->user);
            BufferUtil::writeWithNull($data, $userData);
        }
        if ($this->password == null) {
            $data[] = 0;
        } else {
            BufferUtil::writeWithLength($data, $this->password);
        }
        if ($this->database == null) {
            $data[] = 0;
        } else {
            $database = getBytes($this->database);
            BufferUtil::writeWithNull($data, $database);
        }
        BufferUtil::writeWithNull($data, getBytes('mysql_native_password'));
        return $data;
    }

//    public void write(ChannelHandlerContext ctx) {
//    // default init 256,so it can avoid buff extract
//    ByteBuf buffer = ctx.alloc().buffer();
//        BufferUtil.writeUB3(buffer, calcPacketSize());
//        buffer.writeByte(packetId);
//        BufferUtil.writeUB4(buffer, clientFlags);
//        BufferUtil.writeUB4(buffer, maxPacketSize);
//        buffer.writeByte((byte) charsetIndex);
//        buffer.writeBytes(FILLER);
//        if (user == null) {
//            buffer.writeByte((byte) 0);
//        } else {
//            byte[] userData = user.getBytes();
//            BufferUtil.writeWithNull(buffer, userData);
//        }
//        if (password == null) {
//            buffer.writeByte((byte) 0);
//        } else {
//            BufferUtil.writeWithLength(buffer, password);
//        }
//        if (database == null) {
//            buffer.writeByte((byte) 0);
//        } else {
//            byte[] databaseData = database.getBytes();
//            BufferUtil.writeWithNull(buffer, databaseData);
//        }
//        ctx.writeAndFlush(buffer);
//    }

    public function calcPacketSize()
    {
        $size = 32;// 4+4+1+23;
        $size += ($this->user == null) ? 1 : strlen($this->user) + 1;
        $size += ($this->password == null) ? 1 : BufferUtil::getLength($this->password);
        $size += ($this->database == null) ? 1 : strlen($this->database) + 1;
        $size += strlen('mysql_native_password') + 1;
        return $size;
    }

    protected function getPacketInfo()
    {
        return "MySQL Authentication Packet";
    }
}