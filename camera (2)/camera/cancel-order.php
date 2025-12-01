<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/order_functions.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    set_flash_message('error', 'Bạn cần đăng nhập để thực hiện chức năng này.');
    redirect(SITE_URL . '/login.php');
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'Không tìm thấy đơn hàng.');
    redirect(SITE_URL . '/orders.php');
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$order = get_order_by_id($order_id);

// Kiểm tra đơn hàng có thuộc về người dùng hiện tại không và có thể hủy không
if (!$order || $order['user_id'] != $user_id || $order['status'] != 'pending') {
    set_flash_message('error', 'Không thể hủy đơn hàng này.');
    redirect(SITE_URL . '/orders.php');
}

// Sử dụng hàm cancel_order để hủy đơn hàng
$update_result = cancel_order($order_id, $user_id);

if ($update_result) {
    set_flash_message('success', 'Đơn hàng #' . $order_id . ' đã được hủy thành công.');
} else {
    set_flash_message('error', 'Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại sau.');
}

// Chuyển hướng về trang chi tiết đơn hàng
redirect(SITE_URL . '/order-detail.php?id=' . $order_id);
?>
