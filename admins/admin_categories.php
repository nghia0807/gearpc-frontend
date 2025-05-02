<?php
// --- Use admin_session for admin pages and enforce role check ---
session_name('admin_session');
session_start();

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

// Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $code = trim($_POST['delete_code'] ?? '');
    if ($code !== '') {
        $res = apiRequest('DELETE', "$apiBase/delete", $token, [$code]);
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
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa fa-plus"></i> Thêm danh mục</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['id']) ?></td>
                    <td><?= htmlspecialchars($cat['code']) ?></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm editBtn"
                                data-id="<?= htmlspecialchars($cat['id']) ?>"
                                data-name="<?= htmlspecialchars($cat['name']) ?>"
                                >Sửa</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận xóa?');">
                            <input type="hidden" name="delete_code" value="<?= htmlspecialchars($cat['code']) ?>">
                            <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="4" class="text-center">Không có danh mục nào.</td></tr>
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
        <button type="submit" name="edit_category" class="btn btn-primary">Lưu</button>
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
</script>
</body>
</html>