<?php
session_start();
session_destroy();

require_once 'configs/redis.php';

$token = $_POST['token'] ?? '';
if (!empty($token)) {
    $redis->del("auth_token:$token");
}

// Xuất HTML chứa JS để xóa localStorage
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Logout</title>
</head>

<body>
    <script>
        // Xóa token ở localStorage
        localStorage.removeItem("auth_token");

        // Chuyển hướng về login.php
        window.location.href = "login.php";
    </script>
</body>

</html>