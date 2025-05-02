<?php
session_name('admin_session');
session_start();

$token = $_SESSION['token'];
$pageIndex = isset($_GET['page']) ? intval($_GET['page']) : 0;
$pageSize = 10;
$alerts = [];
$products = [];
$totalCount = 0;

// Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $code = trim($_POST['delete_code'] ?? '');
    if ($code !== '') {
        $res = apiRequest("http://localhost:5000/api/products/delete", $token, [$code]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Xóa sản phẩm thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể xóa sản phẩm.'];
        }
    }
}

// Helper: cURL request
function apiRequest($url, $token, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        "Authorization: Bearer $token",
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'message' => $err];
    return json_decode($response, true);
}

// Fetch products
$res = apiRequest("http://localhost:5000/api/products?pageIndex=$pageIndex&pageSize=$pageSize", $token);
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $products = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể tải sản phẩm.'];
}

// Helper: image placeholder
function productImage($img) {
    if (!$img || !filter_var($img, FILTER_VALIDATE_URL)) {
        return '<img src="https://via.placeholder.com/50x50?text=No+Img" class="product-thumb" width="50" height="50">';
    }
    return '<img src="' . htmlspecialchars($img) . '" class="product-thumb" width="50" height="50" onerror="this.src=\'https://via.placeholder.com/50x50?text=No+Img\';">';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container admin-products">
    <!-- <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?> -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Danh sách sản phẩm</h4>
        <button class="btn btn-success" id="addProductBtn">
            Thêm sản phẩm
        </button>
    </div>
    <div id="addProductAlert"></div>
    <div class="table-responsive">
        <table class="table table-bordered product-table align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Hình ảnh</th>
                    <th>Thương hiệu</th>
                    <th>Giá hiện tại</th>
                    <th>Giá gốc</th>
                    <th>Mô tả ngắn</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['id']) ?></td>
                    <td><?= htmlspecialchars($product['code']) ?></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= productImage($product['imageUrl'] ?? '') ?></td>
                    <td><?= !empty($product['brandName']) ? htmlspecialchars($product['brandName']) : 'N/A' ?></td>
                    <td><?= htmlspecialchars(number_format($product['currentPrice'], 2)) ?></td>
                    <td><?= htmlspecialchars(number_format($product['originalPrice'], 2)) ?></td>
                    <td><?= htmlspecialchars($product['shortDescription']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm action-btn view-product-btn" data-id="<?= htmlspecialchars($product['id']) ?>" title="Xem">
                            Xem
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận xóa sản phẩm này?');">
                            <input type="hidden" name="delete_code" value="<?= htmlspecialchars($product['code']) ?>">
                            <button type="submit" name="delete_product" class="btn btn-danger btn-sm action-btn" title="Xóa">
                                Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted">Không có sản phẩm nào.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalCount > ($pageIndex + 1) * $pageSize): ?>
        <a href="?page=<?= $pageIndex + 1 ?>" class="btn btn-outline-secondary">Tải thêm</a>
    <?php endif; ?>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" id="addProductForm" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel"><i class="fa fa-plus"></i> Thêm sản phẩm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div id="addProductFormAlert"></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Tên sản phẩm</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Mã sản phẩm</label>
            <input type="text" class="form-control" name="code" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Hình ảnh chính</label>
            <input type="file" class="form-control" name="imageBase64" id="mainImageInput" accept="image/*" required>
            <img id="mainImgPreview" class="img-preview mt-2 d-block" src="https://via.placeholder.com/80x80?text=No+Img" alt="Preview">
          </div>
          <div class="col-md-6">
            <label class="form-label required">Thương hiệu</label>
            <select class="form-select" name="brandCode" id="brandSelect" required>
              <option value="">-- Chọn thương hiệu --</option>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label required">Danh mục</label>
            <div id="categoriesCheckboxes" class="row"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Trạng thái</label>
            <select class="form-select" name="status" required>
              <option value="New" selected>Mới</option>
              <option value="Used">Đã sử dụng</option>
              <option value="Refurbished">Tân trang</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Gift Codes (phân cách dấu phẩy)</label>
            <input type="text" class="form-control" name="giftCodes" placeholder="VD: GIFT1,GIFT2">
          </div>
        </div>
        <hr>
        <h6>
            Biến thể sản phẩm
            <button type="button" class="btn btn-success btn-sm add-variant-group-btn ms-2">
                <i class="fa fa-plus"></i> Thêm nhóm biến thể
            </button>
        </h6>
        <div id="variantGroupsContainer"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
        <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> Thêm sản phẩm</button>
      </div>
    </form>
  </div>
</div>

<!-- Product Detail Modal -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-box"></i> Chi tiết sản phẩm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div id="product-modal-alert" class="alert alert-danger d-none"></div>
        <div id="product-modal-content">
            <div class="text-center text-muted py-4">
                <div class="spinner-border text-primary"></div>
                <div>Đang tải...</div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Đóng
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const token = '<?= htmlspecialchars($_SESSION['token']) ?>';

// Show modal
$('#addProductBtn').on('click', function() {
    $('#addProductForm')[0].reset();
    $('#mainImgPreview').attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    $('#addProductFormAlert').html('');
    resetVariantGroups();
    mainImageBase64 = '';
    $('#addProductModal').modal('show');
});

// Fetch brands and categories for select options
function fetchBrandsAndCategories() {
    // Brands
    fetch('http://localhost:5000/api/brands/get?pageIndex=0&pageSize=100', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
        let html = '<option value="">-- Chọn thương hiệu --</option>';
        if (data.success && data.data && data.data.data) {
            data.data.data.forEach(b => {
                html += `<option value="${b.code}">${b.name}</option>`;
            });
        }
        $('#brandSelect').html(html);
    });
    // Categories as checkboxes
    fetch('http://localhost:5000/api/categories/get?pageIndex=0&pageSize=100', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
        let html = '';
        if (data.success && data.data && data.data.data) {
            data.data.data.forEach(c => {
                html += `
                <div class="form-check col-md-4 mb-1">
                    <input class="form-check-input category-checkbox" type="checkbox" value="${c.code}" id="cat_${c.code}">
                    <label class="form-check-label" for="cat_${c.code}">${c.name}</label>
                </div>`;
            });
        }
        $('#categoriesCheckboxes').html(html);
    });
}
fetchBrandsAndCategories();

// Main image preview & base64
let mainImageBase64 = '';
$('#mainImageInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            mainImageBase64 = evt.target.result.split(',')[1];
            $('#mainImgPreview').attr('src', evt.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        mainImageBase64 = '';
        $('#mainImgPreview').attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    }
});

// --- Variant Groups/Options Dynamic Form ---
let variantGroupIdx = 0;

// Template for a variant group
function getVariantGroupHtml(idx) {
    return `
    <div class="variant-group" data-group-idx="${idx}">
        <button type="button" class="btn btn-danger btn-sm remove-variant-group-btn" title="Xoá nhóm">
            <i class="fa fa-trash"></i>
        </button>
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label required">Tên tuỳ chọn (optionTitle)</label>
                <input type="text" class="form-control option-title-input" name="optionTitle_${idx}" required>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary btn-sm add-variant-option-btn" data-group-idx="${idx}">
                    <i class="fa fa-plus"></i> Thêm tuỳ chọn
                </button>
            </div>
        </div>
        <div class="variant-options-container mt-3"></div>
    </div>
    `;
}

// Template for a variant option
function getVariantOptionHtml(groupIdx, optionIdx) {
    return `
    <div class="variant-option" data-option-idx="${optionIdx}">
        <button type="button" class="btn btn-outline-danger btn-sm remove-variant-option-btn" title="Xoá tuỳ chọn">
            <i class="fa fa-trash"></i>
        </button>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required">Nhãn tuỳ chọn (optionLabel)</label>
                <input type="text" class="form-control option-label-input" name="optionLabel_${groupIdx}_${optionIdx}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label required">Giá gốc</label>
                <input type="number" class="form-control original-price-input" name="originalPrice_${groupIdx}_${optionIdx}" min="0" step="0.01" required>
            </div>
            <div class="col-md-3">
                <label class="form-label required">Giá hiện tại</label>
                <input type="number" class="form-control current-price-input" name="currentPrice_${groupIdx}_${optionIdx}" min="0" step="0.01" required>
            </div>
            <div class="col-md-3">
                <label class="form-label required">Số lượng</label>
                <input type="number" class="form-control quantity-input" name="quantity_${groupIdx}_${optionIdx}" min="0" step="1" value="0" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Barcode</label>
                <input type="text" class="form-control barcode-input" name="barcode_${groupIdx}_${optionIdx}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mô tả ngắn</label>
                <input type="text" class="form-control short-description-input" name="shortDescription_${groupIdx}_${optionIdx}">
            </div>
            <div class="col-md-12">
                <label class="form-label required">Mô tả chi tiết</label>
                <div class="descriptions-container" data-group-idx="${groupIdx}" data-option-idx="${optionIdx}">
                    <!-- Description items will be appended here -->
                </div>
                <button type="button" class="btn btn-outline-success btn-sm mt-2 add-description-btn" data-group-idx="${groupIdx}" data-option-idx="${optionIdx}">
                    <i class="fa fa-plus"></i> Thêm mô tả
                </button>
            </div>
            <div class="col-md-12">
                <label class="form-label required">Ảnh chi tiết (imagesBase64)</label>
                <div class="images-container" data-group-idx="${groupIdx}" data-option-idx="${optionIdx}">
                    <!-- Image items will be appended here -->
                </div>
                <button type="button" class="btn btn-outline-success btn-sm mt-2 add-image-btn" data-group-idx="${groupIdx}" data-option-idx="${optionIdx}">
                    <i class="fa fa-plus"></i> Thêm ảnh
                </button>
            </div>
        </div>
    </div>
    `;
}

// Template for a single description item
function getDescriptionHtml(groupIdx, optionIdx, descIdx) {
    return `
    <div class="row g-2 align-items-end description-item mb-2" data-desc-idx="${descIdx}">
        <div class="col-md-3">
            <input type="text" class="form-control desc-name-input" name="descName_${groupIdx}_${optionIdx}_${descIdx}" placeholder="Tên mô tả" required>
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control desc-text-input" name="descText_${groupIdx}_${optionIdx}_${descIdx}" placeholder="Nội dung mô tả" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control desc-priority-input" name="descPriority_${groupIdx}_${optionIdx}_${descIdx}" value="${descIdx+1}" min="1" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm remove-description-btn" title="Xoá mô tả">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
    `;
}

// Template for a single image item
function getImageHtml(groupIdx, optionIdx, imgIdx) {
    return `
    <div class="row g-2 align-items-end image-item mb-2" data-img-idx="${imgIdx}">
        <div class="col-md-5">
            <input type="file" class="form-control image-input" name="image_${groupIdx}_${optionIdx}_${imgIdx}" accept="image/*" required>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control image-priority-input" name="imagePriority_${groupIdx}_${optionIdx}_${imgIdx}" value="${imgIdx+1}" min="1" required>
        </div>
        <div class="col-md-2">
            <img class="img-preview d-block image-preview" src="https://via.placeholder.com/80x80?text=No+Img" style="width:40px;height:40px;object-fit:contain;background:#f8f9fa;" alt="Preview">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm remove-image-btn" title="Xoá ảnh">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
    `;
}

// Add initial description when adding a variant option
function addInitialDescription($option, groupIdx, optionIdx) {
    const $descContainer = $option.find('.descriptions-container');
    $descContainer.empty();
    $descContainer.append(getDescriptionHtml(groupIdx, optionIdx, 0));
}

// Add initial image when adding a variant option
function addInitialImage($option, groupIdx, optionIdx) {
    const $imgContainer = $option.find('.images-container');
    $imgContainer.empty();
    $imgContainer.append(getImageHtml(groupIdx, optionIdx, 0));
}

// When adding a variant option, add one description and one image by default
function addVariantOption(groupIdx) {
    const $group = $(`.variant-group[data-group-idx="${groupIdx}"]`);
    if ($group.length === 0) return;
    const $optionsContainer = $group.find('.variant-options-container');
    const optionIdx = $optionsContainer.children('.variant-option').length;
    $optionsContainer.append(getVariantOptionHtml(groupIdx, optionIdx));
    // Add one description by default
    const $option = $optionsContainer.children('.variant-option').last();
    addInitialDescription($option, groupIdx, optionIdx);
    // Add one image by default
    addInitialImage($option, groupIdx, optionIdx);
}

function resetVariantGroups() {
    $('#variantGroupsContainer').empty();
    variantGroupIdx = 0;
    addVariantGroup();
}

function addVariantGroup() {
    const idx = variantGroupIdx++;
    $('#variantGroupsContainer').append(getVariantGroupHtml(idx));
    // Add one option by default
    addVariantOption(idx);
}

// Handle "Thêm nhóm biến thể" button
$('.add-variant-group-btn').on('click', function() {
    addVariantGroup();
});

// Handle "Thêm tuỳ chọn" button inside each group (event delegation)
$('#variantGroupsContainer').on('click', '.add-variant-option-btn', function() {
    const groupIdx = $(this).data('group-idx');
    addVariantOption(groupIdx);
});

// Remove variant group
$('#variantGroupsContainer').on('click', '.remove-variant-group-btn', function() {
    if (confirm('Xóa nhóm biến thể này?')) {
        $(this).closest('.variant-group').remove();
    }
});

// Remove variant option
$('#variantGroupsContainer').on('click', '.remove-variant-option-btn', function() {
    if (confirm('Xóa tùy chọn này?')) {
        $(this).closest('.variant-option').remove();
    }
});

// --- Description & Image logic ---
// Add description logic
$('#variantGroupsContainer').on('click', '.add-description-btn', function() {
    const groupIdx = $(this).data('group-idx');
    const optionIdx = $(this).data('option-idx');
    const $descContainer = $(`.variant-group[data-group-idx="${groupIdx}"] .variant-option[data-option-idx="${optionIdx}"] .descriptions-container`);
    const descIdx = $descContainer.children('.description-item').length;
    $descContainer.append(getDescriptionHtml(groupIdx, optionIdx, descIdx));
});

// Remove description logic
$('#variantGroupsContainer').on('click', '.remove-description-btn', function() {
    const $descItem = $(this).closest('.description-item');
    const $descContainer = $descItem.parent();
    $descItem.remove();
    // Re-number priorities after removal
    $descContainer.children('.description-item').each(function(i) {
        $(this).find('.desc-priority-input').val(i+1);
    });
});

// Add image logic
$('#variantGroupsContainer').on('click', '.add-image-btn', function() {
    const groupIdx = $(this).data('group-idx');
    const optionIdx = $(this).data('option-idx');
    const $imgContainer = $(`.variant-group[data-group-idx="${groupIdx}"] .variant-option[data-option-idx="${optionIdx}"] .images-container`);
    const imgIdx = $imgContainer.children('.image-item').length;
    $imgContainer.append(getImageHtml(groupIdx, optionIdx, imgIdx));
});

// Remove image logic
$('#variantGroupsContainer').on('click', '.remove-image-btn', function() {
    const $imgItem = $(this).closest('.image-item');
    const $imgContainer = $imgItem.parent();
    $imgItem.remove();
    // Re-number priorities after removal
    $imgContainer.children('.image-item').each(function(i) {
        $(this).find('.image-priority-input').val(i+1);
    });
});

// Image preview & base64 for each image item
const variantImagesBase64Map = {}; // { groupIdx_optionIdx_imgIdx: base64 }

$('#variantGroupsContainer').on('change', '.image-input', function(e) {
    const $imgItem = $(this).closest('.image-item');
    const $option = $(this).closest('.variant-option');
    const $group = $(this).closest('.variant-group');
    const groupIdx = $group.data('group-idx');
    const optionIdx = $option.data('option-idx');
    const imgIdx = $imgItem.data('img-idx');
    const key = `${groupIdx}_${optionIdx}_${imgIdx}`;
    const file = this.files[0];
    const $preview = $imgItem.find('.image-preview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            variantImagesBase64Map[key] = evt.target.result.split(',')[1];
            $preview.attr('src', evt.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        variantImagesBase64Map[key] = '';
        $preview.attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    }
});

// Add Product Form Submit
$('#addProductForm').on('submit', function(e) {
    e.preventDefault();
    $('#addProductFormAlert').html('');
    const form = this;
    // --- Collect main product fields ---
    const name = form.name.value.trim();
    const code = form.code.value.trim();
    const brandCode = form.brandCode.value.trim();
    // Collect checked categories
    const categoriesCode = $('#categoriesCheckboxes .category-checkbox:checked').map(function() {
        return this.value;
    }).get();
    const status = form.status.value;
    const giftCodes = form.giftCodes.value.trim();

    // --- Collect variants ---
    let variants = [];
    let valid = true;
    $('#variantGroupsContainer .variant-group').each(function() {
        const $group = $(this);
        const groupIdx = $group.data('group-idx');
        const optionTitle = $group.find('.option-title-input').val().trim();
        if (!optionTitle) valid = false;
        let options = [];
        $group.find('.variant-option').each(function() {
            const $opt = $(this);
            const optionIdx = $opt.data('option-idx');
            const optionLabel = $opt.find('.option-label-input').val().trim();
            // Sửa: quantity là số nguyên, mặc định 0 nếu không có
            const quantity = parseInt($opt.find('.quantity-input').val(), 10);
            // Sửa: barcode là string, không ép kiểu số
            const barcode = $opt.find('.barcode-input').val() ? $opt.find('.barcode-input').val().trim() : "";
            const originalPrice = parseFloat($opt.find('.original-price-input').val());
            const currentPrice = parseFloat($opt.find('.current-price-input').val());
            const shortDescription = $opt.find('.short-description-input').val().trim();
            const piority = parseInt($opt.find('.piority-input').val(), 10) || 1;

            // --- Collect descriptions ---
            let descriptions = [];
            let descValid = true;
            $opt.find('.descriptions-container .description-item').each(function() {
                const $desc = $(this);
                const name = $desc.find('.desc-name-input').val().trim();
                const descriptionText = $desc.find('.desc-text-input').val().trim();
                const priority = parseInt($desc.find('.desc-priority-input').val(), 10) || 1;
                if (!name || !descriptionText) descValid = false;
                descriptions.push({ name, descriptionText, priority });
            });
            descriptions.sort((a, b) => a.priority - b.priority);

            // --- Collect images ---
            let imagesBase64 = [];
            let imgValid = true;
            $opt.find('.images-container .image-item').each(function() {
                const $img = $(this);
                const imgIdx = $img.data('img-idx');
                const priority = parseInt($img.find('.image-priority-input').val(), 10) || 1;
                const key = `${groupIdx}_${optionIdx}_${imgIdx}`;
                const base64Content = variantImagesBase64Map[key] || '';
                if (!base64Content) imgValid = false;
                imagesBase64.push({ base64Content, priority });
            });
            imagesBase64.sort((a, b) => a.priority - b.priority);

            if (!optionLabel || isNaN(quantity) || !Number.isFinite(originalPrice) || !Number.isFinite(currentPrice) || imagesBase64.length === 0 || descriptions.length === 0 || !descValid || !imgValid) valid = false;
            options.push({
                optionLabel,
                quantity: isNaN(quantity) ? 0 : quantity,
                originalPrice,
                currentPrice,
                barcode,
                descriptions,
                imagesBase64,
                shortDescription
            });
        });
        if (options.length === 0) valid = false;
        variants.push({
            optionTitle,
            options
        });
    });

    if (!name || !code || !mainImageBase64 || !brandCode || !categoriesCode.length || !variants.length || !valid) {
        $('#addProductFormAlert').html('<div class="alert alert-danger">Vui lòng nhập đầy đủ các trường bắt buộc.</div>');
        return;
    }

    // Prepare request body
    const body = {
        name,
        code,
        imageBase64: mainImageBase64,
        categoriesCode,
        status,
        brandCode,
        giftCodes: giftCodes ? giftCodes.split(',').map(s => s.trim()).filter(Boolean) : [],
        variants
    };

    // Send API request
    $('#addProductForm button[type=submit]').prop('disabled', true);
    fetch('http://localhost:5000/api/products/add', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(resp => {
        $('#addProductForm button[type=submit]').prop('disabled', false);
        if (resp.success) {
            $('#addProductFormAlert').html('<div class="alert alert-success">Thêm sản phẩm thành công!</div>');
            setTimeout(() => {
                $('#addProductModal').modal('hide');
                location.reload();
            }, 1200);
        } else {
            $('#addProductFormAlert').html('<div class="alert alert-danger">' + (resp.message || 'Không thể thêm sản phẩm.') + '</div>');
        }
    })
    .catch(err => {
        $('#addProductForm button[type=submit]').prop('disabled', false);
        $('#addProductFormAlert').html('<div class="alert alert-danger">Lỗi mạng hoặc máy chủ không phản hồi.</div>');
    });
});

$(function() {
    $('.view-product-btn').on('click', function() {
        var productId = $(this).data('id');
        $('#productDetailModal').modal('show');
        $('#product-modal-alert').addClass('d-none').text('');
        $('#product-modal-content').html(
            '<div class="text-center text-muted py-4">' +
            '<div class="spinner-border text-primary"></div>' +
            '<div>Đang tải...</div></div>'
        );
        // Use direct API call with Bearer token
        $.ajax({
            url: 'http://localhost:5000/api/products/' + encodeURIComponent(productId),
            method: 'GET',
            headers: {
                'Authorization': 'Bearer <?= htmlspecialchars($_SESSION['token']) ?>',
                'Accept': 'application/json'
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    renderProductModal(resp.data);
                } else {
                    $('#product-modal-alert').removeClass('d-none').text(resp.message || 'Không thể tải chi tiết sản phẩm.');
                    $('#product-modal-content').html('');
                }
            },
            error: function(xhr, status, error) {
                $('#product-modal-alert').removeClass('d-none').text('Lỗi mạng: ' + error);
                $('#product-modal-content').html('');
            }
        });
    });

    function renderProductModal(data) {
        function esc(str) {
            return $('<div>').text(str || '').html();
        }
        var info = data.productInfo || {};
        var img = info.imageUrl
            ? '<img src="' + esc(info.imageUrl) + '" class="modal-thumb mb-2" style="max-width:100px;max-height:100px;object-fit:contain;display:block;margin:auto;background:#f8f9fa;" alt="Product Image" onerror="this.src=\'https://via.placeholder.com/100x100?text=No+Img\';">'
            : '<img src="https://via.placeholder.com/100x100?text=No+Img" class="modal-thumb mb-2" style="max-width:100px;max-height:100px;object-fit:contain;display:block;margin:auto;background:#f8f9fa;" alt="No Image">';
        var brand = info.brand ? esc(info.brand) : 'N/A';
        var category = Array.isArray(info.category) ? esc(info.category.join(', ')) : (info.category ? esc(info.category) : 'N/A');
        var status = info.status ? esc(info.status) : 'N/A';
        var price = data.price || {};
        var detail = data.productDetail || {};
        var options = data.productOptions || [];
        var gifts = data.gifts || [];
        var createdDate = data.createdDate ? esc(data.createdDate) : '';
        var createdBy = data.createdBy ? esc(data.createdBy) : '';

        // Description: array of objects with name, descriptionText
        var descList = '';
        if (Array.isArray(detail.description) && detail.description.length) {
            descList = '<ul class="desc-list">';
            detail.description.forEach(function(d) {
                descList += '<li><b>' + esc(d.name) + ':</b> ' + esc(d.descriptionText) + '</li>';
            });
            descList += '</ul>';
        } else if (detail.description) {
            descList = '<div>' + esc(detail.description) + '</div>';
        }

        // Images: array of objects with url
        var detailImgs = '';
        if (Array.isArray(detail.image) && detail.image.length) {
            detailImgs = detail.image.map(function(imgObj) {
                var url = imgObj && imgObj.url ? imgObj.url : '';
                return '<img src="' + esc(url) + '" class="modal-thumb me-2 mb-2" style="width:60px;height:60px;" alt="Detail Image" onerror="this.src=\'https://via.placeholder.com/60x60?text=No+Img\';">';
            }).join('');
        }

        // Calculate total quantity from options
        var totalQuantity = 0;
        if (options.length) {
            options.forEach(function(opt) {
                if (Array.isArray(opt.options)) {
                    opt.options.forEach(function(o) {
                        if (typeof o.quantity === 'number') {
                            totalQuantity += o.quantity;
                        }
                    });
                }
            });
        }

        // Gifts
        var giftsHtml = '';
        if (gifts.length) {
            giftsHtml = '<div class="row">';
            gifts.forEach(function(g) {
                giftsHtml += '<div class="col-6 col-md-4 mb-2"><div class="card p-2 text-center">';
                giftsHtml += g.image
                    ? '<img src="' + esc(g.image) + '" class="modal-thumb mb-1" style="width:60px;height:60px;" alt="Gift Image" onerror="this.src=\'https://via.placeholder.com/60x60?text=No+Img\';">'
                    : '<img src="https://via.placeholder.com/60x60?text=No+Img" class="modal-thumb mb-1" alt="No Image">';
                giftsHtml += '<div class="fw-bold">' + esc(g.name) + '</div>';
                giftsHtml += '<div class="text-muted small">' + esc(g.code) + '</div>';
                giftsHtml += '</div></div>';
            });
            giftsHtml += '</div>';
        }

        // Options
        var optionsHtml = '';
        if (options.length) {
            optionsHtml = '<ul class="desc-list">';
            options.forEach(function(opt) {
                optionsHtml += '<li><b>' + esc(opt.title) + ':</b> ';
                if (Array.isArray(opt.options)) {
                    optionsHtml += opt.options.map(function(o) {
                        // Remove quantity from here
                        return esc(o.label) + (o.selected ? ' (Được chọn)' : '');
                    }).join(', ');
                }
                optionsHtml += '</li>';
            });
            optionsHtml += '</ul>';
        }

        var html = `
        <div class="row">
            <div class="col-md-4 text-center">
                ${img}
                <div class="fw-bold mt-2">${esc(info.name)}</div>
                <div class="text-muted small">Mã: ${esc(info.code)}</div>
                <div class="text-muted small">Trạng thái: ${status}</div>
                <div class="text-muted small">Danh mục: ${category}</div>
                <div class="text-muted small">Thương hiệu: ${brand}</div>
            </div>
            <div class="col-md-8">
                <h6>Giá</h6>
                <div>
                    <span class="me-2">Gốc: <span class="text-decoration-line-through text-muted">${esc(price.originalPrice)}</span></span>
                    <span class="me-2">Hiện tại: <span class="fw-bold text-success">${esc(price.currentPrice)}</span></span>
                    <span class="me-2">Giảm: <span class="text-danger">${esc(price.discountPrice)}</span></span>
                    <span class="me-2">(${esc(price.discountPercentage)}%)</span>
                </div>
                <hr>
                <h6>Chi tiết sản phẩm</h6>
                <div>Mã vạch: <span class="text-muted">${esc(detail.barcode)}</span></div>
                <div>Mô tả ngắn: <span class="text-muted">${esc(detail.shortDescription)}</span></div>
                <div>Số lượng: <span class="text-muted">${totalQuantity}</span></div>
                <div>Mô tả: ${descList}</div>
                <div>Hình ảnh: ${detailImgs}</div>
                <hr>
                <h6>Tuỳ chọn</h6>
                ${optionsHtml || '<div class="text-muted">Không có tuỳ chọn.</div>'}
                <hr>
                <h6>Quà tặng</h6>
                ${giftsHtml || '<div class="text-muted">Không có quà tặng.</div>'}
                <hr>
                <div class="text-muted small">Tạo lúc: ${createdDate} bởi ${createdBy}</div>
            </div>
        </div>
        `;
        $('#product-modal-content').html(html);
    }
});
</script>
</body>
</html>