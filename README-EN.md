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
# [SMProxy](http://smproxy.louislivi.com/#/en/)

[![release](https://img.shields.io/github/release/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/releases)
[![forks](https://img.shields.io/github/forks/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/network/members)
[![stars](https://img.shields.io/github/stars/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/stargazers)
[![Build Status](https://img.shields.io/travis/com/louislivi/SMProxy.svg?style=popout-square)](https://travis-ci.com/louislivi/SMProxy)
[![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
[![license](https://img.shields.io/github/license/louislivi/SMProxy.svg?style=popout-square)](https://github.com/louislivi/SMProxy/blob/master/LICENSE)
[![SMProxy](https://img.shields.io/badge/SMProxy-%F0%9F%92%97-pink.svg?style=popout-square)](https://github.com/louislivi/SMProxy)

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

For early design reasons, PHP does not have a native connection pool. So the number of database connections will be easily increasing and reaching the maximum when we got lots of requests. 
Using one of many database middlewares like Mycat will cause some limitations, e.g. batch inserts. And it's also too heavy in most cases. 
So we created SMProxy using 100% PHP + Swoole, which only supports connection pool and read/write separation, but much more lightweight. 
Not like Mycat, we're trying to build SMProxy with Swoole Coroutine to schedule HandshakeV10 packet forwarding, so we don't have to parse all SQL packets. 
That really makes SMProxy more stable and reliable.

## Contributing & Discussing

- Documentation: http://smproxy.louislivi.com/#/en/
- Community: [![Gitter](https://img.shields.io/gitter/room/louislivi/SMproxy.svg?style=popout-square)](https://gitter.im/louislivi/SMproxy)
