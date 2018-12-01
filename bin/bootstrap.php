<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:33.
 */

define('IN_PHAR', boolval(Phar::running(false)));

define('ROOT', IN_PHAR ? dirname(Phar::running(false)) : realpath(__DIR__ . '/..'));

define('DB_DELIMITER', 0x02);

require_once __DIR__ . '/../vendor/autoload.php';

set_error_handler('_error_handler', E_ALL | E_STRICT);

define('SMPROXY_VERSION', IN_PHAR ? '@phar-version@' : absorb_version_from_git());

(new \SMProxy\Bootstrap())->bootstrap();
