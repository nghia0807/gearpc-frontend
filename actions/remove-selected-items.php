<?php
session_name('user_session');
session_start();

// Determine if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Set response header if it's an Ajax request
if ($isAjax) {
    header('Content-Type: application/json');
}

// Check login
if (!isset($_SESSION['token'])) {
    $message = "You are not logged in.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

// Receive data from request
$inputData = file_get_contents('php://input');
$postData = json_decode($inputData, true);

if (empty($postData['itemInfo']) || !is_array($postData['itemInfo'])) {
    $message = "No products selected.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

$token = $_SESSION['token'];
$itemInfo = $postData['itemInfo'];
$successCount = 0;
$errorCount = 0;

// Delete products with new API
$apiUrl = 'http://tamcutephomaique.ddns.net:5001/api/carts/delete';

// Data has been properly formatted from the client
$data = [
    'itemInfo' => $itemInfo
];

// Make a single API call to delete all selected items
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "DELETE",
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Since we're now making a single API call for all items
if ($httpCode === 200) {
    $successCount = count($itemInfo);
    $errorCount = 0;
} else {
    $successCount = 0;
    $errorCount = count($itemInfo);
}

// Return result
if ($successCount > 0) {
    $message = "Removed $successCount products from your cart.";
    if ($errorCount > 0) {
        $message .= " $errorCount products could not be removed.";
    }

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    $message = "Could not remove the selected products.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}
