<?php
require_once __DIR__ . '/../includes/session_init.php';

// Get token from session
$token = $_SESSION['token'] ?? null;

if (!$token) {
    header('Location: not-logged-in.php');
    exit;
}

// Get selected items from session storage (passed from cart page)
// Initialize variables for form data
$customerName = $_SESSION['user']['fullName'] ?? '';
$customerEmail = $_SESSION['user']['email'] ?? '';
$customerPhone = '';
$shippingAddress = '';
$notes = '';
$shippingFee = 0;
$selectedItems = [];

// Check if 'buyNow' parameter is set
if (isset($_GET['buyNow']) && $_GET['buyNow'] === 'true' && isset($_GET['itemId'])) {
    $itemId = $_GET['itemId'];
    $requestedQuantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($requestedQuantity < 1)
        $requestedQuantity = 1;

    // Call API to get product details directly
    $apiUrl = 'http://localhost:5000/api/products/' . $itemId;
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

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $product = $data['data'] ?? null;

        if ($product) {
            $productId = $product['id'] ?? '';
            $productName = $product['name'] ?? '';

            // Handle cases where price is an array
            if (is_array($product['price'])) {
                $productPrice = $product['price']['currentPrice'] ?? 0;
            } else {
                $productPrice = $product['price'] ?? 0;
            }

            // Handle cases where image URL is nested or directly available
            if (isset($product['productInfo']['imageUrl'])) {
                $imageUrl = $product['productInfo']['imageUrl'];
            } elseif (isset($product['imageUrl'])) {
                $imageUrl = $product['imageUrl'];
            } else {
                $imageUrl = 'https://via.placeholder.com/150';
            }

            $totalPrice = $productPrice * $requestedQuantity;

            $selectedItems[] = [
                'itemId' => $productId,
                'itemType' => 'Product',
                'quantity' => $requestedQuantity,
                'name' => $productName,
                'price' => $productPrice,
                'imageUrl' => $imageUrl,
                'totalPrice' => $totalPrice
            ];
        } else {
            echo "Product not found.";
            exit;
        }
    } else {
        echo "Unable to retrieve product details. Error code: $httpCode";
        exit;
    }
} else if (isset($_GET['items'])) {
    $itemIds = explode(',', $_GET['items']);

    // Get quantity from URL parameter if provided
    $requestedQuantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($requestedQuantity < 1)
        $requestedQuantity = 1;

    // Call API to get cart to retrieve selected items details
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

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $cartItems = $data['data']['items'] ?? [];
        foreach ($cartItems as $item) {
            if (in_array($item['itemId'], $itemIds)) {
                // Use requested quantity if available and directly buying from product page
                $quantity = isset($requestedQuantity) && count($itemIds) == 1 ? $requestedQuantity : $item['quantity'];
                $totalPrice = $item['price'] * $quantity;

                $selectedItems[] = [
                    'itemId' => $item['itemId'],
                    'itemType' => 'Product',
                    'quantity' => $quantity,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'imageUrl' => $item['imageUrl'],
                    'totalPrice' => $totalPrice
                ];
            }
        }
    } else {
        echo "Unable to retrieve cart items. Error code: $httpCode";
        exit;
    }
} else {
    // No valid parameters
    header('Location: index.php?page=cart');
    exit;
}

// Calculate order totals
$subtotal = array_sum(array_column($selectedItems, 'totalPrice'));
$total = $subtotal + $shippingFee;

// Handle form submission
$orderSuccess = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $customerName = $_POST['customerName'] ?? '';
    $customerEmail = $_POST['customerEmail'] ?? '';
    $customerPhone = $_POST['customerPhone'] ?? '';
    $shippingAddress = $_POST['shippingAddress'] ?? '';
    $paymentMethod = $_POST['paymentMethod'] ?? 'COD';
    $notes = $_POST['notes'] ?? '';

    // Basic validation
    if (empty($customerName) || empty($customerPhone) || empty($shippingAddress) || empty($customerEmail)) {
        $orderError = 'Please fill in all required fields.';
    } else {
        // Prepare order data for API
        $orderData = [
            'selectedItems' => array_map(function ($item) {
                return [
                    'itemId' => $item['itemId'],
                    'itemType' => 'Product',
                    'quantity' => $item['quantity']
                ];
            }, $selectedItems),
            'paymentMethod' => $paymentMethod,
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
            'shippingAddress' => $shippingAddress,
            'customerEmail' => $customerEmail,
            'notes' => $notes,
            'shippingFee' => $shippingFee
        ];

        // Submit order to API
        $orderApiUrl = 'http://localhost:5000/api/orders/create';
        $ch = curl_init($orderApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($orderData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $responseData = json_decode($response, true);
            $orderSuccess = true;

            // Store order information in session for confirmation page
            $_SESSION['lastOrder'] = [
                'orderId' => $responseData['data']['orderId'] ?? '',
                'orderDate' => date('Y-m-d H:i:s'),
                'total' => $total,
                'items' => $selectedItems
            ];

            // Redirect to order confirmation page
            header('Location: index.php?page=order-confirmation');
            exit;
        } else {
            $responseData = json_decode($response, true);
            $orderError = $responseData['message'] ?? 'Failed to create order. Please try again.';
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Order Information</h3>
                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($orderError): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($orderError) ?></div>
                    <?php endif; ?>

                    <form method="post" id="orderForm">
                        <!-- Customer Information -->
                        <div class="mb-4">
                            <h5>Customer Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customerName" class="form-label">Full Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customerName" name="customerName"
                                        value="<?= htmlspecialchars($customerName) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="customerEmail" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="customerEmail" name="customerEmail"
                                        value="<?= htmlspecialchars($customerEmail) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="customerPhone" class="form-label">Phone Number <span
                                            class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="customerPhone" name="customerPhone"
                                        value="<?= htmlspecialchars($customerPhone) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Information -->
                        <div class="mb-4">
                            <h5>Shipping Information</h5>
                            <div class="mb-3">
                                <label for="shippingAddress" class="form-label">Shipping Address <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="shippingAddress" name="shippingAddress" rows="3"
                                    required><?= htmlspecialchars($shippingAddress) ?></textarea>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <h5>Payment Method</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="paymentCOD"
                                    value="COD" checked>
                                <label class="form-check-label" for="paymentCOD">
                                    Cash On Delivery (COD)
                                </label>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-4">
                            <h5>Additional Notes</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                    placeholder="Any special instructions for delivery or other notes"><?= htmlspecialchars($notes) ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Order Summary</h3>
                </div>
                <div class="card-body">
                    <!-- Selected Items -->
                    <div class="mb-3">
                        <h5>Items (<?= count($selectedItems) ?>)</h5>
                        <?php foreach ($selectedItems as $item): ?>
                            <div class="d-flex align-items-center py-2 border-bottom">
                                <div class="flex-shrink-0" style="width: 50px;">
                                    <img src="<?= htmlspecialchars($item['imageUrl']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted">
                                        Quantity: <?= htmlspecialchars($item['quantity']) ?> x
                                        $<?= number_format($item['price'], 2) ?>
                                    </small>
                                </div>
                                <div class="ms-auto">
                                    <span class="fw-bold">$<?= number_format($item['totalPrice'], 2) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Totals -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee:</span>
                            <span>$<?= number_format($shippingFee, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" form="orderForm" class="btn btn-success btn-lg">Place Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderForm = document.getElementById('orderForm');

        orderForm.addEventListener('submit', function (e) {
            const requiredFields = ['customerName', 'customerEmail', 'customerPhone', 'shippingAddress'];
            let valid = true;

            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
</script>