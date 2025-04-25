<?php
// --- Session and API Login Logic ---
session_name('user_session');
session_set_cookie_params(['path' => '/']);
session_start();

$alert = '';
$alertType = '';
$errors = [
    'username' => '',
    'password' => ''
];

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');

    // --- Server-side validation ---
    $isValid = true;
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
        $isValid = false;
    } elseif (strlen($username) < 8) {
        $errors['username'] = 'Username must be at least 8 characters.';
        $isValid = false;
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
        $isValid = false;
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
        $isValid = false;
    }

    // If valid, call the API
    if ($isValid) {
        $apiUrl = 'http://localhost:5000/api/auth/login';
        $postData = json_encode([
            'username' => $username,
            'password' => $password
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $alert = 'Cannot connect to server. Please try again later.';
            $alertType = 'danger';
        } else {
            $respData = json_decode($response, true);
            if ($httpCode === 200 && isset($respData['success']) && $respData['success'] === true) {
                // --- Role check logic ---
                $role = $respData['data']['user']['role'] ?? null;
                if ($role && $role === 'User') {
                    // Use user-specific session keys
                    $_SESSION['user_token'] = $respData['data']['token'];
                    $_SESSION['user'] = $respData['data']['user'];
                    $_SESSION['user_expiration'] = $respData['data']['expiration'];
                    $_SESSION['user_role'] = $role;
                    $alert = 'Login successful! Redirecting...';
                    $alertType = 'success';
                    echo "<script>setTimeout(function(){ window.location.href = 'home.php'; }, 500);</script>";
                } else {
                    $alert = 'Invalid or unauthorized role. Access denied.';
                    $alertType = 'danger';
                }
            } else {
                $apiMsg = $respData['message'] ?? 'Username or password is incorrect.';
                $alert = $apiMsg;
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
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background-color: #121212;
      color: #e3e3e3;
    }
    input:-webkit-autofill {
      background-color: #121212 !important;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      -webkit-box-shadow: 0 0 0 1000px #121212 inset !important;
      transition: background-color 9999s ease-out, color 9999s ease-out;
    }
    .form-control {
      width: 304px;
      height: 44px;
      background-color: #121212;
      border-color: #555;
      color: #ffffff !important;
    }
    /* Validation styling for is-invalid */
    .form-control.is-invalid {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
    }
    .invalid-feedback {
      color: #dc3545;
      font-size: 0.95em;
      text-align: left;
      width: 304px;
      margin-top: 0.25rem;
      margin-bottom: 0.5rem;
    }
    .blue-text {
      color: #e3e3e3 !important; 
      font-weight: bold !important;
    }
  </style>
</head>
<body>
  <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 text-center">
    <img src="../assets/img/logo.png" alt="Logo" class="mb-4" />
    <h5 class="mb-4" style="font-weight: 700;">Sign In</h5>
    <!-- Bootstrap alert for API or validation messages -->
    <?php if (!empty($alert)): ?>
      <div class="alert alert-<?php echo htmlspecialchars($alertType, ENT_QUOTES, 'UTF-8'); ?> w-100 mb-4" style="max-width: 340px; margin: 0 auto;">
        <?php echo htmlspecialchars($alert, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
    <form method="post" action="" id="loginForm" novalidate>
      <div class="floating-group">
        <input type="text" class="form-control floating-input <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" placeholder=" " required minlength="8" value="<?php echo isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : ''; ?>" />
        <label for="username">Username</label>
        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="floating-group">
        <input type="password" class="form-control floating-input <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder=" " required minlength="8" />
        <label for="password">Password</label>
        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <button type="submit" class="btn btn-custom" style="font-weight: bold;">Sign In</button>
    </form>
    <p class="mt-2">
        New to Tech Zone?
        <a href="register.php" class="blue-text" style="color: #e3e3e3; font-weight: bold;">Sign Up</a>
    </p>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Client-side validation -->
  <script>
    // --- Client-side validation ---
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      let valid = true;

      // Clear previous errors
      document.querySelectorAll('.form-control').forEach(function(input) {
        input.classList.remove('is-invalid');
      });
      document.querySelectorAll('.invalid-feedback').forEach(function(div) {
        div.textContent = '';
      });

      // Username
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

      // Password
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

      if (!valid) {
        e.preventDefault();
      }
    });
  </script>
</body>
</html>
