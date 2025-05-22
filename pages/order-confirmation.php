<?php
require_once __DIR__ . '/../includes/session_init.php';

// Get token from session
$token = $_SESSION['token'] ?? null;

if (!$token) {
    header('Location: not-logged-in.php');
    exit;
}

// Check if we have order information
if (!isset($_SESSION['lastOrder'])) {
    header('Location: index.php');
    exit;
}

$order = $_SESSION['lastOrder'];
$orderId = $order['orderId'];
$orderDate = $order['orderDate'];
$total = $order['total'];
$items = $order['items'];
?>


<div class="container my-5 justify-content-center min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-auto">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 w-100" style="width: fit-content;" :>
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <h2 class="mt-3">Order Placed Successfully!</h2>
                        <p class="text-muted">Thank you for your purchase.</p>
                    </div>

                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order ID:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($orderId) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1"><strong>Order Date:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($orderDate) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Order Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0" style="width: 40px;">
                                                        <img src="<?= htmlspecialchars($item['imageUrl']) ?>"
                                                            alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid">
                                                    </div>
                                                    <div class="ms-3">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td class="text-end">$<?= number_format($item['totalPrice'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>$<?= number_format($total, 2) ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <p class="mb-4 text-muted">We'll send an email confirmation with details and tracking info.
                        </p>
                        <div class="d-grid gap-2 d-md-block">
                            <a href="index.php" class="btn btn-outline-success me-md-2">Continue Shopping</a>
                            <a href="index.php?page=my-orders" class="btn btn-success">My Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Clear the order session data to prevent refreshing the confirmation page
unset($_SESSION['lastOrder']);
?>