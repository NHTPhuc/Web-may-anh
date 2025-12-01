<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (is_logged_in()) {
    redirect(SITE_URL);
}

// Thiết lập tiêu đề trang
$page_title = 'Đăng ký';

// Xử lý đăng ký
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    
    // Kiểm tra dữ liệu
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($fullname)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!is_valid_email($email)) {
        $error = 'Email không hợp lệ';
    } elseif ($password != $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        // Đăng ký tài khoản
        $result = register($username, $email, $password, $fullname);
        
        if ($result['success']) {
            $success = true;
        } else {
            $error = $result['message'];
        }
    }
}

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="register-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Đăng ký tài khoản</h1>
                <p>Vui lòng điền đầy đủ thông tin để đăng ký tài khoản</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                Đăng ký tài khoản thành công! Vui lòng <a href="<?php echo SITE_URL; ?>/login.php">đăng nhập</a> để tiếp
                tục.
            </div>
            <?php else: ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo SITE_URL; ?>/register.php" method="post" class="auth-form">
                <div class="form-group">
                    <label for="fullname">Họ và tên</label>
                    <input type="text" id="fullname" name="fullname"
                        value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
                </div>

                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="<?php echo SITE_URL; ?>/login.php">Đăng nhập</a></p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>