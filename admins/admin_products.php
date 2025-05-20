<?php
session_name('admin_session');
session_start();

// Kiểm tra token tồn tại, nếu không thì chuyển hướng về trang đăng nhập
if (!isset($_SESSION['token'])) {
    header('Location: manage_login.php');
    exit;
}

$token = $_SESSION['token'];
$apiBaseUrl = 'http://localhost:5000/api/products';
$pageIndex = isset($_GET['page']) ? intval($_GET['page']) : 0;
$pageSize = 10;
$alerts = [];

// Add search parameter handling
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Process toast parameters from URL if present
if (isset($_GET['toast_type']) && isset($_GET['toast_msg'])) {
    $type = $_GET['toast_type'];
    $msg = $_GET['toast_msg'];
    // Only allow valid toast types
    if (in_array($type, ['success', 'danger', 'warning', 'info'])) {
        $alerts[] = ['type' => $type, 'msg' => $msg];
    }
}

// Toast component
include '../components/toasts.php';

// --- Fetch products from API ---
function fetchProducts($apiBaseUrl, $token, $pageIndex, $pageSize, &$alerts, &$totalCount)
{
    $url = $apiBaseUrl . "?pageIndex=$pageIndex&pageSize=$pageSize";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $alerts[] = ['type' => 'danger', 'msg' => 'Unable to connect to API.'];
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    $success = isset($data['success']) ? $data['success'] : false;
    if (!$data || !$success || $httpCode !== 200) {
        $alerts[] = ['type' => 'danger', 'msg' => isset($data['message']) ? $data['message'] : 'Unable to load products, please try again'];
        return [];
    }
    $totalCount = $data['data']['totalCount'];
    return $data['data']['data'];
}

// --- Fetch brands and categories for selection ---
function fetchAll($url, $token) {
    $ch = curl_init($url . '?pageIndex=0&pageSize=1000');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    $success = isset($data['success']) ? $data['success'] : false;
    if (!$data || !$success) return [];
    return $data['data']['data'] ?? [];
}

// --- API Handler Functions ---

// Delete products by codes
function deleteProducts($codes, $token) {
    $url = 'http://localhost:5000/api/products/delete';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($codes));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'],
        'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
    ];
}

// Add a new product
function addProduct($productData, $token) {
    $url = 'http://localhost:5000/api/products/add';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($productData));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'],
        'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
    ];
}

// Update product name
function updateProductName($productCode, $name, $token) {
    $url = "http://localhost:5000/api/products/updateProductName?productCode=$productCode";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $name]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'],
        'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
    ];
}

// Update product brand
function updateProductBrand($productCode, $brandCode, $token) {
    $url = "http://localhost:5000/api/products/updateProductBrand?productCode=$productCode";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['brandCode' => $brandCode]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'],
        'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
    ];
}

// Update product gifts
function updateProductGifts($productCode, $giftCodes, $token) {
    $url = "http://localhost:5000/api/products/updateProductGift?productCode=$productCode";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['giftCodes' => $giftCodes]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'],
        'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
    ];
}

// Get product details by ID
function getProductDetail($productId, $token) {
    $url = "http://localhost:5000/api/products/$productId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300) && isset($data['success']) && $data['success'], 
        'data' => isset($data['data']) ? $data['data'] : null
    ];
}

// AJAX Request Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Delete products handler
    if (isset($_POST['action']) && $_POST['action'] === 'deleteProducts' && isset($_POST['codes'])) {
        $codes = json_decode($_POST['codes'], true);
        $result = deleteProducts($codes, $token);
        echo json_encode($result);
        exit;
    }
    
    // Add product handler
    if (isset($_POST['action']) && $_POST['action'] === 'addProduct' && isset($_POST['productData'])) {
        $productData = json_decode($_POST['productData'], true);
        $result = addProduct($productData, $token);
        echo json_encode($result);
        exit;
    }
    
    // Update product name handler
    if (isset($_POST['action']) && $_POST['action'] === 'updateProductName' && 
        isset($_POST['productCode']) && isset($_POST['name'])) {
        $result = updateProductName($_POST['productCode'], $_POST['name'], $token);
        echo json_encode($result);
        exit;
    }
    
    // Update product brand handler
    if (isset($_POST['action']) && $_POST['action'] === 'updateProductBrand' && 
        isset($_POST['productCode']) && isset($_POST['brandCode'])) {
        $result = updateProductBrand($_POST['productCode'], $_POST['brandCode'], $token);
        echo json_encode($result);
        exit;
    }
    
    // Update product gifts handler
    if (isset($_POST['action']) && $_POST['action'] === 'updateProductGifts' && 
        isset($_POST['productCode']) && isset($_POST['giftCodes'])) {
        $giftCodes = json_decode($_POST['giftCodes'], true);
        $result = updateProductGifts($_POST['productCode'], $giftCodes, $token);
        echo json_encode($result);
        exit;
    }
    
    // Get product detail handler
    if (isset($_POST['action']) && $_POST['action'] === 'getProductDetail' && isset($_POST['productId'])) {
        $result = getProductDetail($_POST['productId'], $token);
        echo json_encode($result);
        exit;
    }
    
    // If we got here, it's an invalid request
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$brandsList = fetchAll('http://localhost:5000/api/brands/get', $token);
$categoriesList = fetchAll('http://localhost:5000/api/categories/get', $token);
$giftsList = fetchAll('http://localhost:5000/api/gifts/get', $token);

// --- Fetch products for current page ---
$products = fetchProducts($apiBaseUrl, $token, $pageIndex, $pageSize, $alerts, $totalCount);

$totalPages = ceil($totalCount / $pageSize);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Tham chiếu đến file CSS riêng -->
    <link rel="stylesheet" href="css/admin_products.css">
    <style>
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #fff;
            padding: 15px 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: padding 0.3s, box-shadow 0.3s;
            border-radius: 10px;
        }
        
        .sticky-header.is-sticky {
            padding: 10px 0;
        }
        
        @media (max-width: 767.98px) {
            .sticky-header .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .sticky-header h4 {
                margin-bottom: 10px !important;
            }
        }
        
        /* Add some padding to top of content to prevent sudden jump */
        .main-card {
            padding-top: 10px;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container position-relative">
    <!-- Toast container positioned absolutely -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <?php renderToasts('toast-container', 0, 3500); ?>
    </div>

    <!-- New sticky header div -->
    <div class="sticky-header mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Product List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelected" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete
                </button>
                <button id="btnAddProduct" class="btn btn-success">
                    <i class="fa fa-plus"></i> Add Product
                </button>
            </div>
        </div>
    </div>

    <div class="main-card">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="text-center" style="width:40px;">
                            <input type="checkbox" id="selectAllProducts" class="custom-checkbox">
                        </th>
                        <th class="text-center" style="width:40px;">#</th>
                        <th style="width:60px;">ID</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th class="text-center">Image</th>
                        <th>Brand</th>
                        <th>Current Price</th>
                        <th>Original Price</th>
                        <th style="width: 120px;" class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-box fa-2x mb-2"></i>
                                    <p>No products found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="product-checkbox custom-checkbox" data-code="<?= htmlspecialchars($product['code']) ?>">
                                </td>
                                <td class="text-center"><?= $pageIndex * $pageSize + $index + 1 ?></td>
                                <td><span class="product-id"><?= htmlspecialchars($product['id']) ?></span></td>
                                <td><span class="product-code"><?= htmlspecialchars($product['code']) ?></span></td>
                                <td><span class="product-name"><?= htmlspecialchars($product['name']) ?></span></td>
                                <td class="text-center">
                                    <?php if (!empty($product['imageUrl'])): ?>
                                        <div class="product-image-container">
                                            <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Product Image">
                                        </div>
                                    <?php else: ?>
                                        <div class="product-image-container">
                                            <i class="fa fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['brandName']) ?></td>
                                <td><?= number_format($product['currentPrice'], 0, ',', '.') ?>₫</td>
                                <td><?= number_format($product['originalPrice'], 0, ',', '.') ?>₫</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm action-btn view btn-view-product" data-id="<?= htmlspecialchars($product['id']) ?>" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <!-- Remove the edit button here -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Define the jumpToPage function globally at the top level of the document -->
            <script>
                // Make sure jumpToPage is defined in the global scope
                window.jumpToPage = function(e) {
                    e.preventDefault();
                    const input = document.getElementById('jumpToPage');
                    const page = parseInt(input.value) - 1;
                    const maxPage = <?= $totalPages - 1 ?>;
                    
                    if (isNaN(page) || page < 0 || page > maxPage) {
                        alert(`Please enter a valid page number between 1 and ${maxPage + 1}`);
                        return false;
                    }
                    
                    window.location.href = `?page=${page}`;
                    return false;
                };
            </script>

            <!-- Pagination section -->
            <?php if ($totalCount > 0): ?>
                <div class="card-footer bg-white py-3">
                    <nav aria-label="Product pagination" class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <ul class="pagination mb-0">
                            <!-- First page button -->
                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=0" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Previous page button -->
                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= max(0, $pageIndex - 1) ?>" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php 
                            $startPage = max(0, min($pageIndex - 2, $totalPages - 5));
                            $endPage = min($startPage + 4, $totalPages - 1);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(0, $endPage - 4);
                            }
                            ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i == $pageIndex) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i + 1 ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next page button -->
                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= min($totalPages - 1, $pageIndex + 1) ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            
                            <!-- Last page button -->
                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages - 1 ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>

                        <!-- Page jump form -->
                        <form class="d-flex align-items-center gap-2" onsubmit="return window.jumpToPage(event)">
                            <div class="input-group" style="width: auto;">
                                <input type="number" class="form-control" id="jumpToPage" 
                                    min="1" max="<?= $totalPages ?>" 
                                    placeholder="Page..." 
                                    style="width: 80px;">
                                <button class="btn btn-outline-secondary" type="submit">Go</button>
                            </div>
                            <span class="text-muted">of <?= $totalPages ?></span>
                        </form>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
        <!-- Remove old alert container -->
        <!-- <div id="jsAlertContainer" style="margin-top:16px;"></div> -->
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="addProductForm" autocomplete="off">
        <div class="modal-header bg-primary bg-gradient text-white">
          <h5 class="modal-title" id="addProductModalLabel">
            <i class="fa-solid fa-plus-circle me-2"></i>Add New Product
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body px-4 py-3" style="background: #f8f9fb; overflow-y:auto; max-height:70vh;">
          <div id="addProductAlert"></div>
          
          <!-- Main Info Section -->
          <div class="card mb-4 border-primary shadow-sm">
            <div class="card-header bg-primary bg-opacity-75 text-white py-2">
              <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Basic Product Information</h6>
            </div>
            <div class="card-body pb-3 pt-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-primary">Product Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="name" required placeholder="Enter product name">
                  <div class="form-text">Shown as product title on all pages</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-primary">Product Code <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="code" required placeholder="Enter unique product code">
                  <div class="form-text">Must be unique identifier</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold text-primary">Main Image <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" name="image" accept="image/*" required>
                  <div class="form-text">This will be the primary product image shown in listings</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Classification Section -->
          <div class="card mb-4 border-success shadow-sm">
            <div class="card-header bg-success bg-opacity-75 text-white py-2">
              <h6 class="mb-0"><i class="fa-solid fa-tags me-2"></i>Classification & Status</h6>
            </div>
            <div class="card-body pb-3 pt-3">
              <div class="mb-3">
                <label class="form-label fw-semibold text-success">Categories <span class="text-danger">*</span></label>
                <div class="border rounded p-3 bg-light">
                  <div class="row">
                    <?php foreach ($categoriesList as $cat): ?>
                      <div class="col-md-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="categoriesCode[]" value="<?= htmlspecialchars($cat['code']) ?>" id="cat_<?= htmlspecialchars($cat['code']) ?>">
                          <label class="form-check-label" for="cat_<?= htmlspecialchars($cat['code']) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                          </label>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-success">Brand <span class="text-danger">*</span></label>
                  <select class="form-select" name="brandCode" required>
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brandsList as $brand): ?>
                      <option value="<?= htmlspecialchars($brand['code']) ?>">
                        <?= htmlspecialchars($brand['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-success">Status <span class="text-danger">*</span></label>
                  <select class="form-select" name="status" required>
                    <option value="New">New</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gift Section -->
          <div class="card mb-4 border-warning shadow-sm">
            <div class="card-header bg-warning bg-opacity-75 text-dark py-2">
              <h6 class="mb-0"><i class="fa-solid fa-gift me-2"></i>Gift Items</h6>
            </div>
            <div class="card-body pb-3 pt-3">
              <label class="form-label fw-semibold text-warning">Select Gifts</label>
              <div class="border rounded p-3 bg-light">
                <div class="row">
                  <?php if (empty($giftsList)): ?>
                    <div class="col-12">
                      <div class="text-muted">No gifts available</div>
                    </div>
                  <?php else: ?>
                    <?php foreach ($giftsList as $gift): ?>
                      <div class="col-md-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="giftCodes[]" value="<?= htmlspecialchars($gift['code']) ?>" id="gift_<?= htmlspecialchars($gift['code']) ?>">
                          <label class="form-check-label" for="gift_<?= htmlspecialchars($gift['code']) ?>">
                            <?= htmlspecialchars($gift['name']) ?>
                            <?php if (!empty($gift['imageUrl'])): ?>
                              <img src="<?= htmlspecialchars($gift['imageUrl']) ?>" alt="<?= htmlspecialchars($gift['name']) ?>" style="width:24px;height:24px;object-fit:cover;border-radius:3px;margin-left:5px;">
                            <?php endif; ?>
                          </label>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-text">Select gifts to include with this product</div>
            </div>
          </div>
          
          <!-- Variants Section -->
          <div class="card mb-3 border-info shadow-sm">
            <div class="card-header bg-info bg-opacity-75 text-white py-2">
              <h6 class="mb-0"><i class="fa-solid fa-layer-group me-2"></i>Product Variants</h6>
            </div>
            <div class="card-body pb-3 pt-3">
              <div id="variantsSection">
                <!-- Variant blocks will be inserted here by JS -->
              </div>
              <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-info" id="btnAddVariant">
                  <i class="fa-solid fa-plus"></i> Add Variant
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add Product
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- End Add Product Modal -->

<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="viewProductModalContent" class="text-muted">
          Loading product information...
        </div>
        <div id="viewProductAlert" class="mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Gift Edit Modal -->
<div class="modal fade" id="editGiftsModal" tabindex="-1" aria-labelledby="editGiftsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning bg-opacity-75 text-dark">
        <h5 class="modal-title" id="editGiftsModalLabel">
          <i class="fa-solid fa-gift me-2"></i>Edit Product Gifts
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="editGiftsAlert"></div>
        <form id="editGiftsForm">
          <input type="hidden" id="editGiftsProductCode">
          <div class="border rounded p-3 bg-light">
            <div class="row" id="editGiftsCheckboxes">
              <!-- Checkboxes will be inserted here -->
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="saveGiftsBtn">
          <i class="fa-solid fa-save me-1"></i>Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Thêm container ẩn để lưu trữ brand options cho JavaScript -->
<div id="brandSelectOptions" style="display: none;">
    <?php foreach ($brandsList as $brand): ?>
    <option value="<?= htmlspecialchars($brand['code']) ?>">
        <?= htmlspecialchars($brand['name']) ?>
    </option>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Replace the inline script with a reference to the external file -->
<script src="js/admin_products.js"></script>
<?php initializeToasts(); ?>
</body>
</html>
