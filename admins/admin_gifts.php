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
            <h4 class="mb-0">Gift List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelectedGifts" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete Selected
                </button>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addGiftModal">
                    <i class="fa fa-plus"></i> Add Gift
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle shadow-sm">
                <thead class="table-light">
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAllGifts">
                        </th>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Image</th>
                        <th style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($gifts as $gift): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="gift-checkbox" data-code="<?= htmlspecialchars($gift['code']) ?>">
                        </td>
                        <td><?= htmlspecialchars($gift['id']) ?></td>
                        <td><?= htmlspecialchars($gift['code']) ?></td>
                        <td><?= htmlspecialchars($gift['name']) ?></td>
                        <td>
                            <?php if (!empty($gift['image'])): ?>
                                <img src="<?= htmlspecialchars($gift['image']) ?>" alt="Gift Image" style="max-width:80px;max-height:80px;">
                            <?php endif; ?>
                        </td>
                        <td class="gift-actions">
                            <button 
                                class="btn btn-warning btn-sm editGiftBtn"
                                data-id="<?= htmlspecialchars($gift['id']) ?>"
                                data-code="<?= htmlspecialchars($gift['code']) ?>"
                                data-name="<?= htmlspecialchars($gift['name']) ?>"
                                data-image="<?= htmlspecialchars($gift['image']) ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#editGiftModal"
                            ><i class="fa fa-pen-to-square"></i> Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($gifts)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No gifts found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Gift Modal -->
<div class="modal fade" id="addGiftModal" tabindex="-1" aria-labelledby="addGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-primary text-white rounded-top">
        <h5 class="modal-title" id="addGiftModalLabel">
          <i class="fa fa-plus me-2"></i>Add Gift
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3" style="background: #f8f9fb;">
        <div class="mb-3">
            <label for="code" class="form-label fw-semibold text-primary">Gift Code</label>
            <input type="text" class="form-control shadow-sm" id="code" name="code" required placeholder="Enter gift code">
        </div>
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold text-primary">Gift Name</label>
            <input type="text" class="form-control shadow-sm" id="name" name="name" required placeholder="Enter gift name">
        </div>
        <div class="mb-3">
            <label for="image" class="form-label fw-semibold text-primary">Image</label>
            <input type="file" class="form-control shadow-sm" id="image" name="image" accept="image/*">
        </div>
      </div>
      <div class="modal-footer bg-light rounded-bottom">
        <button type="submit" name="add_gift" class="btn btn-success px-4">
          <i class="fa fa-plus"></i> Add
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Gift Modal -->
<div class="modal fade" id="editGiftModal" tabindex="-1" aria-labelledby="editGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editGiftModalLabel">Edit Gift</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id" name="edit_id">
        <div class="mb-3">
            <label for="edit_code_display" class="form-label">Gift Code</label>
            <input type="text" class="form-control" id="edit_code_display" disabled>
        </div>
        <div class="mb-3">
            <label for="edit_name" class="form-label">Gift Name</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
        </div>
        <div class="mb-3">
            <label for="edit_image" class="form-label">Image (choose to change)</label>
            <input type="file" class="form-control" id="edit_image" name="edit_image" accept="image/*">
            <div class="mt-2">
                <img id="edit_image_preview" src="" alt="Gift Image" style="max-width:80px;max-height:80px;display:none;">
            </div>
        </div>
      </div>
      <div class="modal-footer">
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
        deleteGiftsByCodes(codes);
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-gift').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this gift?')) return;
            deleteGiftsByCodes([code]);
        });
    });

    function deleteGiftsByCodes(codes) {
        btnDeleteSelectedGifts.disabled = true;
        fetch('http://localhost:5000/api/gifts/delete', {
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
                throw new Error(data.message || 'Xóa quà tặng thất bại');
            }
            alert(data.message || 'Xóa quà tặng thành công!');
            setTimeout(() => { window.location.reload(); }, 1000);
        })
        .catch(err => {
            alert(err.message || 'Lỗi xóa quà tặng');
            btnDeleteSelectedGifts.disabled = false;
        });
    }

    // Show all toasts on page load
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    });
});
</script>
</body>
</html>