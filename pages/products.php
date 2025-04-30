<?php
// Start default session (no custom session_name or path)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get filter parameters from URL
$categoryCode = isset($_GET['category']) ? trim($_GET['category']) : '';
$brandCode = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageIndex = isset($_GET['page']) ? max(0, intval($_GET['page'])) : 0;
$pageSize = 12; // Number of products per page

// API Endpoints
$productsApiUrl = "http://localhost:5000/api/products?pageIndex={$pageIndex}&pageSize={$pageSize}";
$brandsApiUrl = "http://localhost:5000/api/brands/get_select";
$categoriesApiUrl = "http://localhost:5000/api/categories/get?pageIndex=0&pageSize=100";

// Add filters to API URL if provided
if (!empty($categoryCode)) {
    $productsApiUrl .= "&categoryCode=" . urlencode($categoryCode);
}
if (!empty($brandCode)) {
    $productsApiUrl .= "&brandCode=" . urlencode($brandCode);
}
if (!empty($searchQuery)) {
    $productsApiUrl .= "&productName=" . urlencode($searchQuery);
}

// Function to make API requests
function makeApiRequest($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => $error];
    }
    
    return json_decode($response, true);
}

// Fetch products, brands, and categories
$productsResponse = makeApiRequest($productsApiUrl);
$brandsResponse = makeApiRequest($brandsApiUrl);
$categoriesResponse = makeApiRequest($categoriesApiUrl);

// Extract data from responses
$products = [];
$totalProducts = 0;
$brands = [];
$categories = [];

if (isset($productsResponse['success']) && $productsResponse['success'] && isset($productsResponse['data']['data'])) {
    $products = $productsResponse['data']['data'];
    $totalProducts = $productsResponse['data']['totalCount'] ?? 0;
}

if (isset($brandsResponse['success']) && $brandsResponse['success'] && isset($brandsResponse['data'])) {
    $brands = $brandsResponse['data'];
}

if (isset($categoriesResponse['success']) && $categoriesResponse['success'] && isset($categoriesResponse['data']['data'])) {
    $categories = $categoriesResponse['data']['data'];
    
    // Sort categories for better display
    $customOrder = [
        'Laptops', 'PCs', 'Main, CPU, VGA', 'Monitors', 'Keyboards', 
        'Mouse + Mouse Pad', 'Earphones', 'Sounds'
    ];
    
    usort($categories, function($a, $b) use ($customOrder) {
        $posA = array_search($a['name'], $customOrder);
        $posB = array_search($b['name'], $customOrder);
        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;
        return $posA - $posB;
    });
}

// Helper function to format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// Helper function to calculate discount percentage
function calculateDiscount($original, $current) {
    if ($original <= 0 || $current <= 0 || $original <= $current) {
        return 0;
    }
    return round((($original - $current) / $original) * 100);
}

// Icon mapping for categories
$categoryIcons = [
    'Laptops' => 'bi bi-laptop',
    'PCs' => 'bi bi-pc-display',
    'Main, CPU, VGA' => 'bi bi-cpu',
    'Mouse + Mouse Pad' => 'bi bi-mouse',
    'Sounds' => 'bi bi-speaker',
    'Monitors' => 'bi bi-display',
    'Earphones' => 'bi bi-headphones',
    'Keyboards' => 'bi bi-keyboard',
];

// Get active category name for display
$activeCategoryName = "";
if (!empty($categoryCode)) {
    foreach ($categories as $cat) {
        if (isset($cat['code']) && $cat['code'] === $categoryCode) {
            $activeCategoryName = $cat['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($activeCategoryName) ? htmlspecialchars($activeCategoryName) : 'All Products' ?> - GearPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .filters-container {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-heading {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .filter-section {
            margin-bottom: 1.5rem;
        }
        .brand-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
        }
        .brand-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #ffffff;
            text-align: center;
            transition: all 0.2s;
        }
        .brand-item:hover {
            background-color: #2d2d2d;
            transform: translateY(-3px);
        }
        .brand-item.active {
            background-color: #6694ea;
        }
        .brand-img-container {
            width: 60px;
            height: 60px;
            background-color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .brand-img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }
        .brand-name {
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 2.5rem;
        }
        .filter-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background-color 0.2s;
        }
        .filter-item:hover {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .filter-item.active {
            background-color: #6694ea;
            color: #ffffff;
        }
        .filter-item i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
        }
        .filter-select {
            background-color: #2d2d2d;
            color: #ffffff;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .filter-select:focus {
            border-color: #6694ea;
            box-shadow: 0 0 0 0.2rem rgba(102, 148, 234, 0.25);
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .product-card {
            background-color: #1e1e1e;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .product-img-container {
            background-color: #ffffff;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .product-img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .product-info {
            padding: 1rem;
        }
        .product-brand {
            font-size: 0.8rem;
            color: #6694ea;
            margin-bottom: 0.5rem;
        }
        .product-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #ffffff;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 2.8rem;
        }
        .product-price-current {
            font-weight: 700;
            font-size: 1.1rem;
            color: #ffa33a;
        }
        .product-price-original {
            color: #aaaaaa;
            text-decoration: line-through;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        .discount-badge {
            background-color: #ffa33a;
            color: #000000;
            font-size: 0.8rem;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            margin-left: 0.5rem;
            font-weight: 600;
        }
        .product-description {
            color: #cccccc;
            font-size: 0.9rem;
            margin-top: 0.75rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 2.6rem;
        }
        .product-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-view-product {
            background-color: #6694ea;
            color: #ffffff;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
            border: none;
            flex: 1;
        }
        .btn-view-product:hover {
            background-color: #5273c0;
            color: #ffffff;
        }
        .btn-add-cart {
            background-color: #ffa33a;
            color: #000000;
            border-radius: 6px;
            padding: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
            border: none;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-add-cart:hover {
            background-color: #e88f2e;
            color: #000000;
        }
        .pagination-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .page-link {
            background-color: #2d2d2d;
            color: #ffffff;
            border-color: #444;
        }
        .page-link:hover {
            background-color: #444;
            color: #ffffff;
            border-color: #555;
        }
        .page-item.active .page-link {
            background-color: #6694ea;
            border-color: #6694ea;
        }
        .page-item.disabled .page-link {
            background-color: #222;
            color: #777;
            border-color: #333;
        }
        .no-products {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 3rem 1rem;
            text-align: center;
        }
        .no-products-icon {
            font-size: 3rem;
            color: #ffa33a;
            margin-bottom: 1rem;
        }
        .search-form {
            margin-bottom: 1.5rem;
        }
        .search-input {
            background-color: #2d2d2d;
            color: #ffffff;
            border: 1px solid #444;
            border-radius: 8px 0 0 8px;
            padding: 0.75rem 1rem;
        }
        .search-input:focus {
            border-color: #6694ea;
            box-shadow: none;
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .search-btn {
            background-color: #6694ea;
            color: #ffffff;
            border: 1px solid #6694ea;
            border-radius: 0 8px 8px 0;
        }
        .search-btn:hover {
            background-color: #5273c0;
            color: #ffffff;
            border-color: #5273c0;
        }
        .all-brands {
            background-color: #2d2d2d;
            border: 2px solid #444;
        }
        .all-brands.active {
            background-color: #6694ea;
            border-color: #6694ea;
        }
        @media (max-width: 768px) {
            .filters-container {
                margin-bottom: 1rem;
            }
            .page-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .brand-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <!-- Page Title -->
        <h1 class="page-title">
            <?php if (!empty($activeCategoryName)): ?>
                <span><?= htmlspecialchars($activeCategoryName) ?></span>
            <?php elseif (!empty($searchQuery)): ?>
                <span>Search Results for: "<?= htmlspecialchars($searchQuery) ?>"</span>
            <?php else: ?>
                <span>All Products</span>
            <?php endif; ?>
        </h1>
        
        <div class="row">
            <!-- Small filters column -->
            <div class="col-12 mb-4">
                <div class="filters-container">
                    <div class="row">
                        <!-- Search Box -->
                        <div class="col-md-12 mb-4">
                            <div class="filter-section">
                                <h5 class="filter-heading">Search Products</h5>
                                <form action="" method="get" class="search-form">
                                    <?php if (!empty($categoryCode)): ?>
                                        <input type="hidden" name="category" value="<?= htmlspecialchars($categoryCode) ?>">
                                    <?php endif; ?>
                                    <?php if (!empty($brandCode)): ?>
                                        <input type="hidden" name="brand" value="<?= htmlspecialchars($brandCode) ?>">
                                    <?php endif; ?>
                                    <div class="input-group">
                                        <input type="text" name="q" class="form-control search-input" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
                                        <button type="submit" class="btn search-btn">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Brands Filter with Images -->
                        <div class="col-md-12">
                            <div class="filter-section">
                                <h5 class="filter-heading">Brands</h5>
                                <div class="brand-grid">
                                    <!-- All Brands option -->
                                    <a href="<?= 'products.php' . ($categoryCode ? '?category=' . urlencode($categoryCode) : '') . 
                                             ($searchQuery ? ($categoryCode ? '&q=' : '?q=') . urlencode($searchQuery) : '') ?>" 
                                       class="brand-item all-brands <?= empty($brandCode) ? 'active' : '' ?>">
                                        <div class="brand-img-container">
                                            <i class="bi bi-grid-3x3-gap" style="font-size: 24px; color: #666;"></i>
                                        </div>
                                        <div class="brand-name">All Brands</div>
                                    </a>
                                    
                                    <!-- Brand items -->
                                    <?php foreach ($brands as $brand): ?>
                                        <?php if (!isset($brand['code']) || !isset($brand['name'])) continue; ?>
                                        <a href="<?= 'products.php?brand=' . urlencode($brand['code']) . 
                                                ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . 
                                                ($searchQuery ? '&q=' . urlencode($searchQuery) : '') ?>" 
                                           class="brand-item <?= $brandCode === $brand['code'] ? 'active' : '' ?>">
                                            <div class="brand-img-container">
                                                <?php if (isset($brand['imageUrl']) && !empty($brand['imageUrl'])): ?>
                                                    <img src="<?= htmlspecialchars($brand['imageUrl']) ?>" alt="<?= htmlspecialchars($brand['name']) ?>" class="brand-img">
                                                <?php else: ?>
                                                    <i class="bi bi-building" style="font-size: 24px; color: #666;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="brand-name"><?= htmlspecialchars($brand['name']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Grid -->
            <div class="col-12">
                <!-- Display Products -->
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <div class="no-products-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4>No products found</h4>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="products.php" class="btn btn-view-product mt-3">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="product-card">
                                    <div class="product-img-container">
                                        <img src="<?= htmlspecialchars($product['imageUrl'] ?? '') ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-img"
                                             onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                    </div>
                                    <div class="product-info">
                                        <div class="product-brand"><?= htmlspecialchars($product['brandName'] ?? 'Unknown Brand') ?></div>
                                        <h5 class="product-title">
                                            <a href="product-detail.php?id=<?= htmlspecialchars($product['id']) ?>" class="text-white text-decoration-none">
                                                <?= htmlspecialchars($product['name']) ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex align-items-center">
                                            <span class="product-price-current"><?= formatCurrency($product['currentPrice']) ?></span>
                                            <?php if (isset($product['originalPrice']) && $product['originalPrice'] > $product['currentPrice']): ?>
                                                <span class="product-price-original"><?= formatCurrency($product['originalPrice']) ?></span>
                                                <?php $discount = calculateDiscount($product['originalPrice'], $product['currentPrice']); ?>
                                                <?php if ($discount > 0): ?>
                                                    <span class="discount-badge">-<?= $discount ?>%</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-description">
                                            <?= htmlspecialchars($product['shortDescription'] ?? '') ?>
                                        </div>
                                        <div class="product-action">
                                            <a href="product-detail.php?id=<?= htmlspecialchars($product['id']) ?>" class="btn-view-product">View Details</a>
                                            <button class="btn-add-cart add-to-cart-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalProducts > $pageSize): ?>
                        <div class="pagination-container">
                            <nav aria-label="Product pagination">
                                <ul class="pagination justify-content-center">
                                    <?php 
                                    $totalPages = ceil($totalProducts / $pageSize);
                                    $maxPagesToShow = 5;
                                    $startPage = max(0, min($pageIndex - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow));
                                    $endPage = min($startPage + $maxPagesToShow, $totalPages);
                                    
                                    // Build pagination URL base
                                    $paginationUrl = 'products.php?page=';
                                    if (!empty($categoryCode)) {
                                        $paginationUrl .= '&category=' . urlencode($categoryCode);
                                    }
                                    if (!empty($brandCode)) {
                                        $paginationUrl .= '&brand=' . urlencode($brandCode);
                                    }
                                    if (!empty($searchQuery)) {
                                        $paginationUrl .= '&q=' . urlencode($searchQuery);
                                    }
                                    ?>
                                    
                                    <!-- Previous Page -->
                                    <li class="page-item <?= $pageIndex <= 0 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $paginationUrl . ($pageIndex - 1) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Page Numbers -->
                                    <?php for ($i = $startPage; $i < $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $pageIndex ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $paginationUrl . $i ?>">
                                                <?= $i + 1 ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Page -->
                                    <li class="page-item <?= $pageIndex >= $totalPages - 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $paginationUrl . ($pageIndex + 1) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    // For now, just show an alert
                    alert('Added to cart!');
                    
                    // Here you would typically send an AJAX request to add the product to the cart
                    console.log('Adding product to cart:', productId);
                });
            });
        });
    </script>
</body>
</html>