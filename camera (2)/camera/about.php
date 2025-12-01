<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Thiết lập tiêu đề trang
$page_title = 'Giới thiệu';

// Include header
include_once TEMPLATE_PATH . 'header.php';
?>

<style>
@keyframes slideInLeft {
  0% {
    opacity: 0;
    transform: translateX(-50px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}
.animated-title {
  animation: slideInLeft 1s ease-out;
  display: inline-block;
}

.team-member img {
    width: 100px;
    height: 100px;
    border-radius: 80%;
    display: block;
    margin: 0 auto 10px auto;
    border: 2px solid;
    box-shadow: 0 5px 15px rgba(22, 22, 23, 0.88);

}

.team-member {
    text-align: center;
}

.team-member:hover {
    transition: all 0.3s;
    cursor: pointer;
    transform: translateY(-5px);
}
.team-grid{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 20px;
    transition: all 0.3s;
}

.about-text{
    box-shadow: 0 5px 15px ;
    border: 0 10px solid #00FFFF;
    padding: 20px;
    border-radius: 10px;

}
.about-text:hover{
    transform: translateY(-5px);
    cursor: pointer;
    animation: about-text 0.3s ease-in-out;
}

.banner-image img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
    border-radius: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}
.banner-image:hover {
    animation: auto;
    cursor: pointer;
    transform: translateY(-5px);
    transition: all 0.3s;
    
}

</style>

<section class="about-page">
    <div class="container">
        <h1 class="animated-title">Về STROTE CAMERA</h1>

        <div class="about-content">
            <div class="banner-image">
                <img src="<?php echo SITE_URL; ?>/assets/images/ChatGPT Image 16_13_19 16 thg 5, 2025.png" alt="STROTE CAMERA Store">
            </div>

            <div class="about-text">
                <h2 class="animated-title">Cửa hàng máy ảnh chuyên nghiệp</h2>
                <p class="animated-title">STROTE CAMERA là cửa hàng chuyên cung cấp các sản phẩm máy ảnh, ống kính và phụ kiện chính hãng với
                    chất lượng cao và giá cả hợp lý. Chúng tôi cam kết mang đến cho khách hàng những sản phẩm tốt nhất
                    cùng dịch vụ chăm sóc khách hàng tận tâm.</p>

                <h3 class="animated-title">Tầm nhìn</h3>
                <p class="animated-title">Trở thành cửa hàng máy ảnh hàng đầu tại Việt Nam, cung cấp các sản phẩm chất lượng cao với giá cả
                    cạnh tranh và dịch vụ khách hàng xuất sắc.</p>

                <h3 class="animated-title">Sứ mệnh</h3>
                <p class="animated-title">Mang đến cho khách hàng những sản phẩm máy ảnh và phụ kiện chính hãng với chất lượng tốt nhất, giúp
                    khách hàng lưu giữ những khoảnh khắc đáng nhớ trong cuộc sống.</p>

                <h3 class="animated-title">Giá trị cốt lõi</h3>
                <ul>
                    <li><strong class="animated-title">Chất lượng:</strong><span class="animated-title"> Chúng tôi chỉ cung cấp các sản phẩm chính hãng với chất lượng tốt nhất.</span></li>
                    <li><strong class="animated-title">Uy tín:</strong><span class="animated-title"> Luôn giữ chữ tín và cam kết với khách hàng.</span></li>
                    <li><strong class="animated-title">Tận tâm:</strong><span class="animated-title"> Đặt lợi ích của khách hàng lên hàng đầu và luôn sẵn sàng hỗ trợ.</span></li>
                    <li><strong class="animated-title">Chuyên nghiệp:</strong><span class="animated-title"> Đội ngũ nhân viên được đào tạo bài bản, am hiểu về sản phẩm.</span></li>
                </ul>
            </div>
        </div>

        <!-- Đảm bảo tất cả thẻ ul, li đã đóng trước khi bắt đầu team-section -->

        <div class="team-section">
            <h2>Đội ngũ của chúng tôi</h2>
            <div class="team-grid">
                <div class="team-member">
                    <img src="<?php echo SITE_URL; ?>/assets/images/team/image.png">
                    <h3>Nguyễn Đặng Trường An</h3>
                    <p>Giám đốc</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo SITE_URL; ?>/assets/images/team/image3.png">
                    <h3>Ngô Hoài Trọng Phúc</h3>
                    <p>Quản lý cửa hàng</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo SITE_URL; ?>/assets/images/team/image2.png">
                    <h3>Hoàng Anh</h3>
                    <p>Chuyên viên tư vấn</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo SITE_URL; ?>/assets/images/team/member4.jpg">
                    <h3>Thanh Vân</h3>
                    <p>Chăm sóc khách hàng</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo SITE_URL; ?>/assets/images/team/member4.jpg">
                    <h3>Hồng Mai</h3>
                    <p>Chăm sóc khách hàng</p>
                </div>
            </div>
</section>

<?php
// Include footer
include_once TEMPLATE_PATH . 'footer.php';
?>