/**
 * STROTE CAMERA - Camera Shop JavaScript
 */

document.addEventListener("DOMContentLoaded", () => {
    // Khởi tạo các sự kiện
    initAddToCart()
    initQuantityButtons()
    initProductTabs()
    initRatingSelect()
    initNotifications()
    initMobileMenu()
    initDropdowns()
    initImageZoom()
    initNewsletterForm()
})

/**
 * Khởi tạo sự kiện thêm vào giỏ hàng
 */
// Event delegation for .add-to-cart buttons to prevent multiple handlers
// and fix double add-to-cart bug

document.addEventListener("click", function(e) {
    // Check if the clicked element is .add-to-cart button
    let target = e.target;
    // If icon or span inside button is clicked, get the button itself
    if (target && !target.classList.contains("add-to-cart") && target.closest(".add-to-cart")) {
        target = target.closest(".add-to-cart");
    }
    if (target && target.classList.contains("add-to-cart")) {
        e.preventDefault();
        const productId = target.dataset.id;
        addToCart(productId, 1);
    }
});

/**
 * Thêm sản phẩm vào giỏ hàng
 */
function addToCart(productId, quantity = 1, redirect = false) {
    // Tạo form data
    const formData = new FormData()
    formData.append("product_id", productId)
    formData.append("quantity", quantity)

    // Gửi request
    fetch("ajax/add-to-cart.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Cập nhật số lượng giỏ hàng
                updateCartCount(data.cart_count)

                // Hiển thị thông báo
                showNotification("Đã thêm sản phẩm vào giỏ hàng!", "success")

                // Chuyển đến trang thanh toán nếu là mua ngay
                if (redirect) {
                    window.location.href = "checkout.php"
                }
            } else {
                showNotification(data.message, "error")
            }
        })
        .catch((error) => {
            console.error("Error:", error)
            showNotification("Có lỗi xảy ra. Vui lòng thử lại sau.", "error")
        })
}

/**
 * Cập nhật số lượng giỏ hàng
 */
function updateCartCount(count) {
    const cartCountElement = document.querySelector(".cart-count")

    if (cartCountElement) {
        cartCountElement.textContent = count
    }
}

/**
 * Khởi tạo nút tăng giảm số lượng
 */
function initQuantityButtons() {
    const minusButtons = document.querySelectorAll(".quantity-btn.minus");
    const plusButtons = document.querySelectorAll(".quantity-btn.plus");
    const quantityInputs = document.querySelectorAll(".quantity-input .quantity");

    function ajaxUpdateCart(productId, quantity, input) {
        const formData = new FormData();
        formData.append("action", "update_cart");
        formData.append("product_id", productId);
        formData.append("quantity", quantity);
        fetch("cart-handler.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Cập nhật lại subtotal từng dòng từ server
                if (input) {
                    const row = input.closest("tr");
                    const subtotalElement = row.querySelector(".product-subtotal .price");
                    if (subtotalElement && data.item_subtotal) {
                        subtotalElement.textContent = data.item_subtotal;
                    }
                }
                // Cập nhật tổng tiền từ server
                if (data.cart_total) {
                    // Tổng cộng
                    const totalElements = document.querySelectorAll(".cart-totals .total td, .cart-totals tr:nth-child(2) td");
                    totalElements.forEach(el => el.textContent = data.cart_total);
                }
                // Cập nhật số lượng giỏ hàng
                if (typeof updateCartCount === "function" && data.cart_count !== undefined) updateCartCount(data.cart_count);
            } else {
                showNotification(data.message || "Cập nhật giỏ hàng thất bại", "error");
            }
        })
        .catch(() => {
            showNotification("Có lỗi xảy ra khi cập nhật giỏ hàng", "error");
        });
    }

    minusButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const input = this.nextElementSibling;
            let value = Number.parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
                ajaxUpdateCart(input.dataset.id, input.value, input);
            }
        });
    });
    plusButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const input = this.previousElementSibling;
            let value = Number.parseInt(input.value);
            let max = Number.parseInt(input.getAttribute("max") || 99);
            if (value < max) {
                input.value = value + 1;
                ajaxUpdateCart(input.dataset.id, input.value, input);
            }
        });
    });
    // Nếu nhập trực tiếp số
    quantityInputs.forEach((input) => {
        input.addEventListener("change", function () {
            let value = Number.parseInt(this.value);
            let min = Number.parseInt(this.getAttribute("min") || 1);
            let max = Number.parseInt(this.getAttribute("max") || 99);
            if (isNaN(value) || value < min) value = min;
            if (value > max) value = max;
            this.value = value;
            ajaxUpdateCart(this.dataset.id, value, this);
        });
    });
}

/**
 * Cập nhật giá sản phẩm trong giỏ hàng
 */
function updateCartItemPrice(input) {
    const row = input.closest("tr")
    const priceElement = row.querySelector(".product-price .price")
    const subtotalElement = row.querySelector(".product-subtotal .price")

    if (priceElement && subtotalElement) {
        const price = Number.parseFloat(priceElement.textContent.replace(/[^\d]/g, ""))
        const quantity = Number.parseInt(input.value)

        const subtotal = price * quantity
        subtotalElement.textContent = formatCurrency(subtotal)

        // Cập nhật tổng giỏ hàng
        updateCartTotal()
    }
}

/**
 * Cập nhật tổng giỏ hàng
 */
function updateCartTotal() {
    const subtotalElements = document.querySelectorAll(".product-subtotal .price")
    let total = 0

    subtotalElements.forEach((element) => {
        const subtotal = Number.parseFloat(element.textContent.replace(/[^\d]/g, ""))
        total += subtotal
    })

    const totalElement = document.querySelector(".cart-totals .total td")

    if (totalElement) {
        totalElement.textContent = formatCurrency(total)
    }
}

/**
 * Định dạng tiền tệ
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(amount)
}

/**
 * Khởi tạo tabs sản phẩm
 */
function initProductTabs() {
    const tabButtons = document.querySelectorAll(".tab-btn")
    const tabPanels = document.querySelectorAll(".tab-panel")

    if (tabButtons.length > 0) {
        tabButtons.forEach((button) => {
            button.addEventListener("click", function() {
                // Xóa trạng thái active
                tabButtons.forEach((btn) => btn.classList.remove("active"))
                tabPanels.forEach((panel) => panel.classList.remove("active"))

                // Thêm trạng thái active cho tab được click
                this.classList.add("active")

                const tabId = this.dataset.tab
                const tabPanel = document.getElementById(tabId)

                if (tabPanel) {
                    tabPanel.classList.add("active")
                }
            })
        })
    }
}

/**
 * Khởi tạo chọn đánh giá sao
 */
function initRatingSelect() {
    const stars = document.querySelectorAll(".rating-select i")
    const ratingInput = document.getElementById("rating")

    if (stars.length > 0 && ratingInput) {
        stars.forEach((star) => {
            star.addEventListener("mouseover", function() {
                const rating = this.dataset.rating

                // Reset tất cả sao
                stars.forEach((s) => (s.className = "far fa-star"))

                // Highlight sao được hover
                for (let i = 0; i < rating; i++) {
                    stars[i].className = "fas fa-star"
                }
            })

            star.addEventListener("mouseout", () => {
                const currentRating = ratingInput.value

                // Reset tất cả sao
                stars.forEach((s) => (s.className = "far fa-star"))

                // Highlight sao đã chọn
                for (let i = 0; i < currentRating; i++) {
                    stars[i].className = "fas fa-star"
                }
            })

            star.addEventListener("click", function() {
                const rating = this.dataset.rating
                ratingInput.value = rating

                // Highlight sao đã chọn
                stars.forEach((s) => (s.className = "far fa-star"))
                for (let i = 0; i < rating; i++) {
                    stars[i].className = "fas fa-star"
                }
            })
        })
    }
}

/**
 * Khởi tạo thông báo
 */
function initNotifications() {
    // Tự động ẩn thông báo sau 5 giây
    const alerts = document.querySelectorAll(".alert")

    if (alerts.length > 0) {
        alerts.forEach((alert) => {
            setTimeout(() => {
                alert.style.opacity = "0"
                setTimeout(() => {
                    alert.remove()
                }, 500)
            }, 5000)
        })
    }
}

/**
 * Hiển thị thông báo
 */
function showNotification(message, type = "info") {
    // Tạo phần tử thông báo
    const notification = document.createElement("div")
    notification.className = `notification ${type}`
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `

    // Thêm vào body
    document.body.appendChild(notification)

    // Hiển thị thông báo
    setTimeout(() => {
        notification.classList.add("show")
    }, 10)

    // Thêm sự kiện đóng thông báo
    const closeButton = notification.querySelector(".notification-close")
    closeButton.addEventListener("click", () => {
        notification.classList.remove("show")
        setTimeout(() => {
            notification.remove()
        }, 300)
    })

    // Tự động ẩn thông báo sau 5 giây
    setTimeout(() => {
        notification.classList.remove("show")
        setTimeout(() => {
            notification.remove()
        }, 300)
    }, 5000)
}

/**
 * Khởi tạo menu mobile
 */
function initMobileMenu() {
    const menuToggle = document.querySelector(".menu-toggle")
    const mainNav = document.querySelector(".main-nav")

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", function() {
            mainNav.classList.toggle("active")
            this.classList.toggle("active")
        })
    }
}

/**
 * Khởi tạo dropdown
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll(".dropdown")

    if (dropdowns.length > 0) {
        dropdowns.forEach((dropdown) => {
            const toggle = dropdown.querySelector(".dropdown-toggle")
            const content = dropdown.querySelector(".dropdown-content")

            if (toggle && content) {
                toggle.addEventListener("click", (e) => {
                    e.preventDefault()
                    content.classList.toggle("show")
                })

                // Đóng dropdown khi click bên ngoài
                document.addEventListener("click", (e) => {
                    if (!dropdown.contains(e.target)) {
                        content.classList.remove("show")
                    }
                })
            }
        })
    }
}

/**
 * Khởi tạo zoom ảnh sản phẩm
 */
function initImageZoom() {
    const mainImage = document.getElementById("main-image")
    const zoomContainer = document.querySelector(".product-main-image")

    if (mainImage && zoomContainer) {
        zoomContainer.addEventListener("mousemove", function(e) {
            const { left, top, width, height } = this.getBoundingClientRect()
            const x = (e.clientX - left) / width
            const y = (e.clientY - top) / height

            mainImage.style.transformOrigin = `${x * 100}% ${y * 100}%`
            mainImage.style.transform = "scale(1.5)"
        })

        zoomContainer.addEventListener("mouseleave", () => {
            mainImage.style.transform = "scale(1)"
        })
    }
}

/**
 * Khởi tạo form đăng ký nhận tin
 */
function initNewsletterForm() {
    const newsletterForm = document.getElementById("newsletter-form")

    if (newsletterForm) {
        newsletterForm.addEventListener("submit", function(e) {
            e.preventDefault()

            const emailInput = this.querySelector('input[name="email"]')
            const email = emailInput.value.trim()

            if (email === "") {
                showNotification("Vui lòng nhập email của bạn", "error")
                return
            }

            if (!isValidEmail(email)) {
                showNotification("Email không hợp lệ", "error")
                return
            }

            // Gửi request đăng ký nhận tin
            const formData = new FormData()
            formData.append("email", email)

            fetch("ajax/newsletter.php", {
                    method: "POST",
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showNotification("Đăng ký nhận tin thành công!", "success")
                        emailInput.value = ""
                    } else {
                        showNotification(data.message, "error")
                    }
                })
                .catch((error) => {
                    console.error("Error:", error)
                    showNotification("Có lỗi xảy ra. Vui lòng thử lại sau.", "error")
                })
        })
    }
}

/**
 * Kiểm tra email hợp lệ
 */
function isValidEmail(email) {
    const re =
        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return re.test(String(email).toLowerCase())
}

/**
 * Đổi hình ảnh chính khi click vào thumbnail
 */
function changeImage(element) {
    const mainImage = document.getElementById("main-image")

    if (mainImage) {
        mainImage.src = element.src

        // Đổi trạng thái active
        const thumbnails = document.querySelectorAll(".thumbnail")
        thumbnails.forEach((thumbnail) => {
            thumbnail.classList.remove("active")
        })

        element.parentElement.classList.add("active")
    }
}

/**
 * Xử lý form đánh giá sản phẩm
 */
function submitReview(productId) {
    const rating = document.getElementById("rating").value
    const review = document.getElementById("review").value

    if (rating === "0") {
        showNotification("Vui lòng chọn số sao đánh giá", "error")
        return
    }

    if (review.trim() === "") {
        showNotification("Vui lòng nhập nội dung đánh giá", "error")
        return
    }

    // Gửi request đánh giá sản phẩm
    const formData = new FormData()
    formData.append("product_id", productId)
    formData.append("rating", rating)
    formData.append("review", review)

    fetch("ajax/review.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showNotification("Đánh giá sản phẩm thành công!", "success")

                // Reset form
                document.getElementById("rating").value = "0"
                document.getElementById("review").value = ""

                // Reset sao
                const stars = document.querySelectorAll(".rating-select i")
                stars.forEach((star) => (star.className = "far fa-star"))

                // Reload trang sau 2 giây
                setTimeout(() => {
                    location.reload()
                }, 2000)
            } else {
                showNotification(data.message, "error")
            }
        })
        .catch((error) => {
            console.error("Error:", error)
            showNotification("Có lỗi xảy ra. Vui lòng thử lại sau.", "error")
        })
}

/**
 * Xử lý form liên hệ
 */
function submitContact() {
    const name = document.getElementById("contact-name").value.trim()
    const email = document.getElementById("contact-email").value.trim()
    const subject = document.getElementById("contact-subject").value.trim()
    const message = document.getElementById("contact-message").value.trim()

    if (name === "" || email === "" || subject === "" || message === "") {
        showNotification("Vui lòng nhập đầy đủ thông tin", "error")
        return
    }

    if (!isValidEmail(email)) {
        showNotification("Email không hợp lệ", "error")
        return
    }

    // Gửi request liên hệ
    const formData = new FormData()
    formData.append("name", name)
    formData.append("email", email)
    formData.append("subject", subject)
    formData.append("message", message)

    fetch("ajax/contact.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showNotification("Gửi liên hệ thành công!", "success")

                // Reset form
                document.getElementById("contact-name").value = ""
                document.getElementById("contact-email").value = ""
                document.getElementById("contact-subject").value = ""
                document.getElementById("contact-message").value = ""
            } else {
                showNotification(data.message, "error")
            }
        })
        .catch((error) => {
            console.error("Error:", error)
            showNotification("Có lỗi xảy ra. Vui lòng thử lại sau.", "error")
        })
}
/**
 * Thêm CSS cho thông báo
 */
;
(() => {
    const style = document.createElement("style")
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #777;
            margin-left: 10px;
        }
        
        .notification.success {
            border-left: 4px solid #2ecc71;
        }
        
        .notification.error {
            border-left: 4px solid #e74c3c;
        }
        
        .notification.info {
            border-left: 4px solid #3498db;
        }
        
        .notification.warning {
            border-left: 4px solid #f39c12;
        }
        
        @media (max-width: 768px) {
            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    `
    document.head.appendChild(style)
})()