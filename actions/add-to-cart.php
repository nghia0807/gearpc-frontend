<?php
session_name('user_session');
session_start();

// Xác định nếu là AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Nhận dữ liệu theo phương thức POST
$inputData = file_get_contents('php://input');
$postData = json_decode($inputData, true);

// Thiết lập response header nếu là Ajax request
if ($isAjax) {
    header('Content-Type: application/json');
}

// Kiểm tra thời gian giữa các lần thêm
if (isset($_SESSION['last_add_time']) && time() - $_SESSION['last_add_time'] < 3) {
    $message = "⏳ Vui lòng đợi vài giây trước khi thêm tiếp.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Kiểm tra đăng nhập và dữ liệu sản phẩm
if (!isset($_SESSION['token'])) {
    $message = "Bạn chưa đăng nhập.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Lấy product ID từ POST form hoặc JSON body
$productId = null;
$quantity = 1;

if ($isAjax && isset($postData['itemId'])) {
    $productId = htmlspecialchars($postData['itemId'], ENT_QUOTES, 'UTF-8');
    $quantity = isset($postData['quantity']) ? intval($postData['quantity']) : 1;
} elseif (isset($_POST['product_id'])) {
    $productId = htmlspecialchars($_POST['product_id'], ENT_QUOTES, 'UTF-8');
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
} else {
    $message = "Thiếu thông tin sản phẩm.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

$_SESSION['last_add_time'] = time();
$token = $_SESSION['token'];

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
    $message = "Lỗi khi gọi API: $curlErr";
    $success = false;
} elseif ($httpCode === 200) {
    $message = "Đã thêm sản phẩm vào giỏ hàng!";
    $success = true;
} else {
    $message = "Thêm sản phẩm thất bại. Mã lỗi: $httpCode";
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
