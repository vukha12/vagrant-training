<?php
// login.php

session_start();
require_once 'models/UserModel.php';
require_once 'configs/redis.php';
$userModel = new UserModel();

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self';");

// If someone put credentials in query string, remove them and redirect to clean URL
if (!empty($_GET['username']) || !empty($_GET['password']) || !empty($_GET['token'])) {
    // Log if you want: error_log("Suspicious GET login attempt from {$_SERVER['REMOTE_ADDR']}");
    $clean = strtok($_SERVER['REQUEST_URI'], '?'); // path without query
    header("Location: {$clean}");
    exit;
}

// Allow only POST to perform authentication actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expect AJAX request returning JSON
    header("Content-Type: application/json; charset=UTF-8");

    // Read only from $_POST (never from $_REQUEST)
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic server-side validation
    if ($username === '') {
        echo json_encode(['status' => 'error', 'message' => 'Username is required']);
        exit;
    }
    $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
    if (!$isEmail && !preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
        echo json_encode(['status' => 'error', 'message' => 'Username must be email or 3-30 chars (letters,numbers,._-)']);
        exit;
    }
    if ($password === '' || strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password must have at least 6 characters']);
        exit;
    }

    // Authenticate - UserModel::auth should use prepared statements
    if ($user = $userModel->auth($username, $password)) {
        $token = bin2hex(random_bytes(32));
        unset($user[0]['password']);
        $redis->set("auth_token:$token", json_encode($user[0]));
        $redis->expire("auth_token:$token", 1200);
        session_regenerate_id(true);

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        setcookie(
            "auth_token",
            $token,
            [
                "expires" => time() + 1200,
                "path" => "/",
                "domain" => "",
                "secure" => $isHttps,
                "httponly" => true,
                "samesite" => "Strict"
            ]
        );

        // IMPORTANT: do NOT return token in JSON. Client receives only success message.
        echo json_encode(['status' => 'success', 'message' => 'Login successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Login failed']);
    }
    exit;
}

// For GET: just render the login page (no sensitive info ever echoed)
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
                    <!-- method="post" để fallback khi JS bị tắt -->
                    <form id="loginForm" method="post" action="login.php" class="form-horizontal" role="form" autocomplete="off">
                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="login-username" type="text" class="form-control" name="username"
                                placeholder="username or email" required>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="login-password" type="password" class="form-control" name="password"
                                placeholder="password" required>
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
            // prevent default so normal form POST won't leak credentials in URL
            e.preventDefault();

            const username = document.getElementById("login-username").value.trim();
            const password = document.getElementById("login-password").value.trim();

            if (!username) {
                alert("Username không được để trống!");
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const userRegex = /^[a-zA-Z0-9._-]{3,30}$/;
            if (!emailRegex.test(username) && !userRegex.test(username)) {
                alert("Username phải là email hoặc 3-30 ký tự (chỉ gồm chữ, số, ., _, -)");
                return;
            }
            if (!password || password.length < 6) {
                alert("Password phải có ít nhất 6 ký tự!");
                return;
            }

            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (result.status === 'success') {
                    // server set HttpOnly cookie; just redirect
                    window.location.href = 'list_users.php';
                } else {
                    alert(result.message || "Sai username hoặc password!");
                }
            } catch (err) {
                console.error(err);
                alert("Có lỗi kết nối server!");
            }
        });
    </script>
</body>

</html>