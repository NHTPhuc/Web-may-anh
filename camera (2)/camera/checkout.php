<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Thanh toán';

// Kiểm tra giỏ hàng
$cart_items = get_cart();
if (empty($cart_items)) {
    set_flash_message('error', 'Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm vào giỏ hàng trước khi thanh toán.');
    redirect(SITE_URL . '/cart.php');
}

// Lấy thông tin giỏ hàng
$cart_total = calculate_cart_total();

// Lấy cài đặt vận chuyển và thanh toán
$conn = db_connect();
$shipping_fee = 30000; // Mặc định
$free_shipping_min = 1000000; // Mặc định
$payment_methods = ['Tiền mặt', 'Chuyển khoản']; // Mặc định

// Lấy cài đặt từ database nếu có
$settings_query = "SELECT * FROM settings WHERE setting_key IN ('shipping_fee', 'free_shipping_min', 'payment_methods')";
$settings_result = mysqli_query($conn, $settings_query);
if ($settings_result) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        if ($row['setting_key'] == 'shipping_fee') {
            $shipping_fee = (int)$row['setting_value'];
        } elseif ($row['setting_key'] == 'free_shipping_min') {
            $free_shipping_min = (int)$row['setting_value'];
        } elseif ($row['setting_key'] == 'payment_methods') {
            $payment_methods = array_map(function($m) {
    $m = trim($m);
    if ($m === 'COD') return 'Tiền mặt';
    if ($m === 'Bank Transfer') return 'Chuyển khoản';
    return $m;
}, explode(',', $row['setting_value']));
        }
    }
}

// Tính phí vận chuyển
$apply_shipping_fee = $cart_total < $free_shipping_min ? $shipping_fee : 0;
$order_total = $cart_total + $apply_shipping_fee;

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $customer_address = isset($_POST['customer_address']) ? trim($_POST['customer_address']) : '';
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Kiểm tra thông tin
    $errors = [];
    
    if (empty($customer_name)) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (empty($customer_email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!is_valid_email($customer_email)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if (empty($customer_phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại';
    }
    
    if (empty($customer_address)) {
        $errors[] = 'Vui lòng nhập địa chỉ giao hàng';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'Vui lòng chọn phương thức thanh toán';
    }
    if (!in_array($payment_method, $payment_methods)) {
        $errors[] = 'Phương thức thanh toán không hợp lệ';
    }
    
    if (empty($errors)) {
            // Lấy user_id nếu đã đăng nhập
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
            
            // Thêm đơn hàng
            $customer_name = mysqli_real_escape_string($conn, $customer_name);
            $customer_email = mysqli_real_escape_string($conn, $customer_email);
            $customer_phone = mysqli_real_escape_string($conn, $customer_phone);
            $customer_address = mysqli_real_escape_string($conn, $customer_address);
            $payment_method = mysqli_real_escape_string($conn, $payment_method);
            $notes = mysqli_real_escape_string($conn, $notes);
            
            // Tạo mã đơn hàng (nếu hàm generate_order_code không tồn tại thì sinh mã đơn đơn giản)
            if (function_exists('generate_order_code')) {
                $order_code = generate_order_code();
            } else {
                $order_code = 'ORD' . date('YmdHis') . rand(100,999);
            }

            
            // Xây dựng câu lệnh SQL với xử lý đặc biệt cho user_id NULL
            if ($user_id === 'NULL') {
                $insert_order_sql = "INSERT INTO orders (order_code, user_id, customer_name, customer_email, customer_phone, customer_address, total_amount, payment_method, notes) 
                                    VALUES ('$order_code', NULL, '$customer_name', '$customer_email', '$customer_phone', '$customer_address', $order_total, '$payment_method', '$notes')";
            } else {
                $insert_order_sql = "INSERT INTO orders (order_code, user_id, customer_name, customer_email, customer_phone, customer_address, total_amount, payment_method, notes) 
                                    VALUES ('$order_code', $user_id, '$customer_name', '$customer_email', '$customer_phone', '$customer_address', $order_total, '$payment_method', '$notes')";
            }
            
            if (mysqli_query($conn, $insert_order_sql)) {
                $order_id = mysqli_insert_id($conn);
                
                // Thêm chi tiết đơn hàng
                foreach ($cart_items as $item) {
                    $product_id = (int)$item['id'];
                    $quantity = (int)$item['quantity'];
                    $price = (float)$item['price'];
                    
                    $insert_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES ($order_id, $product_id, $quantity, $price)";
                    if (!mysqli_query($conn, $insert_item_sql)) {
                    }
                }
                // Xóa giỏ hàng sau khi đặt hàng thành công
                clear_cart();
                // Chuyển hướng sang trang xác nhận đơn hàng
                redirect(SITE_URL . '/order-confirmation.php?id=' . $order_id);
            } else {
                echo '<div style="color:red">DEBUG: Lỗi khi tạo đơn hàng: ' . mysqli_error($conn) . '</div>';
                $errors[] = 'Lỗi khi tạo đơn hàng: ' . mysqli_error($conn);
            }   
        }
    }

// Lấy thông tin người dùng nếu đã đăng nhập
$customer_info = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT * FROM users WHERE id = $user_id";
    $user_result = mysqli_query($conn, $user_query);
    
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        $customer_info['name'] = $user['full_name'] ?? $user['fullname'] ?? '';
        $customer_info['email'] = $user['email'] ?? '';
        $customer_info['phone'] = $user['phone'] ?? '';
        $customer_info['address'] = $user['address'] ?? '';
    }
}

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="checkout-page">
    <div class="container">
        <div class="page-header">
            <h1>Thanh toán</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Trang chủ</a> &gt;
                <a href="<?php echo SITE_URL; ?>/cart.php">Giỏ hàng</a> &gt;
                Thanh toán
            </div>
        </div>

        <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="checkout-content">
            <form action="" method="post" class="checkout-form">
                <div class="checkout-form-container">
                    <div class="billing-details">
                        <h2>Thông tin thanh toán</h2>

                        <div class="form-group">
                            <label for="customer_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="customer_name" name="customer_name"
                                value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : htmlspecialchars($customer_info['name']); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email <span class="required">*</span></label>
                            <input type="email" id="customer_email" name="customer_email"
                                value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : htmlspecialchars($customer_info['email']); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="customer_phone">Số điện thoại <span class="required">*</span></label>
                            <input type="text" id="customer_phone" name="customer_phone"
                                value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : htmlspecialchars($customer_info['phone']); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="customer_address">Địa chỉ giao hàng <span class="required">*</span></label>
                            <textarea id="customer_address" name="customer_address" rows="4"
                                required><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : htmlspecialchars($customer_info['address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="notes">Ghi chú đơn hàng</label>
                            <textarea id="notes" name="notes"
                                rows="4"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="order-details">
                        <h2>Đơn hàng của bạn</h2>

                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Tạm tính</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <strong>× <?php echo $item['quantity']; ?></strong>
                                    </td>
                                    <td><?php echo format_currency($item['price'] * $item['quantity']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="cart-subtotal">
                                    <th>Tạm tính</th>
                                    <td><?php echo format_currency($cart_total); ?></td>
                                </tr>
                                <tr class="shipping">
                                    <th>Phí vận chuyển</th>
                                    <td>
                                        <?php if ($apply_shipping_fee > 0): ?>
                                        <?php echo format_currency($apply_shipping_fee); ?>
                                        <?php else: ?>
                                        Miễn phí
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr class="order-total">
                                    <th>Tổng cộng</th>
                                    <td><?php echo format_currency($order_total); ?></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="payment-methods">
                            <h3>Phương thức thanh toán</h3>

                            <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-method">
                                <input type="radio"
                                    id="payment_<?php echo strtolower(str_replace(' ', '_', $method)); ?>"
                                    name="payment_method" value="<?php echo $method; ?>"
                                    <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == $method) ? 'checked' : ''; ?>
                                    required>
                                <label for="payment_<?php echo strtolower(str_replace(' ', '_', $method)); ?>"><?php echo $method; ?></label>

                                <?php if ($method == 'Chuyển khoản'): ?>
                                <div class="payment-method-description" id="desc_chuyenkhoan" style="display: none;">
                                    <p>Thực hiện thanh toán vào tài khoản ngân hàng của chúng tôi. Vui lòng sử dụng Mã
                                        đơn hàng của bạn trong phần Nội dung thanh toán. Đơn hàng sẽ được giao sau khi
                                        tiền đã chuyển.</p>
                                    <p><strong>Thông tin tài khoản:</strong></p>
                                    <p>Ngân hàng: MB bank</p>
                                    <p>Số tài khoản: 0862528965 </p>
                                    <p>Chủ tài khoản: NGO HOAI TRONG PHUC</p>
                                </div>
                                <?php elseif ($method == 'Tiền mặt'): ?>
                                <div class="payment-method-description" id="desc_tienmat" style="display: none;">
                                    <p>Thanh toán khi nhận hàng.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    function showPaymentDesc() {
                                        var chuyenKhoan = document.getElementById('payment_chuyển_khoản');
                                        var tienMat = document.getElementById('payment_tiền_mặt');
                                        var descChuyenKhoan = document.getElementById('desc_chuyenkhoan');
                                        var descTienMat = document.getElementById('desc_tienmat');
                                        if(chuyenKhoan && descChuyenKhoan) descChuyenKhoan.style.display = chuyenKhoan.checked ? 'block' : 'none';
                                        if(tienMat && descTienMat) descTienMat.style.display = tienMat.checked ? 'block' : 'none';
                                    }
                                    var radios = document.querySelectorAll('input[name="payment_method"]');
                                    radios.forEach(function(radio) {
                                        radio.addEventListener('change', showPaymentDesc);
                                    });
                                    showPaymentDesc(); // Initial call on page load
                                });
                            </script>
                        </div>

                        <button type="submit" name="place_order" class="btn place-order-btn">Đặt hàng</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.checkout-page {
    padding: 40px 0;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.breadcrumb {
    color: #777;
    font-size: 14px;
}

.breadcrumb a {
    color: #333;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: #4a90e2;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

.checkout-form-container {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.billing-details,
.order-details {
    padding: 0 15px;
    margin-bottom: 30px;
}

.billing-details {
    flex: 1;
    min-width: 300px;
}

.order-details {
    width: 40%;
    min-width: 300px;
}

h2 {
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.required {
    color: #e74c3c;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #4a90e2;
    outline: none;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.order-table th,
.order-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.order-table th {
    font-weight: 600;
}

.order-table tfoot th,
.order-table tfoot td {
    padding: 15px;
}

.order-table tfoot tr:last-child {
    border-top: 2px solid #eee;
    font-weight: 700;
}

.payment-methods {
    margin-bottom: 20px;
}

.payment-methods h3 {
    font-size: 16px;
    margin-bottom: 15px;
}

.payment-method {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.payment-method label {
    font-weight: 600;
    margin-left: 5px;
}

.payment-method-description {
    margin-top: 10px;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
    font-size: 14px;
}

.payment-method-description p {
    margin-bottom: 5px;
}

.place-order-btn {
    display: block;
    width: 100%;
    padding: 15px;
    background-color: #4a90e2;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}

.place-order-btn:hover {
    background-color: #3a7bc8;
}

@media (max-width: 768px) {
    .checkout-form-container {
        flex-direction: column;
    }

    .billing-details,
    .order-details {
        width: 100%;
    }
}
</style>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
