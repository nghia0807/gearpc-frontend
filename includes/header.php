<?php
session_start();
$username = $_SESSION['username'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Tech Store</title>
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css"
  />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .navbar-nav .nav-link {
      color: #fff !important;
    }
    .form-control::placeholder {
      color:rgb(187, 187, 187) !important; /* đổi màu tại đây, ví dụ xám nhạt */
      opacity: 0.1; /* đảm bảo nó không bị mờ quá */
    }
  </style>
</head>
<body>
  <?php
  require('../pages/login.php'); // Include the login popup
  ?>
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #212121;">
    <div class="container">
      <!-- Logo -->
      <a class="navbar-brand" href="#">
        <img src="../assets/img/logo.png" alt="Site Logo" width="50px" height="50px" />
      </a>

      <!-- Toggler for mobile view -->
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarContent"
        aria-controls="navbarContent"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navbar content -->
      <div class="collapse navbar-collapse" id="navbarContent">
        <!-- Centered search bar -->
        <form action="search.php" method="get" class="mx-auto" style="max-width: 500px;">
          <div class="input-group" style="width: 500px;">
            <input
              class="form-control"
              type="search"
              name="q"
              placeholder="Search Tech Zone!"
              aria-label="Search"
              style="background-color: #363636; border: none; color: #fff;"
            />
            <button class="btn btn-search" style="background-color: #363636;" type="submit">
              <i class="bi bi-search text-white"></i>
            </button>
          </div>
        </form>

        <!-- Right side: Cart, Login/Register -->
        <ul class="navbar-nav">
          <li class="nav-item me-3">
            <a class="nav-link" href="order.php" style="font-size: 14px;">
              <i class="bi bi-truck me-1" style="font-size: 19px;"></i> Orders
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link" href="cart.php" style="font-size: 14px;">
              <i class="bi bi-cart" style="font-size: 19px;"></i> Cart
            </a>
          </li>
          <?php if ($username): ?>
            <li class="nav-item">
              <a class="nav-link" href="profile.php">Hello, <?php echo htmlspecialchars($username); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Sign Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <button 
                  class="nav-link" 
                  style="font-size: 14px;" 
                  data-bs-toggle="modal"
                  data-bs-target="#loginPopup">
                <i class="bi bi-person " style="font-size: 19px;"></i> Sign In
              </button>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</body>
</html>
