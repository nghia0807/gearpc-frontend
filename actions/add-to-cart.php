<?php
session_name('user_session');
session_start();

if (!isset($_SESSION['token']) || !isset($_POST['product_id'])) {
    http_response_code(401);
    echo "Bạn chưa đăng nhập hoặc thiếu sản phẩm.";
    exit;
}

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
        'Authorization: ' . 'Bearer ' . $token
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo "Lỗi khi gọi API: $curlErr";
    exit;
}

if ($httpCode === 200) {
    $_SESSION['message'] = 'Product added to cart successfully!';
    header('Location: /gearpc-frontend/pages/products.php');
    exit;
} else {
    echo "Thêm sản phẩm thất bại. Mã lỗi: $httpCode<br>";
    echo "Phản hồi: $response";
    exit;
}
