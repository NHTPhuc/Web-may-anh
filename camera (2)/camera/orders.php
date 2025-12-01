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

// Lấy danh sách đơn hàng của người dùng
$orders = get_user_orders($user_id);
$cancelled_order_ids = array();

// Thiết lập tiêu đề trang
$page_title = 'Đơn hàng của tôi';

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="orders-section">
    <div class="container">
        <h1>Đơn hàng của tôi</h1>
        
        <div class="account-container">
            <div class="account-sidebar">
                <ul class="account-menu">
                    <li><a href="<?php echo SITE_URL; ?>/account.php">Thông tin tài khoản</a></li>
                    <li class="active"><a href="<?php echo SITE_URL; ?>/orders.php">Đơn hàng của tôi</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">Đăng xuất</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <div class="orders-filter">
                    <form method="get" action="" class="filter-form">
                        <div class="filter-group">
                            <label for="status">Trạng thái:</label>
                            <select name="status" id="status">
                                <option value="">Tất cả</option>
                                <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                <option value="processing" <?php echo isset($_GET['status']) && $_GET['status'] == 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="shipping" <?php echo isset($_GET['status']) && $_GET['status'] == 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from">Từ ngày:</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to">Đến ngày:</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                        </div>
                        <button type="submit" class="btn-filter">Lọc</button>
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="btn-reset">Reset</a>
                    </form>
                </div>
                
                <div class="orders-list">
                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <p>Bạn chưa có đơn hàng nào.</p>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="btn">Mua sắm ngay</a>
                        </div>
                    <?php
// Lấy danh sách ID các đơn hàng đã hủy (chỉ lấy đơn thực sự là 'cancelled', không lấy 'cancelled_reordered')
$cancelled_order_ids = array();
foreach ($orders as $order) {
    if ($order['status'] === 'cancelled') {
        $cancelled_order_ids[] = $order['id'];
    }
}
?>
<?php else: ?>
                        <?php if (count($cancelled_order_ids) > 0): ?>
                        <form method="post" action="<?php echo SITE_URL; ?>/reorder.php" style="margin-bottom: 15px;">
                            <?php foreach ($cancelled_order_ids as $oid): ?>
                                <input type="hidden" name="order_ids[]" value="<?php echo $oid; ?>">
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-reorder" onclick="return confirm('Bạn có chắc chắn muốn đặt lại tất cả đơn hàng đã hủy?');">
                                <i class="fas fa-redo"></i> Đặt lại tất cả
                            </button>
                        </form>
                        <?php endif; ?>
                        <div class="order-summary">
                            <div class="summary-item">
                                <span class="summary-label">Tổng đơn hàng:</span>
                                <span class="summary-value"><?php echo count($orders); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Chờ xác nhận:</span>
                                <span class="summary-value"><?php echo count(array_filter($orders, function($o) { return $o['status'] == 'pending'; })); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Đang xử lý:</span>
                                <span class="summary-value"><?php echo count(array_filter($orders, function($o) { return $o['status'] == 'processing'; })); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Đang giao hàng:</span>
                                <span class="summary-value"><?php echo count(array_filter($orders, function($o) { return $o['status'] == 'shipping'; })); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Hoàn thành:</span>
                                <span class="summary-value"><?php echo count(array_filter($orders, function($o) { return $o['status'] == 'completed'; })); ?></span>
                            </div>
                        </div>
                        
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Ngày đặt</th>
                                    <th>Sản phẩm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="order-id">#<?php echo $order['id']; ?></td>
                                        <td class="order-date"><?php echo format_date($order['created_at']); ?></td>
                                        <td class="order-products">
                                            <?php 
                                            $order_items = get_order_items($order['id']);
                                            $total_items = count($order_items);
                                            if ($total_items > 0) {
                                                echo '<div class="product-preview">';
                                                echo '<img src="' . (!empty($order_items[0]['image']) ? SITE_URL . '/assets/images/products/' . $order_items[0]['image'] : SITE_URL . '/assets/images/product-default.jpg') . '" alt="' . $order_items[0]['name'] . '">';
                                                echo '<span>' . $order_items[0]['name'] . '</span>';
                                                if ($total_items > 1) {
                                                    echo '<span class="more-items">+' . ($total_items - 1) . ' sản phẩm khác</span>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '<span class="no-items">Không có sản phẩm</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="order-total"><?php echo format_currency($order['total_amount']); ?></td>
                                        <td class="order-status">
                                            <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                                <?php echo get_order_status_text($order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="order-actions">
                                            <a href="<?php echo SITE_URL; ?>/order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                            <?php if ($order['status'] == 'pending'): ?>
                                            <a href="<?php echo SITE_URL; ?>/cancel-order.php?id=<?php echo $order['id']; ?>" class="btn-cancel" title="Hủy đơn hàng" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                                                <i class="fas fa-times"></i> Hủy
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($order['status'] == 'cancelled'): ?>
    <a href="<?php echo SITE_URL; ?>/reorder.php?id=<?php echo $order['id']; ?>" class="btn-reorder" title="Đặt lại">
        <i class="fas fa-redo"></i> Đặt lại
    </a>
<?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .orders-section {
        padding: 40px 0;
    }
    
    .account-container {
        display: flex;
        flex-wrap: wrap;
        margin-top: 30px;
    }
    
    .account-sidebar {
        width: 250px;
        margin-right: 30px;
    }
    
    .account-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        background-color: #f5f5f5;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .account-menu li {
        border-bottom: 1px solid #e0e0e0;
    }
    
    .account-menu li:last-child {
        border-bottom: none;
    }
    
    .account-menu li a {
        display: block;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .account-menu li a:hover {
        background-color: #e9e9e9;
    }
    
    .account-menu li.active a {
        background-color: #4a90e2;
        color: #fff;
        font-weight: 600;
    }
    
    .account-content {
        flex: 1;
        min-width: 300px;
    }
    
    .orders-filter {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 15px;
    }
    
    .filter-group {
        margin-bottom: 10px;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 100%;
    }
    
    .btn-filter {
        padding: 8px 15px;
        background-color: #4a90e2;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-reset {
        padding: 8px 15px;
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        margin-left: 10px;
    }
    
    .order-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .summary-item {
        flex: 1;
        min-width: 120px;
        padding: 15px;
        background-color: #f5f5f5;
        border-radius: 5px;
        text-align: center;
    }
    
    .summary-label {
        display: block;
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .summary-value {
        display: block;
        font-size: 24px;
        font-weight: 600;
        color: #333;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .orders-table th {
        background-color: #f5f5f5;
        font-weight: 600;
    }
    
    .order-id {
        font-weight: 600;
    }
    
    .product-preview {
        display: flex;
        align-items: center;
    }
    
    .product-preview img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
    }
    
    .more-items {
        margin-left: 10px;
        font-size: 12px;
        color: #666;
        background-color: #f0f0f0;
        padding: 2px 6px;
        border-radius: 10px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
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
    
    .order-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-view,
    .btn-cancel,
    .btn-reorder {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        transition: background-color 0.3s;
    }
    
    .btn-view {
        background-color: #4a90e2;
        color: #fff;
    }
    
    .btn-cancel {
        background-color: #e74c3c;
        color: #fff;
    }
    
    .btn-reorder {
        background-color: #2ecc71;
        color: #fff;
    }
    
    .btn-view:hover,
    .btn-cancel:hover,
    .btn-reorder:hover {
        opacity: 0.9;
    }
    
    .btn-view i,
    .btn-cancel i,
    .btn-reorder i {
        margin-right: 5px;
    }
    
    .empty-orders {
        text-align: center;
        padding: 40px 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .empty-orders p {
        margin-bottom: 20px;
        font-size: 16px;
        color: #666;
    }
    
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #4a90e2;
        color: #fff;
        border-radius: 4px;
        text-decoration: none;
        transition: background-color 0.3s;
    }
    
    .btn:hover {
        background-color: #3a7bc8;
    }
    
    @media (max-width: 768px) {
        .account-container {
            flex-direction: column;
        }
        
        .account-sidebar {
            width: 100%;
            margin-right: 0;
            margin-bottom: 20px;
        }
        
        .orders-table {
            font-size: 14px;
        }
        
        .orders-table th:nth-child(3),
        .orders-table td:nth-child(3) {
            display: none;
        }
    }
</style>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
