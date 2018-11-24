<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:33.
 */

define('ROOT', dirname(Phar::running(false)) ?: realpath(__DIR__ . '/..'));

require_once __DIR__ . '/../vendor/autoload.php';

define('SMPROXY_VERSION', absorb_version_from_git());

(new \SMProxy\Bootstrap())->bootstrap();
