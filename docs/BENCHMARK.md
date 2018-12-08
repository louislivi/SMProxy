# SMProxy 连接测试

测试SMProxy与测试MySQL完全一致，MySQL怎么连接，SMProxy就怎么连接。

推荐先采用命令行测试：
(SMProxy<1.2.5 请勿使用MYSQL8.0客户端链接测试)

```
mysql -uroot -p123456 -P3366 -h127.0.0.1
```

也可采用工具连接。

## 没用框架的 PHP 7.2.6

![PHP7.2.6](https://file.gesmen.com.cn/smproxy/1542782011408.jpg)

没用：0.15148401260376, 用了：0.040808916091919

未使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542782075073.jpg)

使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542782043730.jpg)

## ThinkPHP 5.0

![ThinkPHP5](https://file.gesmen.com.cn/smproxy/8604B3D4-0AB0-4772-83E0-EEDA6B86F065.png)

未使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542685140126.jpg)

使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542685109798.jpg)

## Laravel 5.7

![Laravel5.7](https://file.gesmen.com.cn/smproxy/3FE76B55-9422-40DB-B8CE-7024F36BB5A9.png)

未使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542686575874.jpg)

使用连接池:

![ab](https://file.gesmen.com.cn/smproxy/1542686580551.jpg)

## MySQL 连接数

未使用连接池:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625044913.jpg)

使用连接池:

![MySQL](https://file.gesmen.com.cn/smproxy/1542625037536.jpg)

请以实际压测为准，根数据量，网络环境，数据库配置有关。
测试中因超出最大连接数会采用协程挂起 等到有连接关闭再恢复协程继续操作，
所有并发量与配置文件maxConns设置的不合适，会导致比原链接慢，主要是为了控制连接数。
