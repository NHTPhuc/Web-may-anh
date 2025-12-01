<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kiểm tra quyền admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này']);
    exit;
}

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Lấy thông tin
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Kiểm tra dữ liệu
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Đơn hàng không hợp lệ']);
    exit;
}

$valid_statuses = ['pending', 'processing', 'shipping', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

// Kiểm tra đơn hàng tồn tại
$order = get_order_by_id($order_id);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

// Cập nhật trạng thái đơn hàng
$result = db_update('orders', [
    'status' => $status
], "id = $order_id");

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái đơn hàng thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
}
?>
