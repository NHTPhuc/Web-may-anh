<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/order_functions.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    set_flash_message('error', 'Bạn cần đăng nhập để xem trang này.');
    redirect(SITE_URL . '/login.php');
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'Không tìm thấy đơn hàng.');
    redirect(SITE_URL . '/orders.php');
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$order = get_order_by_id($order_id);

// Kiểm tra đơn hàng có thuộc về người dùng hiện tại không
if (!$order || $order['user_id'] != $user_id) {
    set_flash_message('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.');
    redirect(SITE_URL . '/orders.php');
}

// Lấy chi tiết đơn hàng
$order_items = get_order_items($order_id);

// Thiết lập tiêu đề trang
$page_title = 'Chi tiết đơn hàng #' . $order_id;

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="order-detail-section">
    <div class="container">
        <div class="order-navigation">
            <a href="<?php echo SITE_URL; ?>/orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng</a>
        </div>
        
        <div class="order-detail-header">
            <h1>Chi tiết đơn hàng #<?php echo $order_id; ?></h1>
            <div class="order-status-badge <?php echo strtolower($order['status']); ?>">
                <?php echo get_order_status_text($order['status']); ?>
            </div>
        </div>
        
        <div class="order-progress">
            <?php 
            $statuses = ['pending', 'processing', 'shipping', 'completed'];
            $current_status_index = array_search($order['status'], $statuses);
            if ($current_status_index === false) $current_status_index = -1; // For cancelled orders
            
            foreach ($statuses as $index => $status): 
                $status_class = $index <= $current_status_index ? 'completed' : '';
                $status_class .= $index == $current_status_index ? ' current' : '';
            ?>
            <div class="progress-step <?php echo $status_class; ?>">
                <div class="step-icon">
                    <?php if ($index <= $current_status_index): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <i class="fas <?php 
                            switch($status) {
                                case 'pending': echo 'fa-clock'; break;
                                case 'processing': echo 'fa-cog'; break;
                                case 'shipping': echo 'fa-truck'; break;
                                case 'completed': echo 'fa-check-circle'; break;
                                default: echo 'fa-circle';
                            }
                        ?>"></i>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?php echo get_order_status_text($status); ?></div>
            </div>
            <?php if ($index < count($statuses) - 1): ?>
                <div class="progress-line <?php echo $index < $current_status_index ? 'completed' : ''; ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="order-detail-container">
            <div class="order-info-panel">
                <div class="panel-section">
                    <h3><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Mã đơn hàng:</span>
                            <span class="info-value">#<?php echo $order['id']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ngày đặt:</span>
                            <span class="info-value"><?php echo format_date($order['created_at']); ?></span>
                        </div>
                        <?php if (!empty($order['updated_at']) && $order['updated_at'] != $order['created_at']): ?>
                        <div class="info-item">
                            <span class="info-label">Cập nhật lần cuối:</span>
                            <span class="info-value"><?php echo format_date($order['updated_at']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Phương thức thanh toán:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Trạng thái thanh toán:</span>
                            <span class="info-value payment-status <?php echo isset($order['payment_status']) && $order['payment_status'] == 'paid' ? 'paid' : 'unpaid'; ?>">
                                <?php echo isset($order['payment_status']) && $order['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="panel-section">
                    <h3><i class="fas fa-user"></i> Thông tin khách hàng</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Họ tên:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Số điện thoại:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Địa chỉ:</span>
                            <span class="info-value address"><?php echo htmlspecialchars($order['customer_address']); ?></span>
                        </div>
                    </div>
                </div>
                    
                <div class="panel-section">
                    <h3><i class="fas fa-shopping-cart"></i> Sản phẩm đã đặt</h3>
                    <div class="order-items">
                        <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo !empty($item['image']) ? SITE_URL . '/assets/images/products/' . $item['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="item-details">
                                <h4 class="item-name"><?php echo $item['name']; ?></h4>
                                <div class="item-meta">
                                    <span class="item-price"><?php echo format_currency($item['price']); ?></span>
                                    <span class="item-quantity">x <?php echo $item['quantity']; ?></span>
                                </div>
                            </div>
                            <div class="item-total">
                                <?php echo format_currency($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="panel-section">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Tổng kết đơn hàng</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính:</span>
                            <span class="summary-value"><?php echo format_currency(isset($order['subtotal']) ? $order['subtotal'] : $order['total_amount']); ?></span>
                        </div>
                        
                        <?php if (isset($order['shipping_fee']) && $order['shipping_fee'] > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển:</span>
                            <span class="summary-value"><?php echo format_currency($order['shipping_fee']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                        <div class="summary-row discount">
                            <span class="summary-label">Giảm giá:</span>
                            <span class="summary-value">-<?php echo format_currency($order['discount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span class="summary-label">Tổng cộng:</span>
                            <span class="summary-value"><?php echo format_currency($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="panel-section">
                    <h3><i class="fas fa-sticky-note"></i> Ghi chú đơn hàng</h3>
                    <div class="order-notes">
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="order-actions">
                    <?php if ($order['status'] == 'pending'): ?>
                    <a href="<?php echo SITE_URL; ?>/cancel-order.php?id=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                        <i class="fas fa-times"></i> Hủy đơn hàng
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'completed'): ?>
                    <a href="<?php echo SITE_URL; ?>/reorder.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Đặt lại đơn hàng này
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .order-detail-section {
        padding: 40px 0;
    }
    
    .order-navigation {
        margin-bottom: 20px;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        color: #666;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .back-link i {
        margin-right: 5px;
        
    }
    
    .back-link:hover {
        color: #4a90e2;
    }
    
    .order-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .order-detail-header h1 {
        margin: 0;
        font-size: 24px;
    }
    
    .order-status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .order-status-badge.pending {
        background-color: #ffeaa7;
        color: #d68910;
    }
    
    .order-status-badge.processing {
        background-color: #d6eaf8;
        color: #2874a6;
    }
    
    .order-status-badge.shipping {
        background-color: #e8daef;
        color: #8e44ad;
    }
    
    .order-status-badge.completed {
        background-color: #d5f5e3;
        color: #1e8449;
    }
    
    .order-status-badge.cancelled {
        background-color: #f5b7b1;
        color: #922b21;
    }
    
    .order-progress {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        color: #999;
        border: 2px solid #ddd;
    }
    
    .progress-step.completed .step-icon {
        background-color: #4a90e2;
        color: #fff;
        border-color: #4a90e2;
    }
    
    .progress-step.current .step-icon {
        background-color: #fff;
        color: #4a90e2;
        border-color: #4a90e2;
        box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.2);
    }
    
    .step-label {
        font-size: 12px;
        color: #666;
        text-align: center;
        max-width: 100px;
    }
    
    .progress-step.completed .step-label,
    .progress-step.current .step-label {
        color: #4a90e2;
        font-weight: 600;
    }
    
    .progress-line {
        flex: 1;
        height: 2px;
        background-color: #ddd;
        position: relative;
        z-index: 1;
    }
    
    .progress-line.completed {
        background-color: #4a90e2;
    }
    
    .order-detail-container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .order-info-panel {
        padding: 0;
    }
    
    .panel-section {
        padding: 25px;
        border-bottom: 1px solid #eee;
    }
    
    .panel-section:last-child {
        border-bottom: none;
    }
    
    .panel-section h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 18px;
        color: #333;
        display: flex;
        align-items: center;
    }
    
    .panel-section h3 i {
        margin-right: 10px;
        color: #4a90e2;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }
    
    .info-value.address {
        white-space: pre-line;
    }
    
    .payment-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
    }
    
    .payment-status.paid {
        background-color: #d5f5e3;
        color: #1e8449;
    }
    
    .payment-status.unpaid {
        background-color: #fdebd0;
        color: #d35400;
    }
    
    .order-items {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .order-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 6px;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
        border-radius: 4px;
        overflow: hidden;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-name {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #333;
    }
    
    .item-meta {
        display: flex;
        gap: 15px;
        color: #666;
        font-size: 14px;
    }
    
    .item-total {
        font-weight: 600;
        font-size: 16px;
        color: #333;
        margin-left: 15px;
    }
    
    .order-summary {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 6px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
        color: #666;
    }
    
    .summary-row.discount .summary-value {
        color: #e74c3c;
    }
    
    .summary-row.total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    .order-notes {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        color: #666;
        font-style: italic;
    }
    
    .order-notes p {
        margin: 0;
    }
    
    .order-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn i {
        margin-right: 8px;
    }
    
    .btn-primary {
        background-color: #4a90e2;
        color: #fff;
    }
    
    .btn-primary:hover {
        background-color: #3a7bc8;
    }
    
    .btn-danger {
        background-color: #e74c3c;
        color: #fff;
    }
    
    .btn-danger:hover {
        background-color: #d62c1a;
    }
    
    @media (max-width: 768px) {
        .order-detail-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .order-progress {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        
        .progress-step {
            width: 100%;
            flex-direction: row;
            justify-content: flex-start;
        }
        
        .step-icon {
            margin-bottom: 0;
            margin-right: 15px;
        }
        
        .progress-line {
            width: 2px;
            height: 20px;
            margin-left: 19px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .order-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .item-image {
            margin-bottom: 10px;
        }
        
        .item-total {
            margin-left: 0;
            margin-top: 10px;
            align-self: flex-end;
        }
    }
</style>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
