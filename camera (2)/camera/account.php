<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    set_flash_message('error', 'Bạn cần đăng nhập để xem trang này.');
    redirect(SITE_URL . '/login.php');
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Xử lý cập nhật thông tin
$update_success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']); // fullname
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate input
    if (empty($name)) {
        $errors[] = 'Họ tên không được để trống';
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    // Nếu không có lỗi, tiến hành cập nhật
    if (empty($errors)) {
        $update_data = [
            'fullname' => $name, 
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ];
        
        // Kiểm tra nếu có mật khẩu mới
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $errors[] = 'Xác nhận mật khẩu không khớp';
            } else {
                $update_data['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            }
        }
        
        if (empty($errors)) {
            $update_success = update_user($user_id, $update_data);
            if ($update_success) {
                set_flash_message('success', 'Cập nhật thông tin thành công!');
                // Cập nhật thông tin người dùng sau khi update
                $user = get_user_by_id($user_id);
            } else {
                $errors[] = 'Có lỗi xảy ra khi cập nhật thông tin';
            }
        }
    }
}

// Thiết lập tiêu đề trang
$page_title = 'Tài khoản của tôi';

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="profile-section" style="background: #eaf6fa; padding: 40px 0;">
    <div class="container" style="max-width: 600px; margin: 0 auto;">
        <h1>Tài khoản của tôi</h1>
        <?php display_flash_message(); ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 36px; align-items: flex-start; flex-wrap: wrap;">
    <aside class="account-sidebar-pro" style="background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(44,62,80,0.11); padding: 32px 24px 24px 24px; min-width: 220px; max-width: 230px; margin-bottom: 32px;">
        <ul class="account-menu-pro" style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 18px;"><a href="<?php echo SITE_URL; ?>/account.php" style="color: #19508a; font-weight: 700; font-size: 1.08rem; display: flex; align-items: center;"><span style='margin-right:7px;'>&#128100;</span>Thông tin tài khoản</a></li>
            <li style="margin-bottom: 18px;"><a href="<?php echo SITE_URL; ?>/orders.php" style="color: #19508a; font-weight: 700; font-size: 1.08rem; display: flex; align-items: center;"><span style='margin-right:7px;'>&#128179;</span>Đơn hàng của tôi</a></li>
            <li><a href="<?php echo SITE_URL; ?>/logout.php" style="color: #e53e3e; font-weight: 700; font-size: 1.08rem; display: flex; align-items: center;"><span style='margin-right:7px;'>&#128682;</span>Đăng xuất</a></li>
        </ul>
    </aside>
    <div class="profile-card" style="background: #fff; border-radius: 22px; box-shadow: 0 8px 36px rgba(44,62,80,0.11), 0 1.5px 6px rgba(44,62,80,0.07); padding: 38px 36px 32px 36px; min-width: 320px; flex: 1 1 340px; max-width: 440px; margin-bottom: 32px;">
            
                
                    <h2 style="font-size: 1.5rem; margin-bottom: 16px; color: #1a365d;">Thông tin cá nhân</h2>
                    <form action="" method="post" class="profile-form" style="display: flex; flex-direction: column;">
                        <div class="form-group">
                            <label for="name">Họ tên</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ</label>
                            <textarea id="address" name="address" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="section-title" style="margin-top: 24px; font-size: 1.1rem; color: #2563eb; font-weight: 600;">Đổi mật khẩu</div>
                        <p class="small">Để trống nếu không muốn thay đổi mật khẩu</p>
                        
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <input type="password" id="new_password" name="new_password" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu</label>
                            <input type="password" id="confirm_password" name="confirm_password" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                        </div>
                        
                        <div class="form-actions" style="text-align: right;">
                            <button type="submit" name="update_profile" class="btn" style="background: #3182ce; color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.2s;">Cập nhật thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
