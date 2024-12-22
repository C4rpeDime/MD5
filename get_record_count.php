<?php
$servername = "localhost";
$username = "md5";
$password = "123456";
$dbname = "md5";
$tableName = "rainbow_table";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 获取记录总数
$totalRecords = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM $tableName");
if ($result) {
    $row = $result->fetch_assoc();
    $totalRecords = $row['count'];
}

echo $totalRecords;

$conn->close();
?>