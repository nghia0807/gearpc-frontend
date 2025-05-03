<?php
// --- get_product_detail.php ---
// Returns product detail as JSON for AJAX requests

session_name('admin_session');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if (!isset($_GET['id']) || !$_GET['id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID sản phẩm']);
    exit;
}

$token = $_SESSION['token'];
$id = preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['id']); // basic sanitization
$apiUrl = "http://localhost:5000/api/products/$id";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối API']);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);
if (!$data || !$data['success'] || $httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => isset($data['message']) ? $data['message'] : 'Không thể tải thông tin sản phẩm'
    ]);
    exit;
}

// Only return the 'data' field for frontend
echo json_encode([
    'success' => true,
    'data' => $data['data']
]);
