<?php
// Chỉ khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = '';

// --- Logout logic ---
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: manage_login.php');
    exit();
}

// --- Redirect if already logged in as admin ---
if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['Manager', 'Admin'])) {
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) {
            $error = 'Lỗi kết nối máy chủ.';
        } else {
            $respData = json_decode($response, true);
            if ($httpCode === 200 && isset($respData['success']) && $respData['success'] === true) {
                $role = $respData['data']['user']['role'] ?? null;
                if ($role && in_array($role, ['Manager', 'Admin'])) {
                    $_SESSION['token'] = $respData['data']['token'];
                    $_SESSION['user'] = $respData['data']['user'];
                    // Xử lý expiration: nếu là số thì giữ nguyên, nếu là string thì chuyển sang timestamp
                    $expiration = $respData['data']['expiration'] ?? null;
                    if ($expiration) {
                        if (is_numeric($expiration)) {
                            $_SESSION['expiration'] = $expiration;
                        } else {
                            // Nếu là string ISO hoặc định dạng khác, chuyển sang timestamp
                            $_SESSION['expiration'] = strtotime($expiration);
                        }
                    } else {
                        // Nếu không có expiration, đặt mặc định 1 giờ
                        $_SESSION['expiration'] = time() + 3600;
                    }
                    header('Location: admin_categories.php');
                    exit();
                } else {
                    session_unset();
                    session_destroy();
                    $error = 'Bạn không có quyền truy cập.';
                }
            } else {
                $error = $respData['message'] ?? 'Đăng nhập thất bại';
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
    <style>
        .logout-link {
            color: #fff;
            background: #dc3545;
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 12px;
            display: inline-block;
        }
        .logout-link:hover {
            background: #b52a2a;
            color: #fff;
        }
    </style>
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