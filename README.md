# smproxy
## swoole msyql proxy 一个基于mysql协议，swoole 开发的mysql数据库连接池

## 环境
* swoole 2.1+
* php 7.0+

## 运行
bin/server

## 配置文件:
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
      "db1": {
        "serverInfo": "server1",
        "maxSpareConns": 10,
        "maxConns": 20,
        "charset": "utf-8"
      }
    }
  }
}
```
| account 账号信息 | serverInfo 服务信息 | databases 数据库连接池信息 |
| ------ | ------ | ------ |
| account.root 用户标识 与 serverInfo...account.root 对应 | serverInfo.server1 服务标识 与  databases..serverInfo 对应 | databases.db1 数据库名称 |
| account..user 用户名  | serverInfo..write 读写分离 write 写库 read 读库 | databases..serverInfo 服务信息 |
| account..password 密码  | serverInfo..host 数据库连接地址 | databases..maxSpareConns 最大空闲连接数 |
|   | serverInfo..prot 数据库端口 | databases..maxConns 最大连接数 |
|   | serverInfo..timeout 数据库超时时长(秒) | databases..charset 数据库编码格式 |
|   | serverInfo..flag TCP类型目前支持0阻塞 不支持1.非阻塞 |  |
|   | serverInfo..account  与 databases.account 对应|  |

### server.json
```Json
{
  "server": {
    "user":"maiya",
    "password":"P--!jathJhk1UbE3FthiYNmOQJW+XHeX",
    "charset":"utf8mb4",
    "host": "0.0.0.0",
    "port": "3366",
    "mode": 3,
    "sock_type": 1,
    "swoole": {
      "worker_num": 2,
      "max_coro_num": 16000,
      "open_tcp_nodelay": true,
      "daemonize": 0,
      "heartbeat_check_interval": 60,
      "heartbeat_idle_time": 600,
      "reload_async": true,
      "log_file": "/var/www/swoole/swoole-mysql-proxy/logs/error.log",
      "pid_file": "/var/www/swoole/swoole-mysql-proxy/logs/pid/server.pid"
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
| user 服务用户名 | password 服务密码 | charset 服务编码 | host 链接地址 | port 服务端口 多个以,隔开 |  [mode](https://wiki.swoole.com/wiki/page/277.html) | sock_type 1 tcp | swoole swoole配置 | swoole_client_setting 客户端配置 | swoole_client_sock_setting 客户端sock配置 |
| ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ | ------ |
|   |   |   |   |   |   |   |  worker_num work进程数量 | package_max_length 最大包长  | sock_type 1.tcp  |
|   |   |   |   |   |   |   |  max_coro_num 最大携程数  |   | sync_type 1.异步  |
|   |   |   |   |   |   |   |  open_tcp_nodelay 关闭Nagle合并算法  |   |   |
|   |   |   |   |   |   |   |  daemonize 守护进程化 |   |   |
|   |   |   |   |   |   |   |  heartbeat_check_interval 心跳检测 |   |   |
|   |   |   |   |   |   |   |  heartbeat_idle_time 最大空闲时间 |   |   |
|   |   |   |   |   |   |   |  reload_async 异步重启 |   |   |
|   |   |   |   |   |   |   |  log_file 日志目录 |   |   |
|   |   |   |   |   |   |   |  pid_file 主进程pid目录 |   |   |

