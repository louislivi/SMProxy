<?php

namespace SMProxy\MysqlPacket;

use function SMProxy\Helper\array_copy;
use function SMProxy\Helper\getBytes;
use function SMProxy\Helper\getMysqlPackSize;
use SMProxy\MysqlPacket\Util\BufferUtil;
use SMProxy\MysqlPacket\Util\Capabilities;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 上午10:32.
 */
class AuthPacket extends MySQLPacket
{
    private static $FILLER = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; //23位array

    public $clientFlags;
    public $maxPacketSize;
    public $charsetIndex;
    public $extra; // from FILLER(23)
    public $user;
    public $password;
    public $database = 0;
    public $pluginName = 'mysql_native_password';
    public $serverCapabilities;

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
        $len = (int) $mm->readLength();
        if ($len > 0 && $len < count(self::$FILLER)) {
            $this->extra = array_copy($mm->bytes(), $mm->position(), $len);
        }
        $mm->position($current + count(self::$FILLER));
        $this->user = $mm->readStringWithNull();
        $this->password = $mm->readBytesWithLength();
        if ((0 != ($this->clientFlags & Capabilities::CLIENT_CONNECT_WITH_DB)) && $mm->hasRemaining()) {
            $this->database = $mm->readStringWithNull();
        }
        $this->pluginName = $mm->readStringWithNull();

        return $this;
    }

    public function write()
    {
        $data = getMysqlPackSize($this ->calcPacketSize());
        $data[] = $this->packetId;
        BufferUtil::writeUB4($data, $this->clientFlags);
        BufferUtil::writeUB4($data, $this->maxPacketSize);
        $data[] = $this->charsetIndex;

        $data = array_merge($data, self::$FILLER);

        if (null == $this->user) {
            $data[] = 0;
        } else {
            BufferUtil::writeWithNull($data, getBytes($this->user));
        }
        if (null == $this->password) {
            $authResponseLength  = 0;
            $authResponse = 0;
        } else {
            $authResponseLength  = count($this->password);
            $authResponse = $this->password;
        }
        if ($this ->clientFlags & Capabilities::CLIENT_PLUGIN_AUTH_LENENC_CLIENT_DATA) {
                BufferUtil::writeLength($data, $authResponseLength);
                BufferUtil::writeWithNull($data, $authResponse, false);
        } else if ($this ->clientFlags & Capabilities::CLIENT_SECURE_CONNECTION) {
                $data[] = $authResponseLength;
                BufferUtil::writeWithNull($data, $authResponse, false);
        } else {
            BufferUtil::writeWithNull($data, $authResponse);
        }

        if ($this ->clientFlags & Capabilities::CLIENT_CONNECT_WITH_DB) {
            $database = getBytes($this->database);
            BufferUtil::writeWithNull($data, $database);
        }
        if ($this ->clientFlags & Capabilities::CLIENT_PLUGIN_AUTH) {
            BufferUtil::writeWithNull($data, getBytes($this->pluginName));
        }
        return $data;
    }

    public function calcPacketSize()
    {
        $size = 32; // 4+4+1+23;
        $size += (null == $this->user) ? 1 : strlen($this->user) + 1;
        if ($this ->clientFlags & Capabilities::CLIENT_PLUGIN_AUTH_LENENC_CLIENT_DATA) {
            $size += BufferUtil::getLength(count($this->password)) - 1;
        }
        $size += (null == $this->password) ? 1 : BufferUtil::getLength($this->password);
        if ($this ->clientFlags & Capabilities::CLIENT_CONNECT_WITH_DB) {
            $size += (null == $this->database) ? 1 : strlen($this->database) + 1;
        }
        if ($this ->clientFlags & Capabilities::CLIENT_PLUGIN_AUTH) {
            $size += strlen($this ->pluginName) + 1;
        }

        return $size;
    }

    protected function getPacketInfo()
    {
        return 'MySQL Authentication Packet';
    }
}
