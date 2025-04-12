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
    }
    .blue-text {
      color: #e3e3e3 !important; 
      font-weight: bold !important;
    }
  </style>
</head>
<body>
  <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 text-center">
    <img src="logo.png" alt="Logo" class="mb-4" />
    <h5 class="mb-4" style="font-weight: 700;">Create Account</h5>
    <form method="post" action="process_register.php">
      <div class="floating-group">
        <input type="text" class="form-control floating-input" id="fullname" name="fullname" placeholder=" " />
        <label for="fullname">Full Name</label>
      </div>
      <div class="floating-group">
        <input type="email" class="form-control floating-input" id="email" name="email" placeholder=" " />
        <label for="email">Email</label>
      </div>
      <div class="floating-group">
        <input type="password" class="form-control floating-input" id="password" name="password" placeholder=" " />
        <label for="password">Password</label>
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
</body>
</html>
