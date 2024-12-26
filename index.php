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

$searchResult = '';
$resultVisible = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hash = $_POST['hash'];
    if (strlen($hash) == 16) {
        // 如果输入的是16位MD5，扩展为32位的中间部分
        $stmt = $conn->prepare("SELECT original FROM $tableName WHERE hash LIKE CONCAT('%', ?, '%')");
    } else {
        // 否则按32位MD5处理
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

$conn->close();
?>

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
