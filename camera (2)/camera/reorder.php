<?php
//Tự động tạo mới đơn hàng dựa trên đơn cũ
session_start();
require_once 'includes/config.php'; // Kết nối DB

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Lấy thông tin đơn hàng cũ
$sql = "SELECT * FROM orders WHERE id = $order_id";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<b>Lỗi:</b> Không tìm thấy đơn hàng cũ!';
    exit;
}

$order = mysqli_fetch_assoc($result);

// Chỉ cho phép đặt lại nếu đơn đang là 'cancelled'
if ($order['status'] !== 'cancelled') {
    header('Location: orders.php');
    exit;
}

// Tạo đơn hàng mới dựa trên đơn hàng cũ
$order_code = 'ORD' . date('YmdHis') . rand(100,999);

$sql_insert = "INSERT INTO orders (order_code, user_id, created_at, total_amount, status) VALUES (
    '$order_code',
    '{$order['user_id']}',
    NOW(),
    '{$order['total_amount']}',
    'Chờ xử lý'
)";
if (mysqli_query($conn, $sql_insert)) {
    $new_order_id = mysqli_insert_id($conn);
    echo '<b>Đã tạo đơn hàng mới với ID:</b> ' . $new_order_id . '<br>';

    // Nếu có bảng order_items, copy các sản phẩm sang đơn mới (tuỳ cấu trúc DB)
    $sql_items = "SELECT * FROM order_items WHERE order_id = $order_id";
    $result_items = mysqli_query($conn, $sql_items);
    if ($result_items) {
        while ($item = mysqli_fetch_assoc($result_items)) {
            $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES ($new_order_id, '{$item['product_id']}', '{$item['quantity']}', '{$item['price']}')";
            if (!mysqli_query($conn, $sql_insert_item)) {
                echo '<b>Lỗi khi copy sản phẩm:</b> ' . mysqli_error($conn) . '<br>';
            }
        }
        echo '<b>Đã copy sản phẩm sang đơn mới.</b><br>';
    } else {
        echo '<b>Không có sản phẩm nào để copy hoặc lỗi khi truy vấn order_items:</b> ' . mysqli_error($conn) . '<br>';
    }
    // Xóa các sản phẩm trong đơn cũ
    $sql_delete_items = "DELETE FROM order_items WHERE order_id = $order_id";
    mysqli_query($conn, $sql_delete_items);
    // Xóa đơn cũ
    $sql_delete_order = "DELETE FROM orders WHERE id = $order_id";
    mysqli_query($conn, $sql_delete_order);
    // Đánh dấu đơn cũ đã được đặt lại
    $sql_update_reordered = "UPDATE orders SET reordered = 1 WHERE id = $order_id";
    mysqli_query($conn, $sql_update_reordered);
    // Chuyển hướng về trang danh sách đơn hàng sau khi đặt lại thành công
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Đặt lại đơn hàng thành công!'
    ];
    header('Location: orders.php');
    exit;
} else {
    echo '<b>Lỗi khi tạo đơn hàng mới:</b> ' . mysqli_error($conn);
    exit;
}
?>
