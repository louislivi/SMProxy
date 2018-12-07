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
  - [Benchmark](#benchmark)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Configuration](#configuration)
    - [database.json](#databasejson)
    - [server.json](#serverjson)
  - [MySQL8.0](#mysql80)
  - [Community](#community)
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

## Benchmark

See [docs/BENCHMARK-EN.md](docs/BENCHMARK-EN.md).

## Requirements

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
    "user": "<REQUIRED> Username of SMProxy",
    "password": "<REQUIRED> Password of SMProxy",
    "charset": "<OPTIONAL> Charset of SMProxy",
    "host": "<OPTIONAL> Listening address of SMProxy, default 0.0.0.0",
    "port": "<OPTIONAL> Listensing port of SMProxy, default 3366. Use `,` to separate multiple.",
    "mode": "<OPTIONAL> Runtime mode, SWOOLE_PROCESS (default, multiple process) or SWOOLE_BASE.",
    "sock_type": "<OPTIONAL> Socket type, default SWOOLE_SOCK_TCP",
    "logs": {
      "open":"<REQUIRED> True or false to enable / disable logging",
      "config": {
        "system": {
          "log_path": "<REQUIRED> The directory to put SMProxy logs to",
          "log_file": "<REQUIRED> The filename of SMProxy logs",
          "format": "<REQUIRED> Datetime format for the path of SMProxy logs"
        },
        "mysql": {
          "log_path": "<REQUIRED> The directory to put MySQL logs to",
          "log_file": "<REQUIRED> The filename of MySQL logs",
          "format": "<REQUIRED> Datetime format for the path of MySQL logs"
        }
      }
    },
    "swoole": {
      "worker_num": "<REQUIRED> Number of Swoole workers",
      "max_coro_num": "<REQUIRED> Max number of Swoole coroutines, recommended 3000+",
      "pid_file": "<REQUIRED> The directory where put PID files to",
      "open_tcp_nodelay": "<OPTIONAL> True to disable Nagle's algorithms",
      "daemonize": "<OPTIONAL> True to enable daemonize running",
      "heartbeat_check_interval": "<OPTIONAL> Interval of health checking for the TCP connections",
      "heartbeat_idle_time": "<OPTIONAL> Max idle time before the idle connection been closed",
      "reload_async": "<OPTIONAL> True to enable Asynchronous reloading",
      "log_file": "<OPTIONAL> The directory where put Swoole logs to"
    },
    "swoole_client_setting": {
      "package_max_length": "<OPTIONAL> Max single package length of Swoole client，default 16777216 (Max length MySQL supported)"
    },
    "swoole_client_sock_setting": {
      "sock_type": "<OPTIONAL> Socket type of Swoole client, only support TCP for now."
    }
  }
}
```
- `user`,`password`,`port,host`
    - These parameters are for SMProxy server, not MySQL.
    - Feel free to set to anything you like for authenticating of SMProxy.
    - E.g. The login command using MySQL cli with default config: `mysql -uroot -p123456 -P 3366 -h 127.0.0.1`
    - MySQL cli will output `Server version: 5.6.0-SMProxy` when you logged in to SMProxy.
- `worker_num`
    - It is recommended to set to `swoole_cpu_num()` or `swoole_cpu_num()*N`.

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

## Community

[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)

## More Documentation

- MySQL protocol analysis: <https://www.cnblogs.com/davygeek/p/5647175.html>
- MySQL official protocol documentation: <https://dev.MySQL.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake>
- Mycat source code: <https://github.com/MyCATApache/Mycat-Server>
- Swoole: <https://www.swoole.com/>
