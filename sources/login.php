<?php
// Start the session
session_start();

require_once 'models/UserModel.php';
require_once 'configs/redis.php';
$userModel = new UserModel();

// Nếu là request Ajax (fetch) → chỉ trả JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['submit'])) {
    header("Content-Type: application/json; charset=UTF-8");

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($user = $userModel->auth($username, $password)) {
        // Tạo token ngẫu nhiên
        $token = bin2hex(random_bytes(32));

        // Lưu vào Redis với thời hạn 20 phút
        $redis_key = 'user' . $user[0]['id'];
        $redis->set("auth_token:$token", $user[0]['id']);
        $redis->expire("auth_token:$token", 1200);

        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Login failed'
        ]);
    }
    exit; // dừng ở đây, không render HTML nữa
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>User form</title>
    <?php include 'views/meta.php'; ?>
</head>

<body>
    <?php include 'views/header.php'; ?>

    <div class="container">
        <div id="loginbox" style="margin-top:50px;"
            class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">Login</div>
                </div>
                <div style="padding-top:30px" class="panel-body">
                    <form id="loginForm" class="form-horizontal" role="form">
                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon">
                                <i class="glyphicon glyphicon-user"></i>
                            </span>
                            <input id="login-username" type="text" class="form-control"
                                name="username" placeholder="username or email" required>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon">
                                <i class="glyphicon glyphicon-lock"></i>
                            </span>
                            <input id="login-password" type="password" class="form-control"
                                name="password" placeholder="password" required>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <div class="col-sm-12 controls">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch("login.php", {
                    method: "POST",
                    body: formData,
                });

                const result = await response.json();

                if (result.status === "success") {
                    // Lưu token vào localStorage
                    localStorage.setItem("auth_token", result.token);
                    window.location.href = "list_users.php";
                } else {
                    alert(result.message || "Sai username hoặc password!");
                }
            } catch (err) {
                console.error("Lỗi:", err);
                alert("Có lỗi kết nối server!");
            }
        });
    </script>
</body>

</html>