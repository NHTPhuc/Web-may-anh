<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Giỏ hàng';

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    remove_from_cart($product_id);
    
    set_flash_message('success', 'Sản phẩm đã được xóa khỏi giỏ hàng');
    redirect(SITE_URL . '/cart.php');
}

// Xử lý cập nhật giỏ hàng
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        update_cart($product_id, $quantity);
    }
    
    set_flash_message('success', 'Giỏ hàng đã được cập nhật');
    redirect(SITE_URL . '/cart.php');
}

// Xử lý đặt lại đơn hàng
if (isset($_GET['repeat_order']) && !empty($_GET['repeat_order'])) {
    $order_id = (int)$_GET['repeat_order'];
    $order_items = get_order_items($order_id);
    if ($order_items) {
        clear_cart();
        foreach ($order_items as $item) {
            add_to_cart($item['product_id'], $item['quantity']);
        }
        set_flash_message('success', 'Đã thêm lại sản phẩm từ đơn hàng vào giỏ hàng!');
    } else {
        set_flash_message('error', 'Không tìm thấy đơn hàng hoặc đơn hàng không có sản phẩm!');
    }
    redirect(SITE_URL . '/cart.php');
}

// Xử lý xóa toàn bộ giỏ hàng
if (isset($_GET['clear'])) {
    clear_cart();
    
    set_flash_message('success', 'Giỏ hàng đã được xóa');
    redirect(SITE_URL . '/cart.php');
}

// Lấy giỏ hàng
$cart_items = get_cart();
$cart_total = calculate_cart_total();


// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1>Giỏ hàng</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Trang chủ</a> &gt; Giỏ hàng
            </div>
        </div>
        
        <?php if (count($cart_items) > 0): ?>
        <form action="<?php echo SITE_URL; ?>/cart.php" method="post" class="cart-form">
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th class="product-thumbnail">Hình ảnh</th>
                            <th class="product-name">Sản phẩm</th>
                            <th class="product-price">Giá</th>
                            <th class="product-quantity">Số lượng</th>
                            <th class="product-subtotal">Tạm tính</th>
                            <th class="product-remove">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="product-thumbnail">
                                <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $item['id']; ?>">
                                    <img src="<?php echo !empty($item['image']) ? SITE_URL . '/assets/images/products/' . $item['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                </a>
                            </td>
                            <td class="product-name">
                                <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $item['id']; ?>"><?php echo $item['name']; ?></a>
                            </td>
                            <td class="product-price">
                                <span class="price"><?php echo format_currency($item['price']); ?></span>
                            </td>
                            <td class="product-quantity">
                                <div class="quantity-input">
                                    <button type="button" class="quantity-btn minus" data-id="<?php echo $item['id']; ?>">-</button>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="quantity" data-id="<?php echo $item['id']; ?>">
                                    <button type="button" class="quantity-btn plus" data-id="<?php echo $item['id']; ?>">+</button>
                                </div>
                            </td>
                            <td class="product-subtotal">
                                <span class="price"><?php echo format_currency($item['price'] * $item['quantity']); ?></span>
                            </td>
                            <td class="product-remove">
                                <a href="<?php echo SITE_URL; ?>/cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-totals">
                <h2>Tổng giỏ hàng</h2>
                <table>
                    <tr>
                        <th>Tạm tính</th>
                        <td><?php echo format_currency($cart_total); ?></td>
                    </tr>
                    <tr>
                        <th>Phí vận chuyển</th>
                        <td>Miễn phí</td>
                    </tr>
                    <tr class="total">
                        <th>Tổng cộng</th>
                        <td><?php echo format_currency($cart_total); ?></td>
                    </tr>
                </table>
                <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn checkout-btn">Thanh toán</a>
            </div>
        </form>
        <?php else: ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2>Giỏ hàng trống</h2>
            <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn">Tiếp tục mua sắm</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý nút tăng giảm số lượng
        const minusBtns = document.querySelectorAll('.quantity-btn.minus');
        const plusBtns = document.querySelectorAll('.quantity-btn.plus');
        
        minusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        plusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                let max = parseInt(input.getAttribute('max'));
                if (value < max) {
                    input.value = value + 1;
                }
            });
        });
    });
</script>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
