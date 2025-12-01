<?php
/**
 * Các hàm tiện ích cho trang quản trị
 */

// Kiểm tra đăng nhập admin
function check_admin_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
        header("Location: login.php");
        exit;
    }
}

// Lấy thông tin người dùng hiện tại
function get_current_admin() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    return db_fetch_one("SELECT * FROM users WHERE id = $user_id");
}

// Chuyển hướng
function admin_redirect($url) {
    header("Location: $url");
    exit;
}

// Hiển thị thông báo
function set_admin_message($type, $message) {
    $_SESSION['admin_message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Lấy thông báo
function get_admin_message() {
    if (isset($_SESSION['admin_message'])) {
        $message = $_SESSION['admin_message'];
        unset($_SESSION['admin_message']);
        return $message;
    }
    
    return null;
}

// Hiển thị thông báo
function display_admin_message() {
    $message = get_admin_message();
    
    if ($message) {
        $type = $message['type'];
        $text = $message['text'];
        
        echo "<div class='alert alert-$type'>$text</div>";
    }
}

// Kiểm tra bảng có tồn tại không
function table_exists($table_name) {
    $conn = db_connect();
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

// Kiểm tra cột có tồn tại trong bảng không
function column_exists($table_name, $column_name) {
    $conn = db_connect();
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    return mysqli_num_rows($result) > 0;
}

// Lấy cấu trúc bảng
function get_table_structure($table_name) {
    $conn = db_connect();
    $result = mysqli_query($conn, "DESCRIBE $table_name");
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[$row['Field']] = $row['Type'];
    }
    
    return $columns;
}

// Lấy danh sách bảng trong database
function get_all_tables() {
    $conn = db_connect();
    $result = mysqli_query($conn, "SHOW TABLES");
    
    $tables = [];
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    
    return $tables;
}

// Lấy số bản ghi trong bảng
function get_table_count($table_name) {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT COUNT(*) FROM $table_name");
    $row = mysqli_fetch_array($result);
    return $row[0];
}
?>
