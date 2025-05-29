<?php
require_once __DIR__ . '/../includes/session_init.php';


// Check if product ID is provided
$productId = $_GET['id'] ?? null;
if (!$productId) {
    header('Location: products.php');
    exit;
}

// Fetch product details from API
$apiUrl = "http://tamcutephomaique.ddns.net:5001/api/products/{$productId}";
$product = null;
$errorMsg = '';

try {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $errorMsg = 'Cannot connect to server, please try again later';
    } else {
        $jsonData = json_decode($response, true);
        if (!empty($jsonData['success']) && isset($jsonData['data'])) {
            $product = $jsonData['data'];
        } else {
            $errorMsg = $jsonData['message'] ?? 'Unable to load product information';
        }
    }
    curl_close($ch);
} catch (Exception $e) {
    $errorMsg = 'An error occurred: ' . $e->getMessage();
}

// Fetch related products
$relatedProducts = [];
if ($product && !empty($product['productInfo']['code'])) {
    $productCode = $product['productInfo']['code'];
    $relatedApiUrl = "http://tamcutephomaique.ddns.net:5001/api/products/related?productCode={$productCode}&count=5";
    try {
        $ch = curl_init($relatedApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $relatedResponse = curl_exec($ch);
        if (!curl_errno($ch)) {
            $relatedJsonData = json_decode($relatedResponse, true);
            if (!empty($relatedJsonData['success']) && isset($relatedJsonData['data'])) {
                $relatedProducts = $relatedJsonData['data'];
            }
        }
        curl_close($ch);
    } catch (Exception $e) {
        // Ignore related products errors
    }
}

// Fetch variant combinations for this product (if any)
$variantCombinations = [];
if (!empty($product['productInfo']['code'])) {
    $variantApiUrl = "http://tamcutephomaique.ddns.net:5001/api/products/{$product['productInfo']['code']}/variants";
    try {
        $ch = curl_init($variantApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($_SESSION['token'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $_SESSION['token']
            ]);
        }
        $variantResponse = curl_exec($ch);
        if (!curl_errno($ch)) {
            $variantJson = json_decode($variantResponse, true);
            if (!empty($variantJson['success']) && isset($variantJson['data']['combinations'])) {
                $variantCombinations = $variantJson['data']['combinations'];
            }
        }
        curl_close($ch);
    } catch (Exception $e) {
        // Ignore variant errors
    }
}

// Helper: Format currency
function formatCurrency($amount): string
{
    return '$' . number_format($amount, 2);
}

// Helper: Get product images (main + detail)
function getProductImages($product): array
{
    $images = [];
    if (!empty($product['productInfo']['imageUrl'])) {
        $images[] = ['url' => $product['productInfo']['imageUrl'], 'priority' => 0];
    }
    if (!empty($product['productDetail']['image']) && is_array($product['productDetail']['image'])) {
        foreach ($product['productDetail']['image'] as $image)
            $images[] = $image;
    }
    return $images;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['productInfo']['name']) : 'Product Details' ?> - GearPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }

        .product-container {
            width: 100%;
            background-color: #1e1e1e;
            color: #ffffff;
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
            border-color: #ffa33a;
        }

        .option-value.selected {
            background-color: #ffa33a;
            color: #000000;
            border-color: #ffa33a;
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

        /* Gift section */
        .gift-section {
            background-color: rgba(255, 163, 58, 0.1);
            border: 1px dashed #ffa33a;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .gift-section-title {
            display: flex;
            align-items: center;
            color: #ffa33a;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .gift-section-title i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }

        .gift-item {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .gift-item:last-child {
            margin-bottom: 0;
        }

        .gift-item-image {
            width: 48px;
            height: 48px;
            background-color: #fff;
            border-radius: 6px;
            padding: 0.25rem;
            margin-right: 0.75rem;
            overflow: hidden;
        }

        .gift-item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .gift-item-info {
            flex: 1;
        }

        .gift-item-name {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .gift-item-code {
            font-size: 0.8rem;
            color: #999;
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

        .nav-tabs .nav-link:hover {
            color: #ffa33a;
        }

        .nav-tabs .nav-link.active {
            color: #ffffff;
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

        /* Related Products */
        .related-products-slider {
            position: relative;
            width: 100%;
            overflow: hidden;
            padding: 0 15px;
            /* Add padding for slider buttons */
        }

        .related-products {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            /* Firefox */
            -webkit-overflow-scrolling: touch;
            padding: 1rem 0;
            scroll-snap-type: x mandatory;
            /* Enable scroll snapping for smoother scrolling */
            margin: 0 20px;
            /* Add margin for slider buttons */
        }

        .related-products::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Edge */
        }

        .related-product-card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-width: 250px;
            max-width: 320px;
            flex: 0 0 auto;
            scroll-snap-align: start;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            z-index: 10;
            border: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .slider-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .slider-btn.prev {
            left: 5px;
        }

        .slider-btn.next {
            right: 5px;
        }

        .slider-btn:active {
            transform: translateY(-50%) scale(0.95);
        }

        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .related-product-image {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            border-radius: 6px;
            padding: 0.5rem;
            margin-bottom: 1rem;
        }

        .related-product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .related-product-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            line-height: 1.4;
            color: #ffffff;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-product-brand {
            font-size: 0.8rem;
            color: #6694ea;
            margin-bottom: 0.75rem;
        }

        .related-product-price {
            margin-top: auto;
        }

        .related-product-current-price {
            font-weight: 600;
            color: #ffa33a;
            font-size: 1.1rem;
        }

        .related-product-original-price {
            font-size: 0.85rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 0.5rem;
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

            .btn-add-cart,
            .btn-wishlist {
                width: 100%;
                justify-content: center;
            }

            .specs-table td:first-child {
                width: 40%;
            }

            .related-product-card {
                min-width: 180px;
                padding: 0.75rem;
            }

            .related-product-image {
                height: 120px;
            }

            .slider-btn {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .related-product-title {
                font-size: 0.9rem;
            }

            .related-product-current-price {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .related-product-card {
                min-width: 160px;
                padding: 0.5rem;
            }

            .related-product-image {
                height: 100px;
                margin-bottom: 0.75rem;
            }

            .related-product-title {
                font-size: 0.85rem;
                -webkit-line-clamp: 1;
            }

            .related-product-brand {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
            }

            .related-product-current-price {
                font-size: 0.9rem;
            }

            .slider-btn {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
        }

        /* Make sure all text is white */
        .text-muted {
            color: #ffffff !important;
        }

        .btn-add-cart,
        .btn-buy-now {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40%;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-add-cart:hover {
            border-color: #ffa33a !important;
            transform: translateY(-1px);
        }

        .btn-buy-now:hover {
            background-color: #218838 !important;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="container flex flex-column">
        <?php if ($errorMsg): ?>
            <div class="product-container error-container">
                <div class="error-icon"><i class="bi bi-exclamation-circle"></i></div>
                <h3>Unable to load product information</h3>
                <p class="mb-4"><?= htmlspecialchars($errorMsg) ?></p>
                <a href="/index.php?page=products" class="btn btn-add-cart">
                    <i class="bi bi-arrow-left"></i> Back to product list
                </a>
            </div>
        <?php elseif ($product): ?>
            <div class="product-container">
                <div class="row">
                    <!-- Product Images -->
                    <div class="col-lg-5 mb-4">
                        <?php
                        $images = getProductImages($product);
                        if (empty($images)) {
                            $images[] = ['url' => $product['productInfo']['imageUrl']];
                        }
                        // Remove the first image (main image), only show option images
                        if (count($images) > 1) {
                            array_shift($images);
                        }
                        $mainImageUrl = $images[0]['url'] ?? 'https://via.placeholder.com/400x400?text=No+Image';
                        ?>
                        <div class="product-image-container" id="mainImageContainer">
                            <img id="mainImage" src="<?= htmlspecialchars($mainImageUrl) ?>"
                                alt="<?= htmlspecialchars($product['productInfo']['name']) ?>"
                                onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                        </div>
                        <?php if (count($images) > 1): ?>
                            <div class="product-image-nav" id="imageNav">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?= htmlspecialchars($image['url']) ?>" class="<?= $index === 0 ? 'active' : '' ?>"
                                        data-index="<?= $index ?>" alt="Product image <?= $index + 1 ?>"
                                        onerror="this.src='https://via.placeholder.com/70x70?text=No+Image'">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Product Info -->
                    <div class="col-lg-7">
                        <h1 class="product-title"><?= htmlspecialchars($product['productInfo']['name']) ?></h1>
                        <div class="product-meta">
                            <?php if (!empty($product['productInfo']['brand'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span>Brand: <?= htmlspecialchars($product['productInfo']['brand']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($product['productInfo']['category'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-folder"></i>
                                    <span>Category:
                                        <?= htmlspecialchars(is_array($product['productInfo']['category']) ? implode(', ', $product['productInfo']['category']) : $product['productInfo']['category']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($product['productInfo']['status'])): ?>
                                <div class="product-meta-item">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Status: <?= htmlspecialchars($product['productInfo']['status']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-code">
                            Product code: <?= htmlspecialchars($product['productInfo']['code']) ?>
                        </div>

                        <div class="price-container">
                            <div class="d-flex align-items-center">
                                <span class="current-price"><?= formatCurrency($product['price']['currentPrice']) ?></span>
                                <?php if ($product['price']['originalPrice'] > $product['price']['currentPrice']): ?>
                                    <span
                                        class="original-price"><?= formatCurrency($product['price']['originalPrice']) ?></span>
                                    <span
                                        class="discount-badge">-<?= htmlspecialchars($product['price']['discountPercentage']) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($product['gifts']) && is_array($product['gifts'])): ?>
                            <div class="gift-section">
                                <div class="gift-section-title">
                                    <i class="bi bi-gift"></i>
                                    <span>Included gifts</span>
                                </div>
                                <?php foreach ($product['gifts'] as $gift): ?>
                                    <div class="gift-item">
                                        <div class="gift-item-image">
                                            <img src="<?= htmlspecialchars($gift['image']) ?>"
                                                alt="<?= htmlspecialchars($gift['name']) ?>"
                                                onerror="this.src='https://via.placeholder.com/48x48?text=Gift'">
                                        </div>
                                        <div class="gift-item-info">
                                            <div class="gift-item-name"><?= htmlspecialchars($gift['name']) ?></div>
                                            <div class="gift-item-code">Code: <?= htmlspecialchars($gift['code']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['productDetail']['shortDescription'])): ?>
                            <div class="short-desc">
                                <?= htmlspecialchars($product['productDetail']['shortDescription']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['productOptions'])): ?>
                            <?php foreach ($product['productOptions'] as $optionGroup): ?>
                                <div class="option-selector">
                                    <div class="option-label"><?= htmlspecialchars($optionGroup['title']) ?>:</div>
                                    <div class="option-values">
                                        <?php foreach ($optionGroup['options'] as $option): ?>
                                            <div class="option-value <?= !empty($option['selected']) ? 'selected' : '' ?>"
                                                data-option-id="<?= htmlspecialchars($option['id'] ?? '') ?>">
                                                <?= htmlspecialchars($option['label'] ?? 'Not specified') ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>     <?php endif; ?>
                        <div class="quantity-selector">
                            <div class="quantity-label">Quantity:</div>
                            <div class="quantity-controls">
                                <div class="quantity-btn" id="decreaseQty">-</div>
                                <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="10">
                                <div class="quantity-btn" id="increaseQty">+</div>
                            </div>
                        </div>
                        <div class="w-100 flex flex-column gap-4">
                            <button type="button" id="buyNowBtn" class="btn btn-buy-now mb-4"
                                style="color: #ffffff; background-color: #28a745;">
                                <i class="bi bi-bag-check"></i> Buy now
                            </button>
                            <button type="button" id="addToCartBtn" class="btn btn-add-cart mb-4"
                                style="color: #ff9620;background-color: #ffffff0d;">
                                <i class="bi bi-cart-plus"></i> Add to cart
                            </button>
                        </div>
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="specs-tab" data-bs-toggle="tab"
                                    data-bs-target="#specs-tab-pane" type="button" role="tab" aria-controls="specs-tab-pane"
                                    aria-selected="true">
                                    Specifications
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="description-tab" data-bs-toggle="tab"
                                    data-bs-target="#description-tab-pane" type="button" role="tab"
                                    aria-controls="description-tab-pane" aria-selected="false">
                                    Description
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="productTabsContent">
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
                                    <p>No detailed specifications available for this product</p>
                                <?php endif; ?>
                            </div>
                            <div class="tab-pane fade" id="description-tab-pane" role="tabpanel"
                                aria-labelledby="description-tab" tabindex="0">
                                <?php if (!empty($product['productDetail']['description'])): ?>
                                    <div class="mt-3">
                                        <h4><?= htmlspecialchars($product['productInfo']['name']) ?></h4>
                                        <p class="mt-3">Key features:</p>
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
                                    <p>No detailed description available for this product</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Related Products Slider -->
            <div class="product-container w-100">
                <h3 class="mb-4">Products related to this item</h3>
                <?php if ($relatedProducts): ?>
                    <div class="related-products-slider">
                        <button class="slider-btn prev" id="prevBtn">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <div class="related-products" id="relatedProductsSlider">
                            <?php foreach ($relatedProducts as $relatedProduct): ?>
                                <div class="related-product-card">
                                    <a href="/index.php?page=product-detail&id=<?= htmlspecialchars($relatedProduct['id']) ?>"
                                        class="text-decoration-none">
                                        <div class="related-product-image">
                                            <img src="<?= htmlspecialchars($relatedProduct['imageUrl']) ?>"
                                                alt="<?= htmlspecialchars($relatedProduct['name']) ?>"
                                                onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
                                        </div>
                                        <h5 class="related-product-title"><?= htmlspecialchars($relatedProduct['name']) ?></h5>
                                        <?php if (!empty($relatedProduct['brandName'])): ?>
                                            <div class="related-product-brand">
                                                <i class="bi bi-tag-fill me-1"></i>
                                                <?= htmlspecialchars($relatedProduct['brandName']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($relatedProduct['shortDescription'])): ?>
                                            <p class="mb-2 small text-truncate" style="color: #ffffff;">
                                                <?= htmlspecialchars($relatedProduct['shortDescription']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="related-product-price">
                                            <span class="related-product-current-price">
                                                <?= formatCurrency($relatedProduct['currentPrice']) ?>
                                            </span>
                                            <?php if (isset($relatedProduct['originalPrice']) && $relatedProduct['originalPrice'] > $relatedProduct['currentPrice']): ?>
                                                <span class="related-product-original-price">
                                                    <?= formatCurrency($relatedProduct['originalPrice']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="slider-btn next" id="nextBtn">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-box2 fs-1 mb-3"></i>
                        <p>No similar products to display</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginConfirmModal" tabindex="-1" aria-labelledby="loginConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="loginConfirmModalLabel"><i class="bi bi-person-circle me-2"></i>
                        Login required</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please login to use this fearture.</p>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <a href="./login.php" class="btn"
                        style="background-color: #ffa33a; color: #000000; font-weight: 600;">Login</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"
        style="z-index: 1080; margin-bottom: 1.5rem; margin-right: 1.5rem; width: max-content; min-width: 300px; max-width: 90vw;">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Image gallery
            const thumbs = document.querySelectorAll('#imageNav img');
            const mainImage = document.getElementById('mainImage');
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', function () {
                    thumbs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    mainImage.src = this.src;
                });
            });
            // Quantity controls
            const quantityInput = document.getElementById('quantity');
            document.getElementById('decreaseQty').onclick = () => {
                let v = parseInt(quantityInput.value);
                if (v > 1) quantityInput.value = v - 1;
            };
            document.getElementById('increaseQty').onclick = () => {
                let v = parseInt(quantityInput.value);
                if (v < 10) quantityInput.value = v + 1;
            };
            quantityInput.addEventListener('change', function () {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) this.value = 1;
                else if (value > 10) this.value = 10;
            });

            // Product options selection
            document.querySelectorAll('.option-value').forEach(option => {
                option.addEventListener('click', function () {
                    if (this.classList.contains('selected')) return;
                    const optionsGroup = this.closest('.option-values');
                    optionsGroup.querySelectorAll('.option-value').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    const optionId = this.dataset.optionId;
                    if (optionId) {
                        const loadingOverlay = document.createElement('div');
                        loadingOverlay.style.position = 'fixed';
                        loadingOverlay.style.top = '0';
                        loadingOverlay.style.left = '0';
                        loadingOverlay.style.width = '100%';
                        loadingOverlay.style.height = '100%';
                        loadingOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                        loadingOverlay.style.display = 'flex';
                        loadingOverlay.style.justifyContent = 'center';
                        loadingOverlay.style.alignItems = 'center';
                        loadingOverlay.style.zIndex = '9999';
                        const spinner = document.createElement('div');
                        spinner.className = 'spinner-border text-light';
                        spinner.setAttribute('role', 'status');
                        const srOnly = document.createElement('span');
                        srOnly.className = 'visually-hidden';
                        srOnly.textContent = 'Loading...';
                        spinner.appendChild(srOnly);
                        loadingOverlay.appendChild(spinner);
                        document.body.appendChild(loadingOverlay);
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('id', optionId);
                        fetch(`http://tamcutephomaique.ddns.net:5001/api/products/${optionId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.data) {
                                    window.location.href = currentUrl.toString();
                                } else {
                                    alert('Unable to load product information. Please try again later.');
                                    document.body.removeChild(loadingOverlay);
                                }
                            })
                            .catch(() => {
                                alert('An error occurred while loading product information. Please try again later.');
                                document.body.removeChild(loadingOverlay);
                            });
                    }
                });
            });
            // --- Variant Combination: reload page with correct id ---
            <?php if (!empty($variantCombinations)): ?>
                window.variantCombinations = <?= json_encode($variantCombinations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

                // Helper: get selected options as {title: value}
                function getSelectedOptions() {
                    const selected = {};
                    document.querySelectorAll('.option-selector').forEach(selector => {
                        const title = selector.querySelector('.option-label').textContent.trim().replace(':', '');
                        const selectedOption = selector.querySelector('.option-value.selected');
                        if (selectedOption) {
                            selected[title] = selectedOption.textContent.trim();
                        }
                    });
                    return selected;
                }

                // Find matching combination by selectedOptions
                function findCombination(selectedOptions) {
                    return window.variantCombinations.find(combo => {
                        for (const key in combo.selectedOptions) {
                            if (combo.selectedOptions[key] !== (selectedOptions[key] || '')) {
                                return false;
                            }
                        }
                        return true;
                    });
                }

                // Listen for option changes
                document.querySelectorAll('.option-value').forEach(option => {
                    option.addEventListener('click', function () {
                        setTimeout(function () {
                            const selectedOptions = getSelectedOptions();
                            const combo = findCombination(selectedOptions);
                            if (combo && combo.id && combo.id !== '<?= htmlspecialchars($product['productInfo']['id']) ?>') {
                                // Reload page with new id
                                const url = new URL(window.location.href);
                                url.searchParams.set('id', combo.id);
                                window.location.href = url.toString();
                            }
                        }, 100);
                    });
                });
            <?php endif; ?>            // Check if user is logged in
            function isUserLoggedIn() {
                return <?= isset($_SESSION['token']) ? 'true' : 'false' ?>;
            }

            // Show login confirmation modal
            function showLoginConfirmModal() {
                const modal = new bootstrap.Modal(document.getElementById('loginConfirmModal'));
                modal.show();
            }

            // Add to cart with AJAX
            document.getElementById('addToCartBtn').onclick = function () {
                // Check if user is logged in
                if (!isUserLoggedIn()) {
                    showLoginConfirmModal();
                    return;
                }

                const quantity = parseInt(quantityInput.value) || 1;
                // Send AJAX request to add-to-cart.php
                fetch('actions/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        itemId: '<?= htmlspecialchars($product['productInfo']['id'] ?? '') ?>',
                        itemType: 'Product',
                        quantity: quantity
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        // Display toast message
                        showToast(data.message, data.success ? 'success' : 'danger');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while processing your request.', 'danger');
                    });
            };

            // Buy now button
            document.getElementById('buyNowBtn').addEventListener('click', function () {
                // Check if user is logged in
                if (!isUserLoggedIn()) {
                    showLoginConfirmModal();
                    return;
                }

                const quantity = parseInt(document.getElementById('quantity').value) || 1;
                const productId = '<?= htmlspecialchars($product['productInfo']['id']) ?>';

                // Redirect to order page with 'Buy Now' parameters
                window.location.href = 'index.php?page=order&buyNow=true&itemId=' + productId + '&quantity=' + quantity;
            });
            // Function to display toast
            function showToast(message, type = 'info') {
                const toastContainer = document.getElementById('toastContainer');

                // Create toast element
                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.setAttribute('data-bs-delay', '3500');

                // Toast content
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                // Add to container
                toastContainer.appendChild(toastEl);

                // Initialize bootstrap toast
                const toast = new bootstrap.Toast(toastEl);
                toast.show();

                // Remove toast after it's hidden
                toastEl.addEventListener('hidden.bs.toast', function () {
                    toastEl.remove();
                });
            }

            // Similar Products Slider Functionality
            const slider = document.getElementById('relatedProductsSlider');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (slider && prevBtn && nextBtn) {
                // Calculate scroll distance (width of one product card + gap)
                const scrollDistance = () => {
                    const cardWidth = slider.querySelector('.related-product-card')?.offsetWidth || 250;
                    const gap = 24; // 1.5rem in pixels
                    return cardWidth + gap;
                };

                // Scroll to previous products
                prevBtn.addEventListener('click', () => {
                    slider.scrollLeft -= scrollDistance();
                });

                // Scroll to next products
                nextBtn.addEventListener('click', () => {
                    slider.scrollLeft += scrollDistance();
                });

                // Hide/show navigation buttons based on scroll position
                const updateButtonVisibility = () => {
                    prevBtn.style.opacity = slider.scrollLeft <= 10 ? '0.5' : '1';
                    const isAtEnd = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10;
                    nextBtn.style.opacity = isAtEnd ? '0.5' : '1';
                };

                // Update button visibility initially and on scroll
                updateButtonVisibility();
                slider.addEventListener('scroll', updateButtonVisibility);

                // Touch swipe functionality
                let touchStartX = 0;
                let touchEndX = 0;

                slider.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                }, { passive: true });

                slider.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                }, { passive: true });

                function handleSwipe() {
                    const swipeDistance = touchStartX - touchEndX;
                    const threshold = 50; // Minimum distance to be considered a swipe

                    if (swipeDistance > threshold) {
                        // Swipe left (next)
                        slider.scrollLeft += scrollDistance();
                    } else if (swipeDistance < -threshold) {
                        // Swipe right (previous)
                        slider.scrollLeft -= scrollDistance();
                    }
                }

                // Resize observer to update button visibility when window is resized
                const resizeObserver = new ResizeObserver(() => {
                    updateButtonVisibility();
                });
                resizeObserver.observe(slider);
            }
        });
    </script>
</body>

</html>