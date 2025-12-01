<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá sản phẩm']);
    exit;
}

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Lấy thông tin đánh giá
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

// Kiểm tra dữ liệu
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

if ($rating <= 0 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
    exit;
}

if (empty($review)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung đánh giá']);
    exit;
}

// Kiểm tra sản phẩm tồn tại
$product = get_product_by_id($product_id);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    exit;
}

// Kiểm tra người dùng đã đánh giá sản phẩm này chưa
$user_id = $_SESSION['user_id'];
$check_review = db_fetch_one("SELECT id FROM reviews WHERE product_id = $product_id AND user_id = $user_id");

if ($check_review) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
    exit;
}

// Thêm đánh giá vào database
$result = db_insert('reviews', [
    'product_id' => $product_id,
    'user_id' => $user_id,
    'rating' => $rating,
    'comment' => $review
]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Đánh giá sản phẩm thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
}
?>
