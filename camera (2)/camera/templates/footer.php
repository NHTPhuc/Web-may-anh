</main>
    
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>Về STROTE CAMERA</h3>
                    <img src="<?php echo SITE_URL; ?>/assets/images/ChatGPT Image 16_13_19 16 thg 5, 2025.png" alt="STROTE CAMERA" style="max-width: 200px; margin-bottom: 15px;">
                    <p>STROTE CAMERA là cửa hàng chuyên cung cấp các sản phẩm máy ảnh, ống kính và phụ kiện chính hãng với chất lượng cao và giá cả hợp lý.</p>
                    <div class="contact">
                        <p><i class="fas fa-map-marker-alt"></i> 268 Lý Thường Kiệt, Phường 14, Quận 10, TP. HCM</p>
                        <p><i class="fas fa-phone"></i> 0862528965</p>
                        <p><i class="fas fa-envelope"></i> info@strotecamera.vn</p>
                    </div>
                    <div class="socials">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                
                <div class="footer-section links">
                    <h3>Liên kết nhanh</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>">Trang chủ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php">Sản phẩm</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">Giới thiệu</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Liên hệ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/terms.php">Điều khoản sử dụng</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php">Chính sách bảo mật</a></li>
                    </ul>
                </div>
                
                <div class="footer-section categories">
                    <h3>Danh mục sản phẩm</h3>
                    <ul>
                        <?php
                        $footer_categories = get_all_categories();
                        foreach ($footer_categories as $category) {
                            echo '<li><a href="' . SITE_URL . '/products.php?category=' . $category['id'] . '">' . $category['name'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="footer-section newsletter">
                    <h3>Đăng ký nhận tin</h3>
                    <p>Nhận thông tin về sản phẩm mới, khuyến mãi và mẹo chụp ảnh hàng tuần.</p>
                    <form id="newsletter-form">
                        <input type="email" name="email" placeholder="Email của bạn" required>
                        <button type="submit" class="btn">Đăng ký</button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> STROTE CAMERA. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
