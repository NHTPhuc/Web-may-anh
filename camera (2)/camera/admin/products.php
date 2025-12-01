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

// Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    // Kiểm tra sản phẩm có tồn tại không
    $check_product = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
    
    if (mysqli_num_rows($check_product) > 0) {
        // Xóa sản phẩm
        if (mysqli_query($conn, "DELETE FROM products WHERE id = $product_id")) {
            $success_message = "Đã xóa sản phẩm thành công!";
        } else {
            $error_message = "Lỗi khi xóa sản phẩm: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Sản phẩm không tồn tại!";
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where = "WHERE p.name LIKE '%$search%' OR p.description LIKE '%$search%'";
}

// Lọc theo danh mục
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if ($category_filter > 0) {
    $where = empty($where) ? "WHERE p.category_id = $category_filter" : $where . " AND p.category_id = $category_filter";
}

// Lấy tổng số sản phẩm
$total_query = "SELECT COUNT(*) as total FROM products p $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  $where 
                  ORDER BY p.id DESC 
                  LIMIT $offset, $limit";
$products = db_fetch_all($products_query);

// Lấy danh sách danh mục
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = db_fetch_all($categories_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - <?php echo SITE_NAME; ?> Admin</title>
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
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px 20px;
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
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
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
            margin-right: 15px;
        }
        
        .logout-btn {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
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
        
        .add-btn {
            background-color: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
        }
        
        .add-btn i {
            margin-right: 5px;
        }
        
        .add-btn:hover {
            background-color: #27ae60;
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            align-items: center;
        }
        
        .search-form input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            width: 250px;
            font-size: 14px;
        }
        
        .search-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 0 3px 3px 0;
            padding: 8px 15px;
            cursor: pointer;
            margin-left: -1px;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
        }
        
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .filter-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            color: #fff;
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: #3498db;
        }
        
        .btn-edit {
            background-color: #2ecc71;
        }
        
        .btn-delete {
            background-color: #e74c3c;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .current {
            background-color: #3498db;
            color: #fff;
            border-color: #3498db;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
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
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Trang quản trị</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="../"><i class="fas fa-home"></i> Xem trang web</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Quản lý sản phẩm</h1>
                <div class="user-info">
                    <span>Xin chào, <?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Đăng xuất</a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Danh sách sản phẩm</h2>
                    <div>
                        <a href="featured_products.php" class="btn btn-primary"><i class="fas fa-star"></i> Quản lý sản phẩm nổi bật</a>
                        <a href="product_add.php" class="btn btn-success"><i class="fas fa-plus"></i> Thêm sản phẩm mới</a>
                    </div>
                </div>
                
                <div class="filter-section">
                    <form action="" method="get" class="search-form">
                        <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <form action="" method="get" class="filter-form">
                        <select name="category">
                            <option value="0">Tất cả danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Lọc</button>
                    </form>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-message">
                        <p>Không có sản phẩm nào.</p>
                        <p>Hãy <a href="product_add.php">thêm sản phẩm mới</a> để bắt đầu.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Tồn kho</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                                        <?php else: ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/product-default.jpg" alt="Default" class="product-image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Không có danh mục'); ?></td>
                                    <td><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo isset($product['stock']) ? $product['stock'] : 'N/A'; ?></td>
                                    <td>
                                        <a href="product_view.php?id=<?php echo $product['id']; ?>" class="action-btn btn-view">Xem</a>
                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Sửa</a>
                                        <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">&laquo; Trước</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">Tiếp &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
