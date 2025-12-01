<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

// Kết nối database
$conn = db_connect();

// Kiểm tra xem cột image đã tồn tại trong bảng categories chưa
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'image'");

if (mysqli_num_rows($check_column) == 0) {
    // Thêm cột image vào bảng categories
    $alter_table_sql = "ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description";
    
    if (mysqli_query($conn, $alter_table_sql)) {
        echo "Đã thêm cột image vào bảng categories thành công!";
    } else {
        echo "Lỗi khi thêm cột image: " . mysqli_error($conn);
    }
} else {
    echo "Cột image đã tồn tại trong bảng categories.";
}

// Đóng kết nối
mysqli_close($conn);
?>
