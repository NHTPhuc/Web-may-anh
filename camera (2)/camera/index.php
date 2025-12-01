<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Trang chủ';

// Lấy sản phẩm nổi bật
$featured_products = get_featured_products(8);

// Lấy danh mục sản phẩm
$categories = get_all_categories();

// Lấy sản phẩm mới nhất
$latest_products = get_latest_products(4);

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<!-- Banner chính -->
<section class="banner-full" style="position: relative; width: 100vw; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; min-height: 380px; height: 52vw; max-height: 520px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
    <img src="<?php echo SITE_URL; ?>/assets/images/products/best-camera-stores-to-buy-from-13.png" alt="Banner Máy Ảnh" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; filter: brightness(0.6) blur(1.5px); z-index: 1;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg,rgba(22,119,230,0.78) 0%,rgba(56,182,255,0.44) 100%); z-index: 2;"></div>
    <div class="banner-content" style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; max-width: 700px; margin: 0 auto; text-align: center;">
        <h1 class="animate-banner-title">STROTE CAMERA -<br>Cửa hàng máy ảnh chuyên nghiệp</h1>
        <p class="animate-banner-desc">Cung cấp các sản phẩm máy ảnh, ống kính và phụ kiện chính hãng</p>
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn" style="background: linear-gradient(90deg,#38b6ff,#1677e6); color: #fff; font-weight: 700; padding: 14px 38px; border-radius: 22px; font-size: 1.18rem; box-shadow: 0 2px 16px rgba(22,119,230,0.13); transition: 0.2s;">Khám phá ngay</a>
    </div>
</section>

<!-- Danh mục sản phẩm -->
<section class="categories-section">
    <div class="container">
        <h2>Danh mục sản phẩm</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <img src="<?php echo !empty($category['image']) ? (strpos($category['image'], 'assets/images/categories/') === 0 ? SITE_URL . '/' . $category['image'] : SITE_URL . '/assets/images/categories/' . $category['image']) : SITE_URL . '/assets/images/category-default.jpg'; ?>"
     alt="Ảnh danh mục <?php echo htmlspecialchars($category['name']); ?>"
     style="width:80%;max-width:200px;height:140px;object-fit:cover;border-radius:16px;display:block;margin:0 auto 16px auto;">
                <h3><?php echo $category['name']; ?></h3>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" class="btn-small">Xem sản phẩm</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


        <div class="view-all">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Sản phẩm mới nhất -->
<section class="latest-products">
    <div class="container">
        <h2>Sản phẩm mới nhất</h2>
        <div class="products-grid">
            <?php foreach ($latest_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?php echo !empty($product['image']) ? SITE_URL . '/assets/images/products/' . $product['image'] : SITE_URL . '/assets/images/product-default.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                    <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                    <span class="discount-badge">Giảm <?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3><?php echo $product['name']; ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?php echo format_currency($product['price']); ?></span>
                        <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                        <span class="old-price"><?php echo format_currency($product['old_price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" class="btn-view">Chi tiết</a>
                        <button class="btn-cart add-to-cart" data-id="<?php echo $product['id']; ?>">Thêm vào giỏ</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
                    
        </div>
        <div class="about-image">
        <img src="<?php echo SITE_URL; ?>/assets/images/ChatGPT Image 16_13_19 16 thg 5, 2025.png" alt="STROTE CAMERA">
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
