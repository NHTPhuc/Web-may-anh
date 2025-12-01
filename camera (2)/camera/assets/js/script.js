/**
 * STROTE CAMERA - Camera Shop JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    initAddToCartButtons();
    
    // Newsletter form
    initNewsletterForm();
    
    // Mobile menu toggle
    initMobileMenu();
});

/**
 * Initialize Add to Cart buttons
 */
function initAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            addToCart(productId, 1);
        });
    });
}

/**
 * Add product to cart via AJAX
 */
function addToCart(productId, quantity) {
    // Show loading state
    const button = document.querySelector(`.add-to-cart[data-id="${productId}"]`);
    let originalText = 'Thêm vào giỏ';
    
    if (button) {
        originalText = button.textContent;
        button.textContent = 'Đang thêm...';
        button.disabled = true;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('action', 'add_to_cart');
    
    // Send AJAX request
    fetch('cart-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount(data.cart_count);
            
            // Show success message
            showNotification('Thêm vào giỏ hàng thành công!', 'success');
            
            // Reset button state
            if (button) {
                button.textContent = 'Đã thêm';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            }
        } else {
            // Show error message
            showNotification(data.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng.', 'error');
            
            // Reset button state
            if (button) {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra khi thêm vào giỏ hàng.', 'error');
        
        // Reset button state
        if (button) {
            button.textContent = originalText;
            button.disabled = false;
        }
    });
}

/**
 * Update cart count in header
 */
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        
        // Animate the cart icon
        cartCountElement.classList.add('animate');
        setTimeout(() => {
            cartCountElement.classList.remove('animate');
        }, 500);
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    // Check if notification container exists, if not create it
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add notification to container
    notificationContainer.appendChild(notification);
    
    // Add close button functionality
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', function() {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

/**
 * Initialize newsletter form
 */
function initNewsletterForm() {
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[name="email"]').value;
            
            // Here you would typically send an AJAX request to subscribe the email
            // For now, just show a success message
            showNotification('Cảm ơn bạn đã đăng ký nhận tin!', 'success');
            this.reset();
        });
    }
}

/**
 * Initialize mobile menu
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
}
