<?php
// Hiển thị lỗi để dễ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bắt đầu session
session_start();

// Kết nối database
require_once '../includes/config.php';
require_once '../includes/database.php';

// Xử lý đăng ký
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Kiểm tra dữ liệu
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        // Kết nối database
        $conn = db_connect();
        
        // Kiểm tra username đã tồn tại chưa
        $username_check = mysqli_real_escape_string($conn, $username);
        $check_query = "SELECT * FROM users WHERE username = '$username_check'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác';
        } else {
            // Thêm tài khoản mới
            $username_safe = mysqli_real_escape_string($conn, $username);
            $password_safe = $password; // Lưu mật khẩu dạng plaintext để phù hợp với hệ thống hiện tại
            $email_safe = mysqli_real_escape_string($conn, $email);
            
            $insert_query = "INSERT INTO users (username, password, email, role) VALUES ('$username_safe', '$password_safe', '$email_safe', 'admin')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = true;
                $user_id = mysqli_insert_id($conn);
                
                // Tự động đăng nhập
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = 'admin';
            } else {
                $error = 'Lỗi khi tạo tài khoản: ' . mysqli_error($conn);
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
    <title>Đăng ký tài khoản Admin - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .register-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 30px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #666;
            font-size: 14px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2;
        }
        
        .register-button {
            background-color: #4a90e2;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .register-button:hover {
            background-color: #357abd;
        }
        
        .link-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 3px;
            margin-top: 10px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #4a90e2;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Đăng ký tài khoản Admin</h1>
            <p>Tạo tài khoản admin mới để quản lý website</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h3>Đăng ký thành công!</h3>
                <p>Tài khoản admin đã được tạo và bạn đã được đăng nhập tự động.</p>
                <p>
                    <a href="dashboard.php" class="link-button">Vào trang quản trị</a>
                </p>
            </div>
        <?php else: ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <button type="submit" class="register-button">Đăng ký</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</body>
</html>
