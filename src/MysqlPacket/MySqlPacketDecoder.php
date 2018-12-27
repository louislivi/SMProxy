<?php

namespace SMProxy\MysqlPacket;

use function SMProxy\Helper\getBytes;
use SMProxy\Log\Log;
use SMProxy\MysqlPacket\Util\ByteUtil;
use SMProxy\SMProxyException;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/27
 * Time: 上午10:37.
 */
class MySqlPacketDecoder
{
    private $packetHeaderSize = 4;
    private $maxPacketSize = 16777216;

    /**
     * MySql外层结构解包.
     *
     * @param string $data
     *
     * @return \SMProxy\MysqlPacket\BinaryPacket
     * @throws \SMProxy\SMProxyException
     */
    public function decode(string $data)
    {
        $data = getBytes($data);
        // 4 bytes:3 length + 1 packetId
        if (count($data) < $this->packetHeaderSize) {
            throw new SMProxyException('Packet is empty');
        }
        $packetLength = ByteUtil::readUB3($data);
//        // 过载保护
        if ($packetLength > $this->maxPacketSize) {
            throw new SMProxyException('Packet size over the limit ' . $this->maxPacketSize);
        }
        $packetId = $data[3];
//        if (in.readableBytes() < packetLength) {
//            // 半包回溯
//            in.resetReaderIndex();
//            return;
//        }
        $packet = new BinaryPacket();
        $packet->packetLength = $packetLength;
        $packet->packetId = $packetId;
        // data will not be accessed any more,so we can use this array safely
        $packet->data = $data;
        if (null == $packet->data || 0 == count($packet->data)) {
            throw new SMProxyException('get data errorMessage,packetLength=' . $packet->packetLength);
        }

        return $packet;
    }
}
