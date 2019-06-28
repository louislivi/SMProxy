# SMProxy

[![release](https://img.shields.io/github/release/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/releases)
[![forks](https://img.shields.io/github/forks/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/network/members)
[![stars](https://img.shields.io/github/stars/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/stargazers)
[![Build Status](https://img.shields.io/travis/com/louislivi/SMProxy.svg?style=popout-square)](https://travis-ci.com/louislivi/SMProxy)
[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
[![license](https://img.shields.io/github/license/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/blob/master/LICENSE)
[![SMProxy](https://img.shields.io/badge/SMProxy-%F0%9F%92%97-pink.svg?style=popout-square)](https://github.com/louislivi/SMProxy)

> `Github` 项目地址：[https://github.com/louislivi/smproxy](https://github.com/louislivi/smproxy) (支持请点Star)

## Swoole MySQL Proxy

一个基于 MySQL 协议，Swoole 开发的MySQL数据库连接池。

## 原理

将数据库连接作为对象存储在内存中，当用户需要访问数据库时，首次会建立连接，后面并非建立一个新的连接，而是从连接池中取出一个已建立的空闲连接对象。
使用完毕后，用户也并非将连接关闭，而是将连接放回连接池中，以供下一个请求访问使用。而连接的建立、断开都由连接池自身来管理。

同时，还可以通过设置连接池的参数来控制连接池中的初始连接数、连接的上下限数以及每个连接的最大使用次数、最大空闲时间等等。
也可以通过其自身的管理机制来监视数据库连接的数量、使用情况等。超出最大连接数会采用协程挂起，等到有连接关闭再恢复协程继续操作。

## 特性

- 支持读写分离
- 支持数据库连接池，能够有效解决 PHP 带来的数据库连接瓶颈
- 支持 SQL92 标准
- 采用协程调度
- 支持多个数据库连接，多个数据库，多个用户，灵活搭配
- 遵守 MySQL 原生协议，跨语言，跨平台的通用中间件代理
- 支持 MySQL 事务
- 支持 HandshakeV10 协议版本
- 完美兼容 MySQL5.5 - 8.0
- 兼容各大框架，无缝提升性能

## 设计初衷

PHP 没有连接池，所以高并发时数据库会出现连接打满的情况，Mycat 等数据库中间件会出现部分 SQL 无法使用，例如不支持批量添加等，而且过于臃肿。
所以就自己编写了这个仅支持连接池和读写分离的轻量级中间件，使用 Swoole 协程调度 HandshakeV10 协议转发使程序更加稳定，不用像 Mycat 一样解析所有 SQL 包体，增加复杂度。

## 性能测试

请查阅 [docs/BENCHMARK.md](BENCHMARK.md)。

## 环境

- Swoole >= 2.1.3  ![swoole_version](https://img.shields.io/badge/swoole->=2.1.3-yellow.svg?style=popout-square)
- PHP >= 7.0     ![php_version](https://img.shields.io/badge/php->=7.0-blue.svg?style=popout-square)

## 安装

（推荐）直接下载最新发行版的 PHAR 文件，解压即用：

<https://github.com/louislivi/SMProxy/releases/latest>

或者使用 Git 切换任意版本：

```bash
git clone https://github.com/louislivi/SMProxy.git
composer install --no-dev # 如果你想贡献你的代码，请不要使用 --no-dev 参数。
```

## 运行

需要给予 bin/SMProxy 执行权限。

```bash
  SMProxy [ start | stop | restart | status | reload ] [ -c | --config <configuration_path> | --console ]
  SMProxy -h | --help
  SMProxy -v | --version
```

Options:
- start                            运行服务
- stop                             停止服务
- restart                          重启服务
- status                           查询服务运行状态
- reload                           平滑重启
- -h --help                        帮助
- -v --version                     查看当前服务版本
- -c --config <configuration_path> 设置配置项目录
- --console                        前台运行(SMProxy>=1.2.5)

## 配置

- 配置文件位于 `smproxy/conf` 目录中，其中大写 `ROOT` 代表当前 SMProxy 根目录。

### database.json
```json
{
  "database": {
    "account": {
      "自定义用户名": {
        "user": "必选，数据库账户",
        "password": "必选，数据库密码"
      },
      "...": "必选1个，自定义用户名 与serverInfo中的account相对应"
    },
    "serverInfo": {
      "自定义数据库连接信息": {
        "write": {
          "host": "必选，写库地址 多个用[]表示",
          "port": "必选，写库端口",
          "timeout": "必选，写库连接超时时间（秒）",
          "account": "必选，自定义用户名 与 account中的自定义用户名相对应"
        },
        "read": {
          "host": "可选，读库地址 多个用[]表示",
          "port": "可选，读库端口",
          "timeout": "可选，读库连接超时时间（秒）",
          "account": "可选，自定义用户名 与 account中的自定义用户名相对应"
        }
      },
      "...": "必选1个，自定义数据库连接信息 与databases中的serverInfo相对应,read读库可不配置"
    },
    "databases": {
      "数据库名称": {
        "serverInfo": "必选，自定义数据库连接信息 与serverInfo中的自定义数据库连接信息相对应",
        "maxConns": "必选，该库服务最大连接数，支持计算",
        "maxSpareConns": "必选，该库服务最大空闲连接数，支持计算",
        "startConns": "可选，该库服务默认启动连接数，支持计算",
        "maxSpareExp": "可选，该库服务空闲连接数最大空闲时间（秒），默认为0，支持计算",
        "charset": "可选，该库编码格式"
      },
      "...": "必选1个，数据库名称 多个数据库配置多个"
    }
  }
}
```
- `maxConns`,`maxSpareConns`,`startConns`
    - 推荐设置为`server.json`中配置的`worker_num`的倍数`swoole_cpu_num()*N`
- 多个读库，写库
    - 目前采取的是随机获取连接，推荐将`maxConns`，`startConns`，`startConns`至少设置为`max(读库,写库)*worker_num` 的1倍以上
- `timeout`
    - 设置2-5秒最佳。

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
      "pid_file": "必选，worker进程和manager进程pid目录",
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

### 在项目中如何进行配置
- Laravel
    - `.env`
    ```env
    DB_CONNECTION=mysql
    DB_HOST=server.json中配置的host
    DB_PORT=server.json中配置的port
    DB_DATABASE=databse.json中配置的数据库名称
    DB_USERNAME=server.json中配置的user
    DB_PASSWORD=server.json中配置的password
    ```

- ThinkPHP
    - `database.php`
    ```php
    'type' => 'mysql',
    // 服务器地址
    'hostname' => 'server.json中配置的host',
    // 数据库名
    'database' => 'databse.json中配置的数据库名称',
    // 用户名
    'username' => 'server.json中配置的user',
    // 密码
    'password' => 'server.json中配置的password',
    // 端口
    'hostport' => 'server.json中配置的port',
    ```
- 其他框架以此类推，只需要配置代码中连接数据库的`host`，`port`，`user`，`password`与 `SMProxy`中`server.json`中一致即可。

## 路由

### 注解
   - smproxy:db_type=[read | write]
        - 强制使用读库
        ```sql
        /** smproxy:db_type=read */select * from `user` limit 1
        ```
        - 强制使用写库
        ```sql
        /** smproxy:db_type=write */select * from `user` limit 1
        ```

## MySQL8.0

- `SMProxy1.2.4`及以上可直接使用
- `SMProxy1.2.4`以下需要做如下兼容处理：
    - `MySQL-8.0`默认使用了安全性更强的`caching_sha2_password`插件，其他版本如果是从`5.x`升级上来的, 可以直接使用所有`MySQL`功能, 如是新建的`MySQL`, 需要进入`MySQL`命令行执行以下操作来兼容:
    
    ```sql
    ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'password';
    flush privileges;
    ```
    将语句中的 `'root'@'%'` 替换成你所使用的用户, `password` 替换成其密码.
    
    如仍无法使用, 应在my.cnf中设置 `default_authentication_plugin = mysql_native_password`

## 常见问题
- `SMProxy@access denied for user` 
    - 请检查`serve.json`中的账号密码与业务代码中配置的是否一致。
    - 数据库`host`请勿配置`localhost`。
- `SMProxy@Database config dbname write is not exists! ` 
    - 请将`database.json`中的`dbname`项改为你的业务数据库名。
- `Config serverInfo->*->account is not exists! `
    - 请仔细比对`database.json`中`databse->serverInfo->*->*->account`是否在`database->account`下含有相对于的键值对。
- `Reach max connections! Cann't pending fetch!` 
    - 适当增加`maxSpareConns`或增加`database.json`中的`timeout`项。
- `Must be connected before sending data!` 
    - 检查`MySQL`是否有外网访问权限。
    - 检查`MySQL`验证插件是否为`mysql_native_password`或`caching_sha2_password`
    - 排查是否有服务冲突，推荐使用`Docker`进行运行排查环境问题。
- `Connection * waiting timeout`
    - 检查`MySQL`是否有外网访问权限。
    - 启动出现数据库连接超时请检查数据库配置，若正常请降低`startConns`或增加`database.json`中的`timeout`项。
- `The server is not running`
    - 查看`SMProxy`下的日志`mysql.log`和`system.log`。
    - 防止`SMProxy`异常退出，建议使用`Supervisor`或`docker`进行服务挂载。
- `Supervisor` || `docker`
    - 使用`Supervisor`和`docker`时需要使用前台运行模式(v1.2.5+使用`--console`,否则使用`daemonize`参数)否则无法正常启动。
- `502 Bad Gateway`
    - MySQL异常崩溃后连接出现502或连接超时，请不要开启长连接模式。
    - SQL语句过大不要使用连接池，会导致连接阻塞，程序异常。
- `启动SMProxy后CPU占用过高`
    - 因Swoole4.2.12及以下未开启协程Client读写分离所以CPU占比会比较大。
    - 升级Swoole版本到4.2.13及以上并升级SMProxy版本到1.2.8及以上。

## 交流

QQ群：722124111

## 捐赠SMProxy项目

您的捐赠是对SMProxy项目开发组最大的鼓励和支持。我们会坚持开发维护下去。 您的捐赠将被用于：
- 持续和深入地开发
- 文档和社区的建设和维护

### 捐赠方式
[开源中国-捐赠SMProxy项目](https://gitee.com/louislivi/smproxy?donate=true)

## 其他学习资料

- MySQL协议分析 ：<https://www.cnblogs.com/davygeek/p/5647175.html>
- MySQL官方协议文档 ：<https://dev.MySQL.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake>
- Mycat源码 ：<https://github.com/MyCATApache/Mycat-Server>
- Swoole ：<https://www.swoole.com/>
