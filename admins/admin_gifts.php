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
    <!-- Thay thế style inline bằng tham chiếu đến file CSS riêng -->
    <link rel="stylesheet" href="css/admin_gifts.css">
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
        <?php renderToasts('toast-container', 'bottom-0 end-0 p-3', 3500); ?>
        <!-- Phần HTML còn lại giữ nguyên -->
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
<!-- Thay thế script inline bằng tham chiếu đến file JavaScript riêng -->
<script src="js/admin_gifts.js"></script>
<?php initializeToasts(); ?>
</body>
</html>