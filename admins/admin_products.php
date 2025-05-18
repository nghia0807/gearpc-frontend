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
$products = [];
$totalCount = 0;

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
$brandsList = fetchAll('http://localhost:5000/api/brands/get', $token);
$categoriesList = fetchAll('http://localhost:5000/api/categories/get', $token);

// --- Fetch products for current page ---
$products = fetchProducts($apiBaseUrl, $token, $pageIndex, $pageSize, $alerts, $totalCount);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: #f8f9fb;
        }
        .main-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(60,72,88,0.07);
            padding: 2rem 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 1.5px solid #e0e6ed;
        }
        .main-card h4 {
            font-weight: 600;
            color: #222;
        }
        .table thead th {
            background: #f5f6fa;
            color: #333;
            font-weight: 500;
            border-top: none;
        }
        .table-bordered > :not(caption) > * > * {
            border-color: #e3e6ea;
        }
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            box-shadow: none;
        }
        .btn-success, .btn-warning, .btn-danger, .btn-outline-secondary {
            background: none;
            color: inherit;
            border: 1px solid #dee2e6;
        }
        .btn-success {
            color: #198754;
            border-color: #198754;
        }
        .btn-success:hover, .btn-success:focus {
            background: #198754;
            color: #fff;
        }
        .btn-warning {
            color: #b08900;
            border-color: #ffc107;
        }
        .btn-warning:hover, .btn-warning:focus {
            background: #ffc107;
            color: #222;
        }
        .btn-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover, .btn-danger:focus {
            background: #dc3545;
            color: #fff;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #ced4da;
        }
        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
            background: #e9ecef;
            color: #222;
        }
        .modal-content {
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(60,72,88,0.10);
        }
        .modal-header {
            border-bottom: 1px solid #e3e6ea;
        }
        .modal-title {
            font-weight: 500;
        }
        .form-label {
            font-weight: 500;
        }
        .alert {
            border-radius: 0.375rem;
        }
        .table tbody tr:hover {
            background: #f6f8fa;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .product-actions .btn {
            margin-right: 0.25rem;
        }
        @media (max-width: 768px) {
            .main-card {
                padding: 1rem 0.5rem;
            }
        }
        .product-img-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            background: #eee;
        }
        .modal-product-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            background: #eee;
            margin-right: 8px;
        }
        .modal-product-img-list img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 6px;
            margin-bottom: 6px;
            background: #eee;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <div class="main-card">
        <!-- Toast Container -->
        <div aria-live="polite" aria-atomic="true" class="position-relative">
            <div id="toastContainer" class="toast-container position-absolute bottom-0 end-0 p-3" style="z-index: 1080;">
                <?php foreach ($alerts as $alert): ?>
                <div class="toast align-items-center text-bg-<?= $alert['type'] ?> border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
                    <div class="d-flex">
                        <div class="toast-body">
                            <?= htmlspecialchars($alert['msg']) ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- End Toast Container -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Product List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelected" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete Selected
                </button>
                <button id="btnAddProduct" class="btn btn-success">
                    <i class="fa fa-plus"></i> Add Product
                </button>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:32px;">
                            <input type="checkbox" id="selectAllProducts">
                        </th>
                        <th>ID</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Image</th>
                        <th>Brand</th>
                        <th>Current Price</th>
                        <th>Original Price</th>
                        <th>Short Description</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="product-checkbox" data-code="<?= htmlspecialchars($product['code']) ?>">
                                </td>
                                <td><?= htmlspecialchars($product['id']) ?></td>
                                <td><?= htmlspecialchars($product['code']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>
                                    <?php if (!empty($product['imageUrl'])): ?>
                                        <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Product Image" class="product-img-thumb">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/64x64?text=No+Image" alt="No Image" class="product-img-thumb">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['brandName']) ?></td>
                                <td><?= number_format($product['currentPrice'], 0, ',', '.') ?>₫</td>
                                <td><?= number_format($product['originalPrice'], 0, ',', '.') ?>₫</td>
                                <td><?= htmlspecialchars($product['shortDescription']) ?></td>
                                <td class="action-btns">
                                    <button class="btn btn-sm btn-info btn-view-product" data-id="<?= htmlspecialchars($product['id']) ?>">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-warning" disabled>
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalCount > ($pageIndex + 1) * $pageSize): ?>
                <div class="card-footer bg-white text-center">
                    <a href="?page=<?= $pageIndex + 1 ?>" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-angles-down"></i> Load More
                    </a>
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
              <label class="form-label fw-semibold text-warning">Gift Codes</label>
              <input type="text" class="form-control" name="giftCodes" placeholder="e.g. GIFT001,GIFT002,GIFT003">
              <div class="form-text">Enter comma-separated gift codes to include with this product</div>
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
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-view-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetail(productId);
        });
    });

    // --- Product selection logic ---
    const selectAll = document.getElementById('selectAllProducts');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const jsAlertContainer = document.getElementById('jsAlertContainer');

    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        btnDeleteSelected.disabled = !anyChecked;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteSelectedBtn();
        });
    }
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAll.checked) selectAll.checked = false;
        });
    });

    btnDeleteSelected.addEventListener('click', function() {
        const codes = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-code'));
        if (codes.length === 0) return;
        if (!confirm('Are you sure you want to delete the selected products?')) return;
        deleteProductsByCodes(codes);
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this product?')) return;
            deleteProductsByCodes([code]);
        });
    });

    function showJsAlert(type, msg) {
        jsAlertContainer.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
        setTimeout(() => { jsAlertContainer.innerHTML = ''; }, 3500);
    }

    function deleteProductsByCodes(codes) {
        btnDeleteSelected.disabled = true;
        fetch('http://localhost:5000/api/products/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer <?= htmlspecialchars($token) ?>'
            },
            body: JSON.stringify(codes)
        })
        .then(async resp => {
            let data;
            try { data = await resp.json(); } catch { data = {}; }
            if (!resp.ok || !data.success) {
                throw new Error(data.message || 'Xóa sản phẩm thất bại');
            }
            showJsAlert('success', data.message || 'Xóa sản phẩm thành công!');
            // Reload page after short delay
            setTimeout(() => { window.location.reload(); }, 1200);
        })
        .catch(err => {
            showJsAlert('danger', err.message || 'Lỗi xóa sản phẩm');
            btnDeleteSelected.disabled = false;
        });
    }

    // --- Add Product Modal logic ---
    const btnAddProduct = document.getElementById('btnAddProduct');
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    const addProductForm = document.getElementById('addProductForm');
    const addProductAlert = document.getElementById('addProductAlert');

    // --- VARIANTS DYNAMIC ---
    const variantsSection = document.getElementById('variantsSection');
    const btnAddVariant = document.getElementById('btnAddVariant');
    let variantCount = 0;
    const MAX_VARIANTS = 2;

    function createVariantBlock(idx) {
        return `
        <div class="variant-block border rounded p-3 mb-4 bg-white shadow-sm" data-variant-idx="${idx}">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-info"><i class="fa-solid fa-cubes me-2"></i>Variant #${idx + 1}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveVariant" ${idx === 0 ? 'style="display:none;"' : ''}>
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-info">Variant Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="variant_optionTitle_${idx}" placeholder="e.g. Color, Size, Memory" required>
            <div class="form-text">The name for this variant group (e.g. "Color", "Size")</div>
          </div>
          <div class="variant-options-container">
            <label class="form-label fw-semibold text-info">Options for this variant</label>
            <div class="variant-options-list" data-variant-idx="${idx}"></div>
            <div class="text-end mt-2">
              <button type="button" class="btn btn-sm btn-outline-info btnAddOption" data-variant-idx="${idx}">
                <i class="fa-solid fa-plus"></i> Add Option
              </button>
            </div>
          </div>
        </div>
        `;
    }

    function createOptionBlock(variantIdx, optionIdx) {
        return `
        <div class="option-block border rounded p-3 mb-3 bg-light" data-option-idx="${optionIdx}">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-secondary"><i class="fa-solid fa-tag me-2"></i>Option #${optionIdx + 1}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveOption" ${optionIdx === 0 ? 'style="display:none;"' : ''}>
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Option Label <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="variant_${variantIdx}_optionLabel_${optionIdx}" placeholder="e.g. Red, XL, 512GB" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Short Description</label>
              <input type="text" class="form-control" name="variant_${variantIdx}_shortDescription_${optionIdx}" placeholder="Brief description (optional)">
            </div>
          </div>
          
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Original Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="variant_${variantIdx}_originalPrice_${optionIdx}" required>
                <span class="input-group-text">₫</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Current Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="variant_${variantIdx}_currentPrice_${optionIdx}" required>
                <span class="input-group-text">₫</span>
              </div>
            </div>
          </div>
          
          <!-- Descriptions Section -->
          <div class="mt-3 p-2 border rounded bg-white">
            <label class="form-label fw-semibold text-secondary">
              <i class="fa-solid fa-list me-1"></i>Detailed Descriptions
            </label>
            <div class="variantDescriptionsList" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2 btnAddDescription" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}">
              <i class="fa-solid fa-plus"></i> Add Description
            </button>
          </div>
          
          <!-- Images Section -->
          <div class="mt-3 p-2 border rounded bg-white">
            <label class="form-label fw-semibold text-secondary">
              <i class="fa-solid fa-images me-1"></i>Option Images
            </label>
            <input type="file" class="form-control mb-2 variant-option-images-input" 
              name="variant_${variantIdx}_option_${optionIdx}_images[]" accept="image/*" multiple>
            <div class="form-text mb-2">Select multiple images for this option</div>
            <div class="variantOptionImagesPreview d-flex flex-wrap gap-2 mt-2"></div>
            <div class="variantOptionImagesPriority mt-2"></div>
          </div>
        </div>
        `;
    }

    function createDescriptionRow(variantIdx, optionIdx, descIdx = 0, name = '', text = '', priority = 0) {
        return `
        <div class="variant-desc-row border-bottom pb-2 mb-2" data-desc-idx="${descIdx}">
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label small">Name</label>
              <input type="text" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_name[]" 
                placeholder="Description Name" value="${name}">
            </div>
            <div class="col-md-6">
              <label class="form-label small">Content</label>
              <input type="text" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_text[]" 
                placeholder="Description Content" value="${text}">
            </div>
            <div class="col-md-2">
              <label class="form-label small">Priority</label>
              <input type="number" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_priority[]" 
                placeholder="Priority" value="${priority}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-sm btn-outline-danger btn-remove-desc">
                <i class="fa-solid fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>`;
    }
    function createImageRow(variantIdx, optionIdx, imgIdx = 0, priority = 0) {
        return `
        <div class="input-group mb-1 variant-img-row" data-img-idx="${imgIdx}">
            <input type="file" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_image[]" accept="image/*">
            <input type="number" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_image_priority[]" placeholder="Priority" value="${priority}">
            <button type="button" class="btn btn-outline-danger btn-remove-img" tabindex="-1">&times;</button>
        </div>`;
    }

    function refreshVariantRemoveBtns() {
        variantsSection.querySelectorAll('.btnRemoveVariant').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-block').remove();
                variantCount--;
                updateAddVariantBtn();
            };
        });
    }
    function refreshOptionRemoveBtns() {
        variantsSection.querySelectorAll('.btnRemoveOption').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.option-block').remove();
            };
        });
    }
    function refreshDescriptionRemoveBtns() {
        variantsSection.querySelectorAll('.btn-remove-desc').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-desc-row').remove();
            };
        });
    }
    function refreshImageRemoveBtns() {
        variantsSection.querySelectorAll('.btn-remove-img').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-img-row').remove();
            };
        });
    }
    function updateAddVariantBtn() {
        btnAddVariant.disabled = variantCount >= MAX_VARIANTS;
    }

    function addOptionToVariant(variantIdx) {
        const variantBlock = variantsSection.querySelector(`.variant-block[data-variant-idx="${variantIdx}"]`);
        const optionsList = variantBlock.querySelector('.variant-options-list');
        const optionIdx = optionsList.children.length;
        optionsList.insertAdjacentHTML('beforeend', createOptionBlock(variantIdx, optionIdx));
        refreshOptionRemoveBtns();

        // Add one description and one image row by default
        const descList = optionsList.lastElementChild.querySelector('.variantDescriptionsList');
        const imgList = optionsList.lastElementChild.querySelector('.variantImagesList');
        descList.insertAdjacentHTML('beforeend', createDescriptionRow(variantIdx, optionIdx, 0, '', '', 0));
        imgList.insertAdjacentHTML('beforeend', createImageRow(variantIdx, optionIdx, 0, 0));
        refreshDescriptionRemoveBtns();
        refreshImageRemoveBtns();

        // Add handler for add description/image buttons
        optionsList.lastElementChild.querySelector('.btnAddDescription').onclick = function() {
            const descList = this.parentElement.querySelector('.variantDescriptionsList');
            const descIdx = descList.children.length;
            descList.insertAdjacentHTML('beforeend', createDescriptionRow(variantIdx, optionIdx, descIdx, '', '', descIdx));
            refreshDescriptionRemoveBtns();
        };
        optionsList.lastElementChild.querySelector('.btnAddImage').onclick = function() {
            const imgList = this.parentElement.querySelector('.variantImagesList');
            const imgIdx = imgList.children.length;
            imgList.insertAdjacentHTML('beforeend', createImageRow(variantIdx, optionIdx, imgIdx, imgIdx));
            refreshImageRemoveBtns();
        };
    }

    btnAddVariant.onclick = function() {
        if (variantCount >= MAX_VARIANTS) return;
        const idx = variantCount;
        variantsSection.insertAdjacentHTML('beforeend', createVariantBlock(idx));
        variantCount++;
        updateAddVariantBtn();
        refreshVariantRemoveBtns();

        // Add one option by default
        addOptionToVariant(idx);

        // Add handler for add option button
        const variantBlock = variantsSection.querySelector(`.variant-block[data-variant-idx="${idx}"]`);
        variantBlock.querySelector('.btnAddOption').onclick = function() {
            addOptionToVariant(idx);
        };
    };

    // On modal show, reset variants
    btnAddProduct.addEventListener('click', function() {
        addProductForm.reset();
        addProductAlert.innerHTML = '';
        variantsSection.innerHTML = '';
        variantCount = 0;
        btnAddVariant.disabled = false;
        // Thêm biến thể đầu tiên (không dùng await)
        btnAddVariant.click();
        addProductModal.show();
    });

    // --- Gather form data for variants/options ---
    addProductForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        addProductAlert.innerHTML = '';
        // ...existing code for main fields...
        const form = e.target;
        const name = form.name.value.trim();
        const code = form.code.value.trim();
        const status = form.status.value;
        const brandCode = form.brandCode.value;
        const categoriesCode = Array.from(form.querySelectorAll('input[name="categoriesCode[]"]:checked')).map(cb => cb.value);
        const giftCodes = form.giftCodes.value.split(',').map(s => s.trim()).filter(Boolean);

        // Main image
        const imageFile = form.image.files[0];
        let imageBase64 = '';
        if (imageFile) {
            imageBase64 = await fileToBase64(imageFile);
        }

        // --- Gather variants ---
        const variants = [];
        const variantBlocks = Array.from(variantsSection.querySelectorAll('.variant-block'));
        for (let vIdx = 0; vIdx < variantBlocks.length; ++vIdx) {
            const variantBlock = variantBlocks[vIdx];
            const optionTitle = variantBlock.querySelector(`input[name="variant_optionTitle_${vIdx}"]`).value.trim();
            const options = [];
            const optionBlocks = Array.from(variantBlock.querySelectorAll('.option-block'));
            for (let oIdx = 0; oIdx < optionBlocks.length; ++oIdx) {
                const optionBlock = optionBlocks[oIdx];
                const optionLabel = optionBlock.querySelector(`input[name="variant_${vIdx}_optionLabel_${oIdx}"]`).value.trim();
                const originalPrice = parseInt(optionBlock.querySelector(`input[name="variant_${vIdx}_originalPrice_${oIdx}"]`).value, 10) || 0;
                const currentPrice = parseInt(optionBlock.querySelector(`input[name="variant_${vIdx}_currentPrice_${oIdx}"]`).value, 10) || 0;
                const shortDescription = optionBlock.querySelector(`input[name="variant_${vIdx}_shortDescription_${oIdx}"]`).value.trim();

                // Descriptions
                const descNames = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_name[]"]`)).map(i => i.value.trim());
                const descTexts = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_text[]"]`)).map(i => i.value.trim());
                const descPriorities = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_priority[]"]`)).map(i => parseInt(i.value, 10) || 0);
                const descriptions = [];
                for (let i = 0; i < descNames.length; ++i) {
                    if (descNames[i] && descTexts[i]) {
                        descriptions.push({
                            name: descNames[i],
                            descriptionText: descTexts[i],
                            priority: descPriorities[i]
                        });
                    }
                }

                // Option Images (multiple, with priority)
                const imagesInput = optionBlock.querySelector(`input[name="variant_${vIdx}_option_${oIdx}_images[]"]`);
                let imagesBase64 = [];
                if (imagesInput && imagesInput.files.length > 0) {
                    // Get priorities from the UI (input[type=number] generated below)
                    const priorityInputs = optionBlock.querySelectorAll('.variantOptionImagesPriority input[type=number]');
                    for (let i = 0; i < imagesInput.files.length; ++i) {
                        const file = imagesInput.files[i];
                        let priority = i;
                        if (priorityInputs[i]) {
                            priority = parseInt(priorityInputs[i].value, 10) || i;
                        }
                        if (file) {
                            const base64Content = await fileToBase64(file);
                            imagesBase64.push({
                                base64Content,
                                priority
                            });
                        }
                    }
                }

                options.push({
                    optionLabel,
                    originalPrice,
                    currentPrice,
                    descriptions,
                    imagesBase64,
                    shortDescription
                });
            }
            variants.push({
                optionTitle,
                options
            });
        }

        // Build request body
        const body = {
            name,
            code,
            imageBase64,
            categoriesCode,
            status,
            brandCode,
            variants,
            giftCodes
        };

        // POST to API
        try {
            const resp = await fetch('http://localhost:5000/api/products/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= htmlspecialchars($token) ?>'
                },
                body: JSON.stringify(body)
            });
            const text = await resp.text();
            let data = {};
            try {
                data = text ? JSON.parse(text) : {};
            } catch (err) {
                console.error('Failed to parse JSON:', err, text);
            }
            if (!resp.ok || !data.success) {
                throw new Error(data.message || 'Thêm sản phẩm thất bại');
            }
            addProductAlert.innerHTML = '<div class="alert alert-success">Thêm sản phẩm thành công!</div>';
            setTimeout(() => { addProductModal.hide(); window.location.reload(); }, 1200);
        } catch (err) {
            addProductAlert.innerHTML = `<div class="alert alert-danger">${err.message || 'Lỗi thêm sản phẩm'}</div>`;
        }
    });

    function fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result.split(',')[1] || '');
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function showProductDetail(productId) {
        const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
        const contentDiv = document.getElementById('viewProductModalContent');
        contentDiv.innerHTML = '<div class="text-muted">Loading product information...</div>';
        modal.show();

        fetch('http://localhost:5000/api/products/' + encodeURIComponent(productId), {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer <?= htmlspecialchars($token) ?>',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(async response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        })
        .then(data => {
            if (!data.success || !data.data) {
                contentDiv.innerHTML = '<div class="alert alert-danger">Unable to load product information.</div>';
                return;
            }
            contentDiv.innerHTML = renderProductDetail(data.data);
        })
        .catch(err => {
            contentDiv.innerHTML = '<div class="alert alert-danger">Server connection error, please try again.</div>';
        });
    }

    function renderProductDetail(data) {
        function esc(str) {
            return typeof str === 'string' ? str.replace(/[&<>"']/g, function(m) {
                return ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                })[m];
            }) : '';
        }
        const info = data.productInfo || {};
        const price = data.price || {};
        const detail = data.productDetail || {};
        const options = Array.isArray(data.productOptions) ? data.productOptions : [];
        const gifts = Array.isArray(data.gifts) ? data.gifts : [];
        const createdDate = data.createdDate ? formatDateTime(data.createdDate) : '';
        const createdBy = esc(data.createdBy || '');

        let imgThumb = info.imageUrl ? `<img src="${esc(info.imageUrl)}" class="modal-product-thumb me-2 mb-2" alt="Product Image">` :
            `<img src="https://via.placeholder.com/96x96?text=No+Image" class="modal-product-thumb me-2 mb-2" alt="No Image">`;

        let imgList = '';
        if (detail.image && detail.image.length > 0) {
            imgList = detail.image.map(img =>
                `<img src="${esc(img.url)}" alt="Ảnh phụ" title="priority: ${img.priority}" />`
            ).join('');
            imgList = `<div class="modal-product-img-list mb-2">${imgList}</div>`;
        }

        let categories = Array.isArray(info.category) ? info.category.map(esc).join(', ') : '';

        let descList = '';
        if (detail.description && detail.description.length > 0) {
            descList = '<div class="mb-2"><span class="modal-product-label fw-bold">Description:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
            detail.description.forEach(d => {
                descList += `<li>
                    <span class="modal-product-label fw-bold">${esc(d.name)}:</span> ${esc(d.descriptionText)}
                </li>`;
            });
            descList += '</ul></div>';
        }

        let optionList = '';
        if (options.length > 0) {
            optionList = '<div class="mb-2">';
            options.forEach(opt => {
                optionList += `<div class="modal-product-option mb-1"><span class="modal-product-label fw-bold">${esc(opt.title)}:</span> `;
                if (Array.isArray(opt.options)) {
                    optionList += opt.options.map(o =>
                        `<span class="${o.selected ? 'selected' : ''}">${esc(o.label)}</span>`
                    ).join(', ');
                }
                optionList += '</div>';
            });
            optionList += '</div>';
        }

        let giftList = '';
        if (!gifts || gifts.length === 0) {
            giftList = '<div class="text-muted">No gifts</div>';
        } else {
            giftList = '<div class="mb-2">';
            gifts.forEach((g, idx) => {
                if (g && g.image) {
                    giftList += `<img src="${esc(g.image)}" alt="${esc(g.name || '')}" title="${esc(g.name || '')}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin-right:6px;margin-bottom:4px;background:#eee;">`;
                }
            });
            giftList += '</div>';
        }

        let priceHtml = `<div class="row g-2">
            <div class="col-6">
                <span class="modal-product-label fw-bold">Original Price:</span>
                <span class="text-secondary">${formatPrice(price.originalPrice)}₫</span>
            </div>
            <div class="col-6">
                <span class="modal-product-label fw-bold">Current Price:</span>
                <span class="text-success">${formatPrice(price.currentPrice)}₫</span>
            </div>
            <div class="col-6">
                <span class="modal-product-label fw-bold">Discount Price:</span>
                <span class="text-danger">${formatPrice(price.discountPrice)}₫</span>
            </div>
            <div class="col-6">
                <span class="modal-product-label fw-bold">Discount:</span>
                <span class="text-warning">${price.discountPercentage ? esc(price.discountPercentage + '%') : '0%'}</span>
            </div>
        </div>`;

        return `
        <div class="card border-0 shadow-sm mb-0" style="background:#f8f9fb;">
            <div class="card-body pb-2">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="mb-2">${imgThumb}</div>
                        ${imgList}
                    </div>
                    <div class="col-md-8">
                        <div class="mb-2">
                            <span class="modal-product-label fw-bold text-primary">ID:</span> ${esc(info.id)}<br>
                            <span class="modal-product-label fw-bold text-primary">Product Code:</span> ${esc(info.code)}<br>
                            <span class="modal-product-label fw-bold text-primary">Name:</span> ${esc(info.name)}<br>
                            <span class="modal-product-label fw-bold text-primary">Status:</span> ${esc(info.status)}<br>
                            <span class="modal-product-label fw-bold text-primary">Categories:</span> ${categories}<br>
                            <span class="modal-product-label fw-bold text-primary">Brand:</span> ${esc(info.brand)}
                        </div>
                        <div class="mb-2">${priceHtml}</div>
                        <div class="mb-2">
                            <span class="modal-product-label fw-bold text-primary">Short Description:</span> ${esc(detail.shortDescription)}
                        </div>
                        ${descList}
                        ${optionList}
                        <div class="mb-2">
                            <span class="modal-product-label fw-bold text-primary">Gifts:</span><br>
                            ${giftList}
                        </div>
                        <div class="modal-product-meta small text-muted">
                            <span class="modal-product-label fw-bold">Created:</span> ${createdDate}<br>
                            <span class="modal-product-label fw-bold">Created by:</span> ${createdBy}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
    }

    function formatPrice(val) {
        if (typeof val !== 'number') return '0';
        return val.toLocaleString('vi-VN', {maximumFractionDigits: 0});
    }

    function formatDateTime(dt) {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return '';
        const pad = n => n < 10 ? '0' + n : n;
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${pad(d.getFullYear())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // Show all toasts on page load
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    });

    // --- Handle dynamic image preview and priority UI for option images ---
    variantsSection.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('variant-option-images-input')) {
            updateOptionImagesPreviewAndPriority(e.target);
        }
    });

    // Remove image from input.files (by recreating FileList)
    function removeImageFromInput(input, removeIdx) {
        const dt = new DataTransfer();
        Array.from(input.files).forEach((file, idx) => {
            if (idx !== removeIdx) dt.items.add(file);
        });
        input.files = dt.files;
        // Update preview and priorities after removal
        updateOptionImagesPreviewAndPriority(input);
    }

    // Helper to update preview and priority UI for option images
    function updateOptionImagesPreviewAndPriority(input) {
        const previewContainer = input.closest('.option-block').querySelector('.variantOptionImagesPreview');
        const priorityContainer = input.closest('.option-block').querySelector('.variantOptionImagesPriority');
        previewContainer.innerHTML = '';
        priorityContainer.innerHTML = '';
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    // Show image preview with remove button and priority input
                    const wrapper = document.createElement('div');
                    wrapper.className = 'position-relative d-inline-block';
                    wrapper.style.width = '70px';
                    wrapper.style.height = '90px';
                    wrapper.innerHTML = `
                        <img src="${evt.target.result}" class="border rounded" style="width:64px;height:64px;object-fit:cover;">
                        <input type="number" class="form-control form-control-sm mt-1 text-center" value="${i}" min="0" name="priority_${i}" style="width:64px;" placeholder="Priority">
                        <button type="button" class="btn btn-sm btn-danger btn-remove-image position-absolute top-0 end-0 p-1" title="Remove" style="font-size:0.8em;line-height:1;">&times;</button>
                    `;
                    wrapper.querySelector('.btn-remove-image').onclick = function() {
                        removeImageFromInput(input, i);
                    };
                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        }
    }
});
</script>
</body>
</html>
