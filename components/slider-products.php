<?php
/**
 * Product Slider Component
 * 
 * Hiển thị slider các sản phẩm từ một danh mục cụ thể
 * 
 * @param string $title         - Tiêu đề của slider
 * @param array $products       - Mảng các sản phẩm cần hiển thị (có thể truyền trực tiếp hoặc để component tự lấy)
 * @param string $categoryCode  - Mã của danh mục sản phẩm (tùy chọn)
 * @param int $limit            - Số lượng sản phẩm tối đa hiển thị (mặc định là 10)
 * @param string $apiEndpoint   - API endpoint tùy chỉnh để lấy sản phẩm (tùy chọn)
 * @param string $sortBy        - Tiêu chí sắp xếp (mặc định là 'discountPercentage')
 * @param string $sortOrder     - Thứ tự sắp xếp: 'desc' (giảm dần) hoặc 'asc' (tăng dần), mặc định 'desc'
 */

// Định nghĩa hàm formatCurrency nếu chưa tồn tại
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        return '$' . number_format($amount, 2);
    }
}

// Định nghĩa hàm calculateDiscount nếu chưa tồn tại
if (!function_exists('calculateDiscount')) {
    function calculateDiscount($originalPrice, $currentPrice) {
        if ($originalPrice <= 0) return 0;
        return round(($originalPrice - $currentPrice) / $originalPrice * 100);
    }
}

// Định nghĩa hàm cắt ngắn văn bản nếu chưa tồn tại
if (!function_exists('truncateText')) {
    function truncateText($text, $maxLength = 50) {
        if (strlen($text) <= $maxLength) {
            return $text;
        } else {
            $text = substr($text, 0, $maxLength);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false) {
                $text = substr($text, 0, $lastSpace);
            }
            return $text . '...';
        }
    }
}

// Định nghĩa hàm sắp xếp sản phẩm
if (!function_exists('sortProducts')) {
    function sortProducts($products, $sortBy = 'discountPercentage', $sortOrder = 'desc') {
        // Đảm bảo tất cả sản phẩm đều có trường discountPercentage
        foreach ($products as &$product) {
            if (!isset($product['discountPercentage']) || $product['discountPercentage'] === 0) {
                // Tính discountPercentage nếu không có sẵn
                $originalPrice = isset($product['originalPrice']) ? $product['originalPrice'] : 0;
                $currentPrice = isset($product['currentPrice']) ? $product['currentPrice'] : 0;
                $product['discountPercentage'] = $originalPrice > $currentPrice ? 
                    calculateDiscount($originalPrice, $currentPrice) : 0;
            }
        }

        // Sắp xếp sản phẩm theo tiêu chí
        usort($products, function($a, $b) use ($sortBy, $sortOrder) {
            // Lấy giá trị cần so sánh
            $valueA = isset($a[$sortBy]) ? $a[$sortBy] : 0;
            $valueB = isset($b[$sortBy]) ? $b[$sortBy] : 0;
            
            // So sánh theo thứ tự
            if ($sortOrder === 'desc') {
                return $valueB <=> $valueA;
            } else {
                return $valueA <=> $valueB;
            }
        });

        return $products;
    }
}

// Reset biến products để đảm bảo mỗi lần include component đều lấy dữ liệu mới
$sliderProducts = isset($products) ? $products : [];
$title = isset($title) ? $title : 'Sản phẩm nổi bật';
$limit = isset($limit) ? $limit : 10;
$sliderId = isset($categoryCode) ? 'productSlider_' . $categoryCode : 'productSlider_' . uniqid();
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : "http://localhost:5000/api/products?pageIndex=0&pageSize=$limit";
$sortBy = isset($sortBy) ? $sortBy : 'discountPercentage';
$sortOrder = isset($sortOrder) ? $sortOrder : 'desc';

// Giải pháp: Luôn gọi API để lấy dữ liệu khi có categoryCode, không phụ thuộc vào biến products
if ((empty($sliderProducts) && isset($categoryCode) && !empty($categoryCode)) || 
    (!empty($categoryCode) && !isset($_REQUEST['loadFromAPI']) && !isset($noApiCall))) {
    try {
        $_REQUEST['loadFromAPI'] = true;
        // Thêm tham số category code
        $apiUrl = $apiEndpoint;
        if (isset($categoryCode) && !empty($categoryCode)) {
            $apiUrl .= (strpos($apiUrl, '?') !== false ? '&' : '?') . "categoryCode=" . urlencode($categoryCode);
        }
        
        // Log để debug
        error_log("Calling API: " . $apiUrl);
        
        // Gọi API để lấy dữ liệu sản phẩm
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Thêm token xác thực nếu cần
        if (isset($_SESSION['token'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $_SESSION['token']
            ]);
        }
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Parse JSON response
        $responseData = json_decode($response, true);
        
        // Kiểm tra response và lấy dữ liệu sản phẩm
        if ($httpCode == 200 && isset($responseData['success']) && $responseData['success'] === true && isset($responseData['data']['data'])) {
            $sliderProducts = $responseData['data']['data'];
            // Log số lượng sản phẩm lấy được
            error_log("Retrieved " . count($sliderProducts) . " products for category: " . $categoryCode);
        } else {
            // Log lỗi nếu có
            error_log('API Error (' . $httpCode . '): ' . json_encode($responseData));
        }
    } catch (Exception $e) {
        error_log('Exception when fetching products: ' . $e->getMessage());
    }
}

// Sắp xếp sản phẩm theo discountPercentage giảm dần
if (!empty($sliderProducts)) {
    $sliderProducts = sortProducts($sliderProducts, $sortBy, $sortOrder);
}
?>

<div class="product-slider-container">
    <div class="product-slider-header">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <?php if (isset($categoryCode) && !empty($categoryCode)): ?>
        <a href="index.php?page=products&category=<?php echo htmlspecialchars($categoryCode); ?>" class="view-all">Xem tất cả</a>
        <?php endif; ?>
    </div>
    
    <div class="product-slider" id="<?php echo $sliderId; ?>">
        <?php 
        // Hiển thị các sản phẩm trong slider
        if (!empty($sliderProducts)) {
            foreach ($sliderProducts as $product) {
                // Đảm bảo rằng các thuộc tính cần thiết tồn tại
                $productId = isset($product['id']) ? $product['id'] : '';
                $productName = isset($product['name']) ? $product['name'] : 'Sản phẩm';
                $productBrand = isset($product['brandName']) ? $product['brandName'] : 'Unknown Brand';
                $productDescription = isset($product['shortDescription']) ? $product['shortDescription'] : '';
                $productCurrentPrice = isset($product['currentPrice']) ? $product['currentPrice'] : 0;
                $productOriginalPrice = isset($product['originalPrice']) ? $product['originalPrice'] : 0;
                $productImage = isset($product['imageUrl']) ? $product['imageUrl'] : 'https://via.placeholder.com/300x300?text=No+Image';
                
                // Sử dụng discountPercentage nếu có sẵn, hoặc tính toán từ giá
                $discountPercentage = isset($product['discountPercentage']) ? $product['discountPercentage'] : 0;
                if ($discountPercentage == 0 && $productOriginalPrice > $productCurrentPrice && $productOriginalPrice > 0) {
                    $discountPercentage = calculateDiscount($productOriginalPrice, $productCurrentPrice);
                }
                ?>
                <div class="product-slide">
                    <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($productId); ?>">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($productName); ?>"
                                onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
                            <?php if ($discountPercentage > 0): ?>
                            <span class="discount-badge">-<?php echo round($discountPercentage); ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-slide-content">
                            <div class="product-brand"><?php echo htmlspecialchars($productBrand); ?></div>
                            <h3 class="product-name"><?php echo htmlspecialchars(truncateText($productName, 50)); ?></h3>
                            <div class="product-description"><?php echo htmlspecialchars(truncateText($productDescription, 80)); ?></div>
                            <div class="product-price">
                                <?php if ($productOriginalPrice > $productCurrentPrice): ?>
                                <span class="original-price"><?php echo formatCurrency($productOriginalPrice); ?></span>
                                <?php endif; ?>
                                <span class="final-price"><?php echo formatCurrency($productCurrentPrice); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo '<div class="no-products">Không có sản phẩm nào trong danh mục này.</div>';
        }
        ?>
    </div>
    
    <?php if (!empty($sliderProducts) && count($sliderProducts) > 4): ?>
    <button class="slide-btn left" onclick="slideLeft('<?php echo $sliderId; ?>')">&#10094;</button>
    <button class="slide-btn right" onclick="slideRight('<?php echo $sliderId; ?>')">&#10095;</button>
    <?php endif; ?>
</div>

<style>
    .product-slider-container {
        position: relative;
        max-width: 1200px;
        margin: 30px auto;
        padding: 20px;
        background: #1e1e1e;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .product-slider-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .product-slider-header h2 {
        font-size: 24px;
        color: #ffa33a;
        margin: 0;
        padding-bottom: 5px;
        border-bottom: 2px solid #ffa33a;
    }

    .view-all {
        color: #6694ea;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: color 0.3s;
    }

    .view-all:hover {
        color: #ffa33a;
    }

    .product-slider {
        display: flex;
        overflow-x: hidden;
        scroll-behavior: smooth;
        gap: 20px;
        padding-bottom: 10px;
        -webkit-overflow-scrolling: touch;
    }

    .product-slide {
        flex: 0 0 calc(25% - 20px);
        min-width: 220px;
        max-width: 280px;
        background: #252525;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        padding: 10px;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 380px;
    }

    .product-slide:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
    }

    .product-slide a {
        text-decoration: none;
        color: inherit;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .product-image {
        position: relative;
        height: 150px;
        overflow: hidden;
        border-radius: 8px;
        margin-bottom: 10px;
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.5s;
    }

    .product-slide:hover .product-image img {
        transform: scale(1.05);
    }

    .discount-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ffa33a;
        color: #000000;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
    }

    .product-slide-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 5px;
    }

    .product-brand {
        font-size: 0.8rem;
        color: #6694ea;
        margin-bottom: 0.5rem;
    }

    .product-name {
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 8px;
        color: #ffffff;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-height: 1.3;
        height: 2.6em;
    }

    .product-description {
        font-size: 12px;
        color: #cccccc;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        line-height: 1.3;
        margin-bottom: 8px;
        height: 2.6em;
        flex: 1;
    }

    .product-price {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .original-price {
        font-size: 12px;
        color: #aaaaaa;
        text-decoration: line-through;
        margin-bottom: 2px;
    }

    .final-price {
        font-size: 16px;
        font-weight: bold;
        color: #ffa33a;
    }

    .add-to-cart-btn {
        margin-top: 10px;
        background-color: #1e1e1e;
        color: #ffa33a;
        border: 2px solid #ffa33a;
        border-radius: 6px;
        padding: 8px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
        font-weight: 500;
    }

    .add-to-cart-btn:hover {
        background: #000000;
        color: #e88f2e;
    }

    .no-products {
        width: 100%;
        text-align: center;
        padding: 30px;
        color: #aaaaaa;
        font-style: italic;
    }

    /* Nút điều khiển slider */
    .slide-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(30, 30, 30, 0.8);
        border: 1px solid #ffa33a;
        color: #ffa33a;
        font-size: 20px;
        padding: 10px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        transition: transform 0.3s, background 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    .slide-btn:hover {
        transform: translateY(-50%) scale(1.1);
        background: rgba(0, 0, 0, 0.9);
    }

    .slide-btn.left {
        left: 5px;
    }

    .slide-btn.right {
        right: 5px;
    }

    /* Responsive design */
    @media (max-width: 992px) {
        .product-slide {
            flex: 0 0 calc(33.33% - 20px);
        }
    }

    @media (max-width: 768px) {
        .product-slide {
            flex: 0 0 calc(50% - 20px);
        }
        
        .product-slider-header h2 {
            font-size: 20px;
        }

        .product-image {
            height: 140px;
        }
    }

    @media (max-width: 480px) {
        .product-slide {
            flex: 0 0 calc(100% - 20px);
        }
        
        .product-slider-container {
            padding: 15px;
        }

        .product-slider-header h2 {
            font-size: 18px;
        }
        
        .product-image {
            height: 160px;
        }
    }

    /* Toast notifications */
    .toast-container {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast {
        background: #333;
        color: #fff;
        padding: 12px 24px;
        border-radius: 4px;
        min-width: 200px;
        max-width: 300px;
        opacity: 0;
        transform: translateY(100px);
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast.success {
        background: #4CAF50;
    }

    .toast.error {
        background: #F44336;
    }

    .toast.info {
        background: #2196F3;
    }
</style>

<script>
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
    return <?= isset($_SESSION['token']) ? 'true' : 'false' ?>;
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

function createCustomToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}
</script>