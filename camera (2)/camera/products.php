<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Sản phẩm';

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Xử lý lọc sản phẩm
$where = '';
$url_params = '';

// Lọc theo danh mục
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = (int)$_GET['category'];
    $where .= "p.category_id = $category_id";
    $url_params .= "category=$category_id&";
    
    // Lấy thông tin danh mục
    $category = get_category_by_id($category_id);
    if ($category) {
        $page_title = $category['name'];
    }
}

// Tìm kiếm theo từ khóa
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = db_escape($_GET['keyword']);
    if (!empty($where)) {
        $where .= " AND ";
    }
    $where .= "(p.name LIKE '%$keyword%' OR p.description LIKE '%$keyword%')";
    $url_params .= "keyword=" . urlencode($_GET['keyword']) . "&";
    
    $page_title = 'Tìm kiếm: ' . $_GET['keyword'];
}

// Lọc theo giá
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $min_price = (float)$_GET['min_price'];
    if (!empty($where)) {
        $where .= " AND ";
    }
    $where .= "p.price >= $min_price";
    $url_params .= "min_price=$min_price&";
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $max_price = (float)$_GET['max_price'];
    if (!empty($where)) {
        $where .= " AND ";
    }
    $where .= "p.price <= $max_price";
    $url_params .= "max_price=$max_price&";
}

// Sắp xếp sản phẩm
$order_by = "p.id DESC";
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $order_by = "p.price ASC";
            break;
        case 'price_desc':
            $order_by = "p.price DESC";
            break;
        case 'name_asc':
            $order_by = "p.name ASC";
            break;
        case 'name_desc':
            $order_by = "p.name DESC";
            break;
        case 'newest':
            $order_by = "p.id DESC";
            break;
    }
    $url_params .= "sort=" . $_GET['sort'] . "&";
}

// Lấy tổng số sản phẩm
$total_products = db_count('products p', $where);
$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$products = get_all_products($limit, $offset, $where);

// Lấy danh sách danh mục
$categories = get_all_categories();

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<section class="products-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Trang chủ</a> &gt; 
                <?php if (isset($category)): ?>
                <a href="<?php echo SITE_URL; ?>/products.php">Sản phẩm</a> &gt; <?php echo $category['name']; ?>
                <?php else: ?>
                Sản phẩm
                <?php endif; ?>
            </div>
        </div>
        
        <div class="products-container">
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>Danh mục sản phẩm</h3>
                    <ul class="category-list">
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>" class="<?php echo (isset($category_id) && $category_id == $cat['id']) ? 'active' : ''; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>Lọc theo giá</h3>
                    <form action="<?php echo SITE_URL; ?>/products.php" method="get" class="price-filter">
                        <?php if (isset($category_id)): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['keyword'])): ?>
                        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($_GET['keyword']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="min_price">Giá từ:</label>
                            <input type="number" id="min_price" name="min_price" value="<?php echo isset($_GET['min_price']) ? (int)$_GET['min_price'] : ''; ?>" placeholder="Giá thấp nhất">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_price">Đến:</label>
                            <input type="number" id="max_price" name="max_price" value="<?php echo isset($_GET['max_price']) ? (int)$_GET['max_price'] : ''; ?>" placeholder="Giá cao nhất">
                        </div>
                        
                        <button type="submit" class="btn">Lọc</button>
                    </form>
                </div>
            </div>
            
            <div class="products-content">
                <div class="products-header">
                    <div class="products-count">
                        Hiển thị <?php echo count($products); ?> trên <?php echo $total_products; ?> sản phẩm
                    </div>
                    
                    <div class="products-sort">
                        <form action="<?php echo SITE_URL; ?>/products.php" method="get">
                            <?php if (isset($category_id)): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['keyword'])): ?>
                            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($_GET['keyword']); ?>">
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['min_price'])): ?>
                            <input type="hidden" name="min_price" value="<?php echo (int)$_GET['min_price']; ?>">
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['max_price'])): ?>
                            <input type="hidden" name="max_price" value="<?php echo (int)$_GET['max_price']; ?>">
                            <?php endif; ?>
                            
                            <label for="sort">Sắp xếp theo:</label>
                            <select name="sort" id="sort" onchange="this.form.submit()">
                                <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Giá tăng dần</option>
                                <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Giá giảm dần</option>
                                <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_desc') ? 'selected' : ''; ?>>Tên Z-A</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
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
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo $url_params; ?>page=<?php echo $page - 1; ?>" class="prev">&laquo; Trước</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo $url_params; ?>page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo $url_params; ?>page=<?php echo $page + 1; ?>" class="next">Tiếp &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="no-products">
                    <p>Không tìm thấy sản phẩm nào.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>
