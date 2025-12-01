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

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];
$conn = db_connect();
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$current_user = mysqli_fetch_assoc($user_result);

// Kiểm tra ID sản phẩm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];

// Lấy thông tin sản phẩm
$product = db_fetch_one("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.id = $product_id");

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang danh sách
if (!$product) {
    header("Location: products.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem chi tiết sản phẩm - <?php echo SITE_NAME; ?> Admin</title>
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
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
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
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: #fff;
            border-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: #fff;
            border-color: #2ecc71;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: #fff;
            border-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .product-image {
            max-width: 300px;
            max-height: 300px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }
        
        .product-details {
            margin-bottom: 30px;
        }
        
        .product-details h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            width: 150px;
            font-weight: bold;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Trang quản trị</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-camera"></i> Sản phẩm</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Chi tiết sản phẩm</h1>
                <div class="user-info">
                    <img src="<?php echo !empty($current_user['avatar']) ? '../assets/images/avatars/' . $current_user['avatar'] : '../assets/images/avatar-default.png'; ?>" alt="Avatar">
                    <span><?php echo $current_user['fullname'] ?? $current_user['username']; ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <div>
                        <a href="products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </div>
                
                <div class="product-details">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/product-default.jpg" alt="Default" class="product-image">
                    <?php endif; ?>
                    
                    <h3>Thông tin cơ bản</h3>
                    <div class="detail-row">
                        <div class="detail-label">ID:</div>
                        <div class="detail-value"><?php echo $product['id']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Tên sản phẩm:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($product['name']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Slug:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($product['slug'] ?? ''); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Danh mục:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Không có danh mục'); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Giá:</div>
                        <div class="detail-value"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Tồn kho:</div>
                        <div class="detail-value"><?php echo isset($product['stock']) ? $product['stock'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Sản phẩm nổi bật:</div>
                        <div class="detail-value"><?php echo isset($product['featured']) && $product['featured'] == 1 ? 'Có' : 'Không'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Ngày tạo:</div>
                        <div class="detail-value"><?php echo isset($product['created_at']) ? date('d/m/Y H:i', strtotime($product['created_at'])) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Cập nhật lần cuối:</div>
                        <div class="detail-value"><?php echo isset($product['updated_at']) ? date('d/m/Y H:i', strtotime($product['updated_at'])) : 'N/A'; ?></div>
                    </div>
                    
                    <h3>Mô tả sản phẩm</h3>
                    <div class="detail-row">
                        <div class="detail-value">
                            <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'Không có mô tả'; ?>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Sửa sản phẩm</a>
                        <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');"><i class="fas fa-trash"></i> Xóa sản phẩm</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
