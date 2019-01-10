<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:33.
 */

use function SMProxy\Helper\absorb_version_from_git;
use function SMProxy\Helper\smproxy_error;

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants
define('IN_PHAR', boolval(Phar::running(false)));
define('ROOT', IN_PHAR ? dirname(Phar::running(false)) : realpath(__DIR__ . '/..'));
define('DB_DELIMITER', 'SΜ');
define('SMPROXY_VERSION', IN_PHAR ? '@phar-version@' : absorb_version_from_git());

// Set global error handler
set_error_handler('SMProxy\Helper\_error_handler', E_ALL | E_STRICT);

// Check requirements - PHP
if (version_compare(PHP_VERSION, '7.0', '<')) {
    smproxy_error('ERROR: SMProxy requires [PHP >= 7.0].');
}

// Check requirements - Swoole
if (extension_loaded('swoole') && defined('SWOOLE_VERSION')) {
    if (version_compare(SWOOLE_VERSION, '2.1.3', '<')) {
        smproxy_error('ERROR: SMProxy requires [Swoole >= 2.1.3].');
    }
} else {
    smproxy_error('ERROR: Swoole was not installed.');
}

if (extension_loaded('xdebug')) {
    smproxy_error('ERROR: XDebug has been enabled, which conflicts with SMProxy.');
}
