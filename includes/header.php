<?php
require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/../components/cart-badge.php';

// --- Session check for login state and expiration ---
$isLoggedIn = false;
$userFullName = '';
$userRole = '';
$cartItemCount = 0;

if (isset($_SESSION['token'], $_SESSION['user'], $_SESSION['expiration'])) {
  $now = time();
  // Support both timestamp and string expiration
  $exp = is_numeric($_SESSION['expiration']) ? (int) $_SESSION['expiration'] : strtotime($_SESSION['expiration']);
  if ($exp > $now) {
    $isLoggedIn = true;
    $userFullName = htmlspecialchars($_SESSION['user']['fullName'] ?? $_SESSION['user']['username']);
    $userRole = $_SESSION['user']['role'] ?? '';
    
    // Get cart item count for the badge
    $cartResult = getCartItemCount($_SESSION['token']);
    $cartItemCount = $cartResult['count'];
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" />
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
      background-color: #363636 !important;
      border-color: #363636 !important;
      color: #fff !important;
    }

    .form-control:focus {
      border-color: #6694ea !important;
      border-width: 2px !important;
    }

    .form-control:focus+.btn-search {
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
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
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
    }    .user-popover-arrow::after {
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
      box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.05);
    }
    
    /* Cart badge styling */
    .cart-badge {
      font-size: 0.65rem;
      transform: translate(25%, -25%) !important;
      animation: scaleInOut 0.5s;
      background-color: #ffa33a !important;
      font-weight: bold;
    }
    
    @keyframes scaleInOut {
      0% { transform: translate(25%, -25%) scale(0); }
      50% { transform: translate(25%, -25%) scale(1.2); }
      100% { transform: translate(25%, -25%) scale(1); }
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
      flex: 0 1 500px; /* This allows the search to be centered but with max width */
      margin: 0 15px;
    }

    .navbar-right {
      display: flex;
      justify-content: flex-end;
    }

    .container-fluid {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      padding: 0 15px;
    }
    
    .navbar-header {
      display: flex;
      align-items: center;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
      .header-search-flex {
        flex: 0 1 300px;
      }
    }
    
    @media (max-width: 768px) {
      .header-search-flex {
        flex: 0 1 200px;
      }
    }

    /* Prevent logo/buttons from wrapping */
    .container,
    .navbar-collapse,
    .navbar-nav {
      flex-wrap: nowrap !important;
    }

    /* Search suggestions dropdown */
    .search-suggestions {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      background: #212121;
      border-radius: 0 0 8px 8px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
      z-index: 1050;
      max-height: 400px;
      overflow-y: auto;
    }

    .search-suggestions.show {
      display: block;
    }

    .suggestion-group {
      padding: 10px 0;
    }

    .suggestion-group h6 {
      padding: 0 15px;
      margin-bottom: 8px;
      color: #6c757d;
      font-size: 0.8rem;
    }

    .suggestion-item {
      padding: 8px 15px;
      cursor: pointer;
      display: flex;
      align-items: center;
      color: #fff;
    }

    .suggestion-item:hover,
    .suggestion-item.selected {
      background-color: #313131;
    }

    .suggestion-item img {
      width: 40px;
      height: 40px;
      object-fit: contain;
      margin-right: 10px;
      background: #363636;
      border-radius: 4px;
    }

    .suggestion-item-info {
      flex-grow: 1;
    }

    .suggestion-item-title {
      font-weight: 500;
    }

    .suggestion-item-meta {
      font-size: 0.8rem;
      color: #6c757d;
    }

    .search-spinner {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
    }

    .sidebar-popup {
      min-width: auto !important;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      display: none;
    }

    .sidebar-popup .sidebar-menu {
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      border-radius: 0 10px 10px 0;
      padding: 20px;
      max-height: 100vh;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-black">
    <div class="container-fluid">
      <!-- Left: Logo -->
      <div class="navbar-header">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="assets/img/logo.png" alt="Site Logo" width="50px" height="50px" class="me-2" />
          <span class="my-auto">Tech Zone</span>
        </a>
      </div>

      <!-- Center: Search bar -->
      <div class="header-search-flex">
        <form action="index.php" method="get" class="position-relative">
          <div class="input-group">
            <input class="form-control border-end-0" type="search" name="q" id="searchInput" placeholder="Search Tech Zone!"
              aria-label="Search" autocomplete="off" />
            <button class="btn btn-search" type="submit">
              <i class="bi bi-search text-white"></i>
              <div class="spinner-border spinner-border-sm text-light search-spinner" role="status"
                style="display:none">
                <span class="visually-hidden">Loading...</span>
              </div>
            </button>
          </div>
          <div class="search-suggestions" id="searchSuggestions">
            <div class="suggestion-group" id="recentSearches">
              <h6>Recent Searches</h6>
              <div class="suggestions-list" id="recentSearchesList"></div>
            </div>
            <!-- Add dedicated section for product suggestions -->
            <div class="suggestion-group" id="suggestedProducts">
              <h6>Products</h6>
              <div class="suggestions-list" id="suggestedProductsList"></div>
            </div>
          </div>
        </form>
      </div>

      <!-- Right: Navigation links -->
      <div class="navbar-right">
        <ul class="navbar-nav"
          style="flex-direction: row !important; align-items: center; flex-wrap: nowrap !important;">
          <li class="nav-item me-3">
            <a class="nav-link header-items"
              href="<?php echo $isLoggedIn ? 'index.php?page=my-orders' : 'pages/not-logged-in.php'; ?>">
              <i class="bi bi-truck me-1"></i> <span class="nav-label">Orders</span>
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link header-items position-relative"
              href="<?php echo $isLoggedIn ? 'index.php?page=cart' : 'pages/not-logged-in.php'; ?>">
              <i class="bi bi-cart"></i> <span class="nav-label">Cart</span>
              <?php echo $isLoggedIn ? renderCartBadge($cartItemCount) : ''; ?>
            </a>
          </li>
          <?php if ($isLoggedIn): ?>
            <li class="nav-item dropdown position-relative" style="z-index:1060;">
              <button type="button" class="nav-link header-items userMenu" id="userDropdownBtn" aria-expanded="false"
                autocomplete="off">
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
    (function () {
      var btn = document.getElementById('userDropdownBtn');
      var popover = document.getElementById('userPopover');
      if (!btn || !popover) return;

      var showTimeout = null;
      var hideTimeout = null;

      function showPopover() {
        clearTimeout(hideTimeout);
        showTimeout = setTimeout(function () {
          popover.classList.add('show');
          btn.setAttribute('aria-expanded', 'true');
        }, 120); // Delay for popover after background
      }
      function hidePopover() {
        clearTimeout(showTimeout);
        hideTimeout = setTimeout(function () {
          popover.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        }, 0); // No delay for hiding
      }

      btn.addEventListener('mouseenter', showPopover);
      btn.addEventListener('mouseleave', hidePopover);
      popover.addEventListener('mouseenter', function () {
        clearTimeout(hideTimeout);
      });
      popover.addEventListener('mouseleave', hidePopover);

      // Hide on ESC
      document.addEventListener('keydown', function (e) {
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

    // Enhanced Search Feature
    (function () {
      const searchInput = document.getElementById('searchInput');
      const searchSuggestions = document.getElementById('searchSuggestions');
      const suggestedProductsList = document.getElementById('suggestedProductsList');
      const recentSearchesList = document.getElementById('recentSearchesList');
      const searchSpinner = document.querySelector('.search-spinner');

      if (!searchInput || !searchSuggestions) return;

      let debounceTimer;
      let abortController = null;
      let selectedIndex = -1;
      let allSuggestionItems = [];

      // Load recent searches from localStorage
      function loadRecentSearches() {
        const searches = JSON.parse(localStorage.getItem('recentSearches') || '[]');
        recentSearchesList.innerHTML = '';

        if (searches.length === 0) {
          recentSearchesList.innerHTML = '<div class="px-3 py-2 text-muted">No recent searches</div>';
          return;
        }

        searches.slice(0, 5).forEach(term => {
          const div = document.createElement('div');
          div.className = 'suggestion-item';
          div.innerHTML = `
            <i class="bi bi-clock-history me-2"></i>
            <div class="suggestion-item-info">
              <div class="suggestion-item-title">${term}</div>
            </div>
          `;
          div.addEventListener('click', () => {
            searchInput.value = term;
            searchSuggestions.classList.remove('show');
            // Direct search to products page with search parameter
            window.location.href = `index.php?page=products&q=${encodeURIComponent(term)}`;
          });
          recentSearchesList.appendChild(div);
        });
      }

      // Add search term to history
      function addToRecentSearches(term) {
        if (!term.trim()) return;

        const searches = JSON.parse(localStorage.getItem('recentSearches') || '[]');
        // Remove if exists and add to beginning
        const filteredSearches = searches.filter(s => s.toLowerCase() !== term.toLowerCase());
        filteredSearches.unshift(term);

        // Keep only latest 10 searches
        const updatedSearches = filteredSearches.slice(0, 10);
        localStorage.setItem('recentSearches', JSON.stringify(updatedSearches));
      }

      // Fetch product suggestions with improved error handling
      async function fetchSuggestions(query) {
        if (!query.trim()) {
          // Just show recent searches when query is empty
          document.getElementById('suggestedProducts').style.display = 'none';
          loadRecentSearches();
          searchSuggestions.classList.add('show');
          return;
        }

        // Show spinner while loading
        searchSpinner.style.display = 'inline-block';
        document.getElementById('suggestedProducts').style.display = 'block';

        // Cancel previous request if any
        if (abortController) {
          abortController.abort();
        }

        // Create new abort controller for this request
        abortController = new AbortController();

        try {
          const apiUrl = `http://localhost:5000/api/products/search?q=${encodeURIComponent(query)}&pageSize=5`;

          const response = await fetch(apiUrl, {
            signal: abortController.signal,
            headers: {
              'Accept': 'application/json'
            },
            // Add timeout using Promise.race
          }).catch(error => {
            if (error.name === 'AbortError') {
              // Request was aborted, no need to handle
              return null;
            }
            throw error;
          });

          if (!response || !response.ok) {
            throw new Error('Network response was not ok');
          }

          const result = await response.json();
          if (result.success && result.data) {
            renderProductSuggestions(result.data.data || []);
          } else {
            renderProductSuggestions([]);
          }
        } catch (error) {
          console.error('Error fetching suggestions:', error);
          // Show empty product list on error
          renderProductSuggestions([]);
        } finally {
          searchSpinner.style.display = 'none';
          abortController = null;
        }
      }

      // Render product suggestions with improved display
      function renderProductSuggestions(products) {
        suggestedProductsList.innerHTML = '';

        if (!products || products.length === 0) {
          suggestedProductsList.innerHTML = '<div class="px-3 py-2 text-muted">No products found</div>';
          searchSuggestions.classList.add('show');
          return;
        }

        products.forEach(product => {
          const div = document.createElement('div');
          div.className = 'suggestion-item';

          // Format price with VND currency formatting
          const price = product.currentPrice ?
            new Intl.NumberFormat('vi-VN', {
              style: 'currency',
              currency: 'VND',
              minimumFractionDigits: 0,
              maximumFractionDigits: 0
            }).format(product.currentPrice).replace('₫', '').trim() + ' ₫' :
            'Liên hệ';

          // Highlight matching terms in product name
          let highlightedName = product.name;
          const searchTerms = searchInput.value.trim().split(/\s+/).filter(term => term.length > 1);

          if (searchTerms.length > 0) {
            const escapedTerms = searchTerms.map(term =>
              term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            );
            const pattern = new RegExp(`(${escapedTerms.join('|')})`, 'gi');
            highlightedName = product.name.replace(pattern, '<b>$1</b>');
          }

          // Get category name or brand name, with fallbacks
          const category = product.categoryName || product.category || 'Sản phẩm';
          const brand = product.brandName || product.brand || '';
          const meta = brand ? `${category} • ${brand}` : category;

          div.innerHTML = `
            <img src="${product.imageUrl || product.image || 'assets/img/placeholder.png'}" 
                 alt="${product.name}" 
                 onerror="this.src='assets/img/placeholder.png'">
            <div class="suggestion-item-info">
              <div class="suggestion-item-title">${highlightedName}</div>
              <div class="suggestion-item-meta">${meta} • ${price}</div>
            </div>
          `;

          div.addEventListener('click', () => {
            // Add search term to history
            addToRecentSearches(searchInput.value.trim());
            // Navigate to product detail page
            window.location.href = `index.php?page=product-detail&id=${product.id}`;
          });

          suggestedProductsList.appendChild(div);
        });

        searchSuggestions.classList.add('show');
        selectedIndex = -1;
        updateAllSuggestionItems();
      }

      // Update all suggestion items for keyboard navigation
      function updateAllSuggestionItems() {
        allSuggestionItems = Array.from(searchSuggestions.querySelectorAll('.suggestion-item'));
      }

      // Handle keyboard navigation
      function handleKeyNavigation(e) {
        if (!searchSuggestions.classList.contains('show')) return;

        // Arrow down
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (selectedIndex < allSuggestionItems.length - 1) {
            selectedIndex++;
            updateSelection();
          }
        }
        // Arrow up
        else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (selectedIndex > 0) {
            selectedIndex--;
            updateSelection();
          }
        }
        // Enter
        else if (e.key === 'Enter') {
          if (selectedIndex >= 0) {
            e.preventDefault();
            allSuggestionItems[selectedIndex].click();
          } else if (searchInput.value.trim()) {
            // Add to recent searches on direct form submission
            addToRecentSearches(searchInput.value.trim());
          }
        }
        // Escape
        else if (e.key === 'Escape') {
          searchSuggestions.classList.remove('show');
          searchInput.blur();
        }
      }

      function updateSelection() {
        allSuggestionItems.forEach((item, index) => {
          if (index === selectedIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          } else {
            item.classList.remove('selected');
          }
        });
      }

      // Update search form to direct to products page
      const searchForm = document.querySelector('.header-search-flex form');
      if (searchForm) {
        searchForm.setAttribute('action', 'index.php');

        // Add hidden input for page parameter
        const pageInput = document.createElement('input');
        pageInput.type = 'hidden';
        pageInput.name = 'page';
        pageInput.value = 'products';
        searchForm.appendChild(pageInput);

        // Add form submit handler to save search term
        searchForm.addEventListener('submit', function (e) {
          const term = searchInput.value.trim();
          if (term) {
            addToRecentSearches(term);
          }
        });
      }

      // Event listeners for search interaction
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          fetchSuggestions(this.value);
        }, 300);
      });

      searchInput.addEventListener('focus', function () {
        if (this.value.trim()) {
          fetchSuggestions(this.value);
        } else {
          document.getElementById('suggestedProducts').style.display = 'none';
          loadRecentSearches();
          searchSuggestions.classList.add('show');
          updateAllSuggestionItems();
        }
      });

      searchInput.addEventListener('keydown', handleKeyNavigation);

      // Close suggestions when clicking outside
      document.addEventListener('click', function (e) {
        if (!searchSuggestions.contains(e.target) && e.target !== searchInput) {
          searchSuggestions.classList.remove('show');
        }
      });

      // Initial load of recent searches
      loadRecentSearches();
    })();
  </script>
</body>

</html>