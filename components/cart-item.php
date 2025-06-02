<?php
function formatCurrency($amount): string
{
    return '$' . number_format($amount, 2);
}

function renderCartItem($item)
{
    ?>
    <div class="cart-item" data-id="<?= htmlspecialchars($item['itemId']) ?>"
        data-price="<?= htmlspecialchars($item['totalPrice']) ?>">
        <div class="cart-item-checkbox">
            <input type="checkbox" class="custom-checkbox item-checkbox" data-id="<?= htmlspecialchars($item['itemId']) ?>">
        </div>        <div class="cart-item-image">
            <a href="index.php?page=product-detail&id=<?= htmlspecialchars($item['itemId']) ?>">
                <img src="<?= htmlspecialchars($item['imageUrl']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            </a>
        </div>
        <div class="cart-item-name">
            <a href="index.php?page=product-detail&id=<?= htmlspecialchars($item['itemId']) ?>"
                style="text-decoration: none; color: inherit;">
                <?= htmlspecialchars($item['name']) ?>
            </a>
        </div>
        <div class="cart-item-price">
            <?= formatCurrency($item['price']) ?>
        </div>
        <div class="cart-item-quantity">
            <div class="quantity-form" data-item-id="<?= htmlspecialchars($item['itemId']) ?>">
                <button type="button" class="quantity-btn decrease-btn">-</button>
                <input type="number" class="quantity-input" value="<?= htmlspecialchars($item['quantity']) ?>" min="1"
                    readonly>
                <button type="button" class="quantity-btn increase-btn">+</button>
            </div>
        </div>
        <div class="cart-item-total">
            <?= formatCurrency($item['totalPrice']) ?>
        </div>
        <div class="remove-form">
            <button type="button" class="remove-btn remove-single-item" title="Remove product"
                data-id="<?= htmlspecialchars($item['itemId']) ?>">
                <i class="bi bi-trash-fill"></i>
            </button>
        </div>
    </div>
    <?php
}
?>

<script>    document.addEventListener('DOMContentLoaded', function () {
        // JavaScript formatCurrency function to match PHP implementation
        function formatCurrency(amount) {
            return '$' + amount.toFixed(2);
        }

        // Handle quantity controls
        const quantityForms = document.querySelectorAll('.quantity-form');

        quantityForms.forEach(form => {
            const decreaseBtn = form.querySelector('.decrease-btn');
            const increaseBtn = form.querySelector('.increase-btn');
            const quantityInput = form.querySelector('.quantity-input');
            const itemId = form.getAttribute('data-item-id');

            // Function to update cart
            function updateCart(newQuantity) {
                // Prepare payload
                const payload = {
                    "newItemId": itemId,
                    "newItemType": "Product",
                    "oldItemId": itemId,
                    "oldItemType": "Product",
                    "quantity": newQuantity
                };

                // Send PUT request
                fetch('actions/update-cart.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {                    // Update quantity input
                            quantityInput.value = newQuantity;                            // Update total price
                            const cartItem = form.closest('.cart-item');
                            // Extract price from $XX.XX format by removing $ and parsing as float
                            const priceText = cartItem.querySelector('.cart-item-price').textContent;
                            const price = parseFloat(priceText.replace('$', ''));
                            const totalPriceElement = cartItem.querySelector('.cart-item-total');                            // Calculate total price
                            const totalPrice = price * newQuantity;

                            // Format total price using formatCurrency
                            totalPriceElement.textContent = formatCurrency(totalPrice);

                            // Update the data-price attribute to match the new total price
                            cartItem.dataset.price = totalPrice;

                            // Trigger cart total recalculation if needed
                            if (typeof window.updateSelectionUI === 'function') {
                                window.updateSelectionUI();
                            }
                        } else {
                            console.error('Error updating cart:', data.message);
                            alert(data.message || 'Failed to update cart. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            }

            // Decrease quantity
            decreaseBtn.addEventListener('click', function () {
                const currentQuantity = parseInt(quantityInput.value);
                if (currentQuantity > 1) {
                    updateCart(currentQuantity - 1);
                }
            });

            // Increase quantity
            increaseBtn.addEventListener('click', function () {
                const currentQuantity = parseInt(quantityInput.value);
                updateCart(currentQuantity + 1);
            });
        });
    });
</script>