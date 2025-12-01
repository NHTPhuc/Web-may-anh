<?php
/**
 * Cấu hình chung cho website
 */

// Thông tin website
define('SITE_NAME', 'Shop Camera');
define('SITE_URL', 'http://localhost/camera');

// Thông tin database
define('DB_HOST', '127.0.0.1'); // Thay đổi từ 'localhost' thành '127.0.0.1'
define('DB_USER', 'root');
define('DB_PASS', ''); // Đảm bảo mật khẩu chính xác, mặc định thường là trống
define('DB_NAME', 'camera_shop');
define('DB_PORT', '3306'); // Thêm cổng kết nối

// Kết nối MySQL sử dụng mysqli
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$conn) {
    die('Kết nối CSDL thất bại: ' . mysqli_connect_error());
}

// Cấu hình session
session_start();

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình hiển thị lỗi (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

// Đường dẫn thư mục
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDE_PATH', ROOT_PATH . 'includes/');
define('TEMPLATE_PATH', ROOT_PATH . 'templates/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('ASSET_PATH', ROOT_PATH . 'assets/');
define('UPLOAD_PATH', ASSET_PATH . 'images/');

// Số sản phẩm hiển thị trên mỗi trang
define('PRODUCTS_PER_PAGE', 12);
?>
