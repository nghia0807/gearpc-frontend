<?php
session_name('user_session');
session_start();

// Lấy token từ session
$token = $_SESSION['token'] ?? null;

if (!$token) {
    echo "Bạn chưa đăng nhập.";
    exit;
}

// Gọi API để lấy giỏ hàng
$apiUrl = 'http://localhost:5000/api/carts/get';
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Không thể lấy giỏ hàng. Mã lỗi: $httpCode";
    exit;
}

$data = json_decode($response, true);
$cartItems = $data['data']['items'] ?? [];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - GearPC</title>
    <style>
        .cart-container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            background-color: #fdfdfd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .cart-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ff9620;
            padding-bottom: 10px;
        }

        .clear-cart-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .clear-cart-btn:hover {
            background-color: #d43f3f;
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            gap: 15px;
            flex-wrap: wrap;
        }

        .cart-item-image img {
            width: 80px;
            height: auto;
            object-fit: contain;
            border-radius: 4px;
            background-color: #fff;
            padding: 5px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .cart-item-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            flex: 2;
        }

        .cart-item-price,
        .cart-item-total {
            font-size: 14px;
            color: #555;
            flex: 1;
            text-align: center;
        }

        .cart-item-quantity {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .quantity-form {
            display: inline-flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-btn {
            background-color: #ff9620;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 16px;
        }

        .quantity-btn:hover {
            background-color: #e0851c;
        }

        .quantity-form input[type="number"] {
            width: 50px;
            border: none;
            text-align: center;
            font-size: 14px;
            background-color: #f8f8f8;
            pointer-events: none;
        }

        .remove-form {
            flex: 0.5;
            text-align: right;
        }

        .remove-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background-color: #d43f3f;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .cart-item-price,
            .cart-item-total,
            .cart-item-quantity,
            .remove-form {
                text-align: left;
            }

            .remove-form {
                margin-top: 10px;
            }
        }
    </style>

</head>

<body>
    <?php include '../includes/header.php';
    include '../includes/navbar.php'; ?>
    <div class="cart-container">
        <div class="cart-header-title">
            <h2>🛒 Giỏ hàng của bạn</h2>
            <form method="POST" action="/gearpc-frontend/actions/clear-cart.php">
                <button type="submit" class="clear-cart-btn">Xóa tất cả</button>
            </form>
        </div>

        <div class="cart-header cart-item" style="font-weight: bold; background-color: #f2f2f2;">
            <div class="cart-item-image">Ảnh</div>
            <div class="cart-item-name">Tên sản phẩm</div>
            <div class="cart-item-price">Đơn giá</div>
            <div class="cart-item-quantity">Số lượng</div>
            <div class="cart-item-total">Thành tiền</div>
            <div class="remove-form">Xóa</div>
        </div>

        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <a href="/gearpc-frontend/pages/product-detail.php?id=<?= htmlspecialchars($item['itemId']) ?>">
                        <img src="<?= htmlspecialchars($item['imageUrl']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    </a>
                </div>
                <div class="cart-item-name">
                    <a href="/gearpc-frontend/pages/product-detail.php?id=<?= htmlspecialchars($item['itemId']) ?>" style="text-decoration: none; color: inherit;">
                        <?= htmlspecialchars($item['name']) ?>
                    </a>
                </div>
                <div class="cart-item-price"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
                <div class="cart-item-quantity">
                    <form action="/gearpc-frontend/actions/update-cart.php" method="POST" class="quantity-form">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['itemId']) ?>">
                        <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                        <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" readonly>
                        <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
                    </form>
                </div>
                <div class="cart-item-total"><?= number_format($item['totalPrice'], 0, ',', '.') ?> ₫</div>
                <form action="/gearpc-frontend/actions/remove-cart-item.php" method="POST" class="remove-form">
                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['itemId']) ?>">
                    <button type="submit" class="remove-btn" title="Xóa sản phẩm">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>