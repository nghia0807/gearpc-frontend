<?php
// Fetch categories for the Deals dropdown
$categoriesApiUrl = "http://localhost:5000/api/categories/get_select";
$categories = [];

// Function to make API requests
function fetchCategories($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [];
    }
    
    $data = json_decode($response, true);
    return isset($data['success']) && $data['success'] && isset($data['data']) ? $data['data'] : [];
}

// Get categories for the Deals dropdown
$categories = fetchCategories($categoriesApiUrl);

// Sort categories (if needed)
$customOrder = [
    'Laptops', 'PCs', 'Main, CPU, VGA', 'Monitors', 'Keyboards', 
    'Mouse + Mouse Pad', 'Earphones', 'Sounds'
];

if (!empty($categories)) {
    usort($categories, function($a, $b) use ($customOrder) {
        $posA = array_search($a['name'] ?? '', $customOrder);
        $posB = array_search($b['name'] ?? '', $customOrder);
        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;
        return $posA - $posB;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>GearPC</title>
  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
  />
  <!-- Bootstrap Icons -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
  />
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .form-control::placeholder {
      color:rgb(187, 187, 187) !important;
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
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark p-2 navbar-items" style="background-color: #363636;">
    <div class="container-fluid">
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

      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <!-- Updated Deals dropdown with dynamic categories -->
          <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle"
              href="#"
              id="navbarDropdownDeals"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              Deals
            </a>
            <ul class="dropdown-menu p-2" aria-labelledby="navbarDropdownDeals" style="background-color: #212121;">
              <!-- All deals link -->
              <li>
                <a class="dropdown-item blue-text" href="../pages/products.php">
                  <i class="bi bi-tags deal-icon"></i>All Deals
                </a>
              </li>
              
              <!-- Category-based deals links -->
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                  <?php if (isset($category['code']) && isset($category['name'])): ?>
                    <li>
                      <a class="dropdown-item blue-text" href="../pages/products.php?category=<?= urlencode($category['code']) ?>">
                        <?php 
                        // Map category names to appropriate icons
                        $iconClass = 'bi bi-tag';
                        switch ($category['name']) {
                            case 'Laptops': $iconClass = 'bi bi-laptop'; break;
                            case 'PCs': $iconClass = 'bi bi-pc-display'; break;
                            case 'Main, CPU, VGA': $iconClass = 'bi bi-cpu'; break;
                            case 'Mouse + Mouse Pad': $iconClass = 'bi bi-mouse'; break;
                            case 'Sounds': $iconClass = 'bi bi-speaker'; break;
                            case 'Monitors': $iconClass = 'bi bi-display'; break;
                            case 'Earphones': $iconClass = 'bi bi-headphones'; break;
                            case 'Keyboards': $iconClass = 'bi bi-keyboard'; break;
                        }
                        ?>
                        <i class="<?= $iconClass ?> deal-icon"></i><?= htmlspecialchars($category['name']) ?> Deals
                      </a>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Fallback links if API doesn't return categories -->
                <li><a class="dropdown-item blue-text" href="../pages/products.php"><i class="bi bi-laptop deal-icon"></i>Today's Best Deals</a></li>
                <li><a class="dropdown-item blue-text" href="../pages/products.php"><i class="bi bi-laptop deal-icon"></i>Laptop Deals</a></li>
                <li><a class="dropdown-item blue-text" href="../pages/products.php"><i class="bi bi-headphones deal-icon"></i>Headphone Deals</a></li>
                <li><a class="dropdown-item blue-text" href="../pages/products.php"><i class="bi bi-keyboard deal-icon"></i>Keyboard Deals</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <li class="nav-item" style="display: flex; align-items: center;">
            <a class="nav-link pe-0" href="../pages/products.php?sort=bestseller" style="color: yellow !important; font-size: 14px; font-weight: bold;">
              Best Seller
            </a>
            <span class="fire-icon">🔥</span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" >PC Builder</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" >News</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="#" >
              <i class="bi bi-question-circle"></i> Help Center
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Bootstrap JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>