<div class="cart-item">
    <div class="cart-item-image">
        <img src="<?= htmlspecialchars($item['imageUrl']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
    </div>
    <div class="cart-item-details">
        <h3 class="cart-item-name"><?= htmlspecialchars($item['name']) ?></h3>
        <p class="cart-item-price">Giá: <?= number_format($item['price'], 0, ',', '.') ?> ₫</p>
        <div class="cart-item-quantity">
            <form action="/gearpc-frontend/actions/update-cart.php" method="POST" class="quantity-form">
                <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['itemId']) ?>">
                <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" readonly>
                <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
            </form>
        </div>
        <p class="cart-item-total">Tổng: <?= number_format($item['totalPrice'], 0, ',', '.') ?> ₫</p>
        <form action="/gearpc-frontend/actions/remove-cart-item.php" method="POST" class="remove-form">
            <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['itemId']) ?>">
            <button type="submit" class="remove-btn">Xóa</button>
        </form>
    </div>
</div>