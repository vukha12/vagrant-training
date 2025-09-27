<?php
// vulnerable.php
$conn = new mysqli('127.0.0.1', 'root', '', 'test_db');
$user = $_POST['user_id'];
$pass = $_POST['password'];
$sql = "SELECT * FROM Users WHERE user_id = '$user' AND password = '$pass'";
$res = $conn->query($sql);
