<?php

// Lấy tất cả danh mục
function get_all_categories() {
    return db_fetch_all("SELECT * FROM categories ORDER BY name");
}


// Lấy danh mục theo ID
function get_category_by_id($id) {
    $id = (int)$id;
    return db_fetch_one("SELECT * FROM categories WHERE id = $id");
}

// Lấy tất cả sản phẩm
function get_all_products($limit = null, $offset = 0, $where = '') {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    $query .= " ORDER BY p.id DESC";
    
    if ($limit !== null) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    return db_fetch_all($query);
}

// Lấy sản phẩm theo ID
function get_product_by_id($id) {
    $id = (int)$id;
    return db_fetch_one("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.id = $id");
}

// Lấy sản phẩm nổi bật
function get_featured_products($limit = 8) {
    $limit = (int)$limit;
    return db_fetch_all("SELECT * FROM products WHERE featured = 1 ORDER BY id DESC LIMIT $limit");
}

// Lấy sản phẩm mới nhất
function get_latest_products($limit = 8) {
    $limit = (int)$limit;
    return db_fetch_all("SELECT * FROM products ORDER BY id DESC LIMIT $limit");
}

// Lấy sản phẩm liên quan
function get_related_products($product_id, $category_id, $limit = 4) {
    $product_id = (int)$product_id;
    $category_id = (int)$category_id;
    $limit = (int)$limit;
    
    return db_fetch_all("SELECT * FROM products 
                         WHERE category_id = $category_id AND id != $product_id 
                         ORDER BY id DESC LIMIT $limit");
}

// Lấy tất cả đơn hàng
function get_all_orders($limit = null, $offset = 0, $where = '') {
    $query = "SELECT o.*, u.username 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    if ($limit !== null) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    return db_fetch_all($query);
}

// Lấy đơn hàng theo ID
function get_order_by_id($id) {
    $id = (int)$id;
    return db_fetch_one("SELECT o.*, u.username, u.email, u.phone 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         WHERE o.id = $id");
}

// Lấy chi tiết đơn hàng
function get_order_items($order_id) {
    $order_id = (int)$order_id;
    return db_fetch_all("SELECT oi.*, p.name, p.image 
                         FROM order_items oi 
                         LEFT JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = $order_id");
}

// Lấy đơn hàng của người dùng
function get_user_orders($user_id) {
    $user_id = (int)$user_id;
    // Chỉ loại bỏ đơn đã đặt lại, vẫn giữ đơn đã hủy để hiện nút Đặt lại
    return db_fetch_all("SELECT * FROM orders WHERE user_id = $user_id AND status != 'cancelled_reordered' ORDER BY created_at DESC");
}

// Lấy tất cả người dùng
function get_all_users($limit = null, $offset = 0, $where = '') {
    $query = "SELECT * FROM users";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    $query .= " ORDER BY id DESC";
    
    if ($limit !== null) {
        $offset = (int)$offset;
        $limit = (int)$limit;
        $query .= " LIMIT $offset, $limit";
    }
    
    return db_fetch_all($query);
}

// Lấy người dùng theo ID
function get_user_by_id($id) {
    $id = (int)$id;
    return db_fetch_one("SELECT * FROM users WHERE id = $id");
}

// Lấy người dùng theo username hoặc email
function get_user_by_username_or_email($username_or_email) {
    $username_or_email = db_escape($username_or_email);
    return db_fetch_one("SELECT * FROM users WHERE username = '$username_or_email' OR email = '$username_or_email'");
}

// Tạo slug từ chuỗi
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#[^a-z0-9\s]#i',
    );
    
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        '',
    );
    
    $string = preg_replace($search, $replace, mb_strtolower($string, 'UTF-8'));
    $string = preg_replace('/\s+/', ' ', $string);
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    
    return $string;
}

// Định dạng tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Tạo mã đơn hàng
function generate_order_code() {
    return 'VJ' . date('YmdHis') . rand(100, 999);
}

// Kiểm tra người dùng đã đăng nhập chưa
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra người dùng có phải admin không
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Chuyển hướng
function redirect($url) {
    header("Location: $url");
    exit;
}

// Hiển thị thông báo
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Lấy thông báo
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

// Hiển thị thông báo
function display_flash_message() {
    $message = get_flash_message();
    
    if ($message) {
        echo '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
    }
}

// Tính tổng giỏ hàng
function calculate_cart_total() {
    $total = 0;
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    
    return $total;
}

// Đếm số sản phẩm trong giỏ hàng
function count_cart_items() {
    $count = 0;
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    
    return $count;
}

// Lấy giỏ hàng
function get_cart() {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        return $_SESSION['cart'];
    }
    
    return [];
}

// Thêm sản phẩm vào giỏ hàng
function add_to_cart($product_id, $quantity = 1) {
    $product = get_product_by_id($product_id);
    
    if (!$product) {
        return false;
    }
    
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    if ($product['stock'] < $quantity) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity
        ];
    }
    
    return true;
}

// Cập nhật giỏ hàng
function update_cart($product_id, $quantity) {
    if (!isset($_SESSION['cart'][$product_id])) {
        return false;
    }
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
    
    return true;
}

// Xóa sản phẩm khỏi giỏ hàng
function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    
    return false;
}

// Xóa giỏ hàng
function clear_cart() {
    unset($_SESSION['cart']);
}

// Kiểm tra email hợp lệ
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Tạo phân trang
function create_pagination($current_page, $total_pages, $url = '?') {
    $pagination = '<div class="pagination">';
    
    if ($current_page > 1) {
        $pagination .= '<a href="' . $url . 'page=' . ($current_page - 1) . '" class="prev">&laquo; Trước</a>';
    }
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $pagination .= '<span class="current">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . $url . 'page=' . $i . '">' . $i . '</a>';
        }
    }
    
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $url . 'page=' . ($current_page + 1) . '" class="next">Tiếp &raquo;</a>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

// Tải file lên
function upload_file($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] != 0) {
        return false;
    }
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return false;
    }
    
    $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = $destination . $new_file_name;
    
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return $new_file_name;
    }
    
    return false;
}

// Cập nhật thông tin người dùng
function update_user($user_id, $data) {
    $user_id = (int)$user_id;
    $fields = [];
    
    foreach ($data as $key => $value) {
        $value = db_escape($value);
        $fields[] = "$key = '$value'";
    }
    
    $fields_str = implode(', ', $fields);
    
    return db_query("UPDATE users SET $fields_str WHERE id = $user_id");
}

// Lấy text trạng thái đơn hàng
function get_order_status_text($status) {
    $status_map = [
        'pending' => 'Chờ xác nhận',
        'processing' => 'Đang xử lý',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    
    return isset($status_map[$status]) ? $status_map[$status] : $status;
}

// Định dạng ngày tháng
function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}
?>
