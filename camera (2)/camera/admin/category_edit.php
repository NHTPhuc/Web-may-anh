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

// Kiểm tra ID danh mục
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$category_id = (int)$_GET['id'];

// Lấy thông tin danh mục
$category_query = "SELECT * FROM categories WHERE id = $category_id";
$category_result = mysqli_query($conn, $category_query);

if (mysqli_num_rows($category_result) == 0) {
    // Danh mục không tồn tại
    header("Location: categories.php");
    exit;
}

$category = mysqli_fetch_assoc($category_result);
$errors = [];
$success = false;

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    // Lấy dữ liệu từ form
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = isset($_POST['parent_id']) && (int)$_POST['parent_id'] > 0 ? (int)$_POST['parent_id'] : null;
    $current_image = $category['image'] ?? null;
    
    // Validate dữ liệu
    if (empty($name)) {
        $errors[] = 'Tên danh mục không được để trống';
    } else {
        // Kiểm tra tên danh mục đã tồn tại chưa (trừ danh mục hiện tại)
        $check_query = "SELECT * FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $name) . "' AND id != $category_id";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = 'Tên danh mục đã tồn tại, vui lòng chọn tên khác';
        }
    }
    
    // Không cho phép chọn chính nó làm danh mục cha
    if ($parent_id == $category_id) {
        $errors[] = 'Không thể chọn chính danh mục này làm danh mục cha';
        $parent_id = $category['parent_id']; // Khôi phục giá trị cũ
    }
    
        // Xử lý upload ảnh
    $image_path = $current_image;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['category_image']['type'], $allowed_types)) {
            $errors[] = 'Chỉ chấp nhận các ảnh có định dạng: JPG, PNG, GIF, WEBP';
        } elseif ($_FILES['category_image']['size'] > $max_size) {
            $errors[] = 'Kích thước ảnh không được vượt quá 2MB';
        } else {
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
                // Xóa ảnh cũ nếu có
                if ($current_image && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                
                $image_path = 'assets/images/categories/' . $new_filename;
            } else {
                $errors[] = 'Có lỗi xảy ra khi tải ảnh lên';
            }
        }
    }

    // Nếu không có lỗi, tiến hành cập nhật
    if (empty($errors)) {
        $parent_id_sql = is_null($parent_id) ? "NULL" : $parent_id;
        $update_query = "UPDATE categories SET 
            name = '" . mysqli_real_escape_string($conn, $name) . "',
            description = '" . mysqli_real_escape_string($conn, $description) . "',
            parent_id = $parent_id_sql,
            image = '" . mysqli_real_escape_string($conn, $image_path) . "'
            WHERE id = $category_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = true;
            // Cập nhật lại thông tin danh mục
            $category_result = mysqli_query($conn, $category_query);
            $category = mysqli_fetch_assoc($category_result);
        } else {
            $errors[] = 'Lỗi khi cập nhật: ' . mysqli_error($conn);
        }
    }
}

// Lấy danh sách danh mục cha
$parent_categories_query = "SELECT * FROM categories WHERE id != $category_id ORDER BY name";
$parent_categories = db_fetch_all($parent_categories_query);

// Thiết lập tiêu đề trang
$page_title = 'Chỉnh sửa danh mục';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group select {
            height: 40px;
        }
        
        .form-group .help-text {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 3px;
            font-size: 14px;
            text-decoration: none;
            margin-left: 10px;
            cursor: pointer;
            border: none;
        }
        
        .btn-back {
            background-color: #7f8c8d;
            color: #fff;
        }
        
        .btn-save {
            background-color: #2ecc71;
            color: #fff;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>STROTE CAMERA</h2>
                <p>Trang quản trị</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
                    <li><a href="products.php"><i class="fas fa-camera"></i> Sản phẩm</a></li>
                    <li><a href="categories.php" class="active"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>Chỉnh sửa danh mục</h1>
                <div>
                    <a href="categories.php" class="action-btn btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="opacity-0 animate-fade-in bg-green-200 text-green-800 px-4 py-2 rounded mb-4 text-center">
                    Cập nhật thành công!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Thông tin danh mục</h2>
                </div>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Tên danh mục</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_image">Ảnh danh mục</label>
                        <?php if (!empty($category['image']) && file_exists('../' . $category['image'])): ?>
                            <div class="current-image">
                                <img src="<?php echo '../' . $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="max-width: 200px; max-height: 200px; margin-bottom: 10px;">
                                <p>Ảnh hiện tại</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="category_image" name="category_image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="help-text">Chấp nhận các định dạng: JPG, PNG, GIF, WEBP. Kích thước tối đa: 2MB.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_id">Danh mục cha</label>
                        <select id="parent_id" name="parent_id">
                            <option value="0">Không có (Danh mục gốc)</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>" <?php echo $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="categories.php" class="action-btn btn-back">Hủy</a>
                        <button type="submit" name="update_category" class="action-btn btn-save hover:scale-105 hover:bg-blue-600">
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
