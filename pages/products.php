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
$brandsApiUrl = "http://localhost:5000/api/brands/get_select";

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

        .filter-section {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
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
        
        /* Styling for the sort dropdown */
        .sort-options .form-select {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sort-options .form-select:hover {
            border-color: #ff9620;
        }
        
        .brand-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 12px;
        }

        /* First row of brands only */
        .brand-grid-first-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            grid-template-rows: 1fr;
            gap: 12px;
            overflow: hidden;
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
            border-color: #ff9620;
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
            border-color: #ff9620;
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
            height: 2.5rem;
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
            
            .brand-grid-first-row,
            .brand-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
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
                        <!-- Price Range Filter -->
                        <div class="col-md-4 mb-3">
                            <div class="filter-section">
                                <h5 class="filter-heading"><i class="bi bi-cash"></i> Price Range</h5>
                                <div class="price-range-container">
                                    <div class="row g-2">
                                        <div class="col">
                                            <div class="input-group">
                                                <span class="input-group-text bg-dark text-light border-secondary">Min</span>
                                                <input type="number" class="form-control bg-dark text-light border-secondary"
                                                    id="minPrice" placeholder="0"
                                                    value="<?= $minPrice !== null ? $minPrice : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="input-group">
                                                <span class="input-group-text bg-dark text-light border-secondary">Max</span>
                                                <input type="number" class="form-control bg-dark text-light border-secondary"
                                                    id="maxPrice" placeholder="Max"
                                                    value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <button id="apply-price-filter" class="btn btn-sm btn-outline-light w-100 apply-filter-btn">Apply
                                                Filter</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sorting Options -->
                        <div class="col-md-4 mb-3">
                            <div class="filter-section">
                                <h5 class="filter-heading"><i class="bi bi-sort-alpha-down"></i> Sort By</h5>
                                <div class="sort-options">
                                    <select id="sort-options" class="form-select bg-dark text-light border-secondary">
                                        <option value="" <?= $sortBy === '' ? 'selected' : '' ?>>Default sorting</option>
                                        <option value="price-asc" <?= ($sortBy === 'currentPrice' && $sortDirection === 'asc') ? 'selected' : '' ?>>
                                            Price: Low to High
                                        </option>
                                        <option value="price-desc" <?= ($sortBy === 'currentPrice' && $sortDirection === 'desc') ? 'selected' : '' ?>>
                                            Price: High to Low
                                        </option>
                                        <option value="name-asc" <?= ($sortBy === 'name' && $sortDirection === 'asc') ? 'selected' : '' ?>>
                                            Name: A to Z
                                        </option>
                                        <option value="name-desc" <?= ($sortBy === 'name' && $sortDirection === 'desc') ? 'selected' : '' ?>>
                                            Name: Z to A
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="col-md-4">
                            <div class="filter-section">
                                <h5 class="filter-heading"><i class="bi bi-tags"></i> Brands</h5>
                                <!-- First row of brands (always visible) -->
                                <div class="brand-grid-first-row">
                                    <a href="<?= 'index.php?page=products' . ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . ($searchQuery ? '&q=' . urlencode($searchQuery) : '') . ($minPrice !== null ? '&minPrice=' . $minPrice : '') . ($maxPrice !== null ? '&maxPrice=' . $maxPrice : '') . ($sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '') ?>"
                                        class="brand-item all-brands <?= !$brandCode ? 'active' : '' ?>">
                                        <div class="brand-img-container">
                                            <i class="bi bi-grid-3x3-gap" style="font-size:24px;color:#666;"></i>
                                        </div>
                                        <div class="brand-name">All Brands</div>
                                    </a>

                                    <?php
                                    // Calculate how many brands fit in the first row (based on typical screen width)
                                    $brandsInFirstRow = min(10, count($brands)); // Show max 10 brands in first row
                                    $firstRowBrands = array_slice($brands, 0, $brandsInFirstRow - 1);
                                    $remainingBrands = array_slice($brands, $brandsInFirstRow - 1);

                                    // Display first row brands
                                    foreach ($firstRowBrands as $brand):
                                        if (empty($brand['code']) || empty($brand['name'])) continue;
                                    ?>
                                    <a href="<?= 'index.php?page=products&brand=' . urlencode($brand['code']) . ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . ($searchQuery ? '&q=' . urlencode($searchQuery) : '') . ($minPrice !== null ? '&minPrice=' . $minPrice : '') . ($maxPrice !== null ? '&maxPrice=' . $maxPrice : '') . ($sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '') ?>"
                                        class="brand-item <?= $brandCode === $brand['code'] ? 'active' : '' ?>">
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

                                <?php if (count($remainingBrands) > 0): ?>
                                <!-- Collapsible Brands Container -->
                                <div class="collapse mt-3" id="moreBrandsCollapse">
                                    <div class="brand-grid">
                                        <?php foreach ($remainingBrands as $brand): ?>
                                        <?php if (empty($brand['code']) || empty($brand['name'])) continue; ?>
                                        <a href="<?= 'index.php?page=products&brand=' . urlencode($brand['code']) . ($categoryCode ? '&category=' . urlencode($categoryCode) : '') . ($searchQuery ? '&q=' . urlencode($searchQuery) : '') . ($minPrice !== null ? '&minPrice=' . $minPrice : '') . ($maxPrice !== null ? '&maxPrice=' . $maxPrice : '') . ($sortBy ? '&sortBy=' . urlencode($sortBy) . '&sortDirection=' . urlencode($sortDirection) : '') ?>"
                                            class="brand-item <?= $brandCode === $brand['code'] ? 'active' : '' ?>">
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
                                </div>
                                <!-- View More Brands Button -->
                                <button class="btn-view-more-brands"
                                    type="button"
                                    id="viewMoreBrandsBtn"
                                    aria-expanded="false"
                                    aria-controls="moreBrandsCollapse">
                                    <div>
                                        <span>View more brands</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
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
                const moreBrandsCollapse = document.getElementById('moreBrandsCollapse');

                // Initialize Bootstrap collapse instance
                const bsCollapse = new bootstrap.Collapse(moreBrandsCollapse, {
                    toggle: false
                });

                // Add direct click handler to manually toggle collapse state
                viewMoreBrandsBtn.addEventListener('click', function () {
                    if (moreBrandsCollapse.classList.contains('show')) {
                        bsCollapse.hide();
                    } else {
                        bsCollapse.show();
                    }
                });

                // Update button text on collapse/expand
                moreBrandsCollapse.addEventListener('hidden.bs.collapse', function () {
                    viewMoreBrandsBtn.querySelector('span').textContent = 'View more brands';
                });

                moreBrandsCollapse.addEventListener('shown.bs.collapse', function () {
                    viewMoreBrandsBtn.querySelector('span').textContent = 'View less';
                });

                // Check if a hidden brand is active
                const activeBrandInHidden = moreBrandsCollapse.querySelector('.brand-item.active');
                if (activeBrandInHidden) {
                    // If there's an active brand in the hidden section, show it automatically
                    bsCollapse.show();
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

            // Sorting options change
            const sortOptions = document.getElementById('sort-options');
            if (sortOptions) {
                sortOptions.addEventListener('change', function () {
                    const selectedOption = this.value.split('-');
                    const sortBy = selectedOption[0] === 'price' ? 'currentPrice' : selectedOption[0];
                    const sortDirection = selectedOption[1] || 'asc';

                    // Get current price filter values
                    const minPrice = document.getElementById('minPrice').value;
                    const maxPrice = document.getElementById('maxPrice').value;

                    // Redirect to the same page with updated sortBy and sortDirection
                    window.location.href = 'index.php?page=products' +
                        '<?= $categoryCode ? '&category=' . urlencode($categoryCode) : '' ?>' +
                        '<?= $brandCode ? '&brand=' . urlencode($brandCode) : '' ?>' +
                        '<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>' +
                        (minPrice !== '' ? '&minPrice=' + encodeURIComponent(minPrice) : '') +
                        (maxPrice !== '' ? '&maxPrice=' + encodeURIComponent(maxPrice) : '') +
                        '&sortBy=' + encodeURIComponent(sortBy) +
                        '&sortDirection=' + encodeURIComponent(sortDirection);
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
            
            // Add sorting options
            const sortOptions = document.getElementById('sort-options');
            if (sortOptions && sortOptions.value) {
                const selectedOption = sortOptions.value.split('-');
                const sortBy = selectedOption[0] === 'price' ? 'currentPrice' : selectedOption[0];
                const sortDirection = selectedOption[1] || 'asc';
                
                apiUrl += `&sortBy=${encodeURIComponent(sortBy)}&sortDirection=${encodeURIComponent(sortDirection)}`;
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