<?php
session_name('admin_session');
session_start();

if (!isset($_SESSION['token'])) {
    header('Location: manage_login.php');
    exit;
}
$token = $_SESSION['token'];

$apiUrl = 'http://localhost:5000/api/gifts/get?pageIndex=0&pageSize=10';
$alerts = [];

// Toast component
include '../components/toasts.php';

// Handle add gift POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gift'])) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $imageBase64 = '';

    // Handle image upload
    if (!empty($_FILES['image']['tmp_name'])) {
        $imgData = file_get_contents($_FILES['image']['tmp_name']);
        $imageBase64 = 'data:' . mime_content_type($_FILES['image']['tmp_name']) . ';base64,' . base64_encode($imgData);
    }

    if ($code === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Gift code and name cannot be empty.'];
    } else {
        $postData = [
            'code' => $code,
            'name' => $name,
            'imageBase64' => $imageBase64
        ];
        $ch = curl_init('http://localhost:5000/api/gifts/add');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $resAdd = $err ? ['success' => false, 'message' => $err] : json_decode($response, true);

        if (!empty($resAdd['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Gift added successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $resAdd['message'] ?? 'Unable to add gift.'];
        }
    }
}

// Handle update gift POST (by id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_gift'])) {
    $id = trim($_POST['edit_id'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    $imageBase64 = '';

    // Handle image upload (optional)
    if (!empty($_FILES['edit_image']['tmp_name'])) {
        $imgData = file_get_contents($_FILES['edit_image']['tmp_name']);
        $imageBase64 = 'data:' . mime_content_type($_FILES['edit_image']['tmp_name']) . ';base64,' . base64_encode($imgData);
    }

    if ($id === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Gift ID and name cannot be empty.'];
    } else {
        $putData = [
            'id' => $id,
            'name' => $name,
            'imageBase64' => $imageBase64
        ];
        $ch = curl_init('http://localhost:5000/api/gifts/update');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($putData));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $resEdit = $err ? ['success' => false, 'message' => $err] : json_decode($response, true);

        if (!empty($resEdit['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Gift updated successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $resEdit['message'] ?? 'Unable to update gift.'];
        }
    }
}

// Handle delete gift POST (single or multiple)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_gift'])) {
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
        $ch = curl_init('http://localhost:5000/api/gifts/delete');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($codes));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $resDelete = $err ? ['success' => false, 'message' => $err] : json_decode($response, true);

        if (!empty($resDelete['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Gift deleted successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $resDelete['message'] ?? 'Unable to delete gift.'];
        }
    }
}

function apiRequest($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'message' => $err];
    return json_decode($response, true);
}

$res = apiRequest($apiUrl, $token);
$gifts = [];
$totalCount = 0;
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $gifts = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to load gifts.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift Management</title>
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
        .gift-actions .btn {
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
        .gift-image-container {
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
        
        .gift-image-container img {
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
        .gift-code {
            font-weight: 600;
            color: #0d6efd;
        }
        
        .gift-name {
            font-weight: 500;
        }
        
        .gift-id {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(13, 110, 253, 0.2);
            border-top: 5px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="text-primary mb-0 fw-bold">Loading...</p>
        </div>
    </div>
    
    <div class="main-card">
        <?php renderToasts(null, 1080, 3500); ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Gift List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelectedGifts" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete
                </button>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addGiftModal">
                    <i class="fa fa-plus"></i> Add Gift
                </button>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle shadow-sm mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllGifts" class="custom-checkbox">
                            </th>
                            <th style="width: 60px;">ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="text-center" style="width: 120px;">Image</th>
                            <th style="width: 70px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($gifts as $gift): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="gift-checkbox custom-checkbox" data-code="<?= htmlspecialchars($gift['code']) ?>">
                            </td>
                            <td><span class="gift-id"><?= htmlspecialchars($gift['id']) ?></span></td>
                            <td><span class="gift-code"><?= htmlspecialchars($gift['code']) ?></span></td>
                            <td><span class="gift-name"><?= htmlspecialchars($gift['name']) ?></span></td>
                            <td class="text-center">
                                <?php if (!empty($gift['image'])): ?>
                                    <div class="gift-image-container">
                                        <img src="<?= htmlspecialchars($gift['image']) ?>" alt="Gift Image">
                                    </div>
                                <?php else: ?>
                                    <div class="gift-image-container">
                                        <i class="fa fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button 
                                        class="btn btn-sm action-btn edit editGiftBtn"
                                        data-id="<?= htmlspecialchars($gift['id']) ?>"
                                        data-code="<?= htmlspecialchars($gift['code']) ?>"
                                        data-name="<?= htmlspecialchars($gift['name']) ?>"
                                        data-image="<?= htmlspecialchars($gift['image']) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editGiftModal"
                                        title="Edit Gift"
                                        ><i class="fa fa-pen"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($gifts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-box fa-2x mb-2"></i>
                                    <p>No gifts found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalCount > 10): ?>
                <div class="card-footer bg-white text-center py-3">
                    <a href="?page=1" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-angles-down"></i> Load More
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Gift Modal -->
<div class="modal fade" id="addGiftModal" tabindex="-1" aria-labelledby="addGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-success bg-gradient text-white">
        <h5 class="modal-title" id="addGiftModalLabel">
          <i class="fa fa-plus me-2"></i>Add Gift
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <div class="mb-3">
            <label for="code" class="form-label fw-semibold text-success">Gift Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-sm" id="code" name="code" required placeholder="Enter gift code">
            <div class="form-text">Must be a unique identifier</div>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold text-success">Gift Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-sm" id="name" name="name" required placeholder="Enter gift name">
            <div class="form-text">Shown as gift title on all pages</div>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label fw-semibold text-success">Image</label>
            <input type="file" class="form-control shadow-sm" id="image" name="image" accept="image/*">
            <div class="form-text">Optional: Upload a gift image</div>
        </div>
      </div>
      <div class="modal-footer bg-light rounded-bottom">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="add_gift" class="btn btn-success px-4">
          <i class="fa fa-plus"></i> Add
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Gift Modal -->
<div class="modal fade" id="editGiftModal" tabindex="-1" aria-labelledby="editGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-warning bg-gradient text-dark">
        <h5 class="modal-title" id="editGiftModalLabel">
          <i class="fa-solid fa-edit me-2"></i>Edit Gift
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <input type="hidden" id="edit_id" name="edit_id">
        <div class="mb-3">
            <label for="edit_code_display" class="form-label fw-semibold text-warning">Gift Code</label>
            <input type="text" class="form-control" id="edit_code_display" disabled>
            <div class="form-text">Gift code cannot be changed</div>
        </div>
        <div class="mb-3">
            <label for="edit_name" class="form-label fw-semibold text-warning">Gift Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
            <div class="form-text">Update the gift display name</div>
        </div>
        <div class="mb-3">
            <label for="edit_image" class="form-label fw-semibold text-warning">Image</label>
            <input type="file" class="form-control" id="edit_image" name="edit_image" accept="image/*">
            <div class="form-text">Choose a new image to update (optional)</div>
            <div class="mt-2">
                <img id="edit_image_preview" src="" alt="Gift Image" style="max-width:80px;max-height:80px;display:none;">
            </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" name="edit_gift" class="btn btn-warning">
            <i class="fa fa-pen-to-square"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Loading overlay functionality
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    function showLoading() {
        loadingOverlay.classList.add('active');
    }
    
    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }

    // --- Edit modal logic ---
    document.querySelectorAll('.editGiftBtn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const image = this.getAttribute('data-image');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code_display').value = code;
            document.getElementById('edit_name').value = name;
            const imgPreview = document.getElementById('edit_image_preview');
            if (image) {
                imgPreview.src = image;
                imgPreview.style.display = '';
            } else {
                imgPreview.src = '';
                imgPreview.style.display = 'none';
            }
            // Clear file input
            document.getElementById('edit_image').value = '';
        });
    });

    // --- Gift selection logic ---
    const selectAllGifts = document.getElementById('selectAllGifts');
    const giftCheckboxes = document.querySelectorAll('.gift-checkbox');
    const btnDeleteSelectedGifts = document.getElementById('btnDeleteSelectedGifts');

    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(giftCheckboxes).some(cb => cb.checked);
        btnDeleteSelectedGifts.disabled = !anyChecked;
    }

    if (selectAllGifts) {
        selectAllGifts.addEventListener('change', function() {
            giftCheckboxes.forEach(cb => cb.checked = selectAllGifts.checked);
            updateDeleteSelectedBtn();
        });
    }
    giftCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAllGifts.checked) selectAllGifts.checked = false;
        });
    });

    btnDeleteSelectedGifts.addEventListener('click', function() {
        const codes = Array.from(giftCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-code'));
        if (codes.length === 0) return;
        if (!confirm('Are you sure you want to delete the selected gifts?')) return;
        
        // Show loading before submitting
        showLoading();
        
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
        input2.name = 'delete_gift';
        input2.value = '1';
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-gift').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this gift?')) return;
            
            // Show loading before submitting
            showLoading();
            
            // Submit via hidden form (POST)
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_code';
            input.value = code;
            form.appendChild(input);
            const input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'delete_gift';
            input2.value = '1';
            form.appendChild(input2);
            document.body.appendChild(form);
            form.submit();
        });
    });
    
    // Add form submission handlers for loading indicator
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            // Don't show loading for forms that aren't submitting to API
            if (this.getAttribute('data-no-loading') === 'true') return;
            showLoading();
        });
    });

    // Show all toasts on page load
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    });
    
    // Hide loading overlay when page is fully loaded
    window.addEventListener('load', hideLoading);
});
</script>
<?php initializeToasts(); ?>
</body>
</html>