<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../components/cart-item.php';
require_once __DIR__ . '/../components/confirm-modal.php';

// Get token from session
$token = $_SESSION['token'] ?? null;

if (!$token) {
    header('Location: not-logged-in.php');
    exit;
}

// Call API to get cart
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
    echo "Unable to retrieve cart. Error code: $httpCode";
    exit;
}

$data = json_decode($response, true);
$cartItems = $data['data']['items'] ?? [];
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - GearPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .cart-container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            background-color: #fdfdfd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            flex: 1 0 auto;
            overflow-x: auto;
        }

        .cart-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ff9620;
            padding-bottom: 10px;
        }

        .cart-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }

        .btn-danger {
            background-color: #ff4d4d !important;
            color: white !important;
            border-color: #ff4d4d !important;
        }

        .btn-danger:hover {
            background-color: #d43f3f !important;
            border-color: #d43f3f !important;
        }

        .cart-container .btn-primary {
            background-color: #ff9620 !important;
            color: white !important;
            border-color: #ff9620 !important;
        }

        .cart-container .btn-primary:hover {
            background-color: #e0851c !important;
            border-color: #e0851c !important;
        }

        .btn-primary:focus,
        .btn-primary:focus-visible,
        .btn-primary:active:focus {
            outline: none !important;
            box-shadow: none !important;
        }


        .cart-item {
            display: grid;
            grid-template-columns: 40px 100px 2fr 1fr 1fr 1fr 0.5fr;
            align-items: center;
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }

        .cart-header.cart-item {
            background-color: #f2f2f2;
            font-weight: bold;
            border-radius: 6px 6px 0 0;
            padding: 12px 10px;
        }

        .cart-item-checkbox {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #ff9620;
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
        }

        .cart-item-price,
        .cart-item-total {
            font-size: 14px;
            color: #555;
            text-align: center;
        }

        .cart-item-quantity {
            flex: 1;
            display: flex;
        }

        .quantity-form {
            display: inline-flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .quantity-btn {
            background-color: rgb(83, 82, 82);
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: rgb(139, 139, 139);
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
            .cart-container {
                padding: 15px;
                margin: 10px;
                overflow-x: hidden;
            }

            .cart-header-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .cart-header-title h2 {
                margin-bottom: 10px;
                font-size: 20px;
            }

            .cart-actions {
                width: 100%;
                justify-content: space-between;
            }

            .cart-item {
                grid-template-columns: 40px 80px 1fr;
                grid-template-rows: auto auto auto auto;
                gap: 10px;
                padding: 15px 10px;
                border-radius: 8px;
                margin-bottom: 10px;
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .cart-header.cart-item {
                display: none;
                /* Hide the header on mobile */
            }

            .cart-item-price,
            .cart-item-total,
            .cart-item-quantity,
            .remove-form {
                text-align: left;
                grid-column: 3 / span 1;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            }

            .cart-item-price::before {
                content: "Price: ";
                font-weight: 500;
                margin-right: 8px;
                min-width: 60px;
            }

            .cart-item-total::before {
                content: "Total: ";
                font-weight: 500;
                margin-right: 8px;
                min-width: 60px;
                color: #ff9620;
            }

            .cart-item-quantity::before {
                content: "Quantity: ";
                font-weight: 500;
                margin-right: 8px;
                min-width: 60px;
            }

            .cart-item-checkbox {
                grid-row: span 4;
                padding-top: 10px;
            }

            .cart-item-image {
                grid-row: span 4;
            }

            .cart-item-name {
                grid-column: 3;
                margin-bottom: 12px;
                font-weight: bold;
            }

            .cart-summary {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px 15px;
                background-color: #f9f9f9;
                border-radius: 8px;
                margin-top: 20px;
            }

            .cart-summary-text {
                margin-right: 0;
                margin-bottom: 12px;
                width: 100%;
                display: flex;
                justify-content: space-between;
                font-size: 15px;
            }

            .cart-total {
                font-size: 18px;
            }

            .checkout-btn {
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
                height: 48px;
                font-size: 16px;
            }

            .quantity-form {
                height: 36px;
            }

            .remove-form {
                justify-content: flex-end;
                margin-top: 5px;
            }

            .remove-btn {
                width: 36px;
                height: 36px;
                display: flex;
                justify-content: center;
                align-items: center;
                border-radius: 50%;
                background-color: #ffeeee;
                color: #ff4d4d;
            }

            .remove-btn i {
                font-size: 16px;
            }
        }

        /* Small screens */
        @media (max-width: 480px) {
            .cart-container {
                padding: 10px;
                margin: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .cart-item {
                grid-template-columns: 30px 70px 1fr;
                gap: 8px;
                padding: 12px 8px;
            }

            .cart-item-image img {
                width: 60px;
                height: 60px;
            }

            .quantity-form {
                justify-content: flex-start;
                height: 32px;
            }

            .quantity-btn {
                padding: 4px 10px;
                font-size: 14px;
            }

            .quantity-form input[type="number"] {
                width: 40px;
                font-size: 13px;
            }

            .cart-item-name {
                font-size: 14px;
            }

            .cart-item-price,
            .cart-item-total {
                font-size: 13px;
            }

            .cart-item-price::before,
            .cart-item-total::before,
            .cart-item-quantity::before {
                min-width: 50px;
                font-size: 13px;
            }

            .cart-header-title h2 {
                font-size: 18px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }

            .checkout-btn {
                height: 44px;
                font-size: 15px;
            }

            .cart-empty {
                padding: 30px 0;
            }

            .cart-empty i {
                font-size: 50px;
                margin-bottom: 15px;
            }

            .cart-empty h3 {
                font-size: 18px;
            }
        }

        .cart-summary {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .cart-summary-text {
            font-size: 16px;
            margin-right: 20px;
        }

        .cart-total {
            font-weight: bold;
            font-size: 18px;
            color: #ff9620;
        }

        .checkout-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 20px;
            transition: background-color 0.2s ease;
        }

        .checkout-btn:hover {
            background-color: #218838;
        }

        .checkout-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .cart-empty {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }

        .cart-empty i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        /* Sticky footer */
        .footer {
            flex-shrink: 0;
        }
    </style>
</head>
<div class="cart-container">
    <div class="cart-header-title">
        <h2>Your cart</h2>
        <div class="cart-actions">
            <button type="button" id="deleteSelected" class="btn btn-danger" disabled>
                <i class="bi bi-trash"></i> <span class="d-none d-sm-inline">Delete selected</span>
            </button>
        </div>
    </div> <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
            <i class="bi bi-cart-x"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="/index.php?page=products" class="btn btn-primary">Continue Shopping</a>
        </div>
        <script>
            // Hide cart actions when cart is empty
            document.addEventListener('DOMContentLoaded', function () {
                const cartActions = document.querySelector('.cart-actions');
                if (cartActions) {
                    cartActions.style.display = 'none';
                }
            });
        </script>
    <?php else: ?>
        <div class="cart-header cart-item">
            <div class="cart-item-checkbox">
                <input type="checkbox" id="selectAll" class="custom-checkbox">
            </div>
            <div class="cart-item-image">Image</div>
            <div class="cart-item-name">Product name</div>
            <div class="cart-item-price">Price</div>
            <div class="cart-item-quantity">Quantity</div>
            <div class="cart-item-total">Total</div>
            <div class="remove-form">Delete</div>
        </div>
        <?php foreach ($cartItems as $item): ?>
            <?php renderCartItem($item); ?>
        <?php endforeach; ?>

        <div class="cart-summary">
            <div class="cart-summary-text">
                Selected: <span id="selectedCount">0</span> items
            </div>
            <div class="cart-summary-text">
                Total: <span id="selectedTotal" class="cart-total">$0</span>
            </div>
            <button type="button" id="checkoutBtn" class="checkout-btn" disabled>
                Proceed to Checkout
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Toast container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index: 1080; margin-bottom: 1.5rem; margin-right: 1.5rem; width: max-content; min-width: 300px; max-width: 90vw;">
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const deleteSelectedBtn = document.getElementById('deleteSelected');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        const selectedTotalSpan = document.getElementById('selectedTotal');

        // Function to update UI based on selection
        window.updateSelectionUI = function updateSelectionUI() {
            const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            const selectedCount = selectedCheckboxes.length;

            // Update count
            selectedCountSpan.textContent = selectedCount;            // Update total price
            let totalPrice = 0;
            selectedCheckboxes.forEach(checkbox => {
                const itemRow = checkbox.closest('.cart-item');
                // Get the item price from data attribute which should be in numeric format
                const itemPrice = parseFloat(itemRow.dataset.price || 0);
                totalPrice += itemPrice;
            });

            selectedTotalSpan.textContent = formatCurrency(totalPrice);

            // Update button states
            deleteSelectedBtn.disabled = selectedCount === 0;
            checkoutBtn.disabled = selectedCount === 0;

            // Update select all checkbox
            selectAllCheckbox.checked = selectedCount > 0 && selectedCount === itemCheckboxes.length;
        }        // Helper function to format currency
        function formatCurrency(amount) {
            return '$' + amount.toFixed(2);
        }

        // Select All checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;

                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });

                updateSelectionUI();
            });
        }        // Individual checkboxes
        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionUI);
        });        // Single item delete buttons
        document.querySelectorAll('.remove-single-item').forEach(button => {
            button.addEventListener('click', function () {
                const itemId = this.dataset.id;
                // Use custom confirmation modal instead of default browser confirm
                showConfirmModal('Are you sure you want to remove this item from your cart?', function (confirmed) {
                    if (confirmed) {
                        deleteSelectedItems([itemId]); // Reuse the same function with an array of one item
                    }
                }, 'Remove Item');
            });
        });
        // Delete selected items
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', function () {
                const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                    .map(checkbox => checkbox.dataset.id);

                if (selectedIds.length === 0) return;

                // Use custom confirmation modal with appropriate message based on selection count
                const itemCount = selectedIds.length;
                const message = itemCount === 1
                    ? 'Are you sure you want to remove this item from your cart?'
                    : `Are you sure you want to remove these ${itemCount} items from your cart?`;

                showConfirmModal(message, function (confirmed) {
                    if (confirmed) {
                        deleteSelectedItems(selectedIds);
                    }
                }, 'Remove Items');
            });
        }        // Checkout selected items
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function () {
                const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                    .map(checkbox => checkbox.dataset.id);

                if (selectedIds.length === 0) {
                    alert('Please select at least one item to checkout.');
                    return;
                }

                // Redirect to the order page with selected item IDs
                window.location.href = 'index.php?page=order&items=' + selectedIds.join(',');
            });
        }

        // Function to delete selected items via AJAX
        function deleteSelectedItems(itemIds) {
            // Format data according to API requirements
            const itemInfo = itemIds.map(id => ({
                itemId: id,
                itemType: 'Product'
            }));

            fetch('actions/remove-selected-items.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ itemInfo: itemInfo })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showToast(data.message, 'success');

                        // Remove items from DOM
                        itemIds.forEach(id => {
                            const itemRow = document.querySelector(`.cart-item[data-id="${id}"]`);
                            if (itemRow) itemRow.remove();
                        });

                        // Update UI
                        updateSelectionUI();

                        // If cart is empty, reload the page to show empty state
                        const remainingItems = document.querySelectorAll('.cart-item:not(.cart-header)');
                        if (remainingItems.length === 0) {
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        // Show error message
                        showToast(data.message || 'An error occurred', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while processing your request', 'danger');
                });
        }

        // Function to show toast messages
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();

            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
    });
</script>
<!-- Render confirm modal component -->
<?php
renderConfirmModal(
    'deleteConfirmModal',
    'Confirm Removal',
    'Remove',
    'Cancel',
    'btn-danger',
    'btn-secondary'
);
?>

<!-- Link Bootstrap JS at the end of the document -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>