<?php
session_name('user_session');
session_start();

if (isset($_SESSION['last_add_time']) && time() - $_SESSION['last_add_time'] < 3) {
    $_SESSION['message'] = "⏳ Vui lòng đợi vài giây trước khi thêm tiếp.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if (!isset($_SESSION['token']) || !isset($_POST['product_id'])) {
    $_SESSION['message'] = "Bạn chưa đăng nhập hoặc thiếu sản phẩm.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$_SESSION['last_add_time'] = time();

$token = $_SESSION['token'];
$productId = htmlspecialchars($_POST['product_id'], ENT_QUOTES, 'UTF-8');
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

$apiUrl = 'http://localhost:5000/api/carts/add';

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
    $_SESSION['message'] = "Lỗi khi gọi API: $curlErr";
} elseif ($httpCode === 200) {
    $_SESSION['message'] = "Đã thêm sản phẩm vào giỏ hàng!";
} else {
    $_SESSION['message'] = "Thêm sản phẩm thất bại. Mã lỗi: $httpCode";
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
