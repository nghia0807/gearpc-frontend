<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>GearPC</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .form-control::placeholder {
      color: rgb(187, 187, 187) !important;
    }

    .navbar-items .nav-link {
      color: #fff !important;
      font-size: 14px !important;
      font-weight: bold !important;
    }

    .navbar-items .nav-item.dropdown:hover .dropdown-menu {
      display: block;
    }

    .navbar-items .nav-item:hover .nav-link {
      text-decoration: underline;
    }

    .dropdown-item {
      color: white !important;
      padding-top: 10px !important;
      padding-bottom: 10px !important;
      padding-left: 15px !important;
      padding-right: 15px !important;
    }

    .blue-text:hover {
      background-color: #212121 !important;
      text-decoration: underline !important;
    }

    .deal-icon {
      margin-right: 8px;
      width: 16px;
      text-align: center;
    }

    @media (max-width: 991.98px) {
      .navbar .navbar-collapse {
        display: flex !important;
      }
    }

    /* Remove mobile paddings/margins for desktop */
    .navbar-items .navbar-nav {
      margin-bottom: 0 !important;
    }

    .navbar-toggler {
      display: none !important;
    }

    /* Always show navbar expanded */
    .navbar-collapse {
      display: flex !important;
      flex-basis: auto !important;
    }

    /* Adjust container for desktop width */
    .container-fluid {
      max-width: 1400px;
      margin-left: auto;
      margin-right: auto;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark p-2 navbar-items" style="background-color: #363636;">
    <div class="container-fluid">
      <!-- Remove toggler button for mobile -->
      <!-- <button ...navbar-toggler...> ... </button> -->

      <!-- Remove collapse wrapper, keep content always visible -->
      <div class="navbar-collapse" id="navbarContent" style="display: flex !important;">
        <ul class="navbar-nav me-auto mb-0">
          <!-- Deals dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownDeals" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">
              Deals
            </a>
            <ul class="dropdown-menu p-2" aria-labelledby="navbarDropdownDeals" style="background-color: #212121;">
              <li>
                <a class="dropdown-item blue-text" href="index.php?page=products">
                  <i class="bi bi-stars deal-icon"></i>Today's Best Deals
                </a>
              </li>
              <li>
                <a class="dropdown-item blue-text" href="index.php?page=products&category=laptops">
                  Laptop Deals
                </a>
              </li>
              <li>
                <a class="dropdown-item blue-text" href="index.php?page=products&category=headphones">
                  Headphone Deals
                </a>
              </li>
              <li>
                <a class="dropdown-item blue-text" href="index.php?page=products&category=keyboards">
                  Keyboard Deals
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item" style="display: flex; align-items: center;">
            <a class="nav-link pe-0" href="index.php?page=products&sort=bestseller"
              style="color: yellow !important; font-size: 14px; font-weight: bold;">
              Best Seller
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">PC Builder</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">News</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto mb-0">
          <li class="nav-item">
            <a class="nav-link" href="#">
              <i class="bi bi-question-circle"></i> Help Center
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>