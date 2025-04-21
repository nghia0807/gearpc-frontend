<?php
// --- Server-side validation and API integration ---

$alert = '';
$alertType = '';
$errors = [
    'fullname' => '',
    'username' => '',
    'email' => '',
    'password' => ''
];

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $fullname = sanitize($_POST['fullname'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
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
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
        $isValid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
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
        $apiUrl = 'http://localhost:5000/api/auth/register';
        $postData = json_encode([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'fullName' => $fullname
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
            $alert = 'Cannot connect to server. Please try again!';
            $alertType = 'danger';
        } else {
            $respData = json_decode($response, true);
            if ($httpCode === 200 || $httpCode === 201) {
                $alert = 'Register Successfully!';
                $alertType = 'success';
                // Optionally redirect after 2 seconds
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 1000);</script>";
            } else {
                $apiMsg = $respData['message'] ?? 'Registration failed!';
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
  <title>Create Account</title>
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
    .blue-text {
      color: #e3e3e3 !important; 
      font-weight: bold !important;
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
  </style>
</head>
<body>
  <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 text-center">
    <img src="logo.png" alt="Logo" class="mb-4" />
    <h5 class="mb-4" style="font-weight: 700;">Create Account</h5>
    <!-- Bootstrap alert for API or validation messages -->
    <?php if (!empty($alert)): ?>
      <div class="alert alert-<?php echo $alertType; ?> w-100 mb-4" style="max-width: 340px; margin: 0 auto;">
        <?php echo $alert; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="" id="registerForm" novalidate>
      <div class="floating-group">
        <input type="text" class="form-control floating-input <?php echo !empty($errors['fullname']) ? 'is-invalid' : ''; ?>" id="fullname" name="fullname" placeholder=" " required minlength="1" value="<?php echo isset($fullname) ? $fullname : ''; ?>" />
        <label for="fullname">Full Name</label>
        <div class="invalid-feedback"><?php echo $errors['fullname']; ?></div>
      </div>
      <div class="floating-group">
        <input type="text" class="form-control floating-input <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" placeholder=" " required minlength="8" value="<?php echo isset($username) ? $username : ''; ?>" />
        <label for="username">Username</label>
        <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
      </div>
      <div class="floating-group">
        <input type="email" class="form-control floating-input <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" placeholder=" " required value="<?php echo isset($email) ? $email : ''; ?>" />
        <label for="email">Email</label>
        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
      </div>
      <div class="floating-group">
        <input type="password" class="form-control floating-input <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder=" " required minlength="8" />
        <label for="password">Password</label>
        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
      </div>
      <div class="form-group" style="width: 304px; text-align: left; font-size: 13px;">
          <p style="color: #aaaaaa;">
              By creating an account, you agree to Teach Zone's
              <a href="#" class="blue-text">Privacy Notice</a> and
              <a href="#" class="blue-text">Terms of Service</a>
          </p>
      </div>
      <button type="submit" class="btn btn-custom" style="font-weight: bold;">Create Account</button>
    </form>
    <p class="mt-2">
        Have an account?
        <a href="login.php" class="blue-text">Sign In</a>
    </p>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Client-side validation -->
  <script>
    // --- Client-side validation ---
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      let valid = true;

      // Clear previous errors
      document.querySelectorAll('.form-control').forEach(function(input) {
        input.classList.remove('is-invalid');
      });
      document.querySelectorAll('.invalid-feedback').forEach(function(div) {
        div.textContent = '';
      });

      // Full Name
      const fullname = document.getElementById('fullname');
      if (!fullname.value.trim()) {
        fullname.classList.add('is-invalid');
        fullname.nextElementSibling.nextElementSibling.textContent = 'Full name is required.';
        valid = false;
      }

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

      // Email
      const email = document.getElementById('email');
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email.value.trim()) {
        email.classList.add('is-invalid');
        email.nextElementSibling.nextElementSibling.textContent = 'Email is required.';
        valid = false;
      } else if (!emailPattern.test(email.value)) {
        email.classList.add('is-invalid');
        email.nextElementSibling.nextElementSibling.textContent = 'Invalid email format.';
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
