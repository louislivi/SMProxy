<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/23
 * Time: 下午2:33.
 */
date_default_timezone_set('PRC');
//设置根目录
define('ROOT', dirname(__FILE__).'/..');
define('SMPROXY_VERSION', 1.2);
include dirname(__FILE__).'/../vendor/autoload.php';
$bootstrap = new \SMProxy\Bootstrap();
$bootstrap->bootstrap();
