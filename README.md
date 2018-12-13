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
# [SMProxy](http://smproxy.louislivi.com)

[![release](https://img.shields.io/github/release/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/releases)
[![forks](https://img.shields.io/github/forks/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/network/members)
[![stars](https://img.shields.io/github/stars/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/stargazers)
[![Build Status](https://img.shields.io/travis/com/louislivi/SMProxy.svg?style=popout-square)](https://travis-ci.com/louislivi/SMProxy)
[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
[![license](https://img.shields.io/github/license/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/blob/master/LICENSE)
[![SMProxy](https://img.shields.io/badge/SMProxy-%F0%9F%92%97-pink.svg?style=popout-square)](https://github.com/louislivi/SMProxy)

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
- 完美兼容 MySQL4.1 - 8.0
- 兼容各大框架，无缝提升性能

## 设计初衷

PHP 没有连接池，所以高并发时数据库会出现连接打满的情况，Mycat 等数据库中间件会出现部分 SQL 无法使用，例如不支持批量添加等，而且过于臃肿。
所以就自己编写了这个仅支持连接池和读写分离的轻量级中间件，使用 Swoole 协程调度 HandshakeV10 协议转发使程序更加稳定，不用像 Mycat 一样解析所有 SQL 包体，增加复杂度。

## 开发与讨论
- 文档：http://smproxy.louislivi.com
- QQ群：722124111