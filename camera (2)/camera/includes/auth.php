<?php
/**
 * Xử lý đăng nhập, đăng ký và phân quyền
 */

// Đăng nhập
function login($username_or_email, $password) {
    $user = get_user_by_username_or_email($username_or_email);
    
    if (!$user) {
        return false;
    }
    
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

// Đăng ký
function register($username, $email, $password, $fullname) {
    // Kiểm tra username đã tồn tại chưa
    $check_username = db_fetch_one("SELECT id FROM users WHERE username = '" . db_escape($username) . "'");
    
    if ($check_username) {
        return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }
    
    // Kiểm tra email đã tồn tại chưa
    $check_email = db_fetch_one("SELECT id FROM users WHERE email = '" . db_escape($email) . "'");
    
    if ($check_email) {
        return ['success' => false, 'message' => 'Email đã tồn tại'];
    }
    
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm người dùng mới
    $user_id = db_insert('users', [
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'fullname' => $fullname,
        'role' => 'customer'
    ]);
    
    if ($user_id) {
        return ['success' => true, 'user_id' => $user_id];
    }
    
    return ['success' => false, 'message' => 'Đăng ký thất bại'];
}

// Đăng xuất
function logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['user_role']);
    session_destroy();
}

// Kiểm tra quyền truy cập
function check_access($required_role = 'admin') {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    if ($_SESSION['user_role'] != $required_role) {
        set_flash_message('error', 'Bạn không có quyền truy cập trang này');
        redirect('index.php');
    }
}

// Lấy thông tin người dùng hiện tại
function get_logged_in_user() {
    if (is_logged_in()) {
        return get_user_by_id($_SESSION['user_id']);
    }
    
    return null;
}
?>