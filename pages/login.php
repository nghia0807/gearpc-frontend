<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #121212;
      color: #e3e3e3;
    }
    .btn-custom {
      background-color: #ffa33a;
      border-color: #ffa33a;
      color: #121212;
      width: 304px;
      height: 44px;
    }
    .form-control {
      width: 304px;
      height: 44px;
      background-color: #121212;
    }
  </style>
</head>
<body>
  <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 text-center">
    <img src="logo.png" alt="Logo" class="mb-4" />
    <h1>Login</h1>
    <form method="post" action="process_login.php">
      <div class="form-group">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email">
      </div>
      <div class="form-group">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password">
      </div>
      <button type="submit" class="btn btn-custom">Login</button>
    </form>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
