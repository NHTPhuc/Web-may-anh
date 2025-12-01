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
$errors = [];
$success = false;

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // Lấy dữ liệu từ form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = trim($_POST['role']);
    $new_password = trim($_POST['new_password']);
    
    // Validate dữ liệu
    if (empty($username)) {
        $errors[] = 'Tên đăng nhập không được để trống';
    } else {
        // Kiểm tra tên đăng nhập đã tồn tại chưa (trừ người dùng hiện tại)
        $check_query = "SELECT * FROM users WHERE username = '$username' AND id != $user_id";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác';
        }
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    } else {
        // Kiểm tra email đã tồn tại chưa (trừ người dùng hiện tại)
        $check_query = "SELECT * FROM users WHERE email = '$email' AND id != $user_id";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = 'Email đã tồn tại, vui lòng sử dụng email khác';
        }
    }
    
    // Nếu không có lỗi, tiến hành cập nhật
    if (empty($errors)) {
        $update_data = [
            'username' => $username,
            'email' => $email,
            'fullname' => $fullname,
            'phone' => $phone,
            'address' => $address,
            'role' => $role,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Kiểm tra nếu có mật khẩu mới
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
            } else {
                $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }
        
        if (empty($errors)) {
            // Tạo câu lệnh SQL update
            $update_sql = "UPDATE users SET ";
            $update_parts = [];
            
            foreach ($update_data as $key => $value) {
                $value = mysqli_real_escape_string($conn, $value);
                $update_parts[] = "$key = '$value'";
            }
            
            $update_sql .= implode(', ', $update_parts);
            $update_sql .= " WHERE id = $user_id";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = true;
                // Cập nhật lại thông tin người dùng
                $user_result = mysqli_query($conn, $user_query);
                $user = mysqli_fetch_assoc($user_result);
            } else {
                $errors[] = 'Lỗi khi cập nhật: ' . mysqli_error($conn);
            }
        }
    }
}

// Thiết lập tiêu đề trang
$page_title = 'Chỉnh sửa người dùng';
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
                <h1>Chỉnh sửa người dùng</h1>
                <div>
                    <a href="users.php" class="action-btn btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Cập nhật thông tin người dùng thành công!
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
                    <h2>Thông tin người dùng</h2>
                </div>
                
                <form action="" method="post">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname">Họ tên</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Địa chỉ</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Vai trò</label>
                        <select id="role" name="role">
                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input type="password" id="new_password" name="new_password">
                        <div class="help-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="users.php" class="action-btn btn-back">Hủy</a>
                        <button type="submit" name="update_user" class="action-btn btn-save">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
