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
$dbname = "";
$port   = 3366;

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname, $port);
// Check connection
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

$sql = "SELECT * FROM `mysql`.`user`";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // 输出数据
    while($row = $result->fetch_assoc()) {
        var_dump($row);
    }
} else {
    echo "0 结果";
}
$conn->close();
?>