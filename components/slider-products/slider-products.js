/**
 * Product Slider JavaScript
 * Handles slider functionality, touch events, and cart interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các sliders
    const productSliders = document.querySelectorAll('.product-slider');
    
    // Add event listener for add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
    
    // Touch events for mobile swipe support
    productSliders.forEach(slider => {
        let touchStartX = 0;
        let touchEndX = 0;
        
        slider.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        slider.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            const sliderId = this.id;
            
            if (touchEndX < touchStartX - 50) {
                // Swipe left to go right
                slideRight(sliderId);
            } else if (touchEndX > touchStartX + 50) {
                // Swipe right to go left
                slideLeft(sliderId);
            }
        }, { passive: true });
    });
});

// Hàm điều khiển slider
function slideLeft(sliderId) {
    const slider = document.getElementById(sliderId);
    const slideWidth = calculateSlideWidth(slider);
    const scrollLeft = slider.scrollLeft;
    
    if (scrollLeft <= 0) {
        // Nếu đã ở đầu, quay lại cuối
        slider.scrollTo({
            left: slider.scrollWidth,
            behavior: 'smooth'
        });
    } else {
        // Di chuyển sang trái
        slider.scrollBy({
            left: -slideWidth,
            behavior: 'smooth'
        });
    }
}

function slideRight(sliderId) {
    const slider = document.getElementById(sliderId);
    const slideWidth = calculateSlideWidth(slider);
    
    if (slider.scrollLeft + slider.offsetWidth >= slider.scrollWidth - 10) {
        // Nếu đã ở cuối, quay lại đầu
        slider.scrollTo({
            left: 0,
            behavior: 'smooth'
        });
    } else {
        // Di chuyển sang phải
        slider.scrollBy({
            left: slideWidth,
            behavior: 'smooth'
        });
    }
}

// Tính toán chiều rộng của slide dựa trên kích thước màn hình
function calculateSlideWidth(slider) {
    const viewportWidth = window.innerWidth;
    const slideElement = slider.querySelector('.product-slide');
    
    if (!slideElement) return 0;
    
    const slideWidth = slideElement.offsetWidth;
    const slideMargin = parseInt(window.getComputedStyle(slideElement).marginRight);
    
    return slideWidth + slideMargin;
}

// Kiểm tra xem user đã đăng nhập chưa
function isUserLoggedIn() {
    // This will be evaluated server-side in PHP
    return false; // Default false, will be replaced with actual value by PHP
}

// Hiển thị modal đăng nhập
function showLoginConfirmModal() {
    if (typeof bootstrap !== 'undefined') {
        const loginModal = new bootstrap.Modal(document.getElementById('loginConfirmModal'));
        if (loginModal) {
            loginModal.show();
        } else {
            // Fallback nếu không tìm thấy modal
            window.location.href = 'index.php?page=login';
        }
    } else {
        // Fallback nếu bootstrap không được load
        window.location.href = 'index.php?page=login';
    }
}

// Hàm hiển thị thông báo
function showToast(message, type = 'info') {
    // Kiểm tra xem component bootstrap toast có sẵn không
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Toast === 'function') {
        // Sử dụng bootstrap toast
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-delay', '3000');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl);
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    } else {
        // Fallback cho thông báo tự tạo
        const toastContainer = document.querySelector('.toast-container') || createCustomToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        toastContainer.appendChild(toast);
        
        // Hiệu ứng hiển thị
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Create toast container for bootstrap toasts
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1080';
    container.style.marginBottom = '1.5rem';
    container.style.marginRight = '1.5rem';
    document.body.appendChild(container);
    return container;
}

// Create custom toast container for fallback implementation
function createCustomToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// Add to cart functionality (placeholder)
function addToCart(productId) {
    // Check if user is logged in before adding to cart
    if (!isUserLoggedIn()) {
        showLoginConfirmModal();
        return;
    }

    // Example implementation - would typically make an AJAX call to server
    fetch('actions/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Sản phẩm đã được thêm vào giỏ hàng', 'success');
            // Update cart badge if it exists
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(data.cartCount);
            }
        } else {
            showToast(data.message || 'Không thể thêm sản phẩm vào giỏ hàng', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Đã xảy ra lỗi khi thêm sản phẩm', 'error');
    });
}