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

// Set default pagination values
$pageIndex = isset($_GET['pageIndex']) ? max(0, intval($_GET['pageIndex'])) : 0;
$pageSize = 5;

// Variables to store orders and messages
$orders = [];
$totalOrders = 0;
$totalPages = 0;
$errorMessage = "";
$successMessage = "";

// Check for messages from previous actions
if (isset($_SESSION['orders_success'])) {
    $successMessage = $_SESSION['orders_success'];
    unset($_SESSION['orders_success']);
}
if (isset($_SESSION['orders_error'])) {
    $errorMessage = $_SESSION['orders_error'];
    unset($_SESSION['orders_error']);
}

// Call API to get user's orders
function getUserOrders($token, $pageIndex, $pageSize)
{
    $apiUrl = "http://localhost:5000/api/orders/user?pageIndex={$pageIndex}&pageSize={$pageSize}";
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

// Get user's orders
$ordersResponse = getUserOrders($token, $pageIndex, $pageSize);

// Debug: Log API response structure if needed (disable in production)
$debugMode = false; // Set to true to see response structure
if ($debugMode) {
    error_log('MY ORDERS API RESPONSE: ' . json_encode($ordersResponse));
}

if ($ordersResponse['success'] && isset($ordersResponse['data'])) {
    $orders = $ordersResponse['data']['data'] ?? [];
    $totalOrders = $ordersResponse['data']['totalCount'] ?? 0;
    $totalPages = ceil($totalOrders / $pageSize);

    // Debug: Log first order structure to understand exactly what we're getting
    if ($debugMode && !empty($orders) && isset($orders[0])) {
        error_log('FIRST ORDER STRUCTURE: ' . json_encode($orders[0]));
    }
} else {
    $errorMessage = $ordersResponse['message'] ?? "Could not load order information";
}

// Helper function to get order status badge class
function getOrderStatusBadgeClass($status): string
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
function getPaymentStatusBadgeClass($status): string
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
function formatOrderDate($dateString): string
{
    try {
        $date = new DateTime($dateString);
        // Set timezone to UTC+7 (Vietnam timezone)
        $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        // Vietnamese date format: DD/MM/YYYY HH:MM
        return $date->format('d/m/Y H:i');
    } catch (Exception $e) {
        // If date parsing fails, return a fallback
        return 'N/A';
    }
}
?>


<style>
    /* CSS color variables and parameters */
    :root {
        --border-radius: 8px;
        --transition-speed: 0.25s;
    }

    /* Profile banner styling */
    .profile-banner {
        background: linear-gradient(135deg, #414345, #232526);
        border-radius: 0 0 10px 10px;
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

    /* Profile content container */
    .profile-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Sidebar navigation styling */
    .profile-sidebar {
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        width: fit-content;
        min-width: 100%;
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

    /* Order card styling */
    .order-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .order-card:hover {
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .order-card .card-header {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .order-items-preview {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 10px;
        -ms-overflow-style: none;
        /* Hide scrollbar in IE and Edge */
        scrollbar-width: none;
        /* Hide scrollbar in Firefox */
    }

    .order-items-preview::-webkit-scrollbar {
        display: none;
    }

    .order-item-preview {
        width: 120px;
        display: inline-block;
        margin-right: 10px;
        vertical-align: top;
    }

    .order-item-image {
        height: 120px;
        width: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .order-item-image img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .no-image-placeholder {
        height: 120px;
        width: 120px;
        background-color: #eee;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .no-image-placeholder i {
        font-size: 2rem;
        opacity: 0.5;
    }

    .more-items {
        width: 80px;
        height: 120px;
        overflow: visible;
    }

    /* Custom button styles */
    .btn-primary {
        background-color: #ff9620 !important;
        color: white !important;
        border-color: #ff9620 !important;
    }

    .btn-primary:hover {
        background-color: #e0851c !important;
        border-color: #e0851c !important;
    }

    .btn-primary:focus,
    .btn-primary:focus-visible,
    .btn-primary:active:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    /* Custom pagination styles */
    .pagination .page-item.active .page-link {
        background-color: #ffa33a !important;
        border-color: #ffa33a !important;
        color: white !important;
    }

    .pagination .page-item .page-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 163, 58, 0.25);
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d !important;
        border-color: #dee2e6 !important;
    }

    /* Media queries for responsive design */    @media (max-width: 991px) {
        .profile-sidebar {
            min-width: 100% !important;
            width: 100% !important;
            margin-bottom: 20px;
        }
        
        .side-nav-item {
            padding: 10px 16px;
        }
        
        .order-card .card-body {
            padding: 15px 10px;
        }
    }
    
    @media (max-width: 767px) {
        .order-item-preview {
            width: 100px;
        }

        .order-item-image {
            height: 100px;
            width: 100px;
        }

        .order-total {
            margin-top: 15px;
            text-align: left !important;
        }

        .col-md-4 {
            flex-direction: row !important;
            justify-content: space-between !important;
            margin-top: 15px;
        }
        
        .profile-title {
            font-size: 2rem;
        }
        
        .order-items-preview {
            margin-bottom: 15px;
        }
    }
    
    @media (max-width: 576px) {
        .order-card .card-header {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .order-card .card-header div:last-child {
            margin-top: 10px;
        }
        
        .profile-content {
            padding: 15px 10px;
        }
        
        .order-item-preview {
            width: 80px;
        }
        
        .order-item-image {
            height: 80px;
            width: 80px;
        }
    }
</style>


<!-- Bootstrap CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<div class="profile-banner bg-dark text-white py-4 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-12">
                <h1 class="mb-0 profile-title">My Orders</h1>
                <p class="profile-subtitle mb-0">
                    View and track your order history
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid profile-content mb-5">
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
                    <a href="/index.php?page=profile" class="list-group-item list-group-item-action side-nav-item">
                        <i class="bi bi-person-circle me-2"></i> Personal Information
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                    <a href="/index.php?page=my-orders"
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
        </div>                <div class="col-lg-9">
            <!-- Show alerts if available -->
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

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show alert-custom" role="alert">
                    <div class="alert-content">
                        <i class="bi bi-check-circle-fill alert-icon"></i>
                        <div class="alert-message">
                            <strong>Success!</strong> <?php echo $successMessage; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Orders list -->
            <div class="order-history-container">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>Order History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-bag-x" style="font-size: 3rem; color: #ccc;"></i>
                                <h5 class="mt-3">No Orders Found</h5>
                                <p class="text-muted">You haven't placed any orders yet.</p>
                                <a href="/index.php?page=products" class="btn btn-primary mt-2">
                                    <i class="bi bi-cart-plus me-2"></i>Start Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($orders as $order): ?>
                                    <?php                                    // Get order properties with fallback values
                                            $orderId = $order['id'] ?? $order['orderId'] ?? 'N/A';
                                            $orderDate = $order['createdAt'] ?? $order['orderDate'] ?? date('Y-m-d H:i:s');
                                            $orderStatus = $order['status'] ?? 'pending';
                                            $paymentStatus = $order['paymentStatus'] ?? 'pending';

                                            // Check for total amount with more detailed fallbacks and proper validation
                                            if (isset($order['totalAmount']) && is_numeric($order['totalAmount'])) {
                                                $totalAmount = $order['totalAmount'];
                                            } elseif (isset($order['total']) && is_numeric($order['total'])) {
                                                $totalAmount = $order['total'];
                                            } elseif (isset($order['amount']) && is_numeric($order['amount'])) {
                                                $totalAmount = $order['amount'];
                                            } else {
                                                // Calculate from items if no valid total found
                                                $totalAmount = 0;
                                                $orderItems = $order['orderItems'] ?? $order['items'] ?? [];
                                                foreach ($orderItems as $item) {
                                                    $itemPrice = $item['price'] ?? 0;
                                                    $itemQuantity = $item['quantity'] ?? 1;
                                                    $totalAmount += ($itemPrice * $itemQuantity);
                                                }
                                                // Add shipping fee if available
                                                $totalAmount += isset($order['shippingFee']) && is_numeric($order['shippingFee']) ? $order['shippingFee'] : 0;
                                            }

                                            $orderItems = $order['orderItems'] ?? $order['items'] ?? [];

                                            // Get the first 3 items to display as previews
                                            $previewItems = array_slice($orderItems, 0, 3);
                                            $remainingItemsCount = count($orderItems) - count($previewItems);
                                            ?>
                                    <div class="col-12 mb-4">
                                        <div class="card order-card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                                <div>
                                                    <h6 class="mb-0">Order #<?= htmlspecialchars($orderId) ?></h6>
                                                    <small class="text-muted"><?= formatOrderDate($orderDate) ?></small>
                                                </div>
                                                <div class="d-flex">
                                                    <span
                                                        class="badge <?= getOrderStatusBadgeClass($orderStatus) ?> me-2 bg-success">
                                                        <?= htmlspecialchars(ucfirst($orderStatus)) ?>
                                                    </span>
                                                    <span class="badge <?= getPaymentStatusBadgeClass($paymentStatus) ?>">
                                                        <?= htmlspecialchars(ucfirst($paymentStatus)) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <!-- Order items preview -->
                                                    <div class="col-md-8 d-flex flex-row order-items-preview">
                                                        <?php foreach ($previewItems as $item): ?>
                                                            <?php
                                                            $itemName = $item['productName'] ?? $item['name'] ?? 'N/A';
                                                            $itemQuantity = $item['quantity'] ?? 1;
                                                            $itemImageUrl = $item['imageUrl'] ?? '';
                                                            ?>
                                                            <div class="order-item-preview me-3">
                                                                <div class="order-item-image mb-2">
                                                                    <?php if (!empty($itemImageUrl)): ?>
                                                                        <img src="<?= htmlspecialchars($itemImageUrl) ?>"
                                                                            alt="<?= htmlspecialchars($itemName) ?>"
                                                                            class="img-fluid rounded">
                                                                    <?php else: ?>
                                                                        <div
                                                                            class="no-image-placeholder rounded d-flex align-items-center justify-content-center">
                                                                            <i class="bi bi-image text-muted"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <p class="mb-0 small fw-bold text-truncate"
                                                                    title="<?= htmlspecialchars($itemName) ?>">
                                                                    <?= htmlspecialchars($itemName) ?>
                                                                </p>
                                                                <p class="mb-0 small text-muted">Quantity: <?= $itemQuantity ?></p>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if ($remainingItemsCount > 0): ?>
                                                            <div
                                                                class="order-item-preview more-items d-flex align-items-center justify-content-center">
                                                                <span class="badge bg-secondary">
                                                                    +<?= $remainingItemsCount ?> more
                                                                    <?= $remainingItemsCount === 1 ? 'item' : 'items' ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Order summary -->
                                                    <div
                                                        class="col-md-4 d-flex flex-column justify-content-between align-items-end">
                                                        <div class="order-total mb-3 text-end">
                                                            <span class="fw-bold">Total:</span>
                                                            <h5 class="mb-0 text-success">
                                                                $<?= number_format((float) $totalAmount, 2) ?></h5>
                                                        </div>
                                                        <a href="/index.php?page=order-detail&id=<?= $orderId ?>"
                                                            class="btn btn-outline-dark">
                                                            <i class="bi bi-eye me-1"></i>View Details
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div> <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="d-flex justify-content-center mt-4">
                                    <nav aria-label="Orders pagination">
                                        <ul class="pagination">
                                            <!-- First page button -->
                                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=my-orders&pageIndex=0" aria-label="First"
                                                    style="color: #ffa33a; border-color: #ffa33a;">
                                                    <i class="bi bi-chevron-double-left"></i>
                                                </a>
                                            </li>

                                            <!-- Previous page button -->
                                            <li class="page-item <?= ($pageIndex <= 0) ? 'disabled' : '' ?>">
                                                <a class="page-link"
                                                    href="?page=my-orders&pageIndex=<?= max(0, $pageIndex - 1) ?>"
                                                    aria-label="Previous" style="color: #ffa33a; border-color: #ffa33a;">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>

                                            <!-- Page numbers -->
                                            <?php
                                            $startPage = max(0, min($pageIndex - 2, $totalPages - 5));
                                            $endPage = min($startPage + 4, $totalPages - 1);
                                            if ($endPage - $startPage < 4) {
                                                $startPage = max(0, $endPage - 4);
                                            }
                                            ?>         <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?= ($i == $pageIndex) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=my-orders&pageIndex=<?= $i ?>"
                                                        style="<?= ($i == $pageIndex) ? 'background-color: #ffa33a; border-color: #ffa33a; color: white;' : 'color: #ffa33a; border-color: #ffa33a;' ?>"><?= $i + 1 ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <!-- Next page button -->
                                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                                <a class="page-link"
                                                    href="?page=my-orders&pageIndex=<?= min($totalPages - 1, $pageIndex + 1) ?>"
                                                    aria-label="Next" style="color: #ffa33a; border-color: #ffa33a;">
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>

                                            <!-- Last page button -->
                                            <li class="page-item <?= ($pageIndex >= $totalPages - 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=my-orders&pageIndex=<?= $totalPages - 1 ?>"
                                                    aria-label="Last" style="color: #ffa33a; border-color: #ffa33a;">
                                                    <i class="bi bi-chevron-double-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modals -->
<?php foreach ($orders as $order): ?>
    <?php    // Get the order ID using fallback logic
        $orderId = $order['id'] ?? $order['orderId'] ?? 'no-id';
        // Get the order date using fallback logic
        $orderDate = $order['createdAt'] ?? $order['orderDate'] ?? date('Y-m-d H:i:s');
        // Get the order status using fallback logic
        $orderStatus = $order['status'] ?? 'pending';
        // Get the payment status using fallback logic
        $paymentStatus = $order['paymentStatus'] ?? 'pending';

        // Get the total amount using improved fallback logic
        if (isset($order['totalAmount']) && is_numeric($order['totalAmount'])) {
            $totalAmount = $order['totalAmount'];
        } elseif (isset($order['total']) && is_numeric($order['total'])) {
            $totalAmount = $order['total'];
        } elseif (isset($order['amount']) && is_numeric($order['amount'])) {
            $totalAmount = $order['amount'];
        } else {
            // Calculate from items if no valid total found
            $totalAmount = 0;
            $itemsList = $order['orderItems'] ?? $order['items'] ?? [];
            foreach ($itemsList as $item) {
                $itemPrice = $item['price'] ?? 0;
                $itemQuantity = $item['quantity'] ?? 1;
                $totalAmount += ($itemPrice * $itemQuantity);
            }
            // Add shipping fee if available
            $totalAmount += isset($order['shippingFee']) && is_numeric($order['shippingFee']) ? $order['shippingFee'] : 0;
        }

        // Get the order items using fallback logic
        $orderItems = $order['orderItems'] ?? $order['items'] ?? [];
        // Get customer information
        $customerName = $order['customer']['fullName'] ?? $order['customerName'] ?? 'N/A';
        $customerPhone = $order['customer']['phone'] ?? $order['customerPhone'] ?? 'N/A';
        $shippingAddress = $order['deliveryAddress'] ?? $order['shippingAddress'] ?? 'N/A';
        $paymentMethod = $order['paymentMethod'] ?? 'N/A';
        $notes = $order['note'] ?? $order['notes'] ?? '';
        $shippingFee = $order['shippingFee'] ?? 0;
        ?>
    <div class="modal fade" id="orderDetailModal<?= $orderId ?>" tabindex="-1"
        aria-labelledby="orderDetailModalLabel<?= $orderId ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel<?= $orderId ?>">
                        <i class="bi bi-bag-check me-2"></i>Order #<?= htmlspecialchars($orderId) ?> Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Order Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order Date:</strong></p>
                            <p class="text-muted"><?= formatOrderDate($orderDate) ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p>
                                <span class="badge <?= getOrderStatusBadgeClass($orderStatus) ?>">
                                    <?= htmlspecialchars(ucfirst($orderStatus)) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-4">
                        <h6><i class="bi bi-geo-alt me-2"></i>Shipping Address</h6>
                        <p class="mb-0 ms-4">
                            <?= htmlspecialchars($customerName) ?><br>
                            <?= htmlspecialchars($customerPhone) ?><br>
                            <?= htmlspecialchars($shippingAddress) ?>
                        </p>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <h6><i class="bi bi-credit-card me-2"></i>Payment Method</h6>
                        <p class="mb-0 ms-4">
                            <?= htmlspecialchars($paymentMethod) ?>
                            <span class="badge <?= getPaymentStatusBadgeClass($paymentStatus) ?> ms-2">
                                <?= htmlspecialchars(ucfirst($paymentStatus)) ?>
                            </span>
                        </p>
                    </div>

                    <!-- Order Items -->
                    <h6><i class="bi bi-box me-2"></i>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
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
                                                    <div class="flex-shrink-0" style="width: 40px; height: 40px; overflow: hidden;">
                                                        <img src="<?= htmlspecialchars($itemImageUrl) ?>"
                                                            alt="<?= htmlspecialchars($itemName) ?>" class="img-fluid"
                                                            style="object-fit: contain; max-height: 100%; max-width: 100%;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="<?= !empty($itemImageUrl) ? 'ms-3' : '' ?>">
                                                    <?= htmlspecialchars($itemName) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($itemQuantity) ?></td>
                                        <td class="text-end">$<?= number_format($itemPrice, 2) ?></td>
                                        <td class="text-end">$<?= number_format($itemPrice * $itemQuantity, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?= number_format((float) ($totalAmount - $shippingFee), 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping Fee:</strong></td>
                                    <td class="text-end">$<?= number_format($shippingFee, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$<?= number_format((float) $totalAmount, 2) ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Notes -->
                    <?php if (!empty($notes)): ?>
                        <div class="mt-4">
                            <h6><i class="bi bi-chat-left-text me-2"></i>Notes</h6>
                            <p class="mb-0 ms-4 fst-italic">
                                "<?= htmlspecialchars($notes) ?>"
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

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