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
        $alerts[] = ['type' => 'danger', 'msg' => 'Mã và tên quà tặng không được để trống.'];
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
            $alerts[] = ['type' => 'success', 'msg' => 'Thêm quà tặng thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $resAdd['message'] ?? 'Không thể thêm quà tặng.'];
        }
    }
}

// Handle update gift POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_gift'])) {
    $code = trim($_POST['edit_code'] ?? '');
    $name = trim($_POST['edit_name'] ?? '');
    $imageBase64 = '';

    // Handle image upload (optional)
    if (!empty($_FILES['edit_image']['tmp_name'])) {
        $imgData = file_get_contents($_FILES['edit_image']['tmp_name']);
        $imageBase64 = 'data:' . mime_content_type($_FILES['edit_image']['tmp_name']) . ';base64,' . base64_encode($imgData);
    }

    if ($code === '' || $name === '') {
        $alerts[] = ['type' => 'danger', 'msg' => 'Mã và tên quà tặng không được để trống.'];
    } else {
        $putData = [
            'code' => $code,
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
            $alerts[] = ['type' => 'success', 'msg' => 'Cập nhật quà tặng thành công.'];
        } else {
            $alerts[] = ['type' => 'danger', 'msg' => $resEdit['message'] ?? 'Không thể cập nhật quà tặng.'];
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
    $alerts[] = ['type' => 'danger', 'msg' => $res['message'] ?? 'Không thể tải danh sách quà tặng.'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Quà tặng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="container">
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Danh sách quà tặng</h4>
        <div>
            <button id="btnDeleteSelectedGifts" class="btn btn-danger" disabled>Xóa đã chọn</button>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addGiftModal">Thêm quà tặng</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>
                        <input type="checkbox" id="selectAllGifts">
                    </th>
                    <th>ID</th>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Hình ảnh</th>
                    <th>Thao tác</th>
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
                    <td>
                        <button 
                            class="btn btn-warning btn-sm editGiftBtn"
                            data-id="<?= htmlspecialchars($gift['id']) ?>"
                            data-code="<?= htmlspecialchars($gift['code']) ?>"
                            data-name="<?= htmlspecialchars($gift['name']) ?>"
                            data-image="<?= htmlspecialchars($gift['image']) ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editGiftModal"
                        >Sửa</button>
                        <button 
                            class="btn btn-danger btn-sm btn-delete-gift"
                            data-code="<?= htmlspecialchars($gift['code']) ?>"
                        >Xóa</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($gifts)): ?>
                <tr><td colspan="6" class="text-center">Không có quà tặng nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Gift Modal -->
<div class="modal fade" id="addGiftModal" tabindex="-1" aria-labelledby="addGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addGiftModalLabel">Thêm quà tặng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label for="code" class="form-label">Mã quà tặng</label>
            <input type="text" class="form-control" id="code" name="code" required>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Tên quà tặng</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Hình ảnh</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_gift" class="btn btn-success">Thêm</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Gift Modal -->
<div class="modal fade" id="editGiftModal" tabindex="-1" aria-labelledby="editGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editGiftModalLabel">Sửa quà tặng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_code" name="edit_code">
        <div class="mb-3">
            <label for="edit_code_display" class="form-label">Mã quà tặng</label>
            <input type="text" class="form-control" id="edit_code_display" disabled>
        </div>
        <div class="mb-3">
            <label for="edit_name" class="form-label">Tên quà tặng</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
        </div>
        <div class="mb-3">
            <label for="edit_image" class="form-label">Hình ảnh (chọn để thay đổi)</label>
            <input type="file" class="form-control" id="edit_image" name="edit_image" accept="image/*">
            <div class="mt-2">
                <img id="edit_image_preview" src="" alt="Gift Image" style="max-width:80px;max-height:80px;display:none;">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit_gift" class="btn btn-warning">Cập nhật</button>
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
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const image = this.getAttribute('data-image');
            document.getElementById('edit_code').value = code;
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
        if (!confirm('Bạn có chắc chắn muốn xóa các quà tặng đã chọn?')) return;
        deleteGiftsByCodes(codes);
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-gift').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Bạn có chắc chắn muốn xóa quà tặng này?')) return;
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
});
</script>
</body>
</html>