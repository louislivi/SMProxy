中文 | [English](./README-EN.md)
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
  - [原理](#%E5%8E%9F%E7%90%86)
  - [特性](#%E7%89%B9%E6%80%A7)
  - [设计初衷](#%E8%AE%BE%E8%AE%A1%E5%88%9D%E8%A1%B7)
  - [环境](#%E7%8E%AF%E5%A2%83)
  - [安装](#%E5%AE%89%E8%A3%85)
  - [运行](#%E8%BF%90%E8%A1%8C)
  - [SMProxy连接测试](#smproxy%E8%BF%9E%E6%8E%A5%E6%B5%8B%E8%AF%95)
    - [没用框架的 PHP 7.2.6](#%E6%B2%A1%E7%94%A8%E6%A1%86%E6%9E%B6%E7%9A%84-php-726)
    - [ThinkPHP 5.0](#thinkphp-50)
    - [Laravel 5.7](#laravel-57)
    - [MySQL 连接数](#mysql-%E8%BF%9E%E6%8E%A5%E6%95%B0)
  - [交流](#%E4%BA%A4%E6%B5%81)
  - [配置文件](#%E9%85%8D%E7%BD%AE%E6%96%87%E4%BB%B6)
    - [database.json](#databasejson)
    - [server.json](#serverjson)
  - [其他学习资料](#%E5%85%B6%E4%BB%96%E5%AD%A6%E4%B9%A0%E8%B5%84%E6%96%99)

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
- 支持 MySQL 事物
- 支持 HandshakeV10 协议版本
- 完美兼容 MySQL4.1 - 5.7
- 兼容各大框架，无缝提升性能

## 设计初衷

PHP 没有连接池，所以高并发时数据库会出现连接打满的情况，Mycat 等数据库中间件会出现部分 SQL 无法使用，例如不支持批量添加等，而且过于臃肿。
所以就自己编写了这个仅支持连接池和读写分离的轻量级中间件，使用 Swoole 协程调度 HandshakeV10 协议转发使程序更加稳定，不用像 Mycat 一样解析所有 SQL 包体，增加复杂度。

## 环境

- Swoole 2.1+  ![swoole_version](https://img.shields.io/badge/swoole-2.1+-yellow.svg?style=popout-square)
- PHP 7.0+     ![php_version](https://img.shields.io/badge/php-7.0+-blue.svg?style=popout-square)

## 安装

（推荐）直接下载最新发行版的 PHAR 文件，解压即用：

<https://github.com/louislivi/smproxy/releases/latest>

或者使用 Git 切换任意版本：

```bash
git clone https://github.com/louislivi/smproxy.git
composer install --no-dev # 如果你想贡献你的代码，请不要使用 --no-dev 参数。
```

## 运行

需要给予 bin/server 执行权限。

- `bin/server start`   : 运行服务
- `bin/server stop`    : 停止服务
- `bin/server restart` : 重启服务
- `bin/server status`  : 查询服务运行状态
- `bin/server reload`  : 平滑重启
- `bin/server -h`      : 帮助
- `bin/server -v`      : 查看当前服务版本
- `bin/server -c`      : 设置配置项目录

## SMProxy连接测试

测试SMProxy与测试MySQL完全一致，MySQL怎么连接，SMProxy就怎么连接。

推荐先采用命令行测试：

```
mysql -uroot -p123456 -P3366 -h127.0.0.1
```

也可采用工具连接。

### 没用框架的 PHP 7.2.6

![PHP7.2.6](https://file.gesmen.com.cn/smproxy/1542782011408.jpg)

没用：0.15148401260376,用了：0.040808916091919

未使用连接池: 0.15148401260376

![ab](https://file.gesmen.com.cn/smproxy/1542782075073.jpg)

使用连接池: 0.040808916091919

![ab](https://file.gesmen.com.cn/smproxy/1542782043730.jpg)

### ThinkPHP 5.0

![ThinkPHP5](https://file.gesmen.com.cn/smproxy/8604B3D4-0AB0-4772-83E0-EEDA6B86F065.png)

未使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542685140126.jpg)

使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542685109798.jpg)

### Laravel 5.7

![Laravel5.7](https://file.gesmen.com.cn/smproxy/3FE76B55-9422-40DB-B8CE-7024F36BB5A9.png)

未使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542686575874.jpg)

使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542686580551.jpg)

### MySQL 连接数

未使用连接池:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625044913.jpg)

使用连接池:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625037536.jpg)

请以实际压测为准，根数据量，网络环境，数据库配置有关。
测试中因超出最大连接数会采用协程挂起 等到有连接关闭再恢复协程继续操作，
所有并发量与配置文件maxConns设置的不合适，会导致比原链接慢，主要是为了控制连接数。

## 交流

QQ群：722124111

## 配置文件

配置文件位于 `smproxy/conf` 目录中，其中大写 `ROOT` 代表当前 SMProxy 根目录。

### database.json

| account 账号信息                                        | serverInfo 服务信息                                        | databases 数据库连接池信息              |
| ------------------------------------------------------ | ---------------------------------------------------------- | --------------------------------------- |
| account.root 用户标识 与 serverInfo...account.root 对应  | serverInfo.server1 服务标识 与  databases..serverInfo 对应 | databases.dbname 数据库名称             |
| account..user 用户名                                    | serverInfo..write 读写分离 write 写库 read 读库            | databases..serverInfo 服务信息          |
| account..password 密码                                 | serverInfo..host 数据库连接地址\[数组:多读多写\]             | databases..maxSpareConns 最大空闲连接数  |
|                                                       | serverInfo..prot 数据库端口                                | databases..maxConns 最大连接数          |
|                                                       | serverInfo..timeout 数据库超时时长(秒)                      | databases..charset 数据库编码格式        |
|                                                       | serverInfo..account  与 databases.account 对应            | databases..maxSpareExp 最大空闲时间      |
|                                                       |                                                          |  databases..startConns 服务启动连接数     |

### server.json

| user 服务用户名 | password 服务密码 | charset 服务编码 | host 链接地址 | port 服务端口 多个以,隔开 | mode 运行模式                                         | sock_type SWOOLE_SOCK_TCP tcp | logs 日志配置                 | swoole swoole配置                  | swoole_client_setting 客户端配置 | swoole_client_sock_setting 客户端sock配置               |
| --------------- | ----------------- | ---------------- | ------------- | ------------------------- | ----------------------------------------------------- | ----------------------------- | ----------------------------- | ---------------------------------- | -------------------------------- | ------------------------------------------------------- |
|                 |                   |                  |               |                           | SWOOLE_PROCESS多进程模式（默认），SWOOLE_BASE基本模式 |                               | logs.open 日志开关            | worker_num work进程数量            | package_max_length 最大包长      | sock_type SWOOLE_SOCK_TCP tcp                           |
|                 |                   |                  |               |                           |                                                       |                               | logs.config 日志配置项        | max_coro_num 最大携程数            |                                  | sync_type SWOOLE_SOCK_SYNC 同步，SWOOLE_SOCK_ASYNC 异步 |
|                 |                   |                  |               |                           |                                                       |                               | logs.system or MySQL 配置模块 | open_tcp_nodelay 关闭Nagle合并算法 |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               | logs..log_path 日志目录       | daemonize 守护进程化               |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               | logs..log_file 日志文件名     | heartbeat_check_interval 心跳检测  |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               | logs..format 日志日期格式     | heartbeat_idle_time 最大空闲时间   |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               |                               | reload_async 异步重启              |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               |                               | log_file 日志目录                  |                                  |                                                         |
|                 |                   |                  |               |                           |                                                       |                               |                               | pid_file 主进程pid目录             |                                  |                                                         |

## 其他学习资料

- MySQL协议分析 ：<https://www.cnblogs.com/davygeek/p/5647175.html>
- MySQL官方协议文档 ：<https://dev.MySQL.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake>
- Mycat源码 ：<https://github.com/MyCATApache/Mycat-Server>
- Swoole ：<https://www.swoole.com/>
