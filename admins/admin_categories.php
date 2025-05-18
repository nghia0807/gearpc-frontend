<?php
session_name('admin_session');
session_start();

// Check if token exists, otherwise redirect to login page
if (!isset($_SESSION['token'])) {
    header('Location: manage_login.php');
    exit;
}
$token = $_SESSION['token'];

$apiBase = 'http://localhost:5000/api/categories';
$pageIndex = isset($_GET['page']) ? intval($_GET['page']) : 0;
$pageSize = 10;
$alerts = [];
$categories = [];
$totalCount = 0;
$selectedCategories = [];
$showSelected = isset($_GET['show_selected']);

// Toast component
include '../components/toasts.php';

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

// Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($code === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Category code and name cannot be empty.'];
    } else {
        $res = apiRequest('POST', "$apiBase/add", $token, ['code' => $code, 'name' => $name]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Category added successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to add category.'];
        }
    }
}

// Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = trim($_POST['edit_id'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    if ($id === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Category name cannot be empty.'];
    } else {
        $res = apiRequest('PUT', "$apiBase/update", $token, ['id' => $id, 'name' => $name]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Category updated successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to update category.'];
        }
    }
}

// Delete Category (single or multiple)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
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
        // Fix: send as ['codes' => [...]]
        $res = apiRequest('DELETE', "$apiBase/delete", $token, $codes);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Category deleted successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to delete category.'];
        }
    }
}

// Fetch categories
$res = apiRequest('GET', "$apiBase/get?pageIndex=$pageIndex&pageSize=$pageSize", $token);
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $categories = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to load categories.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management</title>
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
        .category-code {
            font-weight: 600;
            color: #0d6efd;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-id {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .card {
            border: none;
            overflow: hidden;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <div class="main-card">
        <?php renderToasts(null, 1080, 3500); ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Category List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelectedCategories" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fa fa-plus"></i> Add Category
                </button>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllCategories" class="custom-checkbox">
                            </th>
                            <th style="width: 60px;">ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th style="width: 70px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="category-checkbox custom-checkbox" data-code="<?= htmlspecialchars($cat['code']) ?>">
                            </td>
                            <td><span class="category-id"><?= htmlspecialchars($cat['id']) ?></span></td>
                            <td><span class="category-code"><?= htmlspecialchars($cat['code']) ?></span></td>
                            <td><span class="category-name"><?= htmlspecialchars($cat['name']) ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm action-btn edit editBtn"
                                    data-id="<?= htmlspecialchars($cat['id']) ?>"
                                    data-name="<?= htmlspecialchars($cat['name']) ?>"
                                    ><i class="fa fa-pen"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-folder fa-2x mb-2"></i>
                                    <p>No categories found.</p>
                                </div>
                            </td>
                        </tr>
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
        <?php if ($showSelected): ?>
            <hr>
            <h5 class="mt-4 mb-3">Selected Categories</h5>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($selectedCategories as $cat): ?>
                            <tr>
                                <td><?= htmlspecialchars($cat['code']) ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($selectedCategories)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header bg-success bg-gradient text-white">
        <h5 class="modal-title" id="addModalLabel"><i class="fa-solid fa-plus-circle me-2"></i>Add Category</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <div class="mb-3">
            <label for="code" class="form-label fw-semibold text-success">Category Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="code" name="code" required>
            <div class="form-text">Must be unique identifier</div>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold text-success">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required>
            <div class="form-text">Shown as category title on all pages</div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="add_category" class="btn btn-success">
            <i class="fa fa-plus"></i> Add
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header bg-warning bg-gradient text-dark">
        <h5 class="modal-title" id="editModalLabel"><i class="fa-solid fa-edit me-2"></i>Edit Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <input type="hidden" id="edit_id" name="edit_id">
        <div class="mb-3">
            <label for="edit_name" class="form-label fw-semibold text-warning">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
            <div class="form-text">Update the category display name</div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="edit_category" class="btn btn-warning">
            <i class="fa fa-pen-to-square"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
});

// --- Multiple delete logic for categories ---
const selectAllCategories = document.getElementById('selectAllCategories');
const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
const btnDeleteSelectedCategories = document.getElementById('btnDeleteSelectedCategories');

function updateDeleteSelectedBtn() {
    const anyChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);
    btnDeleteSelectedCategories.disabled = !anyChecked;
}

if (selectAllCategories) {
    selectAllCategories.addEventListener('change', function() {
        categoryCheckboxes.forEach(cb => cb.checked = selectAllCategories.checked);
        updateDeleteSelectedBtn();
    });
}
categoryCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateDeleteSelectedBtn();
        if (!this.checked && selectAllCategories.checked) selectAllCategories.checked = false;
    });
});

btnDeleteSelectedCategories.addEventListener('click', function() {
    const codes = Array.from(categoryCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.getAttribute('data-code'));
    if (codes.length === 0) return;
    if (!confirm('Are you sure you want to delete the selected categories?')) return;
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
    input2.name = 'delete_category';
    input2.value = '1';
    form.appendChild(input2);
    document.body.appendChild(form);
    form.submit();
});
</script>
<?php initializeToasts(); ?>
</body>
</html>