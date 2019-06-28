# SMProxy

[![release](https://img.shields.io/github/release/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/releases)
[![forks](https://img.shields.io/github/forks/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/network/members)
[![stars](https://img.shields.io/github/stars/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/stargazers)
[![Build Status](https://img.shields.io/travis/com/louislivi/SMProxy.svg?style=popout-square)](https://travis-ci.com/louislivi/SMProxy)
[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
[![license](https://img.shields.io/github/license/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/blob/master/LICENSE)
[![SMProxy](https://img.shields.io/badge/SMProxy-%F0%9F%92%97-pink.svg?style=popout-square)](https://github.com/louislivi/SMProxy)

> Checkout on `Github`: <https://github.com/louislivi/smproxy> (Star me if helps)

## Swoole MySQL Proxy

A MySQL database connection pool based on MySQL protocol and Swoole.

## Principle

Store the database connection as an object in memory. When users need to access the database, a connection established for the first time. After that, instead of establishing a new connection, free connections will be retrieved from the connection pool when users require. Also, users don't need to close connection but put it back into the connection pool for other requests to use.

The connection pool itself manages all these things, connecting, disconnecting. At the same time, you can also configure the parameters of the connection pool, like:

- The initial number of connections
- Min / Max number of connections
- Number of max requests per connection
- Max idle time of connections

...etc.

It's also possible to monitor the number of database connections, usage, etc. through its own management system.

If the maximum number of connections exceeded, the coroutine will be suspended and wait until a connection is released.

## Features

- Read/Write Splitting
- Connection Pool
- SQL92 Standard
- Coroutine Scheduling
- Multiple database connections, multiple databases, multiple users...
- Build with MySQL native protocol, cross-language, cross-platform.
- Compatible with MySQL Transaction
- Compatible with HandshakeV10
- Compatible with MySQL 5.5 - 8.0
- Compatible with Various Frameworks

## Why This

For prior design reasons, PHP does not have a native connection pool. So the number of database connections will be quickly increasing and reaching the maximum when we got lots of requests.
Using one of many database middlewares like Mycat cause some limitations, e.g., batch inserts. Also, it's too heavy in most cases.
So we created SMProxy using 100% PHP + Swoole, which only supports connection pool and read/write separation, but much more lightweight.
Not like Mycat, we're trying to build SMProxy with Swoole Coroutine to schedule HandshakeV10 packet forwarding, so we don't have to parse all SQL packets.
That really makes SMProxy more stable and reliable.

## Benchmark

See [docs/BENCHMARK-EN.md](BENCHMARK.md).

## Requirements

- Swoole >= 2.1.3  ![swoole_version](https://img.shields.io/badge/swoole->=2.1.3-yellow.svg?style=popout-square)
- PHP >= 7.0     ![php_version](https://img.shields.io/badge/php->=7.0-blue.svg?style=popout-square)

## Installation

(Recommended) Directly download PHAR in latest release:

<https://github.com/louislivi/SMProxy/releases/latest>

Or use git to checkout any version:

```bash
git clone https://github.com/louislivi/SMProxy.git
composer install --no-dev # If you want to contribute to this repo, please DO NOT use --no-dev.
```

## Usage

`bin/SMProxy` needs execute permission.

```bash
  SMProxy [ start | stop | restart | status | reload ] [ -c | --config <configuration_path> | --console ]
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
- --console                        Front desk operation(SMProxy>=1.2.5)

## Configuration

The configuration files are located in the `smproxy/conf` directory. The uppercase `ROOT` represents the SMProxy root directory.

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
    - Recommended set to multiple of `worker_num` configured in `server.json`. (`swoole_cpu_num()*N`)
- With multiple readable / writable databases
    - We are now only supporting random algorithm for retrieving connections. So setting `maxConns`, `startConns`, `startConns` at least more than 1 times of `max(master, slave) * worker_num` was recommended.
- `timeout`
    - Recommended between 2-5 seconds.

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

### Integration

- Laravel
    - `.env`
    ```
    DB_CONNECTION=mysql
    Host configured in DB_HOST=server.json
    DB_PORT=port configured in server.json
    Database name configured in DB_DATABASE=databse.json
    User configured in DB_USERNAME=server.json
    DB_PASSWORD=password configured in server.json
    ```

- ThinkPHP
    - `database.php`
    ```php
    'type' => 'mysql',
    // server address
    'hostname' => 'host' configured in server.json,
    // data storage name
    'database' => 'database name configured in databse.json',
    // username
    'username' => 'user' configured in server.json,
    // password
    'password' => 'password' configured in server.json,
    // port
    'hostport' => 'port' configured in server.json,
    ```

- Other frameworks and so on, only need to configure the code to connect to the database `host`, `port`, `user`, `password` and `SMProxy` in the `server.json`.

## Route

### Annotation

- smproxy:db_type=[read | write]

    - Forced use read-only servers
    ```sql
    /** smproxy:db_type=read */select * from `user` limit 1
    ```

    - Forced use write-only servers

    ```sql
    /** smproxy:db_type=write */select * from `user` limit 1
    ```

## MySQL8.0

- `SMProxy1.2.4` and above can be used directly
- `SMProxy1.2.4` The following needs to be compatible:
    - `MySQL-8.0` uses the more secure `caching_sha2_password` plugin by default. If you upgraded from `5.x`, all the thing should still work directly. For example, if you are creating a new `MySQL`, you need to enter `MySQL. `The command line performs the following operations to be compatible:

    ```sql
    ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'password';
    Flush privileges;
    ```

    Replace `'root'@'%'` in the statement with the user you are using, and replace `password` with its password.

    If it is still not available, set `default_authentication_plugin = mysql_native_password` in my.cnf.

## Troubleshooting
- `SMProxy@access denied for user`
    - Please check if the account password in `serve.json` is the same as that configured in the business code.
    - Do not configure `localhost` for database `host`.
- `SMProxy@Database config dbname write is not exists! `
    - Change the `dbname` entry in `database.json` to your business database name.
- `Config serverInfo->*->account is not exists! `
    - Please review `database.json`, make sure that `databse->serverInfo->*->*->account` exists in `database->account`.
- `Reach max connections! Cann't pending fetch!`
    - Increase `maxSpareConns` appropriately or increase the `timeout` entry in `database.json`.
- `Must be connected before sending data!`
    - Check if `MySQL` has access to the external network.
    - Check if `MySQL` verifies that the plugin is `mysql_native_password` or `caching_sha2_password`
    - Check for service conflicts. It is recommended to use `Docker` to run a troubleshooting environment.
- `Connection * waiting timeout`
    - Check if `MySQL` has access to the external network.
    - Start the database connection timeout. Please check the database configuration. If it is normal, please lower the `startConns` or increase the `timeout` item in `database.json`.
- `The server is not running`
    - View the logs `mysql.log` and `system.log` under `SMProxy`.
    - Prevent `SMProxy` from exiting abnormally. It is recommended to use `Supervisor` or `docker` for service mounting.
- `Supervisor` || `docker`
    - Use `Supervisor` and `docker` to use the foreground run mode (v1.2.5+ use `--console`, otherwise use `daemonize` parameter) or it will not start properly.
- `502 Bad Gateway`
    - After MySQL crashes abnormally, the connection appears 502 or the connection times out. Please do not enable long connection mode.
    - If the SQL statement is too large, do not use a succession pool, which will cause the connection to be blocked and the program to be abnormal.
- `CPU usage is too high after starting SMProxy`
     - Because Swoole 4.2.12 and below does not open the coroutine Client read and write separation, the CPU ratio will be larger.
     - Upgrade the Swoole version to 4.2.13 and above and upgrade the SMProxy version to 1.2.8 and above.

## Community

[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)

## More Documentation

- MySQL protocol analysis: <https://www.cnblogs.com/davygeek/p/5647175.html>
- MySQL official protocol documentation: <https://dev.MySQL.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake>
- Mycat source code: <https://github.com/MyCATApache/Mycat-Server>
- Swoole: <https://www.swoole.com/>
