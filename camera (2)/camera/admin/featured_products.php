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

// Xử lý cập nhật sản phẩm nổi bật
$success_message = '';
$error_message = '';

// Xử lý xóa sản phẩm nổi bật qua GET request
if (isset($_GET['action']) && $_GET['action'] == 'remove_featured' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    if ($product_id > 0) {
        db_query("UPDATE products SET featured = 0 WHERE id = $product_id");
        $success_message = "Đã xóa sản phẩm khỏi danh sách nổi bật thành công!";
        // Redirect để tránh F5 gửi lại request
        header("Location: featured_products.php?success=removed");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_featured') {
            // Lấy danh sách ID sản phẩm được chọn
            $featured_ids = isset($_POST['featured_products']) ? $_POST['featured_products'] : [];
            
            // Cập nhật tất cả sản phẩm thành không nổi bật
            db_query("UPDATE products SET featured = 0");
            
            // Cập nhật các sản phẩm được chọn thành nổi bật
            if (!empty($featured_ids)) {
                $featured_ids_str = implode(',', array_map('intval', $featured_ids));
                db_query("UPDATE products SET featured = 1 WHERE id IN ($featured_ids_str)");
            }
            
            $success_message = "Đã cập nhật danh sách sản phẩm nổi bật thành công!";
        } elseif (isset($_POST['add_featured']) && isset($_POST['product_id']) && !empty($_POST['product_id'])) {
            // Thêm sản phẩm mới vào danh sách nổi bật
            $product_id = (int)$_POST['product_id'];
            db_query("UPDATE products SET featured = 1 WHERE id = $product_id");
            $success_message = "Đã thêm sản phẩm vào danh sách nổi bật thành công!";
        }
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
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
                  ORDER BY p.featured DESC, p.id DESC 
                  LIMIT $offset, $limit";
$products = db_fetch_all($products_query);

// Lấy danh sách danh mục
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = db_fetch_all($categories_query);

// Lấy danh sách sản phẩm nổi bật
$featured_query = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = 1 
                ORDER BY p.id DESC";
$featured = db_fetch_all($featured_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm nổi bật - <?php echo SITE_NAME; ?> Admin</title>
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
        
        .search-form {
            display: flex;
            margin-bottom: 20px;
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
            margin-bottom: 20px;
            align-items: center;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Quản lý sản phẩm nổi bật</h1>
            <a href="dashboard.php" class="btn btn-blue">Quay lại</a>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="box">
            <h2 class="box-title">Thêm sản phẩm nổi bật</h2>
            <form action="" method="post">
                <div class="product-select">
                    <select name="product_id" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?> - <?php echo number_format($product['price'], 0, ',', '.'); ?> đ</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="add_featured" class="btn btn-green">Thêm</button>
                </div>
            </form>
        </div>
        
        <div class="box">
            <h2 class="box-title">Danh sách sản phẩm nổi bật</h2>
            
            <?php if (count($featured) > 0): ?>
            <form action="" method="post">
                <input type="hidden" name="action" value="update_featured">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($featured as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td>
                                <?php if (!empty($item['image'])): ?>
                                <img src="../<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $item['id']; ?>" class="btn btn-small btn-blue"><i class="fas fa-edit"></i></a>
                                <a href="#" onclick="if(confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách nổi bật?')) { window.location='featured_products.php?action=remove_featured&id=<?php echo $item['id']; ?>'; } return false;" class="btn btn-small btn-red"><i class="fas fa-times"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" class="btn btn-success" style="margin-top: 15px;">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </form>
            <?php else: ?>
                <div class="empty-message">Không tìm thấy sản phẩm nào.</div>
            <?php endif; ?>
                
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
            </div>
        </div>
    </div>
    
    <script>
        function toggleAllCheckboxes() {
            var checkboxes = document.getElementsByName('featured_products[]');
            var selectAllCheckbox = document.getElementById('select-all');
            
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = selectAllCheckbox.checked;
            }
        }
    </script>
</body>
</html>
