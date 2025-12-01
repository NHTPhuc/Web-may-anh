<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Lấy email
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Kiểm tra email
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
    exit;
}

if (!is_valid_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

// Kiểm tra email đã tồn tại chưa
$check_email = db_fetch_one("SELECT id FROM newsletters WHERE email = '" . db_escape($email) . "'");

if ($check_email) {
    echo json_encode(['success' => false, 'message' => 'Email này đã đăng ký nhận tin']);
    exit;
}

// Thêm email vào database
$result = db_insert('newsletters', [
    'email' => $email,
    'status' => 'active'
]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Đăng ký nhận tin thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
}
?>
