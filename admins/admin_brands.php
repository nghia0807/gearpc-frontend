<?php
session_name('admin_session');
session_start();

// Check if token exists, otherwise redirect to login page
if (!isset($_SESSION['token'])) {
    header('Location: manage_login.php');
    exit;
}

$token = $_SESSION['token'];
$apiBase = 'http://localhost:5000/api/brands';
$pageIndex = isset($_GET['page']) ? intval($_GET['page']) : 0;
$pageSize = 10;
$alerts = [];
$brands = [];
$totalCount = 0;

// Include toast component
include '../components/toasts.php';

// Helper: cURL request
function apiRequest($method, $url, $token, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'message' => $err];
    return json_decode($response, true);
}

// Add Brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $imageBase64 = trim($_POST['imageBase64'] ?? '');
    if ($code === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Mã và tên thương hiệu không được để trống.'];
    } else {
        $res = apiRequest('POST', "$apiBase/add", $token, [
            'code' => $code,
            'name' => $name,
            'imageBase64' => $imageBase64
        ]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Thêm thương hiệu thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể thêm thương hiệu.'];
        }
    }
}

// Edit Brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_brand'])) {
    $id = trim($_POST['edit_id'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    $imageBase64 = trim($_POST['edit_imageBase64'] ?? '');
    if ($id === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Tên thương hiệu không được để trống.'];
    } else {
        $res = apiRequest('PUT', "$apiBase/update", $token, [
            'id' => $id,
            'name' => $name,
            'imageBase64' => $imageBase64
        ]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Cập nhật thương hiệu thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể cập nhật thương hiệu.'];
        }
    }
}

// Delete Brand (single or multiple)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_brand'])) {
    $codes = [];
    if (!empty($_POST['delete_code'])) {
        // Single delete
        $codes[] = trim($_POST['delete_code']);
    } elseif (!empty($_POST['delete_codes'])) {
        // Multiple delete (from JS)
        $codes = json_decode($_POST['delete_codes'], true);
        if (!is_array($codes)) $codes = [];
    }
    if (!empty($codes)) {
        $res = apiRequest('DELETE', "$apiBase/delete", $token, $codes);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Xóa thương hiệu thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể xóa thương hiệu.'];
        }
    }
}

// Fetch brands
$res = apiRequest('GET', "$apiBase/get?pageIndex=$pageIndex&pageSize=$pageSize", $token);
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $brands = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể tải thương hiệu.'];
}

// Helper: image placeholder
function brandImage($img) {
    if (!$img) {
        return '<img src="https://via.placeholder.com/60x60?text=No+Image" class="img-thumbnail" width="60" height="60">';
    }
    if (filter_var($img, FILTER_VALIDATE_URL)) {
        return '<img src="' . htmlspecialchars($img) . '" class="img-thumbnail" width="60" height="60">';
    }
    if (preg_match('/^data:image\/[a-z]+;base64,/', $img)) {
        return '<img src="' . htmlspecialchars($img) . '" class="img-thumbnail" width="60" height="60">';
    }
    if (strlen($img) > 100) {
        return '<img src="data:image/png;base64,' . htmlspecialchars($img) . '" class="img-thumbnail" width="60" height="60">';
    }
    return '<img src="https://via.placeholder.com/60x60?text=No+Image" class="img-thumbnail" width="60" height="60">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brand Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .brand-actions .btn {
            margin-right: 0.25rem;
        }
        @media (max-width: 768px) {
            .main-card {
                padding: 1rem 0.5rem;
            }
        }
        
        /* New table styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Custom checkbox styling */
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0d6efd;
        }
        
        /* Image container */
        .brand-image-container {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e3e6ea;
            width: 64px;
            height: 64px;
            margin: 0 auto;
        }
        
        .brand-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            border-radius: 6px;
            padding: 6px 12px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .action-btn i {
            font-size: 14px;
        }
        
        .action-btn.edit {
            background-color: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
            color: #b08900;
        }
        
        .action-btn.edit:hover {
            background-color: #ffc107;
            color: #212529;
        }
        
        .action-btn.delete {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .action-btn.delete:hover {
            background-color: #dc3545;
            color: #fff;
        }
        
        /* Text styling */
        .brand-code {
            font-weight: 600;
            color: #0d6efd;
        }
        
        .brand-name {
            font-weight: 500;
        }
        
        .brand-id {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <div class="main-card">
        <?php renderToasts(null, 1080, 3500); ?>
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Brand List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelectedBrands" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fa fa-plus"></i> Add Brand
                </button>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle shadow-sm mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllBrands" class="custom-checkbox">
                            </th>
                            <th style="width: 60px;">ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="text-center" style="width: 120px;">Image</th>
                            <th style="width: 70px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="brand-checkbox custom-checkbox" data-code="<?= htmlspecialchars($brand['code']) ?>">
                            </td>
                            <td><span class="brand-id"><?= htmlspecialchars($brand['id']) ?></span></td>
                            <td><span class="brand-code"><?= htmlspecialchars($brand['code']) ?></span></td>
                            <td><span class="brand-name"><?= htmlspecialchars($brand['name']) ?></span></td>
                            <td class="text-center">
                                <div class="brand-image-container">
                                    <?php if (!empty($brand['image'])): ?>
                                        <img src="<?= htmlspecialchars($brand['image']) ?>" alt="Brand Image">
                                    <?php else: ?>
                                        <i class="fa fa-image text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <button 
                                    class="btn btn-sm action-btn edit editBtn"
                                    data-id="<?= htmlspecialchars($brand['id']) ?>"
                                    data-code="<?= htmlspecialchars($brand['code']) ?>"
                                    data-name="<?= htmlspecialchars($brand['name']) ?>"
                                    data-image="<?= htmlspecialchars($brand['image']) ?>"
                                    ><i class="fa fa-pen"></i> Edit
                            </button>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-building fa-2x mb-2"></i>
                                    <p>No brands found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalCount > ($pageIndex + 1) * $pageSize): ?>
                <div class="card-footer bg-white text-center py-3">
                    <a href="?page=<?= $pageIndex + 1 ?>" class="btn btn-outline-secondary">
                        <i class="fa fa-angle-double-down"></i> Load More
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" enctype="multipart/form-data" id="addBrandForm">
      <div class="modal-header bg-success bg-gradient text-white">
        <h5 class="modal-title" id="addModalLabel">
          <i class="fa fa-plus me-2"></i>Add Brand
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <div class="mb-3">
            <label for="code" class="form-label fw-semibold text-success">Brand Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-sm" id="code" name="code" required placeholder="Enter brand code">
            <div class="form-text">Must be a unique identifier</div>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold text-success">Brand Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-sm" id="name" name="name" required placeholder="Enter brand name">
            <div class="form-text">Shown as brand title on all pages</div>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label fw-semibold text-success">Image</label>
            <input type="file" class="form-control shadow-sm" id="image" accept="image/*">
            <input type="hidden" name="imageBase64" id="imageBase64">
            <div class="form-text">Optional: Upload a brand logo</div>
            <div class="mt-2">
                <img id="imgPreview" src="https://via.placeholder.com/80x80?text=No+Image" class="img-thumbnail" width="80" height="80">
            </div>
        </div>
      </div>
      <div class="modal-footer bg-light rounded-bottom">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="add_brand" class="btn btn-success px-4">
          <i class="fa fa-plus"></i> Add
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" enctype="multipart/form-data" id="editBrandForm">
      <div class="modal-header bg-warning bg-gradient text-dark">
        <h5 class="modal-title" id="editModalLabel">
          <i class="fa-solid fa-edit me-2"></i>Edit Brand
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <input type="hidden" id="edit_id" name="edit_id">
        <div class="mb-3">
            <label for="edit_code" class="form-label fw-semibold text-warning">Brand Code</label>
            <input type="text" class="form-control" id="edit_code" name="edit_code" readonly>
            <div class="form-text">Brand code cannot be changed</div>
        </div>
        <div class="mb-3">
            <label for="edit_name" class="form-label fw-semibold text-warning">Brand Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
            <div class="form-text">Update the brand display name</div>
        </div>
        <div class="mb-3">
            <label for="edit_image" class="form-label fw-semibold text-warning">Image</label>
            <input type="file" class="form-control" id="edit_image" accept="image/*">
            <input type="hidden" name="edit_imageBase64" id="edit_imageBase64">
            <div class="form-text">Choose a new image to update (optional)</div>
            <div class="mt-2">
                <img id="editImgPreview" src="https://via.placeholder.com/80x80?text=No+Image" class="img-thumbnail" width="80" height="80">
            </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="edit_brand" class="btn btn-warning">
            <i class="fa fa-pen-to-square"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add Brand: convert image to base64
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imgPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imageBase64').value = evt.target.result.split(',')[1];
            preview.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imageBase64').value = '';
        preview.src = 'https://via.placeholder.com/80x80?text=No+Image';
    }
});

// Edit Brand: fill modal with data
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_code').value = this.dataset.code;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_imageBase64').value = '';
        var preview = document.getElementById('editImgPreview');
        var image = this.dataset.image;
        if (image && image.length > 0) {
            if (image.startsWith('http') || image.startsWith('data:image')) {
                preview.src = image;
            } else {
                preview.src = 'data:image/png;base64,' + image;
            }
        } else {
            preview.src = 'https://via.placeholder.com/80x80?text=No+Image';
        }
        document.getElementById('edit_image').value = '';
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
});

// Edit Brand: convert image to base64
document.getElementById('edit_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('editImgPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('edit_imageBase64').value = evt.target.result.split(',')[1];
            preview.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// --- Multiple delete logic for brands ---
const selectAllBrands = document.getElementById('selectAllBrands');
const brandCheckboxes = document.querySelectorAll('.brand-checkbox');
const btnDeleteSelectedBrands = document.getElementById('btnDeleteSelectedBrands');

function updateDeleteSelectedBrandBtn() {
    const anyChecked = Array.from(brandCheckboxes).some(cb => cb.checked);
    btnDeleteSelectedBrands.disabled = !anyChecked;
}

if (selectAllBrands) {
    selectAllBrands.addEventListener('change', function() {
        brandCheckboxes.forEach(cb => cb.checked = selectAllBrands.checked);
        updateDeleteSelectedBrandBtn();
    });
}
brandCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateDeleteSelectedBrandBtn();
        if (!this.checked && selectAllBrands.checked) selectAllBrands.checked = false;
    });
});

btnDeleteSelectedBrands.addEventListener('click', function() {
    const codes = Array.from(brandCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.getAttribute('data-code'));
    if (codes.length === 0) return;
    if (!confirm('Are you sure you want to delete the selected brands?')) return;
    // Submit via hidden form (POST)
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'delete_codes';
    input.value = JSON.stringify(codes);
    form.appendChild(input);
    const input2 = document.createElement('input');
    input2.type = 'hidden';
    input2.name = 'delete_brand';
    input2.value = '1';
    form.appendChild(input2);
    document.body.appendChild(form);
    form.submit();
});

// Remove the original toast initialization code since we're now using the component's version
</script>
<?php initializeToasts(); ?>
</body>
</html>