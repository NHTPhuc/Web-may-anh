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
$product = db_fetch_one("SELECT * FROM products WHERE id = $product_id");

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang danh sách
if (!$product) {
    header("Location: products.php");
    exit;
}

// Lấy danh sách danh mục
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name");

// Khởi tạo biến
$name = $product['name'];
$category_id = $product['category_id'];
$price = $product['price'];
$stock = $product['stock'];
$description = $product['description'];
$featured = $product['featured'];
$current_image = $product['image'];
$success_message = '';
$error_message = '';

// Xử lý form cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate dữ liệu
    if (empty($name)) {
        $error_message = "Vui lòng nhập tên sản phẩm";
    } elseif ($category_id <= 0) {
        $error_message = "Vui lòng chọn danh mục";
    } elseif ($price <= 0) {
        $error_message = "Giá sản phẩm phải lớn hơn 0";
    } elseif ($stock < 0) {
        $error_message = "Số lượng tồn kho không được âm";
    } else {
        // Xử lý upload hình ảnh mới nếu có
        $image = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../assets/images/products/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_image = upload_file($_FILES['image'], $upload_dir, ['jpg', 'jpeg', 'png', 'gif']);
            
            if (!$new_image) {
                $error_message = "Lỗi khi tải lên hình ảnh. Chỉ chấp nhận các file JPG, JPEG, PNG, GIF";
            } else {
                // Xóa hình ảnh cũ nếu có
                if (!empty($current_image) && file_exists($upload_dir . $current_image)) {
                    @unlink($upload_dir . $current_image);
                }
                $image = $new_image;
            }
        }
        
        // Nếu không có lỗi, cập nhật sản phẩm
        if (empty($error_message)) {
            // Tạo slug từ tên sản phẩm nếu tên thay đổi
            $slug = ($name !== $product['name']) ? create_slug($name) : $product['slug'];
            
            // Dữ liệu sản phẩm
            $product_data = [
                'name' => $name,
                'slug' => $slug,
                'category_id' => $category_id,
                'price' => $price,
                'stock' => $stock,
                'description' => $description,
                'featured' => $featured,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Thêm hình ảnh nếu có
            if (!empty($image)) {
                $product_data['image'] = $image;
            }
            
            // Cập nhật sản phẩm trong database
            if (db_update('products', $product_data, "id = $product_id")) {
                $success_message = "Cập nhật sản phẩm thành công!";
                
                // Cập nhật lại thông tin sản phẩm
                $product = db_fetch_one("SELECT * FROM products WHERE id = $product_id");
                $name = $product['name'];
                $category_id = $product['category_id'];
                $price = $product['price'];
                $stock = $product['stock'];
                $description = $product['description'];
                $featured = $product['featured'];
                $current_image = $product['image'];
            } else {
                $error_message = "Lỗi khi cập nhật sản phẩm";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - <?php echo SITE_NAME; ?> Admin</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check input {
            margin-right: 10px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 3px;
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
                <h1>Sửa sản phẩm</h1>
                <div class="user-info">
                    <img src="<?php echo !empty($current_user['avatar']) ? '../assets/images/avatars/' . $current_user['avatar'] : '../assets/images/avatar-default.png'; ?>" alt="Avatar">
                    <span><?php echo $current_user['fullname'] ?? $current_user['username']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Thông tin sản phẩm</h2>
                    <div>
                        <a href="product_view.php?id=<?php echo $product_id; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> Xem chi tiết</a>
                        <a href="products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </div>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Tên sản phẩm <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Danh mục <span class="required">*</span></label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Giá <span class="required">*</span></label>
                        <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($price); ?>" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Số lượng tồn kho <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($stock); ?>" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả sản phẩm</label>
                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Hình ảnh sản phẩm</label>
                        <?php if (!empty($current_image)): ?>
                            <div>
                                <p>Hình ảnh hiện tại:</p>
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo $current_image; ?>" alt="<?php echo htmlspecialchars($name); ?>" class="current-image">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <small>Chấp nhận các định dạng: JPG, JPEG, PNG, GIF. Để trống nếu không muốn thay đổi hình ảnh.</small>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="featured" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                        <label for="featured">Sản phẩm nổi bật</label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        <a href="products.php" class="btn btn-danger"><i class="fas fa-times"></i> Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
