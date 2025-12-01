<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="icon" type="image/jpeg" href="<?php echo SITE_URL; ?>/assets/images/OIP.jpg">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <h1>STROTE CAMERA </h1>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>">Trang chủ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php">Sản phẩm</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about.php">Giới thiệu</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php">Liên hệ</a></li>
                </ul>
            </nav>
            
            <div class="header-actions">
                <div class="search-box">
    <form action="<?php echo SITE_URL; ?>/products.php" method="get">
        <input type="text" name="keyword" placeholder="Tìm kiếm..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
</div>
                
                <div class="user-actions">
                    <?php if (is_logged_in()): ?>
                        <div class="dropdown">
                            <a href="#" class="btn-icon" title="Tài khoản">
                                <i class="fas fa-user"></i>
                            </a>
                            <div class="dropdown-content">
                                <a href="<?php echo SITE_URL; ?>/account.php">Tài khoản</a>
                                <a href="<?php echo SITE_URL; ?>/orders.php">Đơn hàng</a>
                                <?php if (is_admin()): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin">Quản trị</a>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/logout.php">Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn-icon" title="Đăng nhập">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn-icon" title="Đăng ký">
                            <i class="fas fa-user-plus"></i>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="btn-icon cart-icon" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo count_cart_items(); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <?php display_flash_message(); ?>
