<?php
session_name('admin_session');
session_start();

$api = getenv('API_URL');

// Check if token exists, otherwise redirect to login page
if (!isset($_SESSION['token'])) {
    header('Location: manage_login.php');
    exit;
}
$token = $_SESSION['token'];

$apiBase = $api . '/api/categories';
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

// Calculate total pages for pagination
$totalPages = ceil($totalCount / $pageSize);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Thay thế style inline bằng tham chiếu đến file CSS riêng -->
    <link rel="stylesheet" href="css/admin_categories.css">
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
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Category List</h4>
            <div class="d-flex gap-2">
                <button id="btnDeleteSelectedCategories" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Delete
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fa fa-plus"></i> Add Category
                </button>
            </div>
        </div>
    </div>
    
    <div class="main-card">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllCategories" class="custom-checkbox">
                            </th>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th style="width: 60px;">ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th style="width: 70px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-folder fa-2x mb-2"></i>
                                    <p>No categories found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="category-checkbox custom-checkbox" data-code="<?= htmlspecialchars($cat['code']) ?>">
                                </td>
                                <td class="text-center"><?= $pageIndex * $pageSize + $index + 1 ?></td>
                                <td><span class="category-id"><?= htmlspecialchars($cat['id']) ?></span></td>
                                <td><span class="category-code"><?= htmlspecialchars($cat['code']) ?></span></td>
                                <td><span class="category-name"><?= htmlspecialchars($cat['name']) ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm action-btn edit editBtn"
                                        data-id="<?= htmlspecialchars($cat['id']) ?>"
                                        data-name="<?= htmlspecialchars($cat['name']) ?>"
                                        ><i class="fa fa-pen"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Replace "Load More" with pagination -->
            <?php if ($totalCount > 0): ?>
                <div class="card-footer bg-white py-3">
                    <nav aria-label="Category pagination" class="d-flex justify-content-between align-items-center flex-wrap gap-3">
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
<script src="js/admin_categories.js"></script>
<?php initializeToasts(); ?>
</body>
</html>