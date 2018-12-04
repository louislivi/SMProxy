<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:33.
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('IN_PHAR', boolval(Phar::running(false)));

define('ROOT', IN_PHAR ? dirname(Phar::running(false)) : realpath(__DIR__ . '/..'));

define('DB_DELIMITER', 'SΜ');

define('SMPROXY_VERSION', IN_PHAR ? '@phar-version@' : absorb_version_from_git());

set_error_handler('_error_handler', E_ALL | E_STRICT);

// 判断php版本
if (version_compare(PHP_VERSION, '7.0', '<')) {
    smproxy_error('ERROR: PHP version must be greater than 7.0!');
}

// 判断swoole版本
if (defined('SWOOLE_VERSION')) {
    if (version_compare(SWOOLE_VERSION, '2.0', '<')) {
        smproxy_error('ERROR: Swoole version must be greater than 2.1!');
    }
} else {
    smproxy_error('ERROR: Swoole not installed!');
}
