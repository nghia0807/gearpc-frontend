<?php
// Fetch categories using PHP cURL
function fetchCategories() {
    $apiUrl = "http://phpbe_app_service:5000/api/categories/get?pageSize=1000"; // Fetch all categories

    $categories = [];
    $errorMsg = '';

    try {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMsg = 'Không thể tải danh mục, vui lòng thử lại';
        } else {
            $jsonData = json_decode($response, true);
            if (isset($jsonData['data']['data'])) {
                $categories = $jsonData['data']['data'];
                // Custom order for categories
                $customOrder = [
                    // Main Products
                    'PCs',
                    'Laptops',
                    // Core Components
                    'Motherboards',
                    'CPUs',
                    'Graphics Cards',
                    'Memory (RAM)',
                    'Power Supply Unit (PSU)',
                    'CPU Cooler',
                    'HDD',
                    'SSD',
                    'PC Cases',
                    // Peripherals
                    'Monitors',
                    'Keyboards',
                    'Mouses + Mouse Pads',
                    // Audio
                    'Sounds',
                    // Software
                    'Operating System'
                ];
                usort($categories, function ($a, $b) use ($customOrder) {
                    $posA = array_search($a['name'], $customOrder);
                    $posB = array_search($b['name'], $customOrder);
                    $posA = $posA === false ? PHP_INT_MAX : $posA;
                    $posB = $posB === false ? PHP_INT_MAX : $posB;
                    return $posA - $posB;
                });
            } else {
                $errorMsg = 'Không có danh mục nào';
            }
        }
        curl_close($ch);
    } catch (Exception $e) {
        $errorMsg = 'Không thể tải danh mục, vui lòng thử lại';
    }

    return [
        'categories' => $categories,
        'errorMsg' => $errorMsg
    ];
}

$icons = [
    // Main Products
    'PCs' => 'bi bi-pc-display-horizontal',
    'Laptops' => 'bi bi-laptop-fill',
    // Core Components
    'Motherboards' => 'bi bi-motherboard-fill',
    'CPUs' => 'bi bi-cpu-fill',
    'Graphics Cards' => 'bi bi-gpu-card',
    'Memory (RAM)' => 'bi bi-memory',
    'Power Supply Unit (PSU)' => 'bi bi-lightning-charge-fill',
    'CPU Cooler' => 'bi bi-fan',
    'HDD' => 'bi bi-device-hdd-fill',
    'SSD' => 'bi bi-device-ssd-fill',
    'PC Cases' => 'bi bi-pc-display',
    // Peripherals
    'Monitors' => 'bi bi-display-fill',
    'Keyboards' => 'bi bi-keyboard-fill',
    'Mouses + Mouse Pads' => 'bi bi-mouse3-fill',
    // Audio
    'Sounds' => 'bi bi-speaker-fill',
    // Software
    'Operating System' => 'bi bi-windows'
];

// If the sidebar is included directly, fetch categories
if (!isset($sidebarData)) {
    $result = fetchCategories();
    $categories = $result['categories'];
    $errorMsg = $result['errorMsg'];
}
?>

<!-- Sidebar Menu CSS -->
<style>    .category-sidebar.sidebar-menu {
        background-color: #414141;
        min-height: auto;
        min-width: auto !important;
        box-shadow: none;
        border-radius: 10px;
        width: fit-content !important;
        padding: 15px;
        /* Removed max-height and overflow-y properties to prevent scrolling */
    }

    .category-sidebar.sidebar-menu a {
        color: white;
        transition: background-color 0.2s;
        display: flex;
        align-items: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .category-sidebar.sidebar-menu a:hover {
        background-color: #303030 !important;
        color: white;
    }

    .category-sidebar .list-group-item {
        border: none !important;
        border-radius: 10px !important;
        background-color: #414141 !important;
        padding-top: 1px !important;
        padding-bottom: 1px !important;
    }    /* Icon spacing */
    .category-sidebar.sidebar-menu i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }

</style>

<nav class="sidebar-menu category-sidebar col-12 p-3 mt-3">
    <?php if (isset($errorMsg) && $errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
    <?php elseif (isset($categories) && $categories): ?>
        <div class="list-group">
            <?php foreach ($categories as $cat): ?>
                <?php
                if (!is_array($cat) || !isset($cat['id']) || !isset($cat['name']))
                    continue;
                $iconClass = $icons[$cat['name']] ?? 'fas fa-folder';
                ?>
                <a href="index.php?page=products&category=<?= urlencode($cat['code'] ?? $cat['name']) ?>"
                   class="list-group-item list-group-item-action">
                    <i class="<?= $iconClass ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Không có danh mục nào</p>
    <?php endif; ?>
</nav>

<!-- Popup Container -->
<!-- Sidebar Menu JavaScript -->
<script>
    function openSidebar() {
        // If we need fresh data, we can fetch it here
        // But for now, we'll just clone and display the existing sidebar
        const popup = document.getElementById('sidebarPopup');
        const popupContent = document.getElementById('sidebarPopupContent');
        
        // If we're on the home page, copy the sidebar content
        const homeSidebar = document.querySelector('.home-sidebar .sidebar-menu');
        if (homeSidebar) {
            popupContent.innerHTML = '';
            popupContent.appendChild(homeSidebar.cloneNode(true));
        } else {
            // If not on home page, the sidebar content is loaded via AJAX
            fetch('components/sidebar/load-sidebar.php')
                .then(response => response.text())
                .then(html => {
                    popupContent.innerHTML = html;
                })
                .catch(error => {
                    popupContent.innerHTML = '<div class="alert alert-danger p-3">Không thể tải menu</div>';
                    console.error('Error loading sidebar:', error);
                });
        }
        
        popup.style.display = 'block';
    }

    function closeSidebar() {
        const popup = document.getElementById('sidebarPopup');
        popup.style.display = 'none';
    }

    // Close sidebar when clicking outside of it
    document.addEventListener('click', function(event) {
        const popup = document.getElementById('sidebarPopup');
        const popupContent = document.getElementById('sidebarPopupContent');
        
        if (popup.style.display === 'block' && 
            !popupContent.contains(event.target) && 
            !event.target.classList.contains('menu-toggle-btn') &&
            !event.target.classList.contains('close-btn')) {
            closeSidebar();
        }
    });
</script>