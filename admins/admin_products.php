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
        .product-img-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            background: #eee;
        }
        .action-btns .btn {
            margin-right: 4px;
        }
        /* --- Modal thumbnail styling --- */
        .modal-product-thumb {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
            margin-right: 8px;
        }
        .modal-product-img-list img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 6px;
            margin-bottom: 6px;
            background: #eee;
        }
        .modal-product-option .selected {
            font-weight: bold;
            color: #198754;
        }
        .modal-product-label {
            font-weight: 500;
            color: black;
        }
        .modal-product-meta {
            font-size: 0.95em;
            color: #888;
        }
        .modal-product-desc-list {
            margin-bottom: 0;
        }
        /* Thêm style cho card trong modal */
        #addProductModal .card {
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        #addProductModal .card-header {
            border-bottom: 1px solid #e3e3e3;
            font-size: 1.05em;
        }
        #addProductModal .card-body {
            padding-bottom: 0.5rem;
        }
        #addProductModal label.form-label {
            font-weight: 500;
        }
        #addProductModal .variant-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 12px;
        }
        #addProductModal .option-block {
            background: #fff;
            border: 1px solid #e3e3e3;
            border-radius: 6px;
            margin-bottom: 10px;
            padding: 10px;
        }
        #addProductModal .input-group .form-control {
            min-width: 0;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Product List</h4>
        <div>
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
    <!-- Alert for JS actions -->
    <div id="jsAlertContainer" style="margin-top:16px;"></div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="addProductForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="overflow-y:auto; max-height:70vh;">
          <div id="addProductAlert"></div>
          <!-- Main Info -->
          <div class="card mb-3 border-primary">
            <div class="card-header bg-primary text-white py-2">
              <strong>Product Information</strong>
            </div>
            <div class="card-body pb-2">
              <div class="row mb-2">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Product Name</label>
                  <input type="text" class="form-control" name="name" required>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Product Code</label>
                  <input type="text" class="form-control" name="code" required>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label">Main Image</label>
                <input type="file" class="form-control" name="image" accept="image/*" required>
              </div>
            </div>
          </div>
          <!-- Category, Brand, Status -->
          <div class="card mb-3 border-success">
            <div class="card-header bg-success text-white py-2">
              <strong>Classification & Status</strong>
            </div>
            <div class="card-body pb-2">
              <div class="mb-2">
                <label class="form-label">Categories</label>
                <div class="row">
                  <?php foreach ($categoriesList as $cat): ?>
                    <div class="col-md-4">
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
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Brand</label>
                  <select class="form-select" name="brandCode" required>
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brandsList as $brand): ?>
                      <option value="<?= htmlspecialchars($brand['code']) ?>">
                        <?= htmlspecialchars($brand['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status" required>
                    <option value="New">New</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <!-- Gift code -->
          <div class="card mb-3 border-warning">
            <div class="card-header bg-warning text-dark py-2">
              <strong>Gift Codes</strong>
            </div>
            <div class="card-body pb-2">
              <label class="form-label">Gift Codes (comma separated, optional)</label>
              <input type="text" class="form-control" name="giftCodes" placeholder="e.g. gift1,gift2">
            </div>
          </div>
          <!-- VARIANTS SECTION -->
          <div class="card mb-3 border-info">
            <div class="card-header bg-info text-white py-2">
              <strong>Product Variants</strong>
            </div>
            <div class="card-body pb-2">
              <div id="variantsSection">
                <!-- Variant blocks will be inserted here by JS -->
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnAddVariant">+ Add Variant</button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Add Product</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        <div class="variant-block border rounded p-2 mb-3" data-variant-idx="${idx}">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold">Variant #${idx + 1}</span>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveVariant" ${idx === 0 ? 'style="display:none;"' : ''}>&times;</button>
          </div>
          <div class="mb-2">
            <label class="form-label">Variant Title</label>
            <input type="text" class="form-control" name="variant_optionTitle_${idx}" placeholder="e.g. Color" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Options for this variant</label>
            <div class="variant-options-list" data-variant-idx="${idx}"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1 btnAddOption" data-variant-idx="${idx}">+ Add Option</button>
          </div>
        </div>
        `;
    }

    function createOptionBlock(variantIdx, optionIdx) {
        return `
        <div class="option-block border p-2 mb-2" data-option-idx="${optionIdx}">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Option #${optionIdx + 1}</span>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveOption" ${optionIdx === 0 ? 'style="display:none;"' : ''}>&times;</button>
          </div>
          <div class="mb-2">
            <label class="form-label">Option Label</label>
            <input type="text" class="form-control" name="variant_${variantIdx}_optionLabel_${optionIdx}" placeholder="e.g. Red" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Original Price</label>
            <input type="number" class="form-control" name="variant_${variantIdx}_originalPrice_${optionIdx}" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Current Price</label>
            <input type="number" class="form-control" name="variant_${variantIdx}_currentPrice_${optionIdx}" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Short Description</label>
            <input type="text" class="form-control" name="variant_${variantIdx}_shortDescription_${optionIdx}">
          </div>
          <!-- Multiple Descriptions -->
          <div class="mb-2">
            <label class="form-label">Detailed Descriptions (add multiple if needed)</label>
            <div class="variantDescriptionsList" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1 btnAddDescription" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}">+ Add Description</button>
          </div>
          <!-- Multiple Images -->
          <div class="mb-2">
            <label class="form-label">Option Images (add multiple if needed)</label>
            <div class="variantImagesList" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1 btnAddImage" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}">+ Add Image</button>
          </div>
        </div>
        `;
    }

    function createDescriptionRow(variantIdx, optionIdx, descIdx = 0, name = '', text = '', priority = 0) {
        return `
        <div class="input-group mb-1 variant-desc-row" data-desc-idx="${descIdx}">
            <input type="text" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_desc_name[]" placeholder="Description Name" value="${name}">
            <input type="text" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_desc_text[]" placeholder="Description Content" value="${text}">
            <input type="number" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_desc_priority[]" placeholder="Priority" value="${priority}">
            <button type="button" class="btn btn-outline-danger btn-remove-desc" tabindex="-1">&times;</button>
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

                // Images
                const imageInputs = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_image[]"]`));
                const imagePriorities = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_image_priority[]"]`)).map(i => parseInt(i.value, 10) || 0);
                const imagesBase64 = [];
                for (let i = 0; i < imageInputs.length; ++i) {
                    const file = imageInputs[i].files[0];
                    if (file) {
                        const base64Content = await fileToBase64(file);
                        imagesBase64.push({
                            base64Content,
                            priority: imagePriorities[i]
                        });
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
            descList = '<div class="mb-2"><span class="modal-product-label">Description:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
            detail.description.forEach(d => {
                descList += `<li>
                    <span class="modal-product-label">${esc(d.name)}:</span> ${esc(d.descriptionText)}
                </li>`;
            });
            descList += '</ul></div>';
        }

        let optionList = '';
        if (options.length > 0) {
            optionList = '<div class="mb-2">';
            options.forEach(opt => {
                optionList += `<div class="modal-product-option mb-1"><span class="modal-product-label">${esc(opt.title)}:</span> `;
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
                // Show gift image, images next to each other
                if (g && g.image) {
                    giftList += `<img src="${esc(g.image)}" alt="${esc(g.name || '')}" title="${esc(g.name || '')}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin-right:6px;margin-bottom:4px;background:#eee;">`;
                }
            });
            giftList += '</div>';
        }

        let priceHtml = `<div>
            <span class="modal-product-label">Original Price:</span> ${formatPrice(price.originalPrice)}₫<br>
            <span class="modal-product-label">Current Price:</span> ${formatPrice(price.currentPrice)}₫<br>
            <span class="modal-product-label">Discount Price:</span> ${formatPrice(price.discountPrice)}₫<br>
            <span class="modal-product-label">Discount:</span> ${price.discountPercentage ? esc(price.discountPercentage + '%') : '0%'}
        </div>`;

        return `
        <div class="row">
            <div class="col-md-4 text-center">
                ${imgThumb}
                ${imgList}
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <span class="modal-product-label">ID:</span> ${esc(info.id)}<br>
                    <span class="modal-product-label">Product Code:</span> ${esc(info.code)}<br>
                    <span class="modal-product-label">Name:</span> ${esc(info.name)}<br>
                    <span class="modal-product-label">Status:</span> ${esc(info.status)}<br>
                    <span class="modal-product-label">Categories:</span> ${categories}<br>
                    <span class="modal-product-label">Brand:</span> ${esc(info.brand)}
                </div>
                <div class="mb-2">${priceHtml}</div>
                <div class="mb-2">
                    <span class="modal-product-label">Short Description:</span> ${esc(detail.shortDescription)}
                </div>
                ${descList}
                ${optionList}
                <div class="mb-2">
                    <span class="modal-product-label">Gifts:</span><br>
                    ${giftList}
                </div>
                <div class="modal-product-meta">
                    <span class="modal-product-label">Created:</span> ${createdDate}<br>
                    <span class="modal-product-label">Created by:</span> ${createdBy}
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
});
</script>
</body>
</html>
