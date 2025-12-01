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

// Lấy thống kê từ database
$total_products = 0;
$total_users = 0;
$total_orders = 0;
$total_revenue = 0;

// Kiểm tra bảng products có tồn tại không
$check_products = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
if (mysqli_num_rows($check_products) > 0) {
    $total_products = db_fetch_value("SELECT COUNT(*) FROM products");
}

// Kiểm tra bảng users
$check_users = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_users) > 0) {
    $total_users = db_fetch_value("SELECT COUNT(*) FROM users");
}

// Kiểm tra bảng orders
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($check_orders) > 0) {
    $total_orders = db_fetch_value("SELECT COUNT(*) FROM orders");
    
    // Kiểm tra cột total_amount trong bảng orders
    $check_total_amount = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'total_amount'");
    if (mysqli_num_rows($check_total_amount) > 0) {
        // Kiểm tra cột status
        $check_status = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
        if (mysqli_num_rows($check_status) > 0) {
            $total_revenue = db_fetch_value("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'") ?? 0;
        } else {
            $total_revenue = db_fetch_value("SELECT SUM(total_amount) FROM orders") ?? 0;
        }
    }
}

// Lấy đơn hàng mới nhất
$latest_orders = [];
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($check_orders) > 0) {
    // Kiểm tra cấu trúc bảng orders
    $order_columns = [];
    $order_columns_result = mysqli_query($conn, "DESCRIBE orders");
    while ($column = mysqli_fetch_assoc($order_columns_result)) {
        $order_columns[] = $column['Field'];
    }
    
    // Xây dựng câu truy vấn dựa trên cấu trúc thực tế
    $latest_orders_query = "SELECT o.* FROM orders o";
    
    // Kiểm tra xem có cột user_id và bảng users không
    if (in_array('user_id', $order_columns) && mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'users'")) > 0) {
        $latest_orders_query = "SELECT o.*, u.username FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.id";
    }
    
    // Kiểm tra xem có cột created_at không
    if (in_array('created_at', $order_columns)) {
        $latest_orders_query .= " ORDER BY o.created_at DESC LIMIT 5";
    } else if (in_array('id', $order_columns)) {
        $latest_orders_query .= " ORDER BY o.id DESC LIMIT 5";
    } else {
        $latest_orders_query .= " LIMIT 5";
    }
    
    $latest_orders = db_fetch_all($latest_orders_query);
}

// Lấy sản phẩm mới nhất
$latest_products = [];
$check_products = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
if (mysqli_num_rows($check_products) > 0) {
    // Kiểm tra cấu trúc bảng products
    $product_columns = [];
    $product_columns_result = mysqli_query($conn, "DESCRIBE products");
    while ($column = mysqli_fetch_assoc($product_columns_result)) {
        $product_columns[] = $column['Field'];
    }
    
    // Xây dựng câu truy vấn dựa trên cấu trúc thực tế
    $latest_products_query = "SELECT p.* FROM products p";
    
    // Kiểm tra xem có cột category_id và bảng categories không
    if (in_array('category_id', $product_columns) && mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'categories'")) > 0) {
        $latest_products_query = "SELECT p.*, c.name as category_name FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.id";
    }
    
    // Kiểm tra xem có cột id không
    if (in_array('id', $product_columns)) {
        $latest_products_query .= " ORDER BY p.id DESC LIMIT 5";
    } else {
        $latest_products_query .= " LIMIT 5";
    }
    
    $latest_products = db_fetch_all($latest_products_query);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển - <?php echo SITE_NAME; ?> Admin</title>
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
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
            color: #3498db;
        }
        
        .stat-card h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
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
        
        .view-all {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .view-all:hover {
            text-decoration: underline;
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
        
        .status {
            display: inline-block;
            padding: 3px 10px;
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
        
        .status-completed {
            background-color: #d5f5e3;
            color: #1e8449;
        }
        
        .status-cancelled {
            background-color: #f5b7b1;
            color: #922b21;
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
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        .database-info {
            margin-top: 30px;
        }
        
        .database-info h2 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .database-info table {
            margin-bottom: 20px;
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
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
                <h1>Bảng điều khiển</h1>
                <div class="user-info">
                    <span>Xin chào, <?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Đăng xuất</a>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3>Tổng sản phẩm</h3>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Tổng người dùng</h3>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Tổng đơn hàng</h3>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Tổng doanh thu</h3>
                    <div class="stat-value"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</div>
                </div>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Đơn hàng gần đây</h2>
                    <a href="orders.php" class="view-all">Xem tất cả</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latest_orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Không có đơn hàng nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latest_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Khách vãng lai'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($order['total_amount'])) {
                                            echo number_format($order['total_amount'], 0, ',', '.') . 'đ';
                                        } else if (isset($order['total'])) {
                                            echo number_format($order['total'], 0, ',', '.') . 'đ';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($order['status'])): ?>
                                        <span class="status status-<?php echo $order['status']; ?>">
                                            <?php 
                                            $status_text = '';
                                            switch ($order['status']) {
                                                case 'pending': $status_text = 'Chờ xác nhận'; break;
                                                case 'processing': $status_text = 'Đang xử lý'; break;
                                                case 'completed': $status_text = 'Hoàn thành'; break;
                                                case 'cancelled': $status_text = 'Đã hủy'; break;
                                                default: $status_text = $order['status'];
                                            }
                                            echo $status_text;
                                            ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="status">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="action-btn btn-view">Xem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Sản phẩm mới nhất</h2>
                    <a href="products.php" class="view-all">Xem tất cả</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latest_products)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Không có sản phẩm nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latest_products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                                        <?php else: ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/product-default.jpg" alt="Default" class="product-image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Không có danh mục'); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($product['price'])) {
                                            echo number_format($product['price'], 0, ',', '.') . 'đ';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($product['stock'])) {
                                            echo $product['stock'];
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Sửa</a>
                                        <a href="product_delete.php?id=<?php echo $product['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-section database-info">
                <h2>Thông tin kết nối database</h2>
                <table>
                    <tr>
                        <th>Thông số</th>
                        <th>Giá trị</th>
                    </tr>
                    <tr>
                        <td>Host</td>
                        <td><?php echo DB_HOST; ?></td>
                    </tr>
                    <tr>
                        <td>Database</td>
                        <td><?php echo DB_NAME; ?></td>
                    </tr>
                    <tr>
                        <td>User</td>
                        <td><?php echo DB_USER; ?></td>
                    </tr>
                    <tr>
                        <td>Port</td>
                        <td><?php echo DB_PORT; ?></td>
                    </tr>
                    <tr>
                        <td>Trạng thái kết nối</td>
                        <td style="color: green;">Đang kết nối</td>
                    </tr>
                </table>
                
                <h2>Danh sách bảng trong database</h2>
                <table>
                    <tr>
                        <th>Tên bảng</th>
                        <th>Số bản ghi</th>
                    </tr>
                    <?php
                    $tables_query = "SHOW TABLES";
                    $tables_result = mysqli_query($conn, $tables_query);
                    
                    while ($table = mysqli_fetch_array($tables_result)) {
                        $table_name = $table[0];
                        $count_query = "SELECT COUNT(*) as count FROM $table_name";
                        $count_result = mysqli_query($conn, $count_query);
                        $count = mysqli_fetch_assoc($count_result)['count'];
                        
                        echo "<tr>";
                        echo "<td>$table_name</td>";
                        echo "<td>$count</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
