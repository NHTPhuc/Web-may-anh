<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Xác nhận đơn hàng';

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(SITE_URL);
}

$order_id = (int)$_GET['id'];
$conn = db_connect();

// Lấy thông tin đơn hàng
$order_query = "SELECT * FROM orders WHERE id = $order_id";
$order_result = mysqli_query($conn, $order_query);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    set_flash_message('error', 'Đơn hàng không tồn tại');
    redirect(SITE_URL);
}

$order = mysqli_fetch_assoc($order_result);

// Lấy chi tiết đơn hàng
$items_query = "SELECT oi.*, p.name, p.image FROM order_items oi 
               LEFT JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
$order_items = [];

if ($items_result) {
    while ($item = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $item;
    }
}

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="order-confirmation">
    <div class="container">
        <div class="page-header">
            <h1>Đặt hàng thành công</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Trang chủ</a> &gt; Xác nhận đơn hàng
            </div>
        </div>
        
        <div class="confirmation-message">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Cảm ơn bạn đã đặt hàng!</h2>
            <p>Đơn hàng của bạn đã được đặt thành công. Mã đơn hàng của bạn là: <strong>#<?php echo $order_id; ?></strong></p>
            <p>Chúng tôi đã gửi email xác nhận đơn hàng đến địa chỉ email của bạn.</p>
        </div>
        
        <div class="order-details">
            <h3>Chi tiết đơn hàng</h3>
            
            <div class="order-info">
                <div class="order-info-item">
                    <h4>Thông tin đơn hàng</h4>
                    <ul>
                        <li><strong>Mã đơn hàng:</strong> #<?php echo $order_id; ?></li>
                        <li><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></li>
                        <li><strong>Trạng thái:</strong> 
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php 
                                $status_text = '';
                                switch ($order['status']) {
                                    case 'pending': $status_text = 'Chờ xác nhận'; break;
                                    case 'processing': $status_text = 'Đang xử lý'; break;
                                    case 'shipping': $status_text = 'Đang giao hàng'; break;
                                    case 'completed': $status_text = 'Hoàn thành'; break;
                                    case 'cancelled': $status_text = 'Đã hủy'; break;
                                    default: $status_text = $order['status'];
                                }
                                echo $status_text;
                                ?>
                            </span>
                        </li>
                        <li><strong>Phương thức thanh toán:</strong> <?php echo $order['payment_method']; ?></li>
                        <li><strong>Tổng tiền:</strong> <?php echo format_currency($order['total_amount']); ?></li>
                    </ul>
                </div>
                
                <div class="order-info-item">
                    <h4>Thông tin khách hàng</h4>
                    <ul>
                        <li><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></li>
                        <li><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></li>
                        <li><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="order-items">
                <h4>Sản phẩm đã đặt</h4>
                
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th class="product-image">Hình ảnh</th>
                            <th class="product-name">Sản phẩm</th>
                            <th class="product-price">Giá</th>
                            <th class="product-quantity">Số lượng</th>
                            <th class="product-total">Tổng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="product-image">
                                <img src="<?php echo !empty($item['image']) ? SITE_URL . '/assets/images/products/' . $item['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                            </td>
                            <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="product-price"><?php echo format_currency($item['price']); ?></td>
                            <td class="product-quantity"><?php echo $item['quantity']; ?></td>
                            <td class="product-total"><?php echo format_currency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="order-subtotal">
                            <th colspan="4">Tạm tính</th>
                            <td><?php 
                                $subtotal = 0;
                                foreach ($order_items as $item) {
                                    $subtotal += $item['price'] * $item['quantity'];
                                }
                                echo format_currency($subtotal);
                            ?></td>
                        </tr>
                        <tr class="order-shipping">
                            <th colspan="4">Phí vận chuyển</th>
                            <td><?php 
                                $shipping_fee = $order['total_amount'] - $subtotal;
                                echo $shipping_fee > 0 ? format_currency($shipping_fee) : 'Miễn phí';
                            ?></td>
                        </tr>
                        <tr class="order-total">
                            <th colspan="4">Tổng cộng</th>
                            <td><?php echo format_currency($order['total_amount']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if ($order['payment_method'] == 'Chuyển khoản'): ?>
            <div class="payment-instructions">
                <h4>Hướng dẫn thanh toán</h4>
                <p>Vui lòng chuyển khoản với nội dung thanh toán là: <strong>CAMERA <?php echo $order_id; ?></strong></p>
                <div class="bank-info">
                    <p><strong>Thông tin tài khoản:</strong></p>
                    <p>Ngân hàng: MB bank</p>
                    <p>Số tài khoản: 0862528965</p>
                    <p>Chủ tài khoản: NGO HOAI TRONG PHUC</p>
                    <p>Số tiền: <?php echo format_currency($order['total_amount']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="order-actions">
                <a href="<?php echo SITE_URL; ?>" class="btn continue-shopping">Tiếp tục mua sắm</a>
                <?php if (is_logged_in()): ?>
                <a href="<?php echo SITE_URL; ?>/account.php?tab=orders" class="btn view-orders">Xem đơn hàng của tôi</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .order-confirmation {
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
    
    .confirmation-message {
        text-align: center;
        margin-bottom: 40px;
        padding: 30px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .confirmation-icon {
        font-size: 60px;
        color: #2ecc71;
        margin-bottom: 20px;
    }
    
    .confirmation-message h2 {
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .confirmation-message p {
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .order-details {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 40px;
    }
    
    .order-details h3 {
        font-size: 20px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .order-info {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px 30px;
    }
    
    .order-info-item {
        flex: 1;
        min-width: 300px;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .order-info-item h4 {
        font-size: 16px;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }
    
    .order-info-item ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .order-info-item ul li {
        margin-bottom: 10px;
    }
    
    .order-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-pending {
        background-color: #ffeaa7;
        color: #d68910;
    }
    
    .status-processing {
        background-color: #d6eaf8;
        color: #2874a6;
    }
    
    .status-shipping {
        background-color: #e8daef;
        color: #8e44ad;
    }
    
    .status-completed {
        background-color: #d5f5e3;
        color: #1e8449;
    }
    
    .status-cancelled {
        background-color: #f5b7b1;
        color: #922b21;
    }
    
    .order-items h4 {
        font-size: 16px;
        margin-bottom: 15px;
    }
    
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    
    .order-items-table th,
    .order-items-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .order-items-table th {
        background-color: #f9f9f9;
        font-weight: 600;
    }
    
    .product-image {
        width: 80px;
    }
    
    .product-image img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 3px;
    }
    
    .order-items-table tfoot th,
    .order-items-table tfoot td {
        padding: 15px;
    }
    
    .order-items-table tfoot tr:last-child {
        border-top: 2px solid #eee;
        font-weight: 700;
    }
    
    .payment-instructions {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .payment-instructions h4 {
        font-size: 16px;
        margin-bottom: 15px;
    }
    
    .bank-info {
        margin-top: 15px;
        padding: 15px;
        background-color: #fff;
        border: 1px solid #eee;
        border-radius: 5px;
    }
    
    .bank-info p {
        margin-bottom: 5px;
    }
    
    .order-actions {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .btn {
        display: inline-block;
        padding: 12px 20px;
        background-color: #4a90e2;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-bottom: 10px;
    }
    
    .btn:hover {
        background-color: #3a7bc8;
    }
    
    .continue-shopping {
        background-color: #2ecc71;
    }
    
    .continue-shopping:hover {
        background-color: #27ae60;
    }
    
    @media (max-width: 768px) {
        .order-info {
            flex-direction: column;
        }
        
        .order-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }
    }
</style>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
