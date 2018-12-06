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
(Do not use the MYSQL8.0 client link test)

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
```json
{
  "database": {
    "account": {
      "<REQUIRED> Account name": {
        "user": "<REQUIRED> Database user.",
        "password": "<REQUIRED> Database password."
      },
      "...": "At least one. Account name will be used in serverInfo below."
    },
    "serverInfo": {
      "<REQUIRED> Connection name": {
        "write": {
          "host": "<REQUIRED> Master database server hosts (write-only). Use [] if more than one.",
          "port": "<REQUIRED> Master database server port.",
          "timeout": "<REQUIRED> Connect timeout.",
          "account": "<REQUIRED> Account name, should be one in accounts above."
        },
        "read": {
          "host": "<OPTIONAL> Slave database server hosts (read-only). Use [] if more than one.",
          "port": "<OPTIONAL> Slave database server port.",
          "timeout": "<OPTIONAL> Connect timeout.",
          "account": "<OPTIONAL> Account name, should be one in accounts above."
        }
      },
      "...": "<REQUIRED> At least one. Connection name will be used in databases below."
    },
    "databases": {
      "<REQUIRED> Database name": {
        "serverInfo": "<REQUIRED> Connection name, should be one in serverInfo above.",
        "maxConns": "<REQUIRED> Max connections. Support expressions evaluating.",
        "maxSpareConns": "<REQUIRED> Max spare connections. Support expressions evaluating.",
        "startConns": "<OPTIONAL> Number of connections at starting. Support expressions evaluating.",
        "maxSpareExp": "<OPTIONAL> Max spare time of idle connections in seconds with default value 0. Support expressions evaluating.",
        "charset": "<OPTIONAL> Charset of this database."
      },
      "...": "At least one. Append more if you need."
    }
  }
}
```
- `maxConns`,`maxSpareConns`,`startConns`
    - It is recommended to set to multiple of `worker_num` configured in `server.json`. (`swoole_cpu_num()*N`)
- With multiple readable / writable databases
    - We are now only support random algorithm for retrieving connections. So it is recommended to set `maxConns`, `startConns`, `startConns` at least more than 1 times of `max(master, slave) * worker_num`.

### server.json
```json
{
  "server": {
    "user": "必选，SMProxy服务用户",
    "password": "必选，SMProxy服务密码",
    "charset": "可选，SMProxy编码，默认utf8mb4",
    "host": "可选，SMProxy地址，默认0.0.0.0",
    "port": "可选，SMProxy端口，默认3366 如需多个以`,`隔开",
    "mode": "可选，SMProxy运行模式，SWOOLE_PROCESS多进程模式（默认），SWOOLE_BASE基本模式",
    "sock_type": "可选，sock类型，SWOOLE_SOCK_TCP tcp",
    "logs": {
      "open":"必选，日志开关，true 开 false 关",
      "config": {
        "system": {
          "log_path": "必选，SMProxy系统日志目录",
          "log_file": "必选，SMProxy系统日志文件名",
          "format": "必选，SMProxy系统日志目录日期格式"
        },
        "mysql": {
          "log_path": "必选，SMProxyMySQL日志目录",
          "log_file": "必选，SMProxyMySQL日志文件名",
          "format": "必选，SMProxyMySQL日志目录日期格式"
        }
      }
    },
    "swoole": {
      "worker_num": "必选，SWOOLE worker进程数，支持计算",
      "max_coro_num": "必选，SWOOLE 协程数，推荐不低于3000",
      "pid_file": "必选，worker进程和manager进程pid目录	",
      "open_tcp_nodelay": "可选，关闭Nagle合并算法",
      "daemonize": "可选，守护进程化，true 为守护进程 false 关闭守护进程",
      "heartbeat_check_interval": "可选，心跳检测",
      "heartbeat_idle_time": "可选，心跳检测最大空闲时间",
      "reload_async": "可选，异步重启，true 开启异步重启 false 关闭异步重启",
      "log_file": "可选，SWOOLE日志目录"
    },
    "swoole_client_setting": {
      "package_max_length": "可选，SWOOLE Client 最大包长，默认16777216MySQL最大支持包长"
    },
    "swoole_client_sock_setting": {
      "sock_type": "可选，SWOOLE Client sock 类型，默认tcp 仅支持tcp"
    }
  }
}
```
- `user`,`password`,`port,host`
    - 为`SMProxy`的账户|密码|端口|地址(非Mysql数据库账户|密码|端口|地址)
    - 可随意设置用于`SMProxy`登录验证
    - 例如默认配置登录为`mysql -uroot -p123456 -P 3366 -h 127.0.0.1`
    - `SMProxy`登录成功MySQL COMMIT会提示`Server version: 5.6.0-SMProxy`
- `worker_num`
    - 推荐使用`swoole_cpu_num()` 或 `swoole_cpu_num()*N`

## MySQL8.0

- `SMProxy1.2.4` and above can be used directly
- `SMProxy1.2.4` The following needs to be compatible
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
