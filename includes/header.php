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
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .navbar-nav .nav-link {
      color: #fff !important;
    }
  </style>
</head>
<body>
  <?php
  require('../pages/login.php'); // Include the login popup
  ?>
  <nav class="navbar navbar-expand-lg navbar-dark bg-black bg-gradient">
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
            />
            <button class="btn btn-search" style="background-color: white;" type="submit">
              <i class="bi bi-search text-black"></i>
            </button>
          </div>
        </form>

        <!-- Right side: Cart, Login/Register -->
        <ul class="navbar-nav">
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
                <i class="bi bi bi-person me-1" style="font-size: 19px;"></i> Sign In
              </button>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</body>
</html>
