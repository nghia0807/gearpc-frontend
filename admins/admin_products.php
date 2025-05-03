<?php
// admin_products.php
// Admin interface for managing products

session_name('admin_session');
session_start();

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
        $alerts[] = ['type' => 'danger', 'msg' => 'Không thể kết nối đến API.'];
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!$data || !$data['success'] || $httpCode !== 200) {
        $alerts[] = ['type' => 'danger', 'msg' => isset($data['message']) ? $data['message'] : 'Không thể tải sản phẩm, vui lòng thử lại'];
        return [];
    }
    $totalCount = $data['data']['totalCount'];
    return $data['data']['data'];
}

// --- Fetch products for current page ---
$products = fetchProducts($apiBaseUrl, $token, $pageIndex, $pageSize, $alerts, $totalCount);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sản phẩm</title>
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
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Danh sách sản phẩm</h4>
        <div>
            <button id="btnDeleteSelected" class="btn btn-danger" disabled>
                <i class="fa fa-trash"></i> Xóa đã chọn
            </button>
            <button class="btn btn-success" disabled>
                <i class="fa fa-plus"></i> Thêm sản phẩm
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
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Hình ảnh</th>
                    <th>Thương hiệu</th>
                    <th>Giá hiện tại</th>
                    <th>Giá gốc</th>
                    <th>Mô tả ngắn</th>
                    <th>Hành động</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">Không có sản phẩm nào.</td>
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
                                    <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Ảnh sản phẩm" class="product-img-thumb">
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
                                    <i class="fa-solid fa-eye"></i> Xem
                                </button>
                                <button class="btn btn-sm btn-warning" disabled>
                                    <i class="fa-solid fa-pen-to-square"></i> Sửa
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-product" data-code="<?= htmlspecialchars($product['code']) ?>">
                                    <i class="fa-solid fa-trash"></i> Xóa
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
                    <i class="fa-solid fa-angles-down"></i> Tải thêm
                </a>
            </div>
        <?php endif; ?>
    </div>
    <!-- Alert for JS actions -->
    <div id="jsAlertContainer" style="margin-top:16px;"></div>
</div>

<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewProductModalLabel">Chi tiết sản phẩm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div id="viewProductModalContent" class="text-muted">
          Đang tải thông tin sản phẩm...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
        if (!confirm('Bạn có chắc chắn muốn xóa các sản phẩm đã chọn?')) return;
        deleteProductsByCodes(codes);
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) return;
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
});

function showProductDetail(productId) {
    const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    const contentDiv = document.getElementById('viewProductModalContent');
    contentDiv.innerHTML = '<div class="text-muted">Đang tải thông tin sản phẩm...</div>';
    modal.show();

    fetch('get_product_detail.php?id=' + encodeURIComponent(productId), {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(async response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return await response.json();
    })
    .then(data => {
        if (!data.success || !data.data) {
            contentDiv.innerHTML = '<div class="alert alert-danger">Không thể tải thông tin sản phẩm.</div>';
            return;
        }
        contentDiv.innerHTML = renderProductDetail(data.data);
    })
    .catch(err => {
        contentDiv.innerHTML = '<div class="alert alert-danger">Lỗi kết nối server, vui lòng thử lại.</div>';
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

    let imgThumb = info.imageUrl ? `<img src="${esc(info.imageUrl)}" class="modal-product-thumb me-2 mb-2" alt="Ảnh sản phẩm">` :
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
        descList = '<div class="mb-2"><span class="modal-product-label">Mô tả:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
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
        giftList = '<div class="text-muted">Không có quà tặng</div>';
    } else {
        giftList = '<ul class="list-group mb-2">';
        gifts.forEach((g, idx) => {
            giftList += `<li class="list-group-item">${esc(JSON.stringify(g))}</li>`;
        });
        giftList += '</ul>';
    }

    let priceHtml = `<div>
        <span class="modal-product-label">Giá gốc:</span> ${formatPrice(price.originalPrice)}₫<br>
        <span class="modal-product-label">Giá hiện tại:</span> ${formatPrice(price.currentPrice)}₫<br>
        <span class="modal-product-label">Giá khuyến mãi:</span> ${formatPrice(price.discountPrice)}₫<br>
        <span class="modal-product-label">Giảm giá:</span> ${price.discountPercentage ? esc(price.discountPercentage + '%') : '0%'}
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
                <span class="modal-product-label">Mã SP:</span> ${esc(info.code)}<br>
                <span class="modal-product-label">Tên:</span> ${esc(info.name)}<br>
                <span class="modal-product-label">Trạng thái:</span> ${esc(info.status)}<br>
                <span class="modal-product-label">Danh mục:</span> ${categories}<br>
                <span class="modal-product-label">Thương hiệu:</span> ${esc(info.brand)}
            </div>
            <div class="mb-2">${priceHtml}</div>
            <div class="mb-2">
                <span class="modal-product-label">Mô tả ngắn:</span> ${esc(detail.shortDescription)}
            </div>
            ${descList}
            ${optionList}
            <div class="mb-2">
                <span class="modal-product-label">Quà tặng:</span><br>
                ${giftList}
            </div>
            <div class="modal-product-meta">
                <span class="modal-product-label">Ngày tạo:</span> ${createdDate}<br>
                <span class="modal-product-label">Người tạo:</span> ${createdBy}
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
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
</script>
</body>
</html>
