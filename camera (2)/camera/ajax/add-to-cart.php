<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Lấy thông tin sản phẩm
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Kiểm tra dữ liệu
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

// Lấy thông tin sản phẩm
$product = get_product_by_id($product_id);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    exit;
}

// Kiểm tra số lượng tồn kho
if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
    exit;
}

// Thêm sản phẩm vào giỏ hàng
if (add_to_cart($product_id, $quantity)) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm sản phẩm vào giỏ hàng',
        'cart_count' => count_cart_items()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng']);
}
?>
