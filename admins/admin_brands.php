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
        $alerts[] = ['type' => 'danger', 'msg' => 'Brand code and name cannot be empty.'];
    } else {
        $res = apiRequest('POST', "$apiBase/add", $token, [
            'code' => $code,
            'name' => $name,
            'imageBase64' => $imageBase64
        ]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Brand added successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to add brand.'];
        }
    }
}

// Edit Brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_brand'])) {
    $id = trim($_POST['edit_id'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    $imageBase64 = trim($_POST['edit_imageBase64'] ?? '');
    if ($id === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Brand name cannot be empty.'];
    } else {
        $res = apiRequest('PUT', "$apiBase/update", $token, [
            'id' => $id,
            'name' => $name,
            'imageBase64' => $imageBase64
        ]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Brand updated successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to update brand.'];
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
            $alerts[] = ['type' => 'success', 'msg' => 'Brand deleted successfully.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to delete brand.'];
        }
    }
}

// Fetch brands
$res = apiRequest('GET', "$apiBase/get?pageIndex=$pageIndex&pageSize=$pageSize", $token);
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $brands = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Unable to load brands.'];
}

// Calculate total pages for pagination
$totalPages = ceil($totalCount / $pageSize);

// Helper: image placeholder
function brandImage($img): string
{
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
    <link rel="stylesheet" href="css/admin_brands.css">
    <style>
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #fff;
            padding: 15px 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: padding 0.3s, box-shadow 0.3s;
            border-radius: 10px;
        }
        
        .sticky-header.is-sticky {
            padding: 10px 0;
        }
        
        @media (max-width: 767.98px) {
            .sticky-header .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .sticky-header h4 {
                margin-bottom: 10px !important;
            }
        }
        
        /* Add some padding to top of content to prevent sudden jump */
        .main-card {
            padding-top: 10px;
        }
    </style>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container position-relative">
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="text-primary mb-0 fw-bold">Loading...</p>
        </div>
    </div>

    <!-- Toast container positioned absolutely -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <?php renderToasts('toast-container', 0, 3500); ?>
    </div>

    <!-- New sticky header div -->
    <div class="sticky-header mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
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
    </div>
    
    <div class="main-card">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle shadow-sm mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllBrands" class="custom-checkbox">
                            </th>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th style="width: 60px;">ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="text-center" style="width: 120px;">Image</th>
                            <th style="width: 70px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-building fa-2x mb-2"></i>
                                    <p>No brands found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($brands as $index => $brand): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="brand-checkbox custom-checkbox" data-code="<?= htmlspecialchars($brand['code']) ?>">
                                </td>
                                <td class="text-center"><?= $pageIndex * $pageSize + $index + 1 ?></td>
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
                                        ><i class="fa fa-pen"></i>
                                </button>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Replace "Load More" button with pagination -->
            <?php if ($totalCount > 0): ?>
                <div class="card-footer bg-white py-3">
                    <nav aria-label="Brand pagination" class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <ul class="pagination mb-0">
                            <!-- First page button -->
                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=0" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Previous page button -->
                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= max(0, $pageIndex - 1) ?>" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php 
                            $startPage = max(0, min($pageIndex - 2, $totalPages - 5));
                            $endPage = min($startPage + 4, $totalPages - 1);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(0, $endPage - 4);
                            }
                            ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i == $pageIndex) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i + 1 ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next page button -->
                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= min($totalPages - 1, $pageIndex + 1) ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            
                            <!-- Last page button -->
                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages - 1 ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>

                        <!-- Page jump form -->
                        <form class="d-flex align-items-center gap-2" onsubmit="return window.jumpToPage(event)">
                            <div class="input-group" style="width: auto;">
                                <input type="number" class="form-control" id="jumpToPage" 
                                    min="1" max="<?= $totalPages ?>" 
                                    placeholder="Page..." 
                                    style="width: 80px;">
                                <button class="btn btn-outline-secondary" type="submit">Go</button>
                            </div>
                            <span class="text-muted">of <?= $totalPages ?></span>
                        </form>
                    </nav>
                </div>
                
                <!-- Define the jumpToPage function globally -->
                <script>
                    // Make sure jumpToPage is defined in the global scope
                    window.jumpToPage = function(e) {
                        e.preventDefault();
                        const input = document.getElementById('jumpToPage');
                        const page = parseInt(input.value) - 1;
                        const maxPage = <?= $totalPages - 1 ?>;
                        
                        if (isNaN(page) || page < 0 || page > maxPage) {
                            alert(`Please enter a valid page number between 1 and ${maxPage + 1}`);
                            return false;
                        }
                        
                        window.location.href = `?page=${page}`;
                        return false;
                    };
                </script>
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
<!-- Thay thế script inline bằng tham chiếu đến file JavaScript riêng -->
<script src="js/admin_brands.js"></script>
<?php initializeToasts(); ?>
</body>
</html>