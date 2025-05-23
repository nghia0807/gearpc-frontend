<?php
require_once __DIR__ . '/../includes/session_init.php';

// Get filter parameters from URL
$categoryCode = isset($_GET['category']) ? trim($_GET['category']) : '';
$brandCode = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageIndex = isset($_GET['pageIndex']) ? max(0, intval($_GET['pageIndex'])) : 0;
$pageSize = 12; // Set fixed page size to 12 products (3 columns x 4 rows)

// Price range filtering
$minPrice = isset($_GET['minPrice']) ? floatval($_GET['minPrice']) : null;
$maxPrice = isset($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : null;

// Sorting
$sortBy = isset($_GET['sortBy']) ? trim($_GET['sortBy']) : '';
$sortDirection = isset($_GET['sortDirection']) ? trim($_GET['sortDirection']) : 'asc';

// API Endpoints
$productsApiUrl = "http://localhost:5000/api/products?pageIndex={$pageIndex}&pageSize={$pageSize}";

// Determine which brands API to use based on whether a category is selected
if ($categoryCode) {
    $brandsApiUrl = "http://localhost:5000/api/brands/by-category/{$categoryCode}";
} else {
    $brandsApiUrl = "http://localhost:5000/api/brands/get_select";
}

// Add filters to API URL if provided
if ($categoryCode)
    $productsApiUrl .= "&categoryCode=" . urlencode($categoryCode);
if ($brandCode)
    $productsApiUrl .= "&brandCode=" . urlencode($brandCode);
if ($searchQuery)
    $productsApiUrl .= "&productName=" . urlencode($searchQuery);
if ($minPrice !== null)
    $productsApiUrl .= "&minPrice=" . urlencode($minPrice);
if ($maxPrice !== null)
    $productsApiUrl .= "&maxPrice=" . urlencode($maxPrice);
if ($sortBy) {
    $productsApiUrl .= "&sortBy=" . urlencode($sortBy);
    $productsApiUrl .= "&sortDirection=" . urlencode($sortDirection);
}

// Helper: Make API requests
function makeApiRequest($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error)
        return ['success' => false, 'message' => $error];
    return json_decode($response, true);
}

// Fetch products, brands
$productsResponse = makeApiRequest($productsApiUrl);
$brandsResponse = makeApiRequest($brandsApiUrl);

// Extract data from responses
$products = [];
$totalProducts = 0;
$brands = [];

if (!empty($productsResponse['success']) && isset($productsResponse['data']['data'])) {
    $products = $productsResponse['data']['data'];
    $totalProducts = $productsResponse['data']['totalCount'] ?? 0;
}
if (!empty($brandsResponse['success']) && isset($brandsResponse['data'])) {
    $brands = $brandsResponse['data'];
}

// Helper: Format currency
function formatCurrency($amount)
{
    return '$' . number_format($amount, 2);
}

// Helper: Calculate discount percentage
function calculateDiscount($original, $current)
{
    if ($original <= 0 || $current <= 0 || $original <= $current)
        return 0;
    return round((($original - $current) / $original) * 100);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }

        .filters-container {
            width: 100%;
            background-color: #1e1e1e;
            color: #ffffff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-heading {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #ff9620;
            display: flex;
            align-items: center;
        }
        
        .filter-heading i {
            margin-right: 0.5rem;
        }

        .price-range-container .form-control:focus,
        .sort-options .form-select:focus {
            border-color: #ff9620;
            box-shadow: 0 0 0 0.25rem rgba(255, 150, 32, 0.25);
        }
        
        /* Custom styling for price inputs */
        .price-inputs {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .price-divider {
            margin: 0 10px;
            color: #6c757d;
        }
        
        .brand-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 12px;
        }

        /* View More Brands Button */
        .btn-view-more-brands {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: white;
            background: none;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #2d2d2d;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
        }

        .btn-view-more-brands:hover {
            background-color: #2d2d2d;
        }

        .btn-view-more-brands i {
            transition: transform 0.3s ease;
            margin-left: 5px;
        }

        .btn-view-more-brands[aria-expanded="true"] i {
            transform: rotate(180deg);
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
            border: 1px solid transparent;
        }

        .brand-item:hover {
            background-color: #2d2d2d;
            color: #ff9620;
            transform: translateY(-3px);
        }

        .brand-item.active {
            background-color: #ff9620;
            color: black;
        }

        .brand-img-container {
            width: 50px;
            height: 50px;
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
        }

        .all-brands.active {
            background-color: #ff9620;
            border-color: #ff9620;
            color: black;
        }

        .apply-filter-btn {
            background-color: #ff9620;
            color: #000;
            border: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .apply-filter-btn:hover {
            background-color: #e68a1c;
            transform: translateY(-2px);
        }

        .no-products {
            background-color: #1e1e1e;
            color: #ffffff;
            border-radius: 10px;
            padding: 3rem 1rem;
            text-align: center;
        }

        .no-products-icon {
            font-size: 3rem;
            color: #ffa33a;
            margin-bottom: 1rem;
        }
        
        /* Improved responsive layout for filters */
        @media (max-width: 768px) {
            .filters-container {
                padding: 1rem;
            }
            
            .filter-section {
                padding-bottom: 0.75rem;
            }
            
            .brand-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }

        /* Horizontal filters styling */
        .horizontal-filters {
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-label {
            color: #1e1e1e;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        
        .filter-label i {
            margin-right: 0.5rem;
        }
        
        /* Hide number input arrows */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        .price-inputs-horizontal {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .price-inputs-horizontal .input-group {
            width: 120px;
        }
        
        .price-filter-btn {
            background-color: #ff9620;
            border: none;
            color: #000;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .price-filter-btn:hover {
            background-color: #e68a1c;
            transform: translateY(-2px);
        }
        
        .sort-select-horizontal {
            width: 200px;
        }
        
        @media (max-width: 768px) {
            .horizontal-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .price-inputs-horizontal {
                width: 100%;
                justify-content: space-between;
            }
            
            .price-inputs-horizontal .input-group {
                width: 45%;
            }
            
            .sort-select-horizontal {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="row w-100">
            <div class="col-12 mb-4">
                <div class="filters-container">
                    <div class="row">
                        <!-- Brands Filter -->
                        <div class="col-md-12">
                            <div class="filter-section">
                                <h5 class="filter-heading"><i class="bi bi-tags"></i> Brands</h5>
                                <div class="brand-grid" id="brandsGrid">
                                    <!-- All Brands option -->
                                    <a href="<?= 'index.php?page=products' . ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . ($searchQuery ? '&q=' . urlencode($searchQuery) : '') . ($minPrice !== null ? '&minPrice=' . $minPrice : '') . ($maxPrice !== null ? '&maxPrice=' . $maxPrice : '') . ($sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '') ?>"
                                        class="brand-item all-brands <?= !$brandCode ? 'active' : '' ?>">
                                        <div class="brand-img-container">
                                            <i class="bi bi-grid-3x3-gap" style="font-size:24px;color:#666;"></i>
                                        </div>
                                        <div class="brand-name">All Brands</div>
                                    </a>

                                    <?php 
                                    $brandsToShow = 10; // Number of brands to show initially
                                    foreach ($brands as $index => $brand): 
                                        if (empty($brand['code']) || empty($brand['name'])) continue;
                                        $isHidden = $index >= $brandsToShow;
                                    ?>
                                        <a href="<?= 'index.php?page=products&brand=' . urlencode($brand['code']) . ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . ($searchQuery ? '&q=' . urlencode($searchQuery) : '') . ($minPrice !== null ? '&minPrice=' . $minPrice : '') . ($maxPrice !== null ? '&maxPrice=' . $maxPrice : '') . ($sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '') ?>"
                                            class="brand-item <?= $brandCode === $brand['code'] ? 'active' : '' ?> <?= $isHidden ? 'collapse' : '' ?>"
                                            <?= $isHidden ? 'data-bs-parent="#brandsGrid"' : '' ?>
                                            <?= $isHidden ? 'id="brand-' . $index . '"' : '' ?>>
                                            <div class="brand-img-container">
                                                <?php if (!empty($brand['imageUrl'])): ?>
                                                    <img src="<?= htmlspecialchars($brand['imageUrl']) ?>"
                                                        alt="<?= htmlspecialchars($brand['name']) ?>" class="brand-img">
                                                <?php else: ?>
                                                    <i class="bi bi-building" style="font-size:24px;color:#666;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="brand-name"><?= htmlspecialchars($brand['name']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (count($brands) > $brandsToShow): ?>
                                <!-- View More Brands Button -->
                                <button class="btn-view-more-brands"
                                    type="button" 
                                    id="viewMoreBrandsBtn"
                                    data-bs-toggle="collapse" 
                                    data-bs-target=".brand-item.collapse"
                                    aria-expanded="false">
                                    <div>
                                        <span>View more</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Horizontal Filters above Products Grid -->
            <div class="col-12 mb-3">
                <div class="horizontal-filters">
                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <div class="filter-label">
                            <i class="bi bi-cash"></i> Price Range:
                        </div>
                        <div class="price-inputs-horizontal">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-dark border-secondary border-2 border-end-0">Min</span>
                                <input type="number" class="form-control bg-white text-dark border-secondary border-2"
                                    id="minPrice" placeholder="0"
                                    value="<?= $minPrice !== null ? $minPrice : '' ?>">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-dark border-secondary border-2 border-end-0">Max</span>
                                <input type="number" class="form-control bg-white text-dark border-secondary border-2"
                                    id="maxPrice" placeholder="Max"
                                    value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                            </div>
                            <button id="apply-price-filter" class="price-filter-btn">
                                <i class="bi bi-funnel"></i> Apply
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="filter-group">
                        <div class="filter-label">
                            <i class="bi bi-sort-alpha-down"></i> Sort By:
                        </div>
                        <select id="sortSelect" class="form-select bg-white text-dark border-secondary border-2 sort-select-horizontal">
                            <option value="" <?= $sortBy === '' ? 'selected' : '' ?>>Default</option>
                            <option value="DiscountPercentageDescending" <?= $sortBy === 'DiscountPercentageDescending' ? 'selected' : '' ?>>Best Deals</option>
                            <option value="NameAscending" <?= $sortBy === 'NameAscending' ? 'selected' : '' ?>>Name A-Z</option>
                            <option value="NameDescending" <?= $sortBy === 'NameDescending' ? 'selected' : '' ?>>Name Z-A</option>
                            <option value="PriceAscending" <?= $sortBy === 'PriceAscending' ? 'selected' : '' ?>>Price Low to High</option>
                            <option value="PriceDescending" <?= $sortBy === 'PriceDescending' ? 'selected' : '' ?>>Price High to Low</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Product Grid -->
            <div class="col-12">
                <?php if (!$products): ?>
                <div class="no-products">
                    <div class="no-products-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h4>No products found</h4>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-view-product mt-3">View All Products</a>
                </div>
                <?php else: ?>
                <!-- Product card -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="products-container">
                    <?php foreach ($products as $product): ?>
                    <?php include 'components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- View More Button (replaces pagination) -->
                <?php if ($totalProducts > $pageSize): ?>
                <div class="text-center mt-5 mb-4">
                    <button id="view-more-btn" class="btn px-4 py-2" data-current-page="<?= $pageIndex ?>"
                        data-total-pages="<?= ceil($totalProducts / $pageSize) ?>"
                        data-category="<?= htmlspecialchars($categoryCode) ?>"
                        data-brand="<?= htmlspecialchars($brandCode) ?>"
                        data-search="<?= htmlspecialchars($searchQuery) ?>">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span>View more products</span>
                    </button>

                    <div id="loading-indicator" class="d-none mt-4">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Loading more products...</div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Products view more functionality
            const viewMoreBtn = document.getElementById('view-more-btn');
            if (viewMoreBtn) {
                viewMoreBtn.addEventListener('click', loadMoreProducts);
            }

            // Brands view more button text update
            const viewMoreBrandsBtn = document.getElementById('viewMoreBrandsBtn');
            if (viewMoreBrandsBtn) {
                const collapsibleBrands = document.querySelectorAll('.brand-item.collapse');
                let isExpanded = false;

                viewMoreBrandsBtn.addEventListener('click', function() {
                    isExpanded = !isExpanded;
                    viewMoreBrandsBtn.querySelector('span').textContent = isExpanded ? 'View less' : 'View more';
                    viewMoreBrandsBtn.querySelector('i').classList.toggle('bi-chevron-up');
                    viewMoreBrandsBtn.querySelector('i').classList.toggle('bi-chevron-down');
                });

                // Check if any hidden brand is active
                const activeBrandInHidden = Array.from(collapsibleBrands).find(brand => brand.classList.contains('active'));
                if (activeBrandInHidden) {
                    // Trigger click to expand if there's an active hidden brand
                    viewMoreBrandsBtn.click();
                }
            }

            // Price filter apply button
            const applyPriceFilterBtn = document.getElementById('apply-price-filter');
            if (applyPriceFilterBtn) {
                applyPriceFilterBtn.addEventListener('click', function () {
                    const minPrice = document.getElementById('minPrice').value;
                    const maxPrice = document.getElementById('maxPrice').value;

                    // Redirect to the same page with updated minPrice and maxPrice
                    window.location.href = 'index.php?page=products' +
                        '<?= $categoryCode ? '&category=' . urlencode($categoryCode) : '' ?>' +
                        '<?= $brandCode ? '&brand=' . urlencode($brandCode) : '' ?>' +
                        '<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>' +
                        (minPrice !== '' ? '&minPrice=' + encodeURIComponent(minPrice) : '') +
                        (maxPrice !== '' ? '&maxPrice=' + encodeURIComponent(maxPrice) : '') +
                        '<?= $sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '' ?>';
                });
            }

            // Sort select change event
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    const selectedSort = this.value;

                    // Redirect to the same page with updated sort parameter
                    window.location.href = 'index.php?page=products' +
                        '<?= $categoryCode ? '&category=' . urlencode($categoryCode) : '' ?>' +
                        '<?= $brandCode ? '&brand=' . urlencode($brandCode) : '' ?>' +
                        '<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>' +
                        '<?= $minPrice !== null ? '&minPrice=' . $minPrice : '' ?>' +
                        '<?= $maxPrice !== null ? '&maxPrice=' . $maxPrice : '' ?>' +
                        (selectedSort ? '&sortBy=' + encodeURIComponent(selectedSort) : '');
                });
            }
        });

        function loadMoreProducts() {
            const btn = document.getElementById('view-more-btn');
            const loadingIndicator = document.getElementById('loading-indicator');
            const productsContainer = document.getElementById('products-container');

            // Get current state
            const currentPage = parseInt(btn.dataset.currentPage) + 1;
            const totalPages = parseInt(btn.dataset.totalPages);

            // Show loading indicator
            loadingIndicator.classList.remove('d-none');
            btn.disabled = true;

            // Build API URL with the same filters as current page
            let apiUrl = `http://localhost:5000/api/products?pageIndex=${currentPage}&pageSize=12`;
            if (btn.dataset.category) apiUrl += `&categoryCode=${encodeURIComponent(btn.dataset.category)}`;
            if (btn.dataset.brand) apiUrl += `&brandCode=${encodeURIComponent(btn.dataset.brand)}`;
            if (btn.dataset.search) apiUrl += `&productName=${encodeURIComponent(btn.dataset.search)}`;
            
            // Add price range filters
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            if (minPrice) apiUrl += `&minPrice=${encodeURIComponent(minPrice)}`;
            if (maxPrice) apiUrl += `&maxPrice=${encodeURIComponent(maxPrice)}`;
            
            // Add sort parameter
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect && sortSelect.value) {
                apiUrl += `&sortBy=${encodeURIComponent(sortSelect.value)}`;
            }
            
            // Fetch additional products
            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.data.length > 0) {
                        // Update button state
                        btn.dataset.currentPage = currentPage;

                        // Format currency function
                        const formatCurrency = (amount) => {
                            return '$' + Number(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        };


                        // Calculate discount percentage
                        const calculateDiscount = (original, current) => {
                            if (original <= 0 || current <= 0 || original <= current) return 0;
                            return Math.round(((original - current) / original) * 100);
                        };

                        // Process each product
                        data.data.data.forEach(product => {
                            // Create product card HTML
                            const productCard = createProductCard(product, formatCurrency, calculateDiscount);
                            productsContainer.insertAdjacentHTML('beforeend', productCard);
                        });

                        // Hide button if we've reached the end
                        if (currentPage >= totalPages - 1) {
                            btn.classList.add('d-none');
                        }
                    } else {
                        // No more products
                        btn.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error loading more products:', error);
                    alert('Error loading more products. Please try again.');
                })
                .finally(() => {
                    // Hide loading indicator
                    loadingIndicator.classList.add('d-none');
                    btn.disabled = false;
                });
        }

        function createProductCard(product, formatCurrency, calculateDiscount) {
            const discount = product.originalPrice > product.currentPrice ?
                calculateDiscount(product.originalPrice, product.currentPrice) : 0;

            return `
            <div class="col">
                <div class="product-card">
                    <a href="index.php?page=product-detail&id=${product.id}" class="text-decoration-none">
                        <div class="product-img-container">
                            <img src="${product.imageUrl || ''}" alt="${product.name}" class="product-img"
                                onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                        </div>
                        <div class="product-info">
                            <div class="product-brand">
                                ${product.brandName || 'Unknown Brand'}
                            </div>
                            <h5 class="product-title">
                                ${product.name}
                            </h5>
                            <div class="d-flex align-items-center">
                                <span class="product-price-current">${formatCurrency(product.currentPrice)}</span>
                                ${product.originalPrice > product.currentPrice ?
                    `<span class="product-price-original">${formatCurrency(product.originalPrice)}</span>
                                     ${discount > 0 ? `<span class="discount-badge">-${discount}%</span>` : ''}` : ''}
                            </div>
                            <div class="product-description">
                                ${product.shortDescription || ''}
                            </div>
                        </div>
                    </a>
                    <div class="product-action">
                        <div class="w-100 d-flex justify-content-between gap-2">
                            <button type="button" class="btn-add-cart mb-3"
                                onclick="addToCartAsync('${product.id}')">
                                <i class="bi bi-cart-plus"></i>
                                <span>Add to cart</span>
                            </button>
                            <button type="button" class="btn-buy-now mb-3"
                                onclick="buyNowAsync('${product.id}')">
                                <i class="bi bi-bag-check"></i>
                                <span>Buy now</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }
    </script>
</body>

</html>