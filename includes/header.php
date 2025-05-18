<?php
require_once __DIR__ . '/session_init.php';

// --- Session check for login state and expiration ---
$isLoggedIn = false;
$userFullName = '';
$userRole = '';
if (isset($_SESSION['token'], $_SESSION['user'], $_SESSION['expiration'])) {
    $now = time();
    // Support both timestamp and string expiration
    $exp = is_numeric($_SESSION['expiration']) ? (int)$_SESSION['expiration'] : strtotime($_SESSION['expiration']);
    if ($exp > $now) {
        $isLoggedIn = true;
        $userFullName = htmlspecialchars($_SESSION['user']['fullName'] ?? $_SESSION['user']['username']);
        $userRole = $_SESSION['user']['role'] ?? '';
    } else {
        // Session expired
        $_SESSION = [];
        session_destroy();
    }
}
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
    .nav-item.dropdown:hover .dropdown-menu {
      display: block;
      background-color: #212121 !important;
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
      border-width: 2px !important;
    }
    .form-control:focus + .btn-search {
      border-color: #6694ea !important; 
      color: #fff !important;
      border-width: 2px !important;
    }
    .btn-search {
      border-left: none !important;
      background-color: #363636 !important; 
      color: #fff !important;
    }
    .btn-search:focus {
      border-color: #363636 !important;
    }
    .bi-search:hover {
      color: #6694ea !important; 
    }
    .userMenu {
      cursor: pointer;
      position: relative;
    }
    .user-popover {
      display: none;
      position: absolute;
      right: 0;
      top: 110%;
      min-width: 180px;
      background: #212121;
      color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.18);
      z-index: 1050;
      padding: 0.5rem 0;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.18s, visibility 0.18s;
    }
    .user-popover.show {
      display: block;
      opacity: 1;
      visibility: visible;
    }
    .user-popover-arrow {
      position: absolute;
      top: -10px;
      right: 24px;
      width: 20px;
      height: 10px;
      overflow: hidden;
    }
    .user-popover-arrow::after {
      content: "";
      display: block;
      margin: auto;
      width: 16px;
      height: 16px;
      background: #212121;
      transform: rotate(45deg);
      position: absolute;
      top: 4px;
      left: 2px;
      box-shadow: -2px -2px 4px rgba(0,0,0,0.05);
    }
    .user-popover .dropdown-item {
      color: #fff;
      padding: 0.5rem 1.2rem;
      text-decoration: none;
      display: block;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
    }
    .user-popover .dropdown-item:hover {
      background: #313131 !important;
    }
    .navbar-toggler {
      display: none !important;
    }
    .navbar-collapse {
      display: flex !important;
      flex-basis: auto !important;
      align-items: center;
      justify-content: space-between;
      flex-wrap: nowrap !important;
    }
    .container {
      max-width: 1400px;
      margin-left: auto;
      margin-right: auto;
      display: flex;
      align-items: center;
      flex-wrap: nowrap !important;
    }
    .navbar-brand {
      margin-right: 1.5rem;
      flex-shrink: 0;
      min-width: 50px;
    }
    .header-search-flex {
      flex: 1 1 0%;
      display: flex;
      justify-content: center;
      min-width: 0;
      max-width: 100%;
    }
    .header-search-flex form {
      width: 100%;
      max-width: 500px;
      min-width: 80px;
    }
    .input-group {
      width: 100%;
      min-width: 0;
      max-width: 100%;
      transition: width 0.2s;
    }
    .form-control {
      min-width: 0;
      width: 100%;
      transition: width 0.2s;
    }
    .navbar-nav {
      margin-bottom: 0 !important;
      flex-direction: row !important;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: nowrap !important;
      white-space: nowrap;
    }
    .navbar-nav > .nav-item {
      margin-bottom: 0 !important;
      white-space: nowrap;
      flex-shrink: 0;
    }
    /* Responsive: shrink search, hide text, never wrap logo/buttons */
    @media (max-width: 1400px) {
      .header-search-flex form { max-width: 400px; }
    }
    @media (max-width: 1200px) {
      .header-search-flex form { max-width: 300px; }
    }
    @media (max-width: 992px) {
      .header-search-flex form { max-width: 180px; }
    }
    @media (max-width: 800px) {
      .header-search-flex form { max-width: 80px; }
      .form-control::placeholder { font-size: 0; }
      .nav-label { display: none !important; }
      .navbar-nav .nav-link.header-items {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
      }
    }
    /* Prevent logo/buttons from wrapping */
    .container, .navbar-collapse, .navbar-nav {
      flex-wrap: nowrap !important;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-black">
    <div class="container me-4">
      <!-- Logo -->
      <a class="navbar-brand" href="index.php">
        <img src="assets/img/logo.png" alt="Site Logo" width="50px" height="50px" />
      </a>

      <!-- Remove toggler for mobile -->
      <!-- <button ...navbar-toggler...> ... </button> -->

      <!-- Remove collapse wrapper, keep content always visible -->
      <div class="navbar-collapse" id="navbarContent" style="display: flex !important;">
        <!-- Centered search bar, flexes to fill space between logo and right buttons -->
        <div class="header-search-flex">
          <form action="search.php" method="get" class="mx-auto">
            <div class="input-group">
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
        </div>
        <!-- Right side: Cart, Login/Register/User -->
        <ul class="navbar-nav" style="flex-direction: row !important; align-items: center; flex-wrap: nowrap !important;">
          <li class="nav-item me-3">
            <a class="nav-link header-items" href="index.php?page=order">
              <i class="bi bi-truck me-1"></i> <span class="nav-label">Orders</span>
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link header-items" href="index.php?page=cart">
              <i class="bi bi-cart"></i> <span class="nav-label">Cart</span>
            </a>
          </li>
          <?php if ($isLoggedIn): ?>
            <li class="nav-item dropdown position-relative" style="z-index:1060;">
              <button type="button"
                class="nav-link header-items userMenu"
                id="userDropdownBtn"
                aria-expanded="false"
                autocomplete="off"
              >
                <i class="bi bi-person"></i> <span class="nav-label"><?php echo $userFullName; ?></span>
              </button>
              <div class="user-popover" id="userPopover" tabindex="-1">
                <div class="user-popover-arrow"></div>
                <a class="dropdown-item" href="index.php?page=profile">
                  <i class="bi bi-person-square pe-1"></i>
                  <span class="nav-label">Profile</span>
                </a>
                <form method="post" style="margin:0;">
                  <button type="submit" name="logout" class="dropdown-item">
                    <i class="bi bi-box-arrow-in-right pe-1"></i>
                    <span class="nav-label">Sign Out</span>
                  </button>
                </form>
              </div>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link header-items" href="pages/login.php">
                <i class="bi bi-person"></i> <span class="nav-label">Login</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
  <script>
    // Custom popover logic for hover with delayed show
    (function() {
      var btn = document.getElementById('userDropdownBtn');
      var popover = document.getElementById('userPopover');
      if (!btn || !popover) return;

      var showTimeout = null;
      var hideTimeout = null;

      function showPopover() {
        clearTimeout(hideTimeout);
        showTimeout = setTimeout(function() {
          popover.classList.add('show');
          btn.setAttribute('aria-expanded', 'true');
        }, 120); // Delay for popover after background
      }
      function hidePopover() {
        clearTimeout(showTimeout);
        hideTimeout = setTimeout(function() {
          popover.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        }, 0); // No delay for hiding
      }

      btn.addEventListener('mouseenter', showPopover);
      btn.addEventListener('mouseleave', hidePopover);
      popover.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);
      });
      popover.addEventListener('mouseleave', hidePopover);

      // Hide on ESC
      document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
          popover.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        }
      });

      // Position popover under the button
      function positionPopover() {
        popover.style.top = (btn.offsetHeight + 4) + "px";
        popover.style.right = "0";
      }
      btn.addEventListener('mouseenter', positionPopover);
      window.addEventListener('resize', positionPopover);
    })();
  </script>
</body>
</html>
