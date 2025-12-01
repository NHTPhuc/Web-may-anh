<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Xử lý form liên hệ
$success = false;
$errors = [];
$name = $email = $phone = $subject = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate input
    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (empty($email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if (empty($subject)) {
        $errors[] = 'Vui lòng nhập tiêu đề';
    }
    
    if (empty($message)) {
        $errors[] = 'Vui lòng nhập nội dung';
    }
    
    // Nếu không có lỗi, lưu thông tin liên hệ vào database
    if (empty($errors)) {
        $contact_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = db_insert('contacts', $contact_data);
        
        if ($result) {
            $success = true;
            // Reset form
            $name = $email = $phone = $subject = $message = '';
        } else {
            $errors[] = 'Có lỗi xảy ra, vui lòng thử lại sau';
        }
    }
}

// Thiết lập tiêu đề trang
$page_title = 'Liên hệ';

// Custom CSS for contact page
$custom_css = '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/contact.css">';

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="contact-section" style="background: #eaf6fa; padding: 40px 0; min-height: 100vh;">
    <div class="container" style="max-width: 1100px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 32px; justify-content: center; align-items: flex-start;">
        <h1>Liên hệ với chúng tôi</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Cảm ơn bạn đã liên hệ với chúng tôi. Chúng tôi sẽ phản hồi trong thời gian sớm nhất!
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
        
        <div class="contact-container" style="display: flex; flex-wrap: wrap; gap: 32px; width: 100%; justify-content: center;">
            <div class="contact-card" style="background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; min-width: 320px; flex: 1 1 320px; max-width: 420px; margin-bottom: 32px;">
                <h2>Thông tin liên hệ</h2>
                <ul>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>268 Lý Thường Kiệt, Phường 14, Quận 10, TP. Hồ Chí Minh</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>0862528965</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>info@strotecamera.vn</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Thứ 2 - Thứ 7: 8:30 - 19:30<br>Chủ nhật: 9:00 - 18:00</span>
                    </li>
                </ul>
                
                <div class="social-links">
                    <h3>Kết nối với chúng tôi</h3>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/ngo.hoai.trong.phuc" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/phuctrong47/?hl=vi" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-youtube"></i></a>
                        <a href="#" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="contact-card" style="background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; min-width: 320px; flex: 1 1 320px; max-width: 420px; margin-bottom: 32px;">
                <h2>Gửi tin nhắn cho chúng tôi</h2>
                <form action="" method="post" class="contact-form" style="display: flex; flex-direction: column;">
                    <div class="form-group">
                        <label for="name">Họ tên <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Tiêu đề <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Nội dung <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="5" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1rem;"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-actions" style="text-align: right;">
                        <button type="submit" name="send_contact" class="btn" style="background: #3182ce; color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.2s;">Gửi tin nhắn</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="map-container" style="margin-top: 48px; width: 100%;">
            <h2 style="font-size: 1.35rem; font-weight: 700; color: #19508a; margin-bottom: 18px; text-align: center;">Bản đồ</h2>
            <div class="map-card" style="background: #fff; border-radius: 18px; box-shadow: 0 6px 32px rgba(44,62,80,0.13); padding: 18px; width: 100%; max-width: 100%;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4795446850147!2d106.65737591533414!3d10.77338369232069!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752ec3c161a3fb%3A0xef77cd47a1cc691e!2zMjY4IEzDvSBUaMaw4budbmcgS2nhu4d0LCBQaMaw4budbmcgMTQsIFF14bqtbiAxMCwgVGjDoG5oIHBo4buRIEjhu5MgQ2jDrCBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2s!4v1621234567890!5m2!1svi!2s" width="100%" height="380" style="border:0; border-radius: 12px; width: 100%; min-width: 240px; min-height: 260px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
