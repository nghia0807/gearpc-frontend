<?php
// Fetch categories using PHP cURL
$pageIndex = isset($_GET['pageIndex']) ? (int)$_GET['pageIndex'] : 0;
$pageSize = 10;
$apiUrl = "http://localhost:5000/api/categories/get?pageIndex={$pageIndex}&pageSize={$pageSize}";

$categories = [];
$totalCount = 0;
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
            $totalCount = isset($jsonData['data']['totalCount']) ? $jsonData['data']['totalCount'] : 0;
            // Custom order for categories
            $customOrder = [
                'Laptops',
                'PCs',
                'Main, CPU, VGA',
                'Monitors',
                'Keyboards',
                'Mouse + Mouse Pad',
                'Earphones',
                'Sounds'
            ];
            usort($categories, function($a, $b) use ($customOrder) {
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
    'Laptops' => 'bi bi-laptop',
    'PCs' => 'bi bi-pc-display',
    'Main, CPU, VGA' => 'bi bi-cpu',
    'Mouse + Mouse Pad' => 'bi bi-mouse',
    'Sounds' => 'bi bi-speaker',
    'Monitors' => 'bi bi-display',
    'Earphones' => 'bi bi-headphones',
    'Keyboards' => 'bi bi-keyboard',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
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
            color: #ffffff !important;
        }
        .content {
            flex: 1;
        }
        .sidebar-menu {
            background-color: #414141;
            min-height: auto; 
            box-shadow: none; 
            border-radius: 10px;
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
<body>
    <?php 
    include '../includes/header.php'; 
    include '../includes/navbar.php';
    ?>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <nav class="sidebar-menu col-md-3 col-12 p-3 mt-3">
                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                    <?php elseif (!empty($categories)): ?>
                        <div class="list-group">
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                if (!is_array($cat) || !isset($cat['id']) || !isset($cat['name'])) continue;
                                $iconClass = isset($icons[$cat['name']]) ? $icons[$cat['name']] : 'fas fa-folder';
                                ?>
                                <a href="/category/<?php echo $cat['name']; ?>" class="list-group-item list-group-item-action">
                                    <i class="<?php echo $iconClass; ?>"></i> <?php echo $cat['name']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($totalCount > count($categories)): ?>
                            <a href="?pageIndex=<?php echo $pageIndex + 1; ?>" class="btn btn-primary mt-3">Load More</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Không có danh mục nào</p>
                    <?php endif; ?>
                </nav>
                <main class="col-md-9 col-12">
                    <!-- Placeholder main content area -->
                    <div class="p-3">
                        <h2>Sản phẩm nổi bật</h2>
                        <!-- ...existing or sample content... -->
                    </div>
                </main>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>