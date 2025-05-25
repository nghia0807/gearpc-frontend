<?php
// --- Admin session setup ---
session_name('admin_session');
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$error = '';

// --- Handle logout ---
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: manage_login.php');
    exit;
}

// --- Handle login POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Call backend API for authentication
        $ch = curl_init('http://phpbe_app_service:5000/api/auth/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'username' => $username,
                'password' => $password
            ])
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            $error = 'Server connection error.';
        } else {
            $respData = json_decode($response, true);
            if ($httpCode === 200 && ($respData['success'] ?? false)) {
                $user = $respData['data']['user'] ?? [];
                $role = $user['role'] ?? null;
                if (in_array($role, ['Manager', 'Admin'], true)) {
                    // Set session variables for authorized admin
                    $_SESSION['token'] = $respData['data']['token'];
                    $_SESSION['user'] = $user;
                    $_SESSION['role'] = $role;
                    // Handle expiration (timestamp or string)
                    $expiration = $respData['data']['expiration'] ?? null;
                    $_SESSION['expiration'] = is_numeric($expiration)
                        ? $expiration
                        : ($expiration ? strtotime($expiration) : time() + 3600);
                    header('Location: admin_categories.php');
                    exit;
                }
                // Unauthorized role
                $_SESSION = [];
                session_destroy();
                $error = 'Bạn không có quyền truy cập.';
            } else {
                $error = $respData['message'] ?? 'Login failed';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
            <h4 class="card-title mb-4 text-center">Admin Login</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>