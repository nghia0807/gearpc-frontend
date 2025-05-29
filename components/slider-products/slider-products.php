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

$api = getenv('API_URL');

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
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : $api . "/api/products?pageIndex=0&pageSize=$limit";
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

// Include CSS and JS files
echo '<link rel="stylesheet" href="components/slider-products/slider-products.css">';
echo '<script src="components/slider-products/slider-products.js" defer></script>';
?>

<div class="product-slider-container">
    <div class="product-slider-header">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <?php if (isset($categoryCode) && !empty($categoryCode)): ?>
        <a href="index.php?page=products&category=<?php echo htmlspecialchars($categoryCode); ?>" class="view-all">View All Products</a>
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