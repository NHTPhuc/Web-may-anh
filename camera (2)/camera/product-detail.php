<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kiểm tra ID sản phẩm
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(SITE_URL);
}

$product_id = (int)$_GET['id'];
$product = get_product_by_id($product_id);

// Nếu không tìm thấy sản phẩm, chuyển hướng về trang sản phẩm
if (!$product) {
    set_flash_message('error', 'Không tìm thấy sản phẩm');
    redirect(SITE_URL . '/products.php');
}

// Thiết lập tiêu đề trang
$page_title = $product['name'];

// Lấy sản phẩm liên quan
$related_products = get_related_products($product_id, $product['category_id'], 4);

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="product-detail">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Trang chủ</a> &gt; 
            <a href="<?php echo SITE_URL; ?>/products.php">Sản phẩm</a> &gt; 
            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a> &gt; 
            <?php echo $product['name']; ?>
        </div>
        
        <div class="product-detail-content">
            <div class="product-gallery">
                <div class="product-main-image">
                    <img src="<?php echo !empty($product['image']) ? SITE_URL . '/assets/images/products/' . $product['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $product['name']; ?>" id="main-image">
                </div>
                
                <div class="product-thumbnails">
                    <div class="thumbnail active">
                        <img src="<?php echo !empty($product['image']) ? SITE_URL . '/assets/images/products/' . $product['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $product['name']; ?>" onclick="changeImage(this)">
                    </div>
                    <!-- Thêm các thumbnail khác nếu có -->
                </div>
            </div>
            
            <div class="product-info">
                <h1><?php echo $product['name']; ?></h1>
                
                <div class="product-price">
                    <span class="current-price"><?php echo format_currency($product['price']); ?></span>
                    <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                    <span class="old-price"><?php echo format_currency($product['old_price']); ?></span>
                    <span class="discount-badge">Giảm <?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-meta">
                    <div class="product-category">
                        <span>Danh mục:</span>
                        <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a>
                    </div>
                    
                    <div class="product-stock">
                        <span>Tình trạng:</span>
                        <?php if ($product['stock'] > 0): ?>
                        <span class="in-stock">Còn hàng (<?php echo $product['stock']; ?>)</span>
                        <?php else: ?>
                        <span class="out-of-stock">Hết hàng</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-description">
                    <h3>Mô tả sản phẩm</h3>
                    <div class="description-content">
                        <?php echo nl2br($product['description']); ?>
                    </div>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                <div class="product-actions">
                    <div class="quantity-selector">
                        <label for="quantity">Số lượng:</label>
                        <div class="quantity-input">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button type="button" class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    
                    <button class="btn add-to-cart-btn" data-id="<?php echo $product['id']; ?>">Thêm vào giỏ hàng</button>
                    <button class="btn buy-now-btn" data-id="<?php echo $product['id']; ?>">Mua ngay</button>
                </div>
                <?php else: ?>
                <div class="out-of-stock-message">
                    <p>Sản phẩm hiện đang hết hàng. Vui lòng quay lại sau.</p>
                </div>
                <?php endif; ?>
                
                <div class="product-share">
                    <span>Chia sẻ:</span>
                    <a href="#" class="share-btn facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="share-btn twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="share-btn pinterest"><i class="fab fa-pinterest-p"></i></a>
                </div>
            </div>
        </div>
        
        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="description">Mô tả chi tiết</button>
                <button class="tab-btn" data-tab="specifications">Thông số kỹ thuật</button>
                <button class="tab-btn" data-tab="reviews">Đánh giá</button>
            </div>
            
            <div class="tabs-content">
                <div class="tab-panel active" id="description">
                    <div class="tab-content">
                        <?php echo nl2br($product['description']); ?>
                    </div>
                </div>
                
                <div class="tab-panel" id="specifications">
                    <div class="tab-content">
                        <table class="specs-table">
                            <tr>
                                <th>Thương hiệu</th>
                                <td>Canon</td>
                            </tr>
                            <tr>
                                <th>Model</th>
                                <td>EOS 5D Mark IV</td>
                            </tr>
                            <tr>
                                <th>Độ phân giải</th>
                                <td>30.4 MP</td>
                            </tr>
                            <tr>
                                <th>Loại cảm biến</th>
                                <td>CMOS</td>
                            </tr>
                            <tr>
                                <th>Dải ISO</th>
                                <td>100-32000 (mở rộng: 50-102400)</td>
                            </tr>
                            <tr>
                                <th>Độ phân giải video</th>
                                <td>4K (4096 x 2160) @ 30fps</td>
                            </tr>
                            <tr>
                                <th>Trọng lượng</th>
                                <td>800g (chỉ thân máy)</td>
                            </tr>
                            <tr>
                                <th>Kích thước</th>
                                <td>150.7 x 116.4 x 75.9mm</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="tab-panel" id="reviews">
                    <div class="tab-content">
                        <div class="reviews-container">
                            <div class="review">
                                <div class="review-header">
                                    <span class="reviewer">Nguyễn Văn A</span>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span class="review-date">15/05/2023</span>
                                </div>
                                <div class="review-content">
                                    <p>Sản phẩm rất tốt, chất lượng hình ảnh tuyệt vời. Giao hàng nhanh và đóng gói cẩn thận.</p>
                                </div>
                            </div>
                            
                            <div class="review">
                                <div class="review-header">
                                    <span class="reviewer">Trần Thị B</span>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <span class="review-date">10/04/2023</span>
                                </div>
                                <div class="review-content">
                                    <p>Máy ảnh rất tốt, nhưng pin hơi yếu. Nhìn chung vẫn rất hài lòng với sản phẩm.</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (is_logged_in()): ?>
                        <div class="write-review">
                            <h3>Viết đánh giá của bạn</h3>
                            <form id="review-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="rating">Đánh giá:</label>
                                    <div class="rating-select">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" value="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="review">Nội dung đánh giá:</label>
                                    <textarea name="review" id="review" rows="5" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn">Gửi đánh giá</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="login-to-review">
                            <p>Vui lòng <a href="<?php echo SITE_URL; ?>/login.php">đăng nhập</a> để viết đánh giá.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (count($related_products) > 0): ?>
        <section class="related-products">
            <h2>Sản phẩm liên quan</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $related): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo !empty($related['image']) ? SITE_URL . '/assets/images/products/' . $related['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $related['name']; ?>">
                        <?php if (!empty($related['old_price']) && $related['old_price'] > $related['price']): ?>
                        <span class="discount-badge">Giảm <?php echo round((($related['old_price'] - $related['price']) / $related['old_price']) * 100); ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?php echo $related['name']; ?></h3>
                        <div class="product-price">
                            <span class="current-price"><?php echo format_currency($related['price']); ?></span>
                            <?php if (!empty($related['old_price']) && $related['old_price'] > $related['price']): ?>
                            <span class="old-price"><?php echo format_currency($related['old_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $related['id']; ?>" class="btn-view">Chi tiết</a>
                            <button class="btn-cart add-to-cart" data-id="<?php echo $related['id']; ?>">Thêm vào giỏ</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<script>
    // Đổi hình ảnh chính khi click vào thumbnail
    function changeImage(element) {
        document.getElementById('main-image').src = element.src;
        
        // Đổi trạng thái active
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumbnail => {
            thumbnail.classList.remove('active');
        });
        
        element.parentElement.classList.add('active');
    }
    
    // Xử lý tabs
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Xóa trạng thái active
                tabBtns.forEach(btn => btn.classList.remove('active'));
                tabPanels.forEach(panel => panel.classList.remove('active'));
                
                // Thêm trạng thái active cho tab được click
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Xử lý đánh giá sao
        const stars = document.querySelectorAll('.rating-select i');
        const ratingInput = document.getElementById('rating');
        
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const rating = this.dataset.rating;
                
                // Reset tất cả sao
                stars.forEach(s => s.className = 'far fa-star');
                
                // Highlight sao được hover
                for (let i = 0; i < rating; i++) {
                    stars[i].className = 'fas fa-star';
                }
            });
            
            star.addEventListener('mouseout', function() {
                const currentRating = ratingInput.value;
                
                // Reset tất cả sao
                stars.forEach(s => s.className = 'far fa-star');
                
                // Highlight sao đã chọn
                for (let i = 0; i < currentRating; i++) {
                    stars[i].className = 'fas fa-star';
                }
            });
            
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                
                // Highlight sao đã chọn
                stars.forEach(s => s.className = 'far fa-star');
                for (let i = 0; i < rating; i++) {
                    stars[i].className = 'fas fa-star';
                }
            });
        });
        
        // Xử lý nút tăng giảm số lượng
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const quantityInput = document.getElementById('quantity');
        
        minusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
        
        plusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.getAttribute('max'));
            if (value < max) {
                quantityInput.value = value + 1;
            }
        });
        
        // Xử lý thêm vào giỏ hàng
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        
        addToCartBtn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const quantity = parseInt(quantityInput.value);
            
            addToCart(productId, quantity);
        });
        
        // Xử lý mua ngay
        const buyNowBtn = document.querySelector('.buy-now-btn');
        
        buyNowBtn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const quantity = parseInt(quantityInput.value);
            
            // Thêm vào giỏ hàng và chuyển đến trang thanh toán
            addToCart(productId, quantity, true);
        });
    });
    
    // Hàm thêm vào giỏ hàng
    function addToCart(productId, quantity, redirect = false) {
        fetch('<?php echo SITE_URL; ?>/ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=' + quantity
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật số lượng giỏ hàng
                document.querySelector('.cart-count').textContent = data.cart_count;
                
                // Hiển thị thông báo
                showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
                
                // Chuyển đến trang thanh toán nếu là mua ngay
                if (redirect) {
                    window.location.href = '<?php echo SITE_URL; ?>/checkout.php';
                }
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Có lỗi xảy ra. Vui lòng thử lại sau.', 'error');
        });
    }
</script>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
