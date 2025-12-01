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

// Kiểm tra bảng settings có tồn tại không
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($check_table) == 0) {
    // Tạo bảng settings nếu chưa tồn tại
    $create_table_sql = "CREATE TABLE settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_sql)) {
        $success_message = "Đã tạo bảng settings thành công!";
        
        // Thêm các cài đặt mặc định
        $default_settings = [
            ['site_name', 'Camera Shop'],
            ['site_description', 'Cửa hàng máy ảnh chuyên nghiệp'],
            ['site_email', 'admin@example.com'],
            ['site_phone', '0123456789'],
            ['site_address', 'Hà Nội, Việt Nam'],
            ['currency_symbol', 'đ'],
            ['currency_code', 'VND'],
            ['tax_rate', '10'],
            ['shipping_fee', '30000'],
            ['free_shipping_min', '1000000'],
            ['payment_methods', 'Tiền mặt,Chuyển khoản'],
            ['order_statuses', 'pending,processing,shipping,completed,cancelled'],
            ['enable_registration', '1'],
            ['enable_reviews', '1'],
            ['items_per_page', '12'],
            ['maintenance_mode', '0']
        ];
        
        foreach ($default_settings as $setting) {
            $key = mysqli_real_escape_string($conn, $setting[0]);
            $value = mysqli_real_escape_string($conn, $setting[1]);
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
        }
    } else {
        $error_message = "Lỗi khi tạo bảng settings: " . mysqli_error($conn);
    }
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    $settings = [
        'site_name' => isset($_POST['site_name']) ? trim($_POST['site_name']) : '',
        'site_description' => isset($_POST['site_description']) ? trim($_POST['site_description']) : '',
        'site_email' => isset($_POST['site_email']) ? trim($_POST['site_email']) : '',
        'site_phone' => isset($_POST['site_phone']) ? trim($_POST['site_phone']) : '',
        'site_address' => isset($_POST['site_address']) ? trim($_POST['site_address']) : '',
        'currency_symbol' => isset($_POST['currency_symbol']) ? trim($_POST['currency_symbol']) : 'đ',
        'currency_code' => isset($_POST['currency_code']) ? trim($_POST['currency_code']) : 'VND',
        'tax_rate' => isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 10,
        'shipping_fee' => isset($_POST['shipping_fee']) ? (int)$_POST['shipping_fee'] : 30000,
        'free_shipping_min' => isset($_POST['free_shipping_min']) ? (int)$_POST['free_shipping_min'] : 1000000,
        'payment_methods' => isset($_POST['payment_methods']) ? trim($_POST['payment_methods']) : 'Tiền mặt,Chuyển khoản',
        'items_per_page' => isset($_POST['items_per_page']) ? (int)$_POST['items_per_page'] : 12,
        'enable_registration' => isset($_POST['enable_registration']) ? 1 : 0,
        'enable_reviews' => isset($_POST['enable_reviews']) ? 1 : 0,
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
    ];
    
    $success = true;
    $errors = [];
    
    foreach ($settings as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        
        $check_query = "SELECT * FROM settings WHERE setting_key = '$key'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Cập nhật cài đặt
            $update_query = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
            if (!mysqli_query($conn, $update_query)) {
                $success = false;
                $errors[] = "Lỗi khi cập nhật $key: " . mysqli_error($conn);
            }
        } else {
            // Thêm cài đặt mới
            $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
            if (!mysqli_query($conn, $insert_query)) {
                $success = false;
                $errors[] = "Lỗi khi thêm $key: " . mysqli_error($conn);
            }
        }
    }
    
    if ($success) {
        $success_message = "Đã cập nhật cài đặt thành công!";
    } else {
        $error_message = "Có lỗi xảy ra khi cập nhật cài đặt: " . implode(", ", $errors);
    }
}

// Lấy tất cả cài đặt
$settings_query = "SELECT * FROM settings";
$settings_result = mysqli_query($conn, $settings_query);
$settings = [];

while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - <?php echo SITE_NAME; ?> Admin</title>
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
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
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
        
        .form-group .checkbox-label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .form-group .checkbox-label input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .submit-btn {
            background-color: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            background-color: #27ae60;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 3px 3px 0 0;
            background-color: #f5f5f5;
        }
        
        .tab.active {
            background-color: #fff;
            border-color: #ddd;
            border-bottom-color: #fff;
            margin-bottom: -1px;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
                    <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="../"><i class="fas fa-home"></i> Xem trang web</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Cài đặt hệ thống</h1>
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
                    <h2>Quản lý cài đặt</h2>
                </div>
                
                <div class="tabs">
                    <div class="tab active" data-tab="general">Cài đặt chung</div>
                    <div class="tab" data-tab="payment">Thanh toán & Vận chuyển</div>
                    <div class="tab" data-tab="features">Tính năng</div>
                </div>
                
                <form action="" method="post">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="tab-content active" id="general">
                        <div class="form-section">
                            <h3>Thông tin cửa hàng</h3>
                            
                            <div class="form-group">
                                <label for="site_name">Tên cửa hàng</label>
                                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Camera Shop'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Mô tả cửa hàng</label>
                                <textarea id="site_description" name="site_description"><?php echo htmlspecialchars($settings['site_description'] ?? 'Cửa hàng máy ảnh chuyên nghiệp'); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_email">Email liên hệ</label>
                                <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'admin@example.com'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_phone">Số điện thoại</label>
                                <input type="text" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($settings['site_phone'] ?? '0123456789'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="site_address">Địa chỉ</label>
                                <textarea id="site_address" name="site_address"><?php echo htmlspecialchars($settings['site_address'] ?? 'Hà Nội, Việt Nam'); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Cài đặt tiền tệ</h3>
                            
                            <div class="form-group">
                                <label for="currency_symbol">Ký hiệu tiền tệ</label>
                                <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'đ'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="currency_code">Mã tiền tệ</label>
                                <input type="text" id="currency_code" name="currency_code" value="<?php echo htmlspecialchars($settings['currency_code'] ?? 'VND'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="payment">
                        <div class="form-section">
                            <h3>Cài đặt thanh toán</h3>
                            
                            <div class="form-group">
                                <label for="payment_methods">Phương thức thanh toán (phân cách bằng dấu phẩy)</label>
                                <input type="text" id="payment_methods" name="payment_methods" value="<?php echo htmlspecialchars($settings['payment_methods'] ?? 'Tiền mặt,Chuyển khoản'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="tax_rate">Thuế VAT (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '10'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Cài đặt vận chuyển</h3>
                            
                            <div class="form-group">
                                <label for="shipping_fee">Phí vận chuyển (VNĐ)</label>
                                <input type="number" id="shipping_fee" name="shipping_fee" min="0" value="<?php echo htmlspecialchars($settings['shipping_fee'] ?? '30000'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="free_shipping_min">Giá trị đơn hàng tối thiểu để miễn phí vận chuyển (VNĐ)</label>
                                <input type="number" id="free_shipping_min" name="free_shipping_min" min="0" value="<?php echo htmlspecialchars($settings['free_shipping_min'] ?? '1000000'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="features">
                        <div class="form-section">
                            <h3>Cài đặt tính năng</h3>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="enable_registration" <?php echo isset($settings['enable_registration']) && $settings['enable_registration'] == '1' ? 'checked' : ''; ?>>
                                    Cho phép đăng ký tài khoản
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="enable_reviews" <?php echo isset($settings['enable_reviews']) && $settings['enable_reviews'] == '1' ? 'checked' : ''; ?>>
                                    Cho phép đánh giá sản phẩm
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="maintenance_mode" <?php echo isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                    Bật chế độ bảo trì
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label for="items_per_page">Số sản phẩm hiển thị trên mỗi trang</label>
                                <input type="number" id="items_per_page" name="items_per_page" min="1" max="100" value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '12'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Lưu cài đặt</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
