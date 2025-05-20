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

// Check login and product data
if (!isset($_SESSION['token'])) {
    $message = "You are not logged in.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Validate input data
if (!isset($postData['oldItemId'], $postData['newItemId'], $postData['quantity'])) {
    $message = "Invalid input data.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

$oldItemId = htmlspecialchars($postData['oldItemId'], ENT_QUOTES, 'UTF-8');
$newItemId = htmlspecialchars($postData['newItemId'], ENT_QUOTES, 'UTF-8');
$quantity = intval($postData['quantity']);

$token = $_SESSION['token'];

$apiUrl = 'http://localhost:5000/api/carts/update';

$data = [
    'oldItemId' => $oldItemId,
    'oldItemType' => 'Product',
    'newItemId' => $newItemId,
    'newItemType' => 'Product',
    'quantity' => $quantity
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PUT', // Use PUT method
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
    $message = "Cart updated successfully!";
    $success = true;
} else {
    $message = "Failed to update cart. Error code: $httpCode";
    $success = false;
}

if ($isAjax) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
} else {
    $_SESSION['message'] = $message;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
