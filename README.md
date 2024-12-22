在这篇文档中，我将详细介绍如何开发一款MD5解密平台。这个平台的核心功能是生成和查询MD5彩虹表。以下是对`index.php`和`chaxun.php`文件的详细拆解和说明。

## 环境准备

确保开发环境的搭建，包括：

- **PHP 7.x或更高版本**
- **MySQL 5.x或更高版本**
- **Apache或Nginx服务器**

## 代码详解

### 数据库连接
```php
$servername = "localhost";
$username = "md5";
$password = "123456";
$dbname = "md5";
$tableName = "rainbow_table";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
die("连接失败: " . $conn->connect_error);
}

```
- **数据库参数**：定义数据库连接所需的参数。
- **创建连接**：使用`mysqli`创建与数据库的连接。
- **检查连接**：如果连接失败，输出错误信息并终止脚本。

### 创建数据表
```php
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

```
- **检查表是否存在**：使用`SHOW TABLES`查询表是否已存在。
- **创建表**：如果表不存在，创建一个新的表`rainbow_table`。
  - **字段定义**：
    - `id`：自增主键。
    - `hash`：存储MD5哈希值。
    - `original`：存储原文。
  - **唯一约束**：确保`hash`字段的唯一性。

### 生成彩虹表
```php
function generateRainbowTable($charset, $batchSize, $conn, $tableName) {
$charsetLength = strlen($charset);
$count = 0;
$maxLength = 8;
for ($length = 1; $length <= $maxLength; $length++) {
if ($count >= $batchSize) break;
$count += generateCombinations($charset, '', $length, $charsetLength, $conn, $batchSize - $count, $tableName);
}
echo "生成了 $count 条记录\n";
}
```

- **字符集**：定义用于生成组合的字符集。
- **递归生成**：通过递归生成所有可能的字符串组合。
- **批量生成**：限制每次生成的记录数量。

### 生成组合
```php
function generateCombinations($charset, $currentString, $maxLength, $charsetLength, $conn, $remaining, $tableName) {
if (strlen($currentString) == $maxLength) {
if ($remaining <= 0) return 0;
$hash = md5($currentString);
$stmt = $conn->prepare("INSERT IGNORE INTO $tableName (hash, original) VALUES (?, ?)");
$stmt->bind_param("ss", $hash, $currentString);
$stmt->execute();
return 1;
}
$count = 0;
for ($i = 0; $i < $charsetLength; $i++) {
if ($count >= $remaining) break;
$count += generateCombinations($charset, $currentString . $charset[$i], $maxLength, $charsetLength, $conn, $remaining - $count, $tableName);
}
return $count;
}
```
- **递归终止条件**：当生成的字符串达到最大长度时，计算其MD5哈希值并插入数据库。
- **插入数据**：使用`INSERT IGNORE`避免重复插入。

### 配置和执行
```php
$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.@#?!$%^&()-=+[]{}|;:,.<>~';
$batchSize = 10000;
generateRainbowTable($charset, $batchSize, $conn, $tableName);
$conn->close();
```

- **字符集和批量大小**：定义生成组合的字符集和每次生成的记录数量。
- **执行生成**：调用`generateRainbowTable`函数生成彩虹表。
- **关闭连接**：完成后关闭数据库连接。

### 前端界面
```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MD5 彩虹表生成器</title>
<script>
window.onload = function() {
setTimeout(function() {
location.reload();
}, 1000);
};
</script>
</head>
<body>
<div class="container mt-5">
<h1 class="text-center">MD5 彩虹表生成器</h1>
<p class="text-center">每生成1万条记录后自动刷新页面。</p>
</div>
</body>
</html>
```
![微信图片_20241222151542.png](https://www.1042.net/usr/uploads/2024/12/3099008077.png)
- **自动刷新**：页面每秒刷新一次，以便持续生成记录。
- **Bootstrap样式**：使用Bootstrap框架美化界面。

## 查询功能实现

在`chaxun.php`中，我实现了MD5哈希值的查询功能。

### 查询逻辑
```php
$searchResult = '';
$resultVisible = false;
if ($SERVER["REQUEST_METHOD"] == "POST") {
$hash = $POST['hash'];
if (strlen($hash) == 16) {
$stmt = $conn->prepare("SELECT original FROM $tableName WHERE hash LIKE CONCAT('%', ?, '%')");
} else {
$stmt = $conn->prepare("SELECT original FROM $tableName WHERE hash = ?");
}
$stmt->bind_param("s", $hash);
$stmt->execute();
$stmt->bind_result($original);
if ($stmt->fetch()) {
$searchResult = "<strong>MD5:</strong> " . htmlspecialchars($hash) . "<br><strong>原文:</strong> " . htmlspecialchars($original);
} else {
$searchResult = "未找到匹配的原文。";
}
$resultVisible = true;
$stmt->close();
}
```
- **处理用户输入**：根据输入的MD5长度选择查询方式。
- **查询数据库**：使用预处理语句防止SQL注入。
- **显示结果**：根据查询结果显示原文或提示未找到。

### 前端界面
```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MD5 查询</title>
<link href="https://cdn.staticfile.net/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateRecordCount() {
$.ajax({
url: 'get_record_count.php',
success: function(data) {
document.getElementById('recordCount').innerText = data;
}
});
}
let countdown = 60;
function updateCountdown() {
document.getElementById('countdown').innerText = countdown;
if (countdown === 0) {
updateRecordCount();
countdown = 60;
} else {
countdown--;
}
setTimeout(updateCountdown, 1000);
}
window.onload = updateCountdown;
</script>
</head>
<body>
<div class="container mt-5">
<h1 class="text-center mb-4">MD5 查询</h1>
<p class="text-center">当前数据库中有 <strong id="recordCount"><?php echo $totalRecords; ?></strong> 条记录。记录将在 <strong id="countdown">60</strong> 秒后更新。</p>
<div class="row justify-content-center">
<div class="col-md-6">
<form method="post">
<div class="input-group mb-3">
<input type="text" class="form-control" id="hash" name="hash" placeholder="请输入 MD5 哈希值" required>
<div class="input-group-append">
<button class="btn btn-primary" type="submit">查询</button>
</div>
</div>
</form>
<?php if ($resultVisible): ?>
<div class="mt-3">
<div class="alert alert-info" role="alert">
<?php echo $searchResult; ?>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>
</body>
</html>
```
![微信图片_20241222151533.png](https://www.1042.net/usr/uploads/2024/12/1496156586.png)
- **输入框和按钮**：用户可以输入MD5哈希值并提交查询。
- **记录计数**：通过AJAX定期更新数据库中的记录总数。

## 总结

通过以上步骤，我成功开发了一款简单的MD5解密平台。这个平台可以生成大量的MD5哈希值及其对应的原文，并提供快速查询功能。
![微信图片_20241222151803.png](https://www.1042.net/usr/uploads/2024/12/2766160643.png)
