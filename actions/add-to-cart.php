<?php
session_name('user_session');
session_start();

// Determine if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Receive data via POST method
$inputData = file_get_contents('php://input');
$postData = json_decode($inputData, true);

// Set response header if it's an Ajax request
if ($isAjax) {
    header('Content-Type: application/json');
}

// Check time between additions
if (isset($_SESSION['last_add_time']) && time() - $_SESSION['last_add_time'] < 1) {
    $message = "Please wait a few seconds before adding more.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

// Check login and product data
if (!isset($_SESSION['token'])) {
    $message = "You are not logged in.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message, 'redirect' => '../pages/not-logged-in.php']);
    } else {
        $_SESSION['message'] = $message;
    }
    exit;
}

// Check if it's a PC component build or a single product
$isComponentBuild = false;
$components = [];
$quantity = 1;

if ($isAjax && isset($postData['components'])) {
    // Multiple components from PC Build
    $isComponentBuild = true;
    $components = $postData['components'];
    
    // Validate that we have at least one component
    if (empty($components) || !is_array($components)) {
        echo json_encode(['success' => false, 'message' => 'No valid components found']);
        exit;
    }
} elseif ($isAjax && isset($postData['itemId'])) {
    // Single product via AJAX (follows the API schema)
    $productId = htmlspecialchars($postData['itemId'], ENT_QUOTES, 'UTF-8');
    $quantity = isset($postData['quantity']) ? intval($postData['quantity']) : 1;
} elseif ($isAjax && isset($postData['id'])) {
    // Alternative format for single product via AJAX
    $productId = htmlspecialchars($postData['id'], ENT_QUOTES, 'UTF-8');
    $quantity = isset($postData['quantity']) ? intval($postData['quantity']) : 1;
} elseif (isset($_POST['product_id'])) {
    // Single product via form submission
    $productId = htmlspecialchars($_POST['product_id'], ENT_QUOTES, 'UTF-8');
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
} else {
    $message = "Missing product information.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

$_SESSION['last_add_time'] = time();
$token = $_SESSION['token'];

$apiUrl = 'http://localhost:5000/api/carts/add';

// Process single product or multiple components
if ($isComponentBuild) {
    // Multiple components handling
    $successCount = 0;
    $failures = [];
    
    // Check if components array is empty
    if (empty($components)) {
        $message = "No components selected.";
        $success = false;
    } else {
        foreach ($components as $category => $component) {
            if (!isset($component['id'])) {
                continue; // Skip components without ID
            }
            
            $data = [
                'itemId' => $component['id'],
                'itemType' => 'Product',
                'quantity' => 1
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ],
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);
            
            if ($curlErr) {
                $failures[] = "Error when calling API for {$component['name']}: $curlErr";
            } elseif ($httpCode === 200) {
                $successCount++;
            } else {
                $failures[] = "Failed to add {$component['name']}. Error code: $httpCode";
            }
        }
        
        // Determine overall success
        if ($successCount > 0) {
            $message = "$successCount component(s) added to cart successfully!";
            $success = true;
            if (!empty($failures)) {
                $message .= " Some components couldn't be added.";
            }
        } else {
            $message = "Failed to add components to cart.";
            $success = false;
        }
    }
} else {
    // Single product handling
    $data = [
        'itemId' => $productId,
        'itemType' => 'Product',
        'quantity' => $quantity
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        $message = "Error when calling API: $curlErr";
        $success = false;
    } elseif ($httpCode === 200) {
        $message = "Product added to cart!";
        $success = true;
    } else {
        $message = "Failed to add product. Error code: $httpCode";
        $success = false;
    }
}

if ($isAjax) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
} else {
    $_SESSION['message'] = $message;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
