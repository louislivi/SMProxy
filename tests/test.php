<?php
/**
 * Created by PhpStorm.
 * User: louislivi
 * Date: 2018-12-09
 * Time: 10:10
 */
$servername = "127.0.0.1";
$username = "root";
$password = "123456";
$dbname = "mysql";
$port   = 3366;
try {
    // 创建连接
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    // Check connection
    if ($conn->connect_error) {
        fwrite(STDERR, "连接失败: " . $conn->connect_error);
    }

    $sql = "SELECT `Host`,`User`,`Plugin` FROM `mysql`.`user` WHERE Host = '%' limit 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // 输出数据
        while($row = $result->fetch_assoc()) {
            fwrite(STDOUT, "SUCCESS:[Host: " . $row["Host"]. " - User: " . $row["User"]. " - Plugin: " . $row["Plugin"] . ']' . PHP_EOL);
        }
    } else {
        fwrite(STDERR, "0 结果");
    }
    $conn->close();
} catch (\Exception $exception) {
    fwrite(STDERR, $exception ->getMessage());
}
?>