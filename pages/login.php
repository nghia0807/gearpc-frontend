<?php
// Use a dedicated session for user authentication
session_name('user_session');
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$alert = '';
$alertType = '';
$errors = ['username' => '', 'password' => ''];

// Sanitize input to prevent XSS
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');

    // Server-side validation
    $isValid = true;
    if (!$username) {
        $errors['username'] = 'Username is required.';
        $isValid = false;
    } elseif (strlen($username) < 8) {
        $errors['username'] = 'Username must be at least 8 characters.';
        $isValid = false;
    }
    if (!$password) {
        $errors['password'] = 'Password is required.';
        $isValid = false;
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
        $isValid = false;
    }

    // If valid, call the authentication API
    if ($isValid) {
        $apiUrl = 'http://localhost:5000/api/auth/login';
        $postData = json_encode(['username' => $username, 'password' => $password]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => 10
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $alert = 'Cannot connect to server. Please try again later.';
            $alertType = 'danger';
        } else {
            $respData = json_decode($response, true);
            if ($httpCode === 200 && !empty($respData['success'])) {
                $role = $respData['data']['user']['role'] ?? null;
                if ($role) {
                    // Store session data
                    $_SESSION['token'] = $respData['data']['token'];
                    $_SESSION['user'] = $respData['data']['user'];
                    $_SESSION['role'] = $role;
                    $_SESSION['expiration'] = $respData['data']['expiration'];
                    if ($role === 'Admin' || $role === 'Manager') {
                        $alert = 'Admin and Manager accounts are not allowed to log in from this page.';
                        $alertType = 'danger';
                        $_SESSION = [];
                        session_destroy();
                    } else {
                        $alert = 'Login successful! Redirecting...';
                        $alertType = 'success';
                        echo "<script>setTimeout(function(){ window.location.href = 'home.php'; }, 500);</script>";
                    }
                } else {
                    $alert = 'Invalid or unauthorized role. Access denied.';
                    $alertType = 'danger';
                }
            } else {
                $alert = $respData['message'] ?? 'Username or password is incorrect.';
                $alertType = 'danger';
            }
        }
    } else {
        $alert = 'Please fix the errors below.';
        $alertType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { background-color: #121212; color: #e3e3e3; }
    input:-webkit-autofill {
      background-color: #121212 !important;
      color: #fff !important;
      -webkit-text-fill-color: #fff !important;
      -webkit-box-shadow: 0 0 0 1000px #121212 inset !important;
      transition: background-color 9999s, color 9999s;
    }
    .form-control {
      width: 304px; height: 44px;
      background-color: #121212;
      border-color: #555;
      color: #fff !important;
    }
    .form-control.is-invalid {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
    }
    .invalid-feedback {
      color: #dc3545; font-size: 0.95em;
      text-align: left; width: 304px;
      margin-top: 0.25rem; margin-bottom: 0.5rem;
    }
    .blue-text { color: #e3e3e3 !important; font-weight: bold !important; }
    .logout-link {
      color: #fff; background: #dc3545;
      padding: 4px 12px; border-radius: 4px;
      text-decoration: none; margin-top: 12px; display: inline-block;
    }
    .logout-link:hover { background: #b52a2a; color: #fff; }
  </style>
</head>
<body>
  <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 text-center">
    <img src="../assets/img/logo.png" alt="Logo" class="mb-4" />
    <h5 class="mb-4 fw-bold">Sign In</h5>
    <!-- Alert for API or validation messages -->
    <?php if ($alert): ?>
      <div class="alert alert-<?= htmlspecialchars($alertType) ?> w-100 mb-4" style="max-width:340px;margin:0 auto;">
        <?= htmlspecialchars($alert) ?>
      </div>
    <?php endif; ?>
    <form method="post" id="loginForm" novalidate>
      <div class="mb-3 floating-group">
        <input type="text" class="form-control floating-input <?= $errors['username'] ? 'is-invalid' : '' ?>" id="username" name="username" placeholder=" " required minlength="8" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" />
        <label for="username">Username</label>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
      </div>
      <div class="mb-3 floating-group">
        <input type="password" class="form-control floating-input <?= $errors['password'] ? 'is-invalid' : '' ?>" id="password" name="password" placeholder=" " required minlength="8" />
        <label for="password">Password</label>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
      </div>
      <button type="submit" class="btn btn-custom fw-bold">Sign In</button>
    </form>
    <p class="mt-2">
      New to Tech Zone?
      <a href="register.php" class="blue-text">Sign Up</a>
    </p>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- Client-side validation -->
  <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      let valid = true;
      // Reset validation states
      document.querySelectorAll('.form-control').forEach(input => input.classList.remove('is-invalid'));
      document.querySelectorAll('.invalid-feedback').forEach(div => div.textContent = '');

      // Username validation
      const username = document.getElementById('username');
      if (!username.value.trim()) {
        username.classList.add('is-invalid');
        username.nextElementSibling.nextElementSibling.textContent = 'Username is required.';
        valid = false;
      } else if (username.value.length < 8) {
        username.classList.add('is-invalid');
        username.nextElementSibling.nextElementSibling.textContent = 'Username must be at least 8 characters.';
        valid = false;
      }

      // Password validation
      const password = document.getElementById('password');
      if (!password.value.trim()) {
        password.classList.add('is-invalid');
        password.nextElementSibling.nextElementSibling.textContent = 'Password is required.';
        valid = false;
      } else if (password.value.length < 8) {
        password.classList.add('is-invalid');
        password.nextElementSibling.nextElementSibling.textContent = 'Password must be at least 8 characters.';
        valid = false;
      }

      if (!valid) e.preventDefault();
    });
  </script>
</body>
</html>
