<?php
/**
 * GearPC Website - Main Entry Point
 * 
 * This file serves as the main entry point for the GearPC website.
 * It redirects users to the home page in the pages directory.
 */

// List of allowed pages for security
$allowedPages = [
    'home' => 'pages/home.php',
    'cart' => 'pages/cart.php',
    'products' => 'pages/products.php',
    'product-detail' => 'pages/product-detail.php',
    'news' => 'pages/news.php',
    'order' => 'pages/order.php',
    'order-confirmation' => 'pages/order-confirmation.php',
    'profile' => 'pages/profile.php',
    // Add more pages here as needed
];

// Determine which page to load (default: home)
$page = $_GET['page'] ?? 'home';
$contentFile = $allowedPages[$page] ?? $allowedPages['home'];

// Buffer the content to extract <head> and <body> parts if needed
ob_start();
include $contentFile;
$pageContent = ob_get_clean();

// Extract <head> content if present
preg_match('/<head>(.*?)<\/head>/is', $pageContent, $headMatches);
$headContent = $headMatches[1] ?? '';

// Extract <style> blocks from <head> for inline styles
preg_match_all('/<style.*?>(.*?)<\/style>/is', $headContent, $styleMatches);
$inlineStyles = implode("\n", $styleMatches[0] ?? []);

// Remove <head>...</head> and <body>...</body> from $pageContent
$pageContent = preg_replace('/<!DOCTYPE html>.*?<body.*?>/is', '', $pageContent);
$pageContent = preg_replace('/<\/body>\s*<\/html>/is', '', $pageContent);
$pageContent = preg_replace('/<head>.*?<\/head>/is', '', $pageContent);

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GearPC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Sticky footer styles -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>
    <!-- Page-specific styles -->
    <?php echo $inlineStyles; ?>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <div class="main-content">
        <?php echo $pageContent; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>