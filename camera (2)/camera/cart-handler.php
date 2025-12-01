<?php
/**
 * Cart Handler - Xử lý AJAX cho giỏ hàng
 */
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Khởi tạo response
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0
];

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức không hợp lệ';
    echo json_encode($response);
    exit;
}

// Xử lý các action
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'add_to_cart':
        handleAddToCart();
        break;
    
    case 'update_cart':
        handleUpdateCart();
        break;
    
    case 'remove_from_cart':
        handleRemoveFromCart();
        break;
    
    default:
        $response['message'] = 'Hành động không hợp lệ';
        break;
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Xử lý thêm sản phẩm vào giỏ hàng
 */
function handleAddToCart() {
    global $response;
    
    // Lấy thông tin sản phẩm
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($product_id <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ';
        return;
    }
    
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Kiểm tra sản phẩm
    $product = get_product_by_id($product_id);
    
    if (!$product) {
        $response['message'] = 'Sản phẩm không tồn tại';
        return;
    }
    
    // Kiểm tra số lượng tồn kho
    if ($product['stock'] < $quantity) {
        $response['message'] = 'Số lượng sản phẩm trong kho không đủ. Hiện chỉ còn ' . $product['stock'] . ' sản phẩm.';
        return;
    }
    
    // Thêm vào giỏ hàng
    $result = add_to_cart($product_id, $quantity);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Thêm sản phẩm vào giỏ hàng thành công';
        $response['cart_count'] = count_cart_items();
    } else {
        $response['message'] = 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng';
    }
}

/**
 * Xử lý cập nhật số lượng sản phẩm trong giỏ hàng
 */
function handleUpdateCart() {
    global $response;
    
    // Lấy thông tin sản phẩm
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($product_id <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ';
        return;
    }
    
    // Kiểm tra sản phẩm
    $product = get_product_by_id($product_id);
    
    if (!$product) {
        $response['message'] = 'Sản phẩm không tồn tại';
        return;
    }
    
    // Kiểm tra số lượng tồn kho nếu tăng số lượng
    if ($quantity > 0) {
        if ($product['stock'] < $quantity) {
            $response['message'] = 'Số lượng sản phẩm trong kho không đủ. Hiện chỉ còn ' . $product['stock'] . ' sản phẩm.';
            return;
        }
    }
    
    // Cập nhật giỏ hàng
    $result = update_cart($product_id, $quantity);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật giỏ hàng thành công';
        $response['cart_count'] = count_cart_items();
        $response['cart_total'] = format_currency(calculate_cart_total());
        // Trả về tạm tính dòng sản phẩm
        $response['item_subtotal'] = format_currency($product['price'] * $quantity);
    } else {
        $response['message'] = 'Có lỗi xảy ra khi cập nhật giỏ hàng';
    }
}

/**
 * Xử lý xóa sản phẩm khỏi giỏ hàng
 */
function handleRemoveFromCart() {
    global $response;
    
    // Lấy thông tin sản phẩm
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($product_id <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ';
        return;
    }
    
    // Xóa khỏi giỏ hàng
    $result = remove_from_cart($product_id);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Xóa sản phẩm khỏi giỏ hàng thành công';
        $response['cart_count'] = count_cart_items();
        $response['cart_total'] = format_currency(calculate_cart_total());
    } else {
        $response['message'] = 'Có lỗi xảy ra khi xóa sản phẩm khỏi giỏ hàng';
    }
}
?>
