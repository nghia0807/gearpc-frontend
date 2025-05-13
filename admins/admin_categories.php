<?php
session_name('admin_session');
session_start();

// Kiểm tra token tồn tại, nếu không thì chuyển hướng về trang đăng nhập
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
        $alerts[] = ['type' => 'danger', 'msg' => 'Mã và tên danh mục không được để trống.'];
    } else {
        $res = apiRequest('POST', "$apiBase/add", $token, ['code' => $code, 'name' => $name]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Thêm danh mục thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể thêm danh mục.'];
        }
    }
}

// Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = trim($_POST['edit_id'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    if ($id === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Tên danh mục không được để trống.'];
    } else {
        $res = apiRequest('PUT', "$apiBase/update", $token, ['id' => $id, 'name' => $name]);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Cập nhật danh mục thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể cập nhật danh mục.'];
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
        $res = apiRequest('DELETE', "$apiBase/delete", $token, $codes);
        if (!empty($res['success'])) {
            $alerts[] = ['type' => 'success', 'msg' => 'Xóa danh mục thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể xóa danh mục.'];
        }
    }
}

// Fetch categories
$res = apiRequest('GET', "$apiBase/get?pageIndex=$pageIndex&pageSize=$pageSize", $token);
if (!empty($res['success']) && !empty($res['data']['data'])) {
    $categories = $res['data']['data'];
    $totalCount = $res['data']['totalCount'];
} else {
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể tải danh mục.'];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Danh sách danh mục</h4>
        <div>
            <button id="btnDeleteSelectedCategories" class="btn btn-danger" disabled>
                <i class="fa fa-trash"></i> Xóa đã chọn
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa fa-plus"></i> Thêm danh mục
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>
                        <input type="checkbox" id="selectAllCategories">
                    </th>
                    <th>ID</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th style="width: 90px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="category-checkbox" data-code="<?= htmlspecialchars($cat['code']) ?>">
                    </td>
                    <td><?= htmlspecialchars($cat['id']) ?></td>
                    <td><?= htmlspecialchars($cat['code']) ?></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm editBtn"
                                data-id="<?= htmlspecialchars($cat['id']) ?>"
                                data-name="<?= htmlspecialchars($cat['name']) ?>"
                        ><i class="fa fa-pen-to-square"></i> Sửa</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="5" class="text-center">Không có danh mục nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalCount > ($pageIndex + 1) * $pageSize): ?>
        <a href="?page=<?= $pageIndex + 1 ?>" class="btn btn-outline-secondary">Tải thêm</a>
    <?php endif; ?>
    <?php if ($showSelected): ?>
        <hr>
        <h5>Danh mục rút gọn</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th>
                        <th>Tên</th>
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
                    <tr><td colspan="2" class="text-center">Không có dữ liệu.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Thêm danh mục</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label for="code" class="form-label">Mã danh mục</label>
            <input type="text" class="form-control" id="code" name="code" required>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Tên danh mục</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_category" class="btn btn-success">Thêm</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Sửa danh mục</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id" name="edit_id">
        <div class="mb-3">
            <label for="edit_name" class="form-label">Tên danh mục</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit_category" class="btn btn-warning">
            <i class="fa fa-pen-to-square"></i> Lưu
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
    if (!confirm('Bạn có chắc chắn muốn xóa các danh mục đã chọn?')) return;
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
</body>
</html>