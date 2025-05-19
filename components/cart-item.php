<?php
function renderCartItem($item) {
    ?>
    <div class="cart-item" data-id="<?= htmlspecialchars($item['itemId']) ?>"
        data-price="<?= htmlspecialchars($item['totalPrice']) ?>">
        <div class="cart-item-checkbox">
            <input type="checkbox" class="custom-checkbox item-checkbox"
                data-id="<?= htmlspecialchars($item['itemId']) ?>">
        </div>
        <div class="cart-item-image">
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
            <?= number_format($item['price'], 0, ',', '.') ?> ₫
        </div>
        <div class="cart-item-quantity">
            <form action="actions/update-cart.php" method="POST" class="quantity-form">
                <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['itemId']) ?>">
                <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" readonly>
                <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
            </form>
        </div>
        <div class="cart-item-total">
            <?= number_format($item['totalPrice'], 0, ',', '.') ?> ₫
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
