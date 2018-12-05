<?php

namespace SMProxy\MysqlPacket\Util;

use function SMProxy\Helper\getBytes;
use function SMProxy\Helper\getString;

/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/10/31
 * Time: 下午2:18.
 */
class SecurityUtil
{
    public static function scramble411(string $pass, array $seed)
    {
        $pass1 = getBytes(sha1($pass, true));
        $pass2 = getBytes(sha1(getString($pass1), true));
        $pass3 = getBytes(sha1(getString($seed) . getString($pass2), true));
        for ($i = 0, $count = count($pass3); $i < $count; ++$i) {
            $pass3[$i] = ($pass3[$i] ^ $pass1[$i]);
        }

        return $pass3;
    }

    public static function scrambleSha256(string $pass, array $seed)
    {
        $pass1 = getBytes(hash('sha256', $pass, true));
        $pass2 = getBytes(hash('sha256', getString($pass1), true));
        $pass3 = getBytes(hash('sha256', getString($pass2) . getString($seed), true));
        for ($i = 0, $count = count($pass3); $i < $count; ++$i) {
            $pass1[$i] ^= $pass3[$i];
        }
        return $pass1;
    }

    private static function xorPassword($password, $salt)
    {
        $password_bytes = getBytes($password);
        $password_bytes[] = 0;
        $salt_len = count($salt);
        for ($i = 0, $count = count($password_bytes); $i < $count; ++$i) {
            $password_bytes[$i] ^= $salt[$i % $salt_len];
        }
        return getString($password_bytes);
    }


    public static function sha2RsaEncrypt($password, $salt, $publicKey)
    {
        /*Encrypt password with salt and public_key.

        Used for sha256_password and caching_sha2_password.
        */
        $message = self::xorPassword($password, $salt);
        openssl_public_encrypt($message, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        return $encrypted;
    }
}
