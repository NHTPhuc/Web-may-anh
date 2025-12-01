<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once 'functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int)$_GET['id'];
$conn = db_connect();

// Lấy thông tin đơn hàng
$order_query = "SELECT o.*, u.username, u.email as user_email, u.phone as user_phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = $order_id";
$order_result = mysqli_query($conn, $order_query);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Lấy chi tiết đơn hàng
$items_query = "SELECT oi.*, p.name, p.image, p.price as current_price 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
$order_items = [];

if ($items_result && mysqli_num_rows($items_result) > 0) {
    while ($item = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $item;
    }
}

// Xử lý cập nhật trạng thái đơn hàng
if (isset($_POST['update_status']) && !empty($_POST['status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE id = $order_id")) {
        $success_message = "Đã cập nhật trạng thái đơn hàng thành công!";
        $order['status'] = $new_status;
    } else {
        $error_message = "Lỗi khi cập nhật trạng thái đơn hàng: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?> - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: #3d5166;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .header h1 {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 10px;
        }
        
        .user-info a {
            color: #e74c3c;
            text-decoration: none;
        }
        
        .content-section {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .content-header h2 {
            font-size: 18px;
            color: #2c3e50;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-navigation {
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .order-id {
            font-size: 20px;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #ffeaa7;
            color: #d68910;
        }
        
        .status-processing {
            background-color: #d6eaf8;
            color: #2874a6;
        }
        
        .status-shipping {
            background-color: #e8daef;
            color: #8e44ad;
        }
        
        .status-completed {
            background-color: #d5f5e3;
            color: #1e8449;
        }
        
        .status-cancelled {
            background-color: #f5b7b1;
            color: #922b21;
        }
        
        .order-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .meta-item {
            font-size: 14px;
        }
        
        .meta-label {
            color: #666;
            margin-right: 5px;
        }
        
        .meta-value {
            font-weight: 500;
        }
        
        .order-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .customer-info,
        .shipping-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-row {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        
        .info-value {
            font-weight: 500;
        }
        
        .order-items {
            margin-top: 30px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .product-info {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 10px;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .order-totals {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-label {
            color: #666;
        }
        
        .total-value {
            font-weight: 500;
        }
        
        .grand-total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 18px;
            font-weight: 600;
        }
        
        .order-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        
        .action-form {
            display: flex;
            gap: 10px;
        }
        
        .action-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: #fff;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: #fff;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: #fff;
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: #fff;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2,
            .sidebar-header p {
                display: none;
            }
            
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .order-sections {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Trang quản trị</p>
            </div>
            <div class="sidebar-menu">
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Bảng điều khiển</span></a>
                <a href="products.php"><i class="fas fa-camera"></i> <span>Sản phẩm</span></a>
                <a href="categories.php"><i class="fas fa-list"></i> <span>Danh mục</span></a>
                <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <span>Đơn hàng</span></a>
                <a href="users.php"><i class="fas fa-users"></i> <span>Người dùng</span></a>
                <a href="settings.php"><i class="fas fa-cog"></i> <span>Cài đặt</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Chi tiết đơn hàng</h1>
                <div class="user-info">
                    <span>Xin chào, admin</span>
                    <a href="logout.php">Đăng xuất</a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="order-navigation">
                    <a href="orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng</a>
                </div>
                
                <div class="order-header">
                    <div class="order-id">Đơn hàng #<?php echo $order_id; ?></div>
                    <div class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php 
                        $status_text = '';
                        switch($order['status']) {
                            case 'pending': $status_text = 'Chờ xác nhận'; break;
                            case 'processing': $status_text = 'Đang xử lý'; break;
                            case 'shipping': $status_text = 'Đang giao hàng'; break;
                            case 'completed': $status_text = 'Hoàn thành'; break;
                            case 'cancelled': $status_text = 'Đã hủy'; break;
                            default: $status_text = $order['status'];
                        }
                        echo $status_text;
                        ?>
                    </div>
                </div>
                
                <div class="order-meta">
                    <div class="meta-item">
                        <span class="meta-label">Ngày đặt:</span>
                        <span class="meta-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Phương thức thanh toán:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Tổng tiền:</span>
                        <span class="meta-value"><?php echo number_format($order['total_amount'], 0, ',', '.') . '₫'; ?></span>
                    </div>
                </div>
                
                <div class="order-sections">
                    <div class="customer-info">
                        <h3 class="section-title"><i class="fas fa-user"></i> Thông tin khách hàng</h3>
                        <div class="info-row">
                            <span class="info-label">Tên khách hàng:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Số điện thoại:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        </div>
                        <?php if (isset($order['username'])): ?>
                        <div class="info-row">
                            <span class="info-label">Tài khoản:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="shipping-info">
                        <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Thông tin giao hàng</h3>
                        <div class="info-row">
                            <span class="info-label">Địa chỉ giao hàng:</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></span>
                        </div>
                        <?php if (isset($order['notes']) && !empty($order['notes'])): ?>
                        <div class="info-row">
                            <span class="info-label">Ghi chú:</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3 class="section-title"><i class="fas fa-shopping-basket"></i> Sản phẩm đã đặt</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div class="product-image">
                                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            <?php if ($item['current_price'] != $item['price']): ?>
                                            <div><small>(Giá hiện tại: <?php echo number_format($item['current_price'], 0, ',', '.') . '₫'; ?>)</small></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.') . '₫'; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item_total, 0, ',', '.') . '₫'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span class="total-label">Tạm tính:</span>
                            <span class="total-value"><?php echo number_format($subtotal, 0, ',', '.') . '₫'; ?></span>
                        </div>
                        <?php if (isset($order['shipping_fee']) && $order['shipping_fee'] > 0): ?>
                        <div class="total-row">
                            <span class="total-label">Phí vận chuyển:</span>
                            <span class="total-value"><?php echo number_format($order['shipping_fee'], 0, ',', '.') . '₫'; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                        <div class="total-row">
                            <span class="total-label">Giảm giá:</span>
                            <span class="total-value">-<?php echo number_format($order['discount'], 0, ',', '.') . '₫'; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="total-row grand-total">
                            <span class="total-label">Tổng cộng:</span>
                            <span class="total-value"><?php echo number_format($order['total_amount'], 0, ',', '.') . '₫'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="order-actions">
                    <form class="action-form" method="post">
                        <select name="status">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                            <option value="shipping" <?php echo $order['status'] == 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Cập nhật trạng thái</button>
                    </form>
                    <a href="orders.php" class="btn btn-warning"><i class="fas fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
