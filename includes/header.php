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
</head>
<body>
  <?php
  require('../pages/login.php'); // Include the login popup
  ?>
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
      <!-- Logo -->
      <a class="navbar-brand" href="#">
        <img src="logo.png" alt="Site Logo" width="40" height="40" />
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
        <form class="d-flex mx-auto" action="search.php" method="get">
          <input
            class="form-control me-2"
            type="search"
            name="q"
            placeholder="Search products..."
            aria-label="Search"
          />
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>

        <!-- Right side: Cart, Login/Register -->
        <ul class="navbar-nav ms-auto">
          <li class="nav-item me-3">
            <a class="nav-link" href="cart.php">
              <i class="bi bi-cart"></i> Cart
            </a>
          </li>
          <?php if ($username): ?>
            <li class="nav-item">
              <a class="nav-link" href="profile.php">Hello, <?php echo htmlspecialchars($username); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Logout</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="modal" data-bs-target="#loginPopup">Login</button>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</body>
</html>
