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

// Kiểm tra ID người dùng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$conn = db_connect();

// Lấy thông tin người dùng
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    // Người dùng không tồn tại
    header("Location: users.php");
    exit;
}

$user = mysqli_fetch_assoc($user_result);

// Lấy thông tin đơn hàng của người dùng (nếu có)
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
$orders = db_fetch_all($orders_query);

// Thiết lập tiêu đề trang
$page_title = 'Xem thông tin người dùng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?> Admin</title>
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
        
        .admin-container {
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
            border-bottom: 1px solid #3d5166;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            color: #ccc;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 2px;
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h1 {
            font-size: 24px;
            color: #2c3e50;
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
        
        .user-info {
            margin-bottom: 30px;
        }
        
        .user-info-item {
            margin-bottom: 15px;
            display: flex;
        }
        
        .user-info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .user-info-value {
            flex: 1;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-admin {
            background-color: #d5f5e3;
            color: #1e8449;
        }
        
        .role-user {
            background-color: #d6eaf8;
            color: #2874a6;
        }
        
        .action-btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 3px;
            font-size: 14px;
            text-decoration: none;
            margin-right: 5px;
            color: #fff;
        }
        
        .btn-back {
            background-color: #7f8c8d;
        }
        
        .btn-edit {
            background-color: #3498db;
        }
        
        .btn-delete {
            background-color: #e74c3c;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Store FL-Camera</h2>
                <p>Trang quản trị</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
                    <li><a href="products.php"><i class="fas fa-camera"></i> Sản phẩm</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>Thông tin người dùng</h1>
                <div>
                    <a href="users.php" class="action-btn btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');"><i class="fas fa-trash"></i> Xóa</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Thông tin cá nhân</h2>
                </div>
                
                <div class="user-info">
                    <div class="user-info-item">
                        <div class="user-info-label">ID:</div>
                        <div class="user-info-value"><?php echo $user['id']; ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Tên đăng nhập:</div>
                        <div class="user-info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Email:</div>
                        <div class="user-info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Họ tên:</div>
                        <div class="user-info-value"><?php echo htmlspecialchars($user['fullname'] ?? 'Chưa cập nhật'); ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Số điện thoại:</div>
                        <div class="user-info-value"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật'; ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Địa chỉ:</div>
                        <div class="user-info-value"><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Chưa cập nhật'; ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Vai trò:</div>
                        <div class="user-info-value">
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo $user['role'] == 'admin' ? 'Admin' : 'Người dùng'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Ngày tạo:</div>
                        <div class="user-info-value"><?php echo isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="user-info-item">
                        <div class="user-info-label">Lần cập nhật cuối:</div>
                        <div class="user-info-value"><?php echo isset($user['updated_at']) ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A'; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Đơn hàng gần đây</h2>
                    <a href="../orders.php?user_id=<?php echo $user['id']; ?>" class="action-btn btn-back">Xem tất cả</a>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-message">Người dùng chưa có đơn hàng nào.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo get_order_status_text($order['status']); ?></td>
                                    <td>
                                        <a href="../order_detail.php?id=<?php echo $order['id']; ?>" class="action-btn btn-view">Xem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
