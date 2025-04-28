<?php
// Kiểm tra xem phiên đã bắt đầu chưa trước khi gọi các hàm session
if (session_status() == PHP_SESSION_NONE) {
    session_name('user_session');
    session_set_cookie_params(['path' => '/']);
    session_start();
}

// Check if product ID is provided
$productId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Fetch product details from API
$apiUrl = "http://localhost:5000/api/products/{$productId}";
$product = null;
$errorMsg = '';

try {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $errorMsg = 'Không thể kết nối đến máy chủ, vui lòng thử lại sau';
    } else {
        $jsonData = json_decode($response, true);
        if ($jsonData['success'] && isset($jsonData['data'])) {
            $product = $jsonData['data'];
        } else {
            $errorMsg = $jsonData['message'] ?? 'Không thể tải thông tin sản phẩm';
        }
    }
    curl_close($ch);
} catch (Exception $e) {
    $errorMsg = 'Đã xảy ra lỗi: ' . $e->getMessage();
}

// Helper function to format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// Get product images (main + detail images)
function getProductImages($product) {
    $images = [];
    
    // Add main image first
    if (!empty($product['productInfo']['imageUrl'])) {
        $images[] = [
            'url' => $product['productInfo']['imageUrl'],
            'priority' => 0
        ];
    }
    
    // Add detail images
    if (!empty($product['productDetail']['image']) && is_array($product['productDetail']['image'])) {
        foreach ($product['productDetail']['image'] as $image) {
            $images[] = $image;
        }
    }
    
    return $images;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['productInfo']['name']) : 'Chi tiết sản phẩm' ?> - GearPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .product-container {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
        }
        /* Product Images */
        .product-image-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .product-image-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        .product-image-nav::-webkit-scrollbar {
            height: 6px;
        }
        .product-image-nav::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        .product-image-nav::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 3px;
        }
        .product-image-nav img {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            padding: 3px;
            background: #fff;
        }
        .product-image-nav img.active {
            border-color: #ffa33a;
        }

        /* Product Info */
        .product-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            color: #ffffff;
        }
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .product-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #ffffff;
        }
        .product-meta-item i {
            margin-right: 0.5rem;
            color: #6694ea;
        }
        .product-code {
            color: #ffffff;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        /* Pricing */
        .price-container {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .current-price {
            font-size: 1.75rem;
            font-weight: bold;
            color: #ffa33a;
        }
        .original-price {
            color: #ffffff;
            text-decoration: line-through;
            font-size: 1.1rem;
            margin-left: 0.75rem;
        }
        .discount-badge {
            background-color: #ffa33a;
            color: #000;
            font-weight: bold;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }
        .short-desc {
            color: #ffffff;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        /* Options and Quantity */
        .option-selector {
            margin-bottom: 1rem;
        }
        .option-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }
        .option-values {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .option-value {
            padding: 0.5rem 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #333;
            color: #ffffff;
        }
        .option-value:hover {
            border-color: #6694ea;
        }
        .option-value.selected {
            background-color: #6694ea;
            color: #fff;
            border-color: #6694ea;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .quantity-label {
            font-weight: 600;
            margin-right: 1rem;
            min-width: 80px;
            color: #ffffff;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
        }
        .quantity-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            color: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            user-select: none;
        }
        .quantity-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .quantity-input {
            width: 60px;
            height: 36px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            border-left: none;
            border-right: none;
            color: #fff;
        }
        .quantity-input:focus {
            outline: none;
        }
        
        /* Action Buttons */
        .product-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .btn-add-cart {
            background-color: #ffa33a;
            border-color: #ffa33a;
            color: #000;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .btn-add-cart:hover {
            background-color: #ff9620;
            border-color: #ff9620;
            color: #000;
        }
        .btn-add-cart i {
            margin-right: 0.5rem;
        }
        .btn-wishlist {
            border: 1px solid #ffa33a;
            background-color: transparent;
            color: #ffa33a;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .btn-wishlist:hover {
            background-color: rgba(255, 163, 58, 0.1);
        }
        .btn-wishlist i {
            margin-right: 0.5rem;
        }
        
        /* Product Tabs */
        .nav-tabs {
            border-bottom: 1px solid #333;
            margin-bottom: 1.5rem;
        }
        .nav-tabs .nav-link {
            color: #ffffff;
            background-color: transparent;
            border: none;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            position: relative;
        }
        .nav-tabs .nav-link.active {
            color: #ffa33a;
            background-color: transparent;
        }
        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ffa33a;
        }
        .tab-content {
            padding: 1rem 0;
            color: #ffffff;
        }
        
        /* Specifications Table */
        .specs-table {
            width: 100%;
            color: #ffffff;
        }
        .specs-table tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .specs-table td {
            padding: 0.75rem 1rem;
        }
        .specs-table td:first-child {
            font-weight: 600;
            width: 30%;
        }
        
        /* Error State */
        .error-container {
            text-align: center;
            padding: 3rem 1rem;
            color: #ffffff;
        }
        .error-icon {
            font-size: 3rem;
            color: #ffa33a;
            margin-bottom: 1rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .product-container {
                padding: 1.25rem;
            }
            .product-image-container {
                height: 280px;
            }
            .product-title {
                font-size: 1.5rem;
            }
            .current-price {
                font-size: 1.5rem;
            }
            .product-actions {
                flex-direction: column;
            }
            .btn-add-cart, .btn-wishlist {
                width: 100%;
                justify-content: center;
            }
            .specs-table td:first-child {
                width: 40%;
            }
        }
        
        /* Make sure all text is white */
        .text-muted {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <?php if (!empty($errorMsg)): ?>
            <!-- Error display -->
            <div class="product-container error-container">
                <div class="error-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <h3>Không thể tải thông tin sản phẩm</h3>
                <p class="mb-4"><?= htmlspecialchars($errorMsg) ?></p>
                <a href="products.php" class="btn btn-add-cart">
                    <i class="bi bi-arrow-left"></i> Quay lại danh sách sản phẩm
                </a>
            </div>
        <?php elseif ($product): ?>
            <!-- Product details -->
            <div class="product-container">
                <div class="row">
                    <!-- Product Images Section -->
                    <div class="col-lg-5 mb-4">
                        <?php 
                        $images = getProductImages($product);
                        $mainImageUrl = !empty($images) ? $images[0]['url'] : 'https://via.placeholder.com/400x400?text=No+Image';
                        ?>
                        
                        <!-- Main image display -->
                        <div class="product-image-container" id="mainImageContainer">
                            <img id="mainImage" src="<?= htmlspecialchars($mainImageUrl) ?>" 
                                 alt="<?= htmlspecialchars($product['productInfo']['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                        </div>
                        
                        <!-- Thumbnail navigation -->
                        <?php if (count($images) > 1): ?>
                            <div class="product-image-nav" id="imageNav">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?= htmlspecialchars($image['url']) ?>" 
                                         class="<?= $index === 0 ? 'active' : '' ?>"
                                         data-index="<?= $index ?>"
                                         alt="Product image <?= $index + 1 ?>"
                                         onerror="this.src='https://via.placeholder.com/70x70?text=No+Image'">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Information Section -->
                    <div class="col-lg-7">
                        <h1 class="product-title"><?= htmlspecialchars($product['productInfo']['name']) ?></h1>
                        
                        <div class="product-meta">
                            <?php if (!empty($product['productInfo']['brand'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span>Thương hiệu: <?= htmlspecialchars($product['productInfo']['brand']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['productInfo']['category'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-folder"></i>
                                    <span>Danh mục: <?= htmlspecialchars(is_array($product['productInfo']['category']) ? implode(', ', $product['productInfo']['category']) : $product['productInfo']['category']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['productInfo']['status'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Trạng thái: <?= htmlspecialchars($product['productInfo']['status']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-code">
                            Mã sản phẩm: <?= htmlspecialchars($product['productInfo']['code']) ?>
                        </div>
                        
                        <!-- Price Information -->
                        <div class="price-container">
                            <div class="d-flex align-items-center">
                                <span class="current-price"><?= formatCurrency($product['price']['currentPrice']) ?></span>
                                
                                <?php if ($product['price']['originalPrice'] > $product['price']['currentPrice']): ?>
                                    <span class="original-price"><?= formatCurrency($product['price']['originalPrice']) ?></span>
                                    <span class="discount-badge">-<?= htmlspecialchars($product['price']['discountPercentage']) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Short Description -->
                        <?php if (!empty($product['productDetail']['shortDescription'])): ?>
                            <div class="short-desc">
                                <?= htmlspecialchars($product['productDetail']['shortDescription']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Product Options Selection -->
                        <?php if (!empty($product['productOptions'])): ?>
                            <?php foreach ($product['productOptions'] as $optionGroup): ?>
                                <div class="option-selector">
                                    <div class="option-label"><?= htmlspecialchars($optionGroup['title']) ?>:</div>
                                    <div class="option-values">
                                        <?php foreach ($optionGroup['options'] as $option): ?>
                                            <div class="option-value <?= $option['selected'] ? 'selected' : '' ?>" 
                                                 data-option-id="<?= htmlspecialchars($option['id']) ?>">
                                                <?= htmlspecialchars($option['label']) ?>
                                                <?php if (isset($option['quantity']) && $option['quantity'] > 0): ?>
                                                    <span class="ms-1">(<?= $option['quantity'] ?> còn lại)</span>
                                                <?php elseif (isset($option['quantity']) && $option['quantity'] == 0): ?>
                                                    <span class="ms-1 text-danger">(Hết hàng)</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Quantity Selection -->
                        <div class="quantity-selector">
                            <div class="quantity-label">Số lượng:</div>
                            <div class="quantity-controls">
                                <div class="quantity-btn" id="decreaseQty">-</div>
                                <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="10">
                                <div class="quantity-btn" id="increaseQty">+</div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="product-actions">
                            <button class="btn btn-add-cart" id="addToCartBtn">
                                <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
                            </button>
                            <button class="btn btn-wishlist">
                                <i class="bi bi-heart"></i> Thêm vào yêu thích
                            </button>
                        </div>
                        
                        <!-- Product Details Tabs -->
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="specs-tab" data-bs-toggle="tab" 
                                        data-bs-target="#specs-tab-pane" type="button" role="tab" 
                                        aria-controls="specs-tab-pane" aria-selected="true">
                                    Thông số kỹ thuật
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="description-tab" data-bs-toggle="tab" 
                                        data-bs-target="#description-tab-pane" type="button" role="tab" 
                                        aria-controls="description-tab-pane" aria-selected="false">
                                    Mô tả chi tiết
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="productTabsContent">
                            <!-- Specifications Tab -->
                            <div class="tab-pane fade show active" id="specs-tab-pane" role="tabpanel" 
                                 aria-labelledby="specs-tab" tabindex="0">
                                
                                <?php if (!empty($product['productDetail']['description'])): ?>
                                    <table class="specs-table">
                                        <tbody>
                                            <?php foreach ($product['productDetail']['description'] as $spec): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($spec['name']) ?></td>
                                                    <td><?= htmlspecialchars($spec['descriptionText']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>Chưa có thông số kỹ thuật chi tiết cho sản phẩm này</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description-tab-pane" role="tabpanel" 
                                 aria-labelledby="description-tab" tabindex="0">
                                
                                <?php if (!empty($product['productDetail']['description'])): ?>
                                    <div class="mt-3">
                                        <h4><?= htmlspecialchars($product['productInfo']['name']) ?></h4>
                                        <p class="mt-3">Đặc điểm nổi bật:</p>
                                        <ul class="mt-2">
                                            <?php foreach ($product['productDetail']['description'] as $desc): ?>
                                                <li class="mb-2">
                                                    <strong><?= htmlspecialchars($desc['name']) ?>:</strong> 
                                                    <?= htmlspecialchars($desc['descriptionText']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <p>Chưa có mô tả chi tiết cho sản phẩm này</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Recommendations -->
            <div class="product-container">
                <h3 class="mb-4">Sản phẩm tương tự</h3>
                <div class="text-center py-4">
                    <i class="bi bi-box2 fs-1 mb-3"></i>
                    <p>Hiện chưa có sản phẩm tương tự để hiển thị</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image gallery functionality
            const thumbs = document.querySelectorAll('#imageNav img');
            const mainImage = document.getElementById('mainImage');
            
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    // Update active state
                    thumbs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update main image
                    mainImage.src = this.src;
                });
            });
            
            // Quantity controls
            const quantityInput = document.getElementById('quantity');
            const decreaseBtn = document.getElementById('decreaseQty');
            const increaseBtn = document.getElementById('increaseQty');
            
            decreaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue < 10) {
                    quantityInput.value = currentValue + 1;
                }
            });
            
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > 10) {
                    this.value = 10;
                }
            });
            
            // Product options selection
            const optionValues = document.querySelectorAll('.option-value');
            optionValues.forEach(option => {
                option.addEventListener('click', function() {
                    // Get all options in the same group
                    const optionsGroup = this.closest('.option-values');
                    const options = optionsGroup.querySelectorAll('.option-value');
                    
                    // Update selection
                    options.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Get the selected option ID
                    const optionId = this.dataset.optionId;
                    
                    // If option has ID, load the product with that ID
                    if (optionId) {
                        // Update URL with the new product/option ID
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('id', optionId);
                        
                        // Navigate to the new URL - this will reload the page with the new product ID
                        window.location.href = currentUrl.toString();
                    }
                });
            });
            
            // Add to cart functionality
            const addToCartBtn = document.getElementById('addToCartBtn');
            addToCartBtn.addEventListener('click', function() {
                // Get selected quantity
                const quantity = parseInt(quantityInput.value) || 1;
                
                // Get selected product options
                const selectedOptions = [];
                document.querySelectorAll('.option-selector').forEach(selector => {
                    const title = selector.querySelector('.option-label').textContent.trim();
                    const selectedOption = selector.querySelector('.option-value.selected');
                    if (selectedOption) {
                        selectedOptions.push({
                            title: title.replace(':', ''),
                            optionId: selectedOption.dataset.optionId,
                            optionLabel: selectedOption.textContent.trim()
                        });
                    }
                });
                
                // For demo purposes, just show an alert
                alert('Đã thêm sản phẩm vào giỏ hàng!');
                
                // You would typically send this data to the server
                console.log('Added to cart:', {
                    productId: '<?= htmlspecialchars($product['productInfo']['id'] ?? '') ?>',
                    productName: '<?= htmlspecialchars($product['productInfo']['name'] ?? '') ?>',
                    quantity: quantity,
                    selectedOptions: selectedOptions,
                    price: <?= isset($product['price']['currentPrice']) ? $product['price']['currentPrice'] : 0 ?>
                });
            });
        });
    </script>
</body>
</html>