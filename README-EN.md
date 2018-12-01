English | [中文](./README.md)
```
  /$$$$$$  /$$      /$$ /$$$$$$$                                        
 /$$__  $$| $$$    /$$$| $$__  $$                                       
| $$  \__/| $$$$  /$$$$| $$  \ $$ /$$$$$$   /$$$$$$  /$$   /$$ /$$   /$$
|  $$$$$$ | $$ $$/$$ $$| $$$$$$$//$$__  $$ /$$__  $$|  $$ /$$/| $$  | $$
 \____  $$| $$  $$$| $$| $$____/| $$  \__/| $$  \ $$ \  $$$$/ | $$  | $$
 /$$  \ $$| $$\  $ | $$| $$     | $$      | $$  | $$  >$$  $$ | $$  | $$
|  $$$$$$/| $$ \/  | $$| $$     | $$      |  $$$$$$/ /$$/\  $$|  $$$$$$$
 \______/ |__/     |__/|__/     |__/       \______/ |__/  \__/ \____  $$
                                                               /$$  | $$
                                                              |  $$$$$$/
                                                               \______/
```
# [SMProxy](https://github.com/louislivi/smproxy)

[![release](https://img.shields.io/github/release/louislivi/smproxy.svg?style=popout-square)](https://github.com/louislivi/smproxy/releases)
[![forks](https://img.shields.io/github/forks/louislivi/smproxy.svg?style=popout-square)](https://github.com/louislivi/smproxy/network/members)
[![stars](https://img.shields.io/github/stars/louislivi/smproxy.svg?style=popout-square)](https://github.com/louislivi/smproxy/stargazers)
[![Build Status](https://img.shields.io/travis/com/louislivi/smproxy.svg?style=popout-square)](https://travis-ci.com/louislivi/smproxy)
[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
[![license](https://img.shields.io/github/license/louislivi/smproxy.svg?style=popout-square)](https://github.com/louislivi/smproxy/blob/master/LICENSE)
[![smproxy](https://img.shields.io/badge/SMProxy-%F0%9F%92%97-pink.svg?style=popout-square)](https://github.com/louislivi/smproxy)

- [SMProxy](#smproxy)
  - [Swoole MySQL Proxy](#swoole-mysql-proxy)
  - [Principle](#principle)
  - [Features](#features)
  - [Why This](#why-this)
  - [Environment Requirements](#environment-requirements)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Connection Test](#connection-test)
    - [PHP 7.2.6 Without Framework](#php-726-without-framework)
    - [ThinkPHP 5.0](#thinkphp-50)
    - [Laravel 5.7](#laravel-57)
    - [Number of MySQL Connections](#number-of-mysql-connections)
  - [Communities & Groups](#communities--groups)
  - [Configuration](#configuration)
    - [database.json](#databasejson)
    - [server.json](#serverjson)
  - [MySQL8.0](#mysql80)
  - [More Documentation](#more-documentation)

## Swoole MySQL Proxy

A MySQL database connection pool based on MySQL protocol and Swoole.

## Principle

Store the database connection as an object in memory. When users need to access the database, a connection will be established for the first time. After that, instead of establishing a new connection, free connections will be retrieved from the connection pool when users require. Also, users don't need to close connection but put it back into the connection pool for other requests to use.

All these things, connecting, disconnecting are managed by the connection pool itself. At the same time, you can also configure the parameters of the connection pool, like:

- The initial number of connections
- Min / Max number of connections
- Number of max requests per connection
- Max idle time of connections

...etc.

It's also possible to monitor the number of database connections, usage, etc. through its own management system.

If the maximum number of connections is exceeded, the coroutine will be suspended and wait until a connection is released.

## Features

- Read/Write Splitting
- Connection Pool
- SQL92 Standard
- Coroutine Scheduling
- Multiple database connections, multiple databases, multiple users...
- Build with MySQL native protocol, cross-language, cross-platform.
- Compatible with MySQL Transaction
- Compatible with HandshakeV10
- Compatible with MySQL 4.1 - 8.0
- Compatible with Various Frameworks

## Why This

PHP does not have a connection pool, so the database will be full when the concurrency is high.
Database middleware such as mycat will appear some sql can not be used, 
for example, does not support batch addition, etc., and is too bloated.
So I wrote this lightweight middleware that only supports connection pooling and read-write separation.
Use the swoole coroutine to schedule HandshakeV10 protocol forwarding to make the program more stable. 
Do not parse all sql packages like mycat, increasing the complexity.

## Environment Requirements

- Swoole 2.1+  ![swoole_version](https://img.shields.io/badge/swoole-2.1+-yellow.svg?style=popout-square)
- PHP 7.0+     ![php_version](https://img.shields.io/badge/php-7.0+-blue.svg?style=popout-square)

## Installation

(Recommended) Directly download PHAR in latest release:

<https://github.com/louislivi/smproxy/releases/latest>

Or use git to checkout any version:

```bash
git clone https://github.com/louislivi/smproxy.git
composer install --no-dev # If you want to contribute to this repo, please DO NOT use --no-dev.
```

## Usage

`bin/SMProxy` needs execute permission.

```
  SMProxy [ start | stop | restart | status | reload ] [ -c | --config <configuration_path> ]
  SMProxy -h | --help
  SMProxy -v | --version
```

Options:
- start                            Start server
- stop                             Shutdown server
- restart                          Restart server
- status                           Show server status
- reload                           Reload configuration
- -h --help                        Display help
- -v --version                     Display version
- -c --config <configuration_path> Specify configuration path


## Connection Test

Testing SMProxy is exactly the same as testing MySQL. How to connect MySQL, how to connect SMProxy.

It is recommended to use the command line test first:

```
mysql -uroot -p123456 -P3366 -h127.0.0.1
```

Tool connections are also available.

### PHP 7.2.6 Without Framework

![php7.2.6](https://file.gesmen.com.cn/smproxy/1542782011408.jpg)

Native:0.15148401260376,With SMProxy:0.040808916091919


Native: 0.15148401260376
 
![ab](https://file.gesmen.com.cn/smproxy/1542782075073.jpg)

With SMProxy: 0.040808916091919

![ab](https://file.gesmen.com.cn/smproxy/1542782043730.jpg)

### ThinkPHP 5.0

![Thinkphp5](https://file.gesmen.com.cn/smproxy/8604B3D4-0AB0-4772-83E0-EEDA6B86F065.png)

Native:

![ab](https://file.gesmen.com.cn/smproxy/1542685140126.jpg)

With SMProxy:

![ab](https://file.gesmen.com.cn/smproxy/1542685109798.jpg)

### Laravel 5.7

![Laravel5.7](https://file.gesmen.com.cn/smproxy/3FE76B55-9422-40DB-B8CE-7024F36BB5A9.png)

Native:

![ab](https://file.gesmen.com.cn/smproxy/1542686575874.jpg)

With SMProxy:

![ab](https://file.gesmen.com.cn/smproxy/1542686580551.jpg)

### Number of MySQL Connections

Native:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625044913.jpg)

With SMProxy:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625037536.jpg)

Please take the actual pressure measurement as the standard, the root data volume, network environment, database configuration.
In the test, the maximum number of connections will be exceeded, and the coroutine will be suspended. Wait until the connection is closed and the coroutine is resumed.
All concurrency and the configuration file maxConns are not suitable, which will result in slower than the original link, mainly to control the number of connections.

## Communities & Groups

QQ group: 722124111

## Configuration

The configuration files are located in the `smproxy/conf` directory, the uppercase `ROOT` represents the SMProxy root directory.

### database.json

| account information                                           | serverInfo service information                                                        | databases database connection pool information    |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------- |
| account.root user ID Corresponds to serverInfo...account.root | serverInfo.server1 Service ID Corresponds to databases..serverInfo                    | databases.dbname database name                    |
| Account..user username                                        | serverInfo..write read-write separation write write library read read library         | databases..serverInfo service information         |
| Account..password password                                    | serverInfo..host database connection address\[Array:read and write multiple\]         | databases..maxSpareConns maximum idle connections |
|                                                               | serverInfo..prot database port                                                        | databases..maxConns maximum number of connections |
|                                                               | serverInfo..timeout database timeout duration (seconds)                               | databases..charset database encoding format       |
|                                                               |  serverInfo..account corresponds to databases.account                                 | databases..maxSpareExp Maximum idle time           |                                  
|                                                               |                                                                                       | databases..startConns service startup connections |

### server.json

| user service username | password service password | charset service code | host link address | port service port multiple, separated | mode run mode                                                       | sock_type SWOOLE_SOCK_TCP tcp | logs log configuration                    | swoole swoole configuration                  | swoole_client_setting client configuration | swoole_client_sock_setting Client sock configuration                   |
| --------------------- | ------------------------- | -------------------- | ----------------- | ------------------------------------- | ------------------------------------------------------------------- | ----------------------------- | ----------------------------------------- | -------------------------------------------- | ------------------------------------------ | ---------------------------------------------------------------------- |
|                       |                           |                      |                   |                                       | SWOOLE_PROCESS multi-process mode (default), SWOOLE_BASE basic mode |                               | logs.open log switch                      | worker_num work process number               | package_max_length maximum packet length   | sock_type SWOOLE_SOCK_TCP tcp                                          |
|                       |                           |                      |                   |                                       |                                                                     |                               | logs.config log configuration item        | max_coro_num maximum number of Ctrips        |                                            | sync_type SWOOLE_SOCK_ASYNC Asynchronous ,SWOOLE_SOCK_SYNC synchronous |
|                       |                           |                      |                   |                                       |                                                                     |                               | logs.system or MySQL configuration module | open_tcp_nodelay Close Nagle merge algorithm |                                            |                                                                        |
|                       |                           |                      |                   |                                       |                                                                     |                               | logs..log_path log directory              | daemonize daemonization                      |
|                       |                           |                      |                   |                                       |                                                                     |                               | logs..log_file log file name              | heartbeat_check_interval heartbeat detection |
|                       |                           |                      |                   |                                       |                                                                     |                               | logs..format log date format              | heartbeat_idle_time maximum idle time        |                                            |                                                                        |
|                       |                           |                      |                   |                                       |                                                                     |                               |                                           | reload_async Asynchronous restart            |                                            |                                                                        |
|                       |                           |                      |                   |                                       |                                                                     |                               |                                           | log_file log directory                       |                                            |                                                                        |
|                       |                           |                      |                   |                                       |                                                                     |                               |                                           | pid_file main process pid directory          |                                            |                                                                        |

## MySQL8.0

`MySQL-8.0` uses the more secure `caching_sha2_password` plugin by default. If it is upgraded from `5.x`, you can use all the `MySQL` functions directly. For example, if you are creating a new `MySQL`, you need to enter `MySQL. `The command line performs the following operations to be compatible:
```SQL
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'password';
Flush privileges;
```
Replace `'root'@'%'` in the statement with the user you are using, and replace `password` with its password.

If it is still not available, set `default_authentication_plugin = mysql_native_password` in my.cnf.

## More Documentation

- MySQL protocol analysis: <https://www.cnblogs.com/davygeek/p/5647175.html>
- MySQL official protocol documentation: <https://dev.MySQL.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake>
- Mycat source code: <https://github.com/MyCATApache/Mycat-Server>
- Swoole: <https://www.swoole.com/>
