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
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    input:-webkit-autofill {
      background-color: #363636 !important;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      -webkit-box-shadow: 0 0 0 1000px #363636 inset !important;
      transition: background-color 9999s ease-out, color 9999s ease-out;
    }
    .navbar-nav .nav-link {
      color: #fff !important;
    }
    .header-items:hover {
      background-color: #1e1e1e;
    }
    .form-control:focus {
      box-shadow: none !important;
    }
    .header-items {
      font-size: 16px !important;
      font-weight: bold !important;
      border-radius: 22px !important;
    } 
    .form-control {
      border-right: none !important;
      background-color: #363636 !important; 
      border-color: #363636 !important; 
      color: #fff !important;
    }
    .form-control:focus {
      border-color: #6694ea !important; 
    }
    .form-control:focus + .btn-search {
      border-color: #6694ea !important; 
      color: #fff !important;
    }
    .btn-search {
      border-left: none !important;
      background-color: #363636 !important; 
      color: #fff !important;
    }
    .btn-search:focus {
      border-color: #363636 !important;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-black">
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
               <button class="btn btn-search" type="submit">
                 <i class="bi bi-search text-white"></i>
                </button>
              </div>
            </form>

        <!-- Right side: Cart, Login/Register -->
        <ul class="navbar-nav">
          <li class="nav-item me-3">
            <a class="nav-link header-items" href="order.php">
              <i class="bi bi-truck me-1"></i> Orders
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link header-items" href="cart.php">
              <i class="bi bi-cart"></i> Cart
            </a>
          </li>
          <?php if ($username): ?>
            <li class="nav-item">
              <a class="nav-link header-items" href="profile.php">Hello, <?php echo htmlspecialchars($username); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link header-items" href="logout.php">Sign Out</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link header-items" href="../pages/login.php">
                <i class="bi bi-person"></i> Sign In
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
