<?php
// Fetch categories using PHP cURL
$apiUrl = "http://localhost:5000/api/categories/get?pageSize=1000"; // Fetch all categories

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
                'Hard Drives & Storage Devices',
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
    'Hard Drives & Storage Devices' => 'bi bi-device-hdd-fill',
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
?>

<head>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Home</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #121212 !important;
            color: #fff !important;
        }

        .content {
            flex: 1;
        }

        .sidebar-menu {
            background-color: #414141;
            min-height: auto;
            max-height: 80vh; /* Set maximum height */
            box-shadow: none;
            border-radius: 10px;
            width: auto !important;
            overflow-y: auto; /* Enable vertical scrolling */
        }

        /* Style the scrollbar */
        .sidebar-menu::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-menu::-webkit-scrollbar-track {
            background: #414141;
            border-radius: 10px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #686868;
            border-radius: 10px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .sidebar-menu a {
            color: white;
            transition: background-color 0.2s;
        }

        .sidebar-menu a:hover {
            background-color: #303030 !important;
            color: white;
        }

        .list-group-item {
            border: none !important;
            border-radius: 10px !important;
            background-color: #414141 !important;
        }
    </style>
</head>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <nav class="sidebar-menu col-12 p-3 mt-3">
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
                <?php elseif ($categories): ?>
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
            <main class="col-md-9 col-12">
                <div class="p-3">
                    <h2>Sản phẩm nổi bật</h2>
                    <!-- ...existing or sample content... -->
                </div>
            </main>
        </div>
    </div>
</div>