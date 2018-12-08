## SMProxy Benchmark

Benchmark for SMProxy is easily to execute. Connect and test it just like MySQL.

It is recommended to test with the command line first (SMProxy<1.2.5 Do not use MySQL 8.0):

```
mysql -uroot -p123456 -P3366 -h127.0.0.1
```

Or connect with any GUI tool.

### PHP 7.2.6 Without Framework

![php7.2.6](https://file.gesmen.com.cn/smproxy/1542782011408.jpg)

Native:0.15148401260376, With SMProxy:0.040808916091919

Native:
 
![ab](https://file.gesmen.com.cn/smproxy/1542782075073.jpg)

With SMProxy:

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

For more information, please visit [Benchmark result in Chinese](./BENCHMARK.md)