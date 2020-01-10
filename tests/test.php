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
$dsn = "mysql:host=$servername;dbname=$dbname;port=$port";			//设置服务器地址、数据库名称
try {
    //初始化一个PDO对象
    $conn = new \PDO($dsn, $username, $password);
    fwrite(STDOUT, 'Connect succeeded!' . PHP_EOL);
    $sql = "SELECT `Host`,`User`,`Plugin` FROM `mysql`.`user` limit 1";
    $result = $conn->query($sql);
    fwrite(STDOUT, 'Executed query:' . $sql . PHP_EOL);
    if ($result->rowCount()) {
        fwrite(STDOUT,  'Result: ' . json_encode($result->fetch()) . PHP_EOL);
    } else {
        fwrite(STDERR, "Result empty!" . PHP_EOL);
    }
    unset($conn);
} catch (\Exception $exception) {
    fwrite(STDERR, $exception ->getMessage());
}
?>
