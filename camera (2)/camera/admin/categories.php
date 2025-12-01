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

// Xử lý thêm danh mục mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $image_path = null;
    
    if (empty($name)) {
        $error_message = "Vui lòng nhập tên danh mục!";
    } else {
        // Kiểm tra danh mục đã tồn tại chưa
        $check_query = "SELECT * FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $name) . "'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Danh mục đã tồn tại!";
        } else {
            // Xử lý upload ảnh
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($_FILES['category_image']['type'], $allowed_types)) {
                    $error_message = 'Chỉ chấp nhận các ảnh có định dạng: JPG, PNG, GIF, WEBP';
                } elseif ($_FILES['category_image']['size'] > $max_size) {
                    $error_message = 'Kích thước ảnh không được vượt quá 2MB';
                } else {
                    // Tiến hành thêm danh mục trước để lấy ID
                    $insert_query = "INSERT INTO categories (name, description, parent_id) VALUES (
                        '" . mysqli_real_escape_string($conn, $name) . "',
                        '" . mysqli_real_escape_string($conn, $description) . "',
                        $parent_id
                    )";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $category_id = mysqli_insert_id($conn);
                        
                        // Tạo thư mục nếu chưa tồn tại
                        $upload_dir = '../assets/images/categories/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Tạo tên file duy nhất
                        $file_extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
                        $new_filename = 'category_' . $category_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['category_image']['tmp_name'], $upload_path)) {
                            $image_path = 'assets/images/categories/' . $new_filename;
                            
                            // Cập nhật ảnh cho danh mục
                            $update_image_query = "UPDATE categories SET image = '" . mysqli_real_escape_string($conn, $image_path) . "' WHERE id = $category_id";
                            mysqli_query($conn, $update_image_query);
                        }
                        
                        $success_message = "Đã thêm danh mục mới thành công!";
                        // Reset form
                        $name = $description = '';
                        $parent_id = 0;
                    } else {
                        $error_message = "Lỗi khi thêm danh mục: " . mysqli_error($conn);
                    }
                }
            } else {
                // Thêm danh mục mới không có ảnh
                $insert_query = "INSERT INTO categories (name, description, parent_id) VALUES (
                    '" . mysqli_real_escape_string($conn, $name) . "',
                    '" . mysqli_real_escape_string($conn, $description) . "',
                    $parent_id
                )";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success_message = "Đã thêm danh mục mới thành công!";
                    // Reset form
                    $name = $description = '';
                    $parent_id = 0;
                } else {
                    $error_message = "Lỗi khi thêm danh mục: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Xử lý xóa danh mục
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    // Kiểm tra danh mục có tồn tại không
    $check_category = mysqli_query($conn, "SELECT * FROM categories WHERE id = $category_id");
    
    if (mysqli_num_rows($check_category) > 0) {
        // Kiểm tra có sản phẩm nào thuộc danh mục này không
        $check_products = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = $category_id");
        $products_count = mysqli_fetch_assoc($check_products)['count'];
        
        if ($products_count > 0) {
            $error_message = "Không thể xóa danh mục này vì có $products_count sản phẩm thuộc danh mục!";
        } else {
            // Xóa danh mục
            if (mysqli_query($conn, "DELETE FROM categories WHERE id = $category_id")) {
                $success_message = "Đã xóa danh mục thành công!";
            } else {
                $error_message = "Lỗi khi xóa danh mục: " . mysqli_error($conn);
            }
        }
    } else {
        $error_message = "Danh mục không tồn tại!";
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
    $where = "WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
}

// Lấy tổng số danh mục
$total_query = "SELECT COUNT(*) as total FROM categories $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_categories = $total_row['total'];
$total_pages = ceil($total_categories / $limit);

// Lấy danh sách danh mục
$categories_query = "SELECT c.*, p.name as parent_name 
                    FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id 
                    $where 
                    ORDER BY c.id DESC 
                    LIMIT $offset, $limit";
$categories = db_fetch_all($categories_query);

// Lấy danh sách danh mục cha
$parent_categories_query = "SELECT * FROM categories WHERE parent_id = 0 ORDER BY name";
$parent_categories = db_fetch_all($parent_categories_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - <?php echo SITE_NAME; ?> Admin</title>
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
            background-color: #3498db;
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
        
        .search-form {
            display: flex;
            align-items: center;
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
        
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            color: #fff;
            margin-right: 5px;
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
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .submit-btn:hover {
            background-color: #27ae60;
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
                    <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                    <li><a href="categories.php" class="active"><i class="fas fa-list"></i> Danh mục</a></li>
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
                <h1>Quản lý danh mục</h1>
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
            
            <div class="content-section form-section">
                <div class="content-header">
                    <h2>Thêm danh mục mới</h2>
                </div>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Tên danh mục</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_image">Ảnh danh mục</label>
                        <input type="file" id="category_image" name="category_image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="help-text">Chấp nhận các định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 2MB.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_id">Danh mục cha</label>
                        <select id="parent_id" name="parent_id">
                            <option value="0">Không có</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>" <?php echo isset($parent_id) && $parent_id == $parent['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">Thêm danh mục</button>
                </form>
            </div>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Danh sách danh mục</h2>
                </div>
                
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Tìm kiếm danh mục..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <?php if (empty($categories)): ?>
                    <div class="empty-message">
                        <p>Không có danh mục nào.</p>
                        <p>Hãy thêm danh mục mới để bắt đầu.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Mô tả</th>
                                <th>Danh mục cha</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                    <td><?php echo $category['parent_id'] > 0 ? htmlspecialchars($category['parent_name']) : 'Không có'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <a href="category_edit.php?id=<?php echo $category['id']; ?>" class="action-btn btn-edit">Sửa</a>
                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&laquo; Trước</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Tiếp &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>  