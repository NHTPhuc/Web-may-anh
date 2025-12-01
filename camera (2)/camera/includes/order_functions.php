<?php
/**
 * Các hàm liên quan đến đơn hàng
 */
// Include functions.php để sử dụng các hàm đã được định nghĩa
require_once __DIR__ . '/functions.php';

// Hủy đơn hàng
function cancel_order($order_id, $user_id) {
    $order_id = (int)$order_id;
    $user_id = (int)$user_id;
    $conn = db_connect();
    
    // Kiểm tra đơn hàng có thuộc về người dùng không
    $check_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id AND status = 'pending'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        return false;
    }
    
    // Cập nhật trạng thái đơn hàng
    $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";
    $update_result = mysqli_query($conn, $update_query);
    
    if ($update_result) {
        // Hoàn lại số lượng sản phẩm
        $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id";
        $items_result = mysqli_query($conn, $items_query);
        
        if ($items_result && mysqli_num_rows($items_result) > 0) {
            while ($item = mysqli_fetch_assoc($items_result)) {
                $product_id = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                
                $update_product_query = "UPDATE products SET stock = stock + $quantity WHERE id = $product_id";
                mysqli_query($conn, $update_product_query);
            }
        }
        
        return true;
    }
    
    return false;
}
?>
