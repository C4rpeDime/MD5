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

// 检查表是否存在，如果不存在则创建
$tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->num_rows > 0;
if (!$tableExists) {
    $createTableSql = "CREATE TABLE $tableName (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        hash VARCHAR(32) NOT NULL,
        original VARCHAR(255) NOT NULL,
        UNIQUE KEY unique_hash (hash)
    )";
    if ($conn->query($createTableSql) === TRUE) {
        echo "表 $tableName 创建成功<br>";
    } else {
        die("创建表错误: " . $conn->error);
    }
}

function generateRainbowTable($charset, $batchSize, $conn, $tableName) {
    $charsetLength = strlen($charset);
    $count = 0;
    $maxLength = 10; // 将最大长度设置为8

    while ($count < $batchSize) {
        $length = rand(1, $maxLength); // 随机选择长度
        $count += generateRandomCombination($charset, $length, $charsetLength, $conn, $batchSize - $count, $tableName);
    }

    echo "生成了 $count 条记录\n";
}

function generateRandomCombination($charset, $length, $charsetLength, $conn, $remaining, $tableName) {
    if ($remaining <= 0) return 0;

    $currentString = '';
    for ($i = 0; $i < $length; $i++) {
        $currentString .= $charset[rand(0, $charsetLength - 1)]; // 随机选择字符
    }

    $hash = md5($currentString);

    // 使用 INSERT IGNORE 避免重复
    $stmt = $conn->prepare("INSERT IGNORE INTO $tableName (hash, original) VALUES (?, ?)");
    $stmt->bind_param("ss", $hash, $currentString);
    $stmt->execute();

    return $stmt->affected_rows > 0 ? 1 : 0; // 只有在成功插入时才计数
}

// 配置
$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.@#*?!$%^&()-_=+[]{}|;:,.<>~'; // 包含小写字母、大写字母、数字和常用符号
$batchSize = 10000; // 每次生成1万条

generateRainbowTable($charset, $batchSize, $conn, $tableName);

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MD5 彩虹表生成器</title>
    <script>
        // 页面加载完成后自动刷新
        window.onload = function() {
            setTimeout(function() {
                location.reload();
            }, 3000); // 1秒后刷新页面
        };
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">MD5 彩虹表生成器</h1>
        <p class="text-center">每生成100000条记录后自动刷新页面。</p>
    </div>
</body>
</html>