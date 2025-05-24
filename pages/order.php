<?php
require_once __DIR__ . '/../includes/session_init.php';

// Get token from session
$token = $_SESSION['token'] ?? null;

if (!$token) {
    header('Location: not-logged-in.php');
    exit;
}

// Initialize variables for form data
$customerName = $_SESSION['user']['fullName'] ?? '';
$customerEmail = $_SESSION['user']['email'] ?? '';
$customerPhone = '';
$shippingAddress = '';
$notes = '';
$shippingFee = 0;
$selectedItems = [];

/**
 * Handle Buy Now functionality - fetches a single product by ID
 * @param string $itemId The product ID
 * @param int $quantity The quantity to purchase
 * @param string $token The user's authentication token
 * @return array|null The product details if found, null otherwise
 */
function handleBuyNow($itemId, $quantity, $token) {
    // Call API to get product details directly
    $apiUrl = 'http://tamcutephomaique.ddns.net:5001/api/products/' . $itemId;
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
        return [
            'success' => false,
            'error' => "Unable to retrieve product details. Error code: $httpCode"
        ];
    }

    $data = json_decode($response, true);
    $product = $data['data'] ?? null;

    if (!$product) {
        return [
            'success' => false,
            'error' => "Product not found."
        ];
    }

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

    $totalPrice = $productPrice * $quantity;

    return [
        'success' => true,
        'item' => [
            'itemId' => $itemId,
            'itemType' => 'Product',
            'quantity' => $quantity,
            'name' => $productName,
            'price' => $productPrice,
            'imageUrl' => $imageUrl,
            'totalPrice' => $totalPrice
        ]
    ];
}

// Check if 'buyNow' parameter is set - this represents direct purchase from product card or product detail
if (isset($_GET['buyNow']) && $_GET['buyNow'] === 'true' && isset($_GET['itemId'])) {
    $itemId = $_GET['itemId'];
    
    // Get quantity from URL parameter if provided (from product detail page)
    // or default to 1 (from product card)
    $requestedQuantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($requestedQuantity < 1) {
        $requestedQuantity = 1;
    }

    $result = handleBuyNow($itemId, $requestedQuantity, $token);
    
    if (!$result['success']) {
        echo $result['error'];
        exit;
    }
    
    // For Buy Now, we have a single product only
    $selectedItems[] = $result['item'];
} else if (isset($_GET['items'])) {
    /**
     * Handle Cart Checkout functionality - fetches selected items from user's cart
     * @param array $itemIds Array of product IDs to checkout from cart
     * @param int|null $requestedQuantity Optional quantity override for single item purchases
     * @param string $token The user's authentication token  
     * @return array Result with success status and items or error message
     */
    function handleCartCheckout($itemIds, $requestedQuantity, $token): array
    {
        $result = [
            'success' => false,
            'items' => [],
            'error' => ''
        ];
        
        // Call API to get cart to retrieve selected items details
        $apiUrl = 'http://tamcutephomaique.ddns.net:5001/api/carts/get';
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
            $result['error'] = "Unable to retrieve cart items. Error code: $httpCode";
            return $result;
        }

        $data = json_decode($response, true);
        $cartItems = $data['data']['items'] ?? [];
        $selectedItems = [];
        
        // Process only the items that were selected in the cart
        foreach ($cartItems as $item) {
            if (in_array($item['itemId'], $itemIds)) {
                // Use requested quantity if available and only one item selected
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
        
        $result['success'] = true;
        $result['items'] = $selectedItems;
        return $result;
    }

    $itemIds = explode(',', $_GET['items']);

    // Get quantity from URL parameter if provided
    $requestedQuantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    if ($requestedQuantity < 1) {
        $requestedQuantity = 1;
    }

    $result = handleCartCheckout($itemIds, $requestedQuantity, $token);
    
    if (!$result['success']) {
        echo $result['error'];
        exit;
    }
    
    // For cart checkout, we might have multiple items
    $selectedItems = $result['items'];
    
    if (empty($selectedItems)) {
        echo "No items were found in your cart.";
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
        $orderApiUrl = 'http://tamcutephomaique.ddns.net:5001/api/orders/create';
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

        $responseData = json_decode($response, true);
        if ($httpCode === 200 || $httpCode === 201) {
            $orderSuccess = true;            // Store order information in session for confirmation page
            // Set timezone to UTC+7 (Vietnam timezone)
            date_default_timezone_set('Asia/Ho_Chi_Minh');
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
            $orderError = $responseData['message'] ?? 'Failed to create order. Please try again.';
        }
    }
}
?>

<!-- Bootstrap CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* CSS color variables and parameters */
    :root {
        /* Essential custom variables - keep only what Bootstrap doesn't provide */
        --border-radius: 8px;
        --transition-speed: 0.25s;
    }

    /* Banner profile - match with profile.php */
    .profile-banner {
        background: linear-gradient(135deg, #000000 0%, #333333 70%, #555555 100%);
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .profile-title {
        font-weight: 700;
        font-size: 2rem;
    }

    .profile-subtitle {
        font-size: 1rem;
        opacity: 0.9;
    }

    /* Profile content container */
    .profile-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Sidebar menu - match with profile.php */
    .profile-sidebar {
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform var(--transition-speed);
        animation: fadeInLeft 0.6s;
        width: 100%; /* Changed from fit-content to 100% */
        min-width: 100%;
    }

    .side-nav-item {
        border: none !important;
        padding: 12px 16px;
        position: relative;
        transition: all var(--transition-speed);
    }

    .side-nav-item:hover {
        background-color: rgba(52, 152, 219, 0.1) !important;
        color: var(--primary-color) !important;
    }

    .side-nav-item.active {
        background-color: white !important;
        color: black !important;
        font-weight: 600;
    }

    .side-nav-arrow {
        opacity: 0;
        transition: transform var(--transition-speed), opacity var(--transition-speed);
    }

    .side-nav-item:hover .side-nav-arrow {
        opacity: 1;
        transform: translateX(5px);
    }

    /* Stats Card - matching profile.php */
    .profile-stats {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.8s;
        width: 100% !important; 
    }

    .stat-item {
        padding: 10px;
        transition: transform var(--transition-speed);
    }

    .stat-item:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        color: #ffa33a;
        animation: fadeInUp 0.8s;
    }

    /* Alert custom styling */
    .alert-custom {
        border-radius: var(--border-radius);
        animation: fadeInDown 0.5s;
    }

    .alert-content {
        display: flex;
        align-items: center;
    }

    .alert-icon {
        font-size: 1.5rem;
        margin-right: 15px;
    }

    /* Animations - matching profile.php */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInLeft {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes fadeInRight {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

<div class="profile-banner bg-dark text-white py-4 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-12">
                <h1 class="mb-0 profile-title">
                    Complete Your Order
                </h1>
                <p class="profile-subtitle mb-0">
                    <i class="bi bi-cart-check me-1"></i>
                    Review and confirm your purchase
                </p>
            </div>
        </div>
    </div>
</div>

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