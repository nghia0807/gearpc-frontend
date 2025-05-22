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

    .menu-toggle-btn {
      cursor: pointer;
      display: flex;
      align-items: center;
      padding: 8px 15px;
      border-radius: 4px;
      color: #fff;
      font-weight: bold;
      transition: background-color 0.2s;
    }

    .menu-toggle-btn:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .menu-toggle-btn i {
      font-size: 20px;
      margin-right: 5px;
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
      margin-left: auto;
      margin-right: auto;
    }
    
    /* Sidebar popup styles */
    .sidebar-popup {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      display: none;
      padding: 15px;
    }

    .sidebar-popup .sidebar-menu {
      position: relative;
      top: 123px;
      left: 10px;
      width: 100% !important;
      border-radius: 10px;
      background-color: #414141;
    }
    
    /* Size sidebar popup exactly like home sidebar */
    #sidebarPopupContent {
      width: fit-content !important;
    }

    
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark p-2 navbar-items" style="background-color: #363636;">
    <div class="container-fluid">
      <!-- Menu Toggle Button -->
      <div class="menu-toggle-btn me-3" onclick="openSidebar()">
        <i class="bi bi-list"></i> Menu
      </div>

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
          </li>          <li class="nav-item">
            <a class="nav-link" href="index.php?page=build">PC Builder</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=news">News</a>
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
  
  <!-- Sidebar Popup Container -->
  <div id="sidebarPopup" class="sidebar-popup">
    <div id="sidebarPopupContent" class="home-sidebar">
      <!-- Content will be loaded dynamically -->
    </div>
  </div>

  <!-- Bootstrap JS and Sidebar Menu Functions -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Store the loaded sidebar content
    let sidebarContentLoaded = false;
    let cachedSidebarContent = null;
    
    // Sidebar Menu Functions
    function openSidebar() {
        const popup = document.getElementById('sidebarPopup');
        const popupContent = document.getElementById('sidebarPopupContent');
        
        // Clear previous content to avoid style conflicts
        popupContent.innerHTML = '';
        
        // If we're on the home page, copy the sidebar content
        const homeSidebar = document.querySelector('.home-sidebar .sidebar-menu');
        if (homeSidebar) {
            // Clone the sidebar content from home page
            const clonedSidebar = homeSidebar.cloneNode(true);
            popupContent.appendChild(clonedSidebar);
        } else {
            // For other pages, we'll use cached content if available or load via AJAX
            if (sidebarContentLoaded && cachedSidebarContent) {
                // Use cached content if available
                popupContent.innerHTML = cachedSidebarContent;
            } else {
                // Show loading indicator
                popupContent.innerHTML = '<div class="d-flex justify-content-center p-4"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                
                // Load content via AJAX
                fetch('components/sidebar/load-sidebar.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not OK');
                        }
                        return response.text();
                    })
                    .then(html => {
                        // Cache the content for future use
                        cachedSidebarContent = html;
                        sidebarContentLoaded = true;
                        
                        // Display the content
                        popupContent.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading sidebar:', error);
                        popupContent.innerHTML = '<div class="alert alert-danger p-3">Không thể tải menu</div>';
                        
                        // Reset cache status on error
                        sidebarContentLoaded = false;
                        cachedSidebarContent = null;
                    });
            }
        }
        
        // Show popup and prevent background scrolling
        popup.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        const popup = document.getElementById('sidebarPopup');
        if (popup) {
            popup.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
    }

    // Close sidebar when clicking outside of it
    document.addEventListener('click', function(event) {
        const popup = document.getElementById('sidebarPopup');
        if (!popup) return;
        
        const popupContent = document.getElementById('sidebarPopupContent');
        const menuToggleBtn = document.querySelector('.menu-toggle-btn');
        const closeBtn = document.querySelector('.sidebar-popup .close-btn');
        
        // Only proceed if popup is displayed
        if (popup.style.display !== 'block') return;
        
        // Check if click is outside the content and not on menu button or close button
        if ((popupContent && !popupContent.contains(event.target)) && 
            (menuToggleBtn && !menuToggleBtn.contains(event.target)) && 
            (!closeBtn || !closeBtn.contains(event.target))) {
            closeSidebar();
        }
    }, true); // Use capture phase for more reliable click detection
    
    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            closeSidebar();
        }
    });
  </script>
</body>

</html>