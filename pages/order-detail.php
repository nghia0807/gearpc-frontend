<?php
require_once __DIR__ . '/../includes/session_init.php';

// Check login
if (!isset($_SESSION['token'])) {
    // Redirect to login page if not logged in
    header("Location: pages/not-logged-in.php");
    exit;
}

// Get login token from session
$token = $_SESSION['token'];

// Get order ID from URL parameter
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    // Redirect to orders page if no order ID provided
    header("Location: index.php?page=my-orders");
    exit;
}

// Variables to store order details and messages
$order = null;
$errorMessage = "";

// Call API to get the order details
function getOrderDetail($token, $orderId)
{
    $apiUrl = "http://localhost:5000/api/orders/{$orderId}";
    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'Connection error: ' . $error];
    }

    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'API error: HTTP code ' . $httpCode];
    }

    $result = json_decode($response, true);

    if (!$result || !isset($result['success'])) {
        return ['success' => false, 'message' => 'Invalid API response'];
    }

    return $result;
}

// Helper function to get order status badge class
function getOrderStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'processing':
            return 'bg-info text-dark';
        case 'shipped':
            return 'bg-primary';
        case 'delivered':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Helper function to get payment status badge class
function getPaymentStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'paid':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'failed':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Format date helper
function formatOrderDate($dateString)
{
    try {
        $date = new DateTime($dateString);
        return $date->format('M d, Y h:i A');
    } catch (Exception $e) {
        // If date parsing fails, return a fallback
        return 'N/A';
    }
}

// Get order details
$orderResponse = getOrderDetail($token, $orderId);

if ($orderResponse['success'] && isset($orderResponse['data'])) {
    $order = $orderResponse['data'];

    // Extract order properties with fallback values
    $orderId = $order['id'] ?? $order['orderId'] ?? 'N/A';
    $orderDate = $order['createdAt'] ?? $order['orderDate'] ?? date('Y-m-d H:i:s');
    $orderStatus = $order['status'] ?? 'pending';
    $paymentStatus = $order['paymentStatus'] ?? 'pending';
    $totalAmount = $order['totalAmount'] ?? $order['total'] ?? 0;
    $orderItems = $order['orderItems'] ?? $order['items'] ?? [];
    $customerName = $order['customer']['fullName'] ?? $order['customerName'] ?? 'N/A';
    $customerPhone = $order['customer']['phone'] ?? $order['customerPhone'] ?? 'N/A';
    $customerEmail = $order['customer']['email'] ?? $order['customerEmail'] ?? 'N/A';
    $shippingAddress = $order['deliveryAddress'] ?? $order['shippingAddress'] ?? 'N/A';
    $paymentMethod = $order['paymentMethod'] ?? 'N/A';
    $notes = $order['note'] ?? $order['notes'] ?? '';
    $shippingFee = $order['shippingFee'] ?? 0;
} else {
    $errorMessage = $orderResponse['message'] ?? "Could not load order information";
}
?>

<!-- Bootstrap CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<div class="profile-banner bg-dark text-white py-4 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-12 d-flex align-items-center">
                <a href="index.php?page=my-orders" class="text-white me-3">
                    <i class="bi bi-arrow-left-circle-fill fs-4"></i>
                </a>
                <div>
                    <h1 class="mb-0 profile-title">Order Details</h1>
                    <p class="profile-subtitle mb-0">
                        Order #<?= htmlspecialchars($orderId) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container profile-content mb-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <!-- Sidebar menu with hover and active effects -->
            <div class="card profile-sidebar">
                <div class="card-header bg-dark profile-sidebar-header">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-person-lines-fill me-2"></i>Account
                    </h5>
                </div>
                <div class="list-group list-group-flush profile-nav">
                    <a href="index.php?page=profile" class="list-group-item list-group-item-action side-nav-item">
                        <i class="bi bi-person-circle me-2"></i> Personal Information
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                    <a href="index.php?page=my-orders"
                        class="list-group-item list-group-item-action side-nav-item active">
                        <i class="bi bi-box-seam me-2"></i> My Orders
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action side-nav-item" data-bs-toggle="modal"
                        data-bs-target="#logoutConfirmModal">
                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <!-- Show error alert if available -->
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-custom" role="alert">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div class="alert-message">
                            <strong>Error!</strong> <?php echo $errorMessage; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($order): ?>
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Order Information
                        </h5>
                        <div>
                            <span class="badge <?= getOrderStatusBadgeClass($orderStatus) ?> me-2">
                                <?= htmlspecialchars(ucfirst($orderStatus)) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Order Date:</strong></p>
                                <p class="text-muted"><?= formatOrderDate($orderDate) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Payment Method:</strong></p>
                                <p class="mb-0">
                                    <?= htmlspecialchars($paymentMethod) ?>
                                    <span class="badge <?= getPaymentStatusBadgeClass($paymentStatus) ?> ms-2">
                                        <?= htmlspecialchars(ucfirst($paymentStatus)) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person me-2"></i>Customer Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Name:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($customerName) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Phone:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($customerPhone) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Email:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($customerEmail) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1"><strong>Shipping Address:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($shippingAddress) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($notes)): ?>
                            <div class="mt-2 p-3 bg-light rounded">
                                <p class="mb-1"><strong>Notes:</strong></p>
                                <p class="fst-italic mb-0"><?= htmlspecialchars($notes) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-box me-2"></i>Order Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60%">Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <?php
                                        // Get the item name
                                        $itemName = $item['productName'] ?? $item['name'] ?? 'N/A';
                                        // Get the item quantity
                                        $itemQuantity = $item['quantity'] ?? 1;
                                        // Get the item price
                                        $itemPrice = $item['price'] ?? 0;
                                        // Get the item image URL
                                        $itemImageUrl = $item['imageUrl'] ?? '';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($itemImageUrl)): ?>
                                                        <div class="flex-shrink-0 me-3" style="width: 60px; height: 60px;">
                                                            <img src="<?= htmlspecialchars($itemImageUrl) ?>"
                                                                alt="<?= htmlspecialchars($itemName) ?>" class="img-fluid rounded">
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($itemName) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle"><?= htmlspecialchars($itemQuantity) ?></td>
                                            <td class="text-end align-middle">$<?= number_format($itemPrice, 2) ?></td>
                                            <td class="text-end align-middle">
                                                $<?= number_format($itemPrice * $itemQuantity, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">$<?= number_format($totalAmount - $shippingFee, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Shipping Fee:</strong></td>
                                        <td class="text-end">$<?= number_format($shippingFee, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>$<?= number_format($totalAmount, 2) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Logout confirmation modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content logout-modal">
            <div class="modal-header logout-modal-header">
                <h5 class="modal-title" id="logoutConfirmModalLabel">
                    <i class="bi bi-box-arrow-right me-2"></i>Confirm Sign Out
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body logout-modal-body">
                <p>Are you sure you want to sign out?</p>
                <p class="text-muted"><small>You'll need to sign in again to access your account features.</small></p>
            </div>
            <div class="modal-footer logout-modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form method="post" style="margin:0;">
                    <button type="submit" name="logout" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper from CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    /* Profile banner styling */
    .profile-banner {
        background: linear-gradient(135deg, #414345, #232526);
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-top: -20px;
    }
    
    .profile-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .profile-subtitle {
        color: rgba(255, 255, 255, 0.9);
    }
    
    /* Sidebar navigation styling */
    .profile-sidebar {
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform var(--transition-speed);
        animation: fadeInLeft 0.6s;
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

    .side-nav-item:hover .side-nav-arrow,
    .side-nav-item.active .side-nav-arrow {
        opacity: 1;
        transform: translateX(5px);
    }

    /* Alert styling */
    .alert-custom {
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .alert-content {
        display: flex;
        align-items: center;
    }

    .alert-icon {
        font-size: 1.5rem;
        margin-right: 15px;
    }
</style>