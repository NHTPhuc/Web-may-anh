<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once 'functions.php';

// Kiểm tra đăng nhập admin
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    // Đã đăng nhập và là admin, chuyển hướng đến dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // Chưa đăng nhập hoặc không phải admin, chuyển hướng đến trang đăng nhập admin
    header("Location: login.php");
    exit;
}
?>
