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
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: black;">
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
            <a class="nav-link header-items" href="order.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">
              <i class="bi bi-truck me-1" style="font-size: 16px; font-weight: bold"></i> Orders
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link header-items" href="cart.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">
              <i class="bi bi-cart" style="font-size: 16px; font-weight: bold"></i> Cart
            </a>
          </li>
          <?php if ($username): ?>
            <li class="nav-item">
              <a class="nav-link header-items" href="profile.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">Hello, <?php echo htmlspecialchars($username); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link header-items" href="logout.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">Sign Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link header-items" href="../pages/login.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">
                <i class="bi bi-person" style="font-size: 16px; font-weight: bold"></i> Sign In
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link header-items" href="../pages/register.php" style="font-size: 16px; font-weight: bold; border-radius: 22px;">
                Register
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</body>
</html>
<?php include 'navbar.php'; ?>
