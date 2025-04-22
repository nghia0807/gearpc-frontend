<?php
session_start();
$error = '';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: manage_login.php');
    exit();
}

if (isset($_SESSION['token'])) {
    header('Location: admin_categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $ch = curl_init('http://localhost:5000/api/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'username' => $username,
            'password' => $password
        ]));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            $error = 'Lỗi kết nối máy chủ.';
        } else {
            $res = json_decode($response, true);
            if (!empty($res['success']) && !empty($res['data']['token'])) {
                $role = $res['data']['user']['role'] ?? '';
                if ($role === 'Manager' || $role === 'Admin') {
                    $_SESSION['token'] = $res['data']['token'];
                    $_SESSION['role'] = $role;
                    header('Location: admin_categories.php');
                    exit();
                } else {
                    $error = 'Bạn không có quyền truy cập. Vui lòng liên hệ quản trị viên.';
                }
            } else {
                $error = $res['message'] ?? 'Đăng nhập thất bại';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card shadow" style="min-width:350px;">
        <div class="card-body">
            <h4 class="card-title mb-4 text-center">Đăng nhập Quản trị</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>