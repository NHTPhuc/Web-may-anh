<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ hoặc trang admin tương ứng
if (is_logged_in()) {
    if (is_admin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL);
    }
}

// Thiết lập tiêu đề trang
$page_title = 'Đăng nhập';

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        if (login($username, $password)) {
            // Chuyển hướng sau khi đăng nhập thành công
            if (is_admin()) {
                redirect(SITE_URL . '/admin/dashboard.php');
            } else {
                $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : SITE_URL;
                unset($_SESSION['redirect_url']);
                redirect($redirect_url);
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="login-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Đăng nhập</h1>
                <p>Vui lòng đăng nhập để tiếp tục</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo SITE_URL; ?>/login.php" method="post" class="auth-form">
                <div class="form-group">
                    <label for="username">Tên đăng nhập hoặc Email</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group remember-me">
                    <label>
                        <input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập
                    </label>
                    <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="forgot-password">Quên mật khẩu?</a>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                </div>
                
                <div class="auth-footer">
                    <p>Chưa có tài khoản? <a href="<?php echo SITE_URL; ?>/register.php">Đăng ký ngay</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
