<?php
// --- Use admin_session with cookie path /admin ---
session_name('admin_session');
session_set_cookie_params(['path' => '/admin']);
session_start();

// --- Check admin session and expiration ---
if (
    !isset($_SESSION['admin_token']) ||
    !isset($_SESSION['admin_user']) ||
    !in_array($_SESSION['admin_user']['role'], ['Manager', 'Admin']) ||
    !isset($_SESSION['admin_expiration']) ||
    strtotime($_SESSION['admin_expiration']) < time()
) {
    session_unset();
    session_destroy();
    setcookie('admin_session', '', time() - 3600, '/admin');
    header('Location: manage_login.php');
    exit();
}
$token = $_SESSION['admin_token'];
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
    <style>
        .modal-thumb {
            width: 100px;
            height: 100px;
            object-fit: cover;
            display: inline-block;
        }
        .admin-products .action-btn {
            margin-right: 4px;
        }
        .product-table th, .product-table td {
            vertical-align: middle !important;
        }
        .form-label.required:after {
            content: "*";
            color: red;
            margin-left: 2px;
        }
        .img-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #ddd;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container admin-products">
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?>
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
            <select class="form-select" name="categoriesCode[]" id="categoriesSelect" multiple required>
            </select>
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
        <h6>Biến thể sản phẩm</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Tên tuỳ chọn (optionTitle)</label>
            <input type="text" class="form-control" name="optionTitle" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Nhãn tuỳ chọn (optionLabel)</label>
            <input type="text" class="form-control" name="optionLabel" required>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Số lượng</label>
            <input type="number" class="form-control" name="quantity" min="1" required>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Giá gốc</label>
            <input type="number" class="form-control" name="originalPrice" min="0" step="0.01" required>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Giá hiện tại</label>
            <input type="number" class="form-control" name="currentPrice" min="0" step="0.01" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Barcode</label>
            <input type="text" class="form-control" name="barcode">
          </div>
          <div class="col-md-6">
            <label class="form-label">Mô tả ngắn</label>
            <input type="text" class="form-control" name="shortDescription">
          </div>
          <div class="col-md-6">
            <label class="form-label required">Tên mô tả</label>
            <input type="text" class="form-control" name="descName" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Nội dung mô tả</label>
            <input type="text" class="form-control" name="descText" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Ảnh chi tiết (imagesBase64)</label>
            <input type="file" class="form-control" name="detailImageBase64" id="detailImageInput" accept="image/*" required>
            <img id="detailImgPreview" class="img-preview mt-2 d-block" src="https://via.placeholder.com/80x80?text=No+Img" alt="Preview">
          </div>
          <div class="col-md-6">
            <label class="form-label">Piority (ảnh/mô tả)</label>
            <input type="number" class="form-control" name="piority" value="1" min="1">
          </div>
        </div>
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
const token = '<?= htmlspecialchars($_SESSION['admin_token']) ?>';

// Show modal
$('#addProductBtn').on('click', function() {
    $('#addProductForm')[0].reset();
    $('#mainImgPreview').attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    $('#detailImgPreview').attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    $('#addProductFormAlert').html('');
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
    // Categories
    fetch('http://localhost:5000/api/categories/get?pageIndex=0&pageSize=100', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
        let html = '<option value="">-- Chọn danh mục --</option>';
        if (data.success && data.data && data.data.data) {
            data.data.data.forEach(c => {
                html += `<option value="${c.code}">${c.name}</option>`;
            });
        }
        $('#categoriesSelect').html(html);
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

// Detail image preview & base64
let detailImageBase64 = '';
$('#detailImageInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            detailImageBase64 = evt.target.result.split(',')[1];
            $('#detailImgPreview').attr('src', evt.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        detailImageBase64 = '';
        $('#detailImgPreview').attr('src', 'https://via.placeholder.com/80x80?text=No+Img');
    }
});

// Add Product Form Submit
$('#addProductForm').on('submit', function(e) {
    e.preventDefault();
    $('#addProductFormAlert').html('');
    // Validate required fields
    const form = this;
    const name = form.name.value.trim();
    const code = form.code.value.trim();
    const brandCode = form.brandCode.value.trim();
    const categoriesCode = Array.from(form['categoriesCode[]'].selectedOptions).map(opt => opt.value);
    const status = form.status.value;
    const giftCodes = form.giftCodes.value.trim();
    const optionTitle = form.optionTitle.value.trim();
    const optionLabel = form.optionLabel.value.trim();
    const quantity = parseInt(form.quantity.value, 10);
    const originalPrice = parseFloat(form.originalPrice.value);
    const currentPrice = parseFloat(form.currentPrice.value);
    const barcode = form.barcode.value.trim();
    const shortDescription = form.shortDescription.value.trim();
    const descName = form.descName.value.trim();
    const descText = form.descText.value.trim();
    const piority = parseInt(form.piority.value, 10) || 1;

    if (!name || !code || !mainImageBase64 || !brandCode || !categoriesCode.length || !optionTitle || !optionLabel || !quantity || !currentPrice || !descName || !descText || !detailImageBase64) {
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
        variants: [{
            optionTitle,
            options: [{
                optionLabel,
                quantity,
                originalPrice,
                currentPrice,
                barcode,
                descriptions: [{
                    name: descName,
                    descriptionText: descText,
                    piority
                }],
                imagesBase64: [{
                    base64Content: detailImageBase64,
                    piority
                }],
                shortDescription
            }]
        }]
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
                'Authorization': 'Bearer <?= htmlspecialchars($_SESSION['admin_token']) ?>',
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
            ? '<img src="' + esc(info.imageUrl) + '" class="modal-thumb mb-2" alt="Product Image" onerror="this.src=\'https://via.placeholder.com/100x100?text=No+Img\';">'
            : '<img src="https://via.placeholder.com/100x100?text=No+Img" class="modal-thumb mb-2" alt="No Image">';
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
                        return esc(o.label) + ' (SL: ' + esc(o.quantity) + (o.selected ? ', Được chọn' : '') + ')';
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