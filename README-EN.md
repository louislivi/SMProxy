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
## swoole mysql proxy 
A mysql database connection pool based on mysql protocol, swoole development
## Principle
    Store the database connection as an object in memory. 
    When the user needs to access the database, 
    the connection is established for the first time, 
    and instead a new connection is established.
    Instead, an established free connection object is taken from the connection pool.   After using it, 
    the user does not close the connection, 
    but puts the connection back into the connection pool.
    For access to the next request. 
    The establishment and disconnection of the  connection are managed by the connection pool itself.
    At the same time, 
    you can also control the connection pool by setting the parameters of the connection pool.
    The initial number of connections, 
    the number of upper and lower limits of the connection, 
    the maximum number of uses per connection, 
    the maximum idle time, 
    and so on.
    It is also possible to monitor the number of database connections, 
    usage, etc. through its own management mechanism.
    If the maximum number of connections is exceeded, 
    the coroutine will be suspended. 
    Wait until the connection is closed and the coroutine will resume.

## Characteristic
- Support for reading and writing separation
- Support database connection pool, can effectively solve the database connection bottleneck brought by PHP
- Support SQL92 standard
- Comply with Mysql native protocol, cross-language, cross-platform universal middleware agent.
- Support multiple database connections, multiple databases, multiple users, flexible matching.
- Support mysql transaction
- Coroutine scheduling
- Support for the HandshakeV10 protocol version
- Perfectly compatible with mysql5.6-5.7
- Compatible with major frameworks to seamlessly improve performance

## Original design intention
    Php does not have a connection pool, so the database will be full when the concurrency is high.
    Database middleware such as mycat will appear some sql can not be used, 
    for example, does not support batch addition, etc., and is too bloated.
    So I wrote this lightweight middleware that only supports connection pooling and read-write separation.
    Use the swoole coroutine to schedule HandshakeV10 protocol forwarding to make the program more stable. 
    Do not parse all sql packages like mycat, increasing the complexity.
## Need environment
* swoole 2.1+
* php 7.0+
## installation
Download the file directly and extract it.
## Run
Need to give bin/server execute permission.
- bin/server start   : Running service
- bin/server stop    : Out of service
- bin/server restart : Restart service
- bin/server status  : Query service running status
- bin/server reload  : Smooth restart
- bin/server -h      : help
- bin/server -v      : view service version

## SMProxy connection test
Testing SMProxy is exactly the same as testing mysql. How to connect mysql, how to connect SMProxy.

It is recommended to use the command line test first:

mysql -uroot -p123456 -P3366 -h127.0.0.1

Tool connections are also available.
### Test
#### Useless framework php7.2.6
![php7.2.6](https://file.gesmen.com.cn/smproxy/1542782011408.jpg)

Unused:0.15148401260376  Use:0.040808916091919

Unused connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542782075073.jpg)

Use connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542782043730.jpg)

#### Thinkphp5.0
![Thinkphp5](https://file.gesmen.com.cn/smproxy/8604B3D4-0AB0-4772-83E0-EEDA6B86F065.png)

Unused connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542685140126.jpg)

Use connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542685109798.jpg)

#### Laravel5.7
![Laravel5.7](https://file.gesmen.com.cn/smproxy/3FE76B55-9422-40DB-B8CE-7024F36BB5A9.png)

Unused connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542686575874.jpg)

Use connection pool:

![ab](https://file.gesmen.com.cn/smproxy/1542686580551.jpg)

#### Mysql connection number
Unused connection pool:

![mysql](https://file.gesmen.com.cn/smproxy/1542625044913.jpg)

Use connection pool:

![mysql](https://file.gesmen.com.cn/smproxy/1542625037536.jpg)

Please take the actual pressure measurement as the standard, the root data volume, network environment, database configuration.
In the test, the maximum number of connections will be exceeded, and the coroutine will be suspended. Wait until the connection is closed and the coroutine is resumed.
All concurrency and the configuration file maxConns are not suitable, which will result in slower than the original link, mainly to control the number of connections.
## communicate with:
QQ group: 722124111
Email   :574747417@qq.com
## Configuration file:
```
The configuration file is located in the smproxy/conf directory
The configuration file uppercase ROOT represents the current SMProxy and directory
```
### database.json
```Json
{
  "database": {
    "account": {
      "root": {
        "user": "root", 
        "password": "123456"
      }
    },
    "serverInfo": {
      "server1": {
        "write": {
          "host": "127.0.0.1",
          "port": 3306,
          "timeout": 0.5,
          "flag": 0,
          "account": "root"
        },
        "read": {
          "host": "127.0.0.1",
          "port": 3306,
          "timeout": 0.5,
          "flag": 0,
          "account": "root"
        }
      }
    },
    "databases": {
      "dbname": {
        "serverInfo": "server1",
        "maxSpareConns": 10,
        "maxSpareExp": 3600,
        "maxConns": 20,
        "charset": "utf-8"
      }
    }
  }
}
```
account information | serverInfo service information | databases database connection pool information |
| ------ | ------ | ------ |
| account.root user ID Corresponds to serverInfo...account.root | serverInfo.server1 Service ID Corresponds to databases..serverInfo | databases.dbname database name |
Account..user username | serverInfo..write read-write separation write write library read read library | databases..serverInfo service information |
Account..password password | serverInfo..host database connection address | databases..maxSpareConns maximum idle connections |
| | serverInfo..prot database port | databases..maxConns maximum number of connections |
| | serverInfo..timeout database timeout duration (seconds) | databases..charset database encoding format |
| | serverInfo..flag TCP type currently supports 0 blocking Not supported 1. Non-blocking | databases..maxSpareExp Maximum idle time |
| | serverInfo..account corresponds to databases.account | |

### server.json
```Json
{
  "server": {
    "user": "root",
    "password": "123456",
    "charset": "utf8mb4",
    "host": "0.0.0.0",
    "port": "3366",
    "mode": 3,
    "sock_type": 1,
    "logs": {
      "open":true,
      "config": {
        "system": {
          "log_path": "ROOT/logs",
          "log_file": "system.log",
          "format": "Y/m/d"
        },
        "mysql": {
          "log_path": "ROOT/logs",
          "log_file": "mysql.log",
          "format": "Y/m/d"
        }
      }
    },
    "swoole": {
      "worker_num": 1,
      "max_coro_num": 16000,
      "open_tcp_nodelay": true,
      "daemonize": 0,
      "heartbeat_check_interval": 60,
      "heartbeat_idle_time": 600,
      "reload_async": true,
      "log_file": "ROOT/logs/error.log",
      "pid_file": "ROOT/logs/pid/server.pid"
    },
    "swoole_client_setting": {
      "package_max_length": 16777216
    },
    "swoole_client_sock_setting": {
      "sock_type": 1,
      "sync_type": 1
    }
  }
}
```
|user service username | password service password | charset service code | host link address | port service port multiple, separated | mode run mode | sock_type 1 tcp | logs log configuration | swoole swoole configuration | swoole_client_setting client configuration | swoole_client_sock_setting Client sock configuration |
 | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ |
 |   |   |   |   |   | 3 SWOOLE_PROCESS multi-process mode (default), 4 SWOOLE_BASE basic mode | | logs.open log switch | worker_num work process number | package_max_length maximum packet length | sock_type 1.tcp |
|   |   |   |   |   |   |   | logs.config log configuration item | max_coro_num maximum number of Ctrips | | sync_type 1. Asynchronous |
|   |   |   |   |   |   |   | logs.system or mysql configuration module | open_tcp_nodelay Close Nagle merge algorithm   |   |   |
|   |   |   |   |   |   |   | logs..log_path log directory | daemonize daemonization |
|   |   |   |   |   |   |   | logs..log_file log file name | heartbeat_check_interval heartbeat detection |
|   |   |   |   |   |   |   | logs..format log date format | heartbeat_idle_time maximum idle time | | |
|   |   |   |   |   |   |   |   |  reload_async Asynchronous restart | | |
|   |   |   |   |   |   |   |   |  log_file log directory | | |
|   |   |   |   |   |   |   |   |  pid_file main process pid directory | | |

## Other learning materials
- mysql protocol analysis: https://www.cnblogs.com/davygeek/p/5647175.html
- mysql official protocol documentation: https://dev.mysql.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake
- mycat source code: https://github.com/MyCATApache/Mycat-Server
- swoole :https://www.swoole.com/
