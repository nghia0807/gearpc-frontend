<?php
/**
 * Component: Product Card
 * @param array $product - Thông tin sản phẩm
 */
if (!isset($product) || empty($product)) {
    return;
}
?>
<style>
    .product-card {
        background-color: #1e1e1e;
        border-radius: 10px;
        overflow: hidden;
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .product-card:hover {
        transform: translateY(-5px);
    }

    .product-img-container {
        background-color: #ffffff;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .product-img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .product-info {
        padding: 1rem;
    }

    .product-brand {
        font-size: 0.8rem;
        color: #6694ea;
        margin-bottom: 0.5rem;
    }

    .product-title {
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #ffffff;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 2.8rem;
    }

    .product-price-current {
        font-weight: 700;
        font-size: 1.1rem;
        color: #ffa33a;
    }

    .product-price-original {
        color: #aaaaaa;
        text-decoration: line-through;
        font-size: 0.9rem;
        margin-left: 0.5rem;
    }

    .discount-badge {
        background-color: #ffa33a;
        color: #000000;
        font-size: 0.8rem;
        padding: 0.1rem 0.4rem;
        border-radius: 4px;
        margin-left: 0.5rem;
        font-weight: 600;
    }

    .product-description {
        color: #cccccc;
        font-size: 0.9rem;
        margin-top: 0.75rem;
        margin-bottom: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 2.6rem;
    }

    .product-action {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-add-cart {
        background-color: #1e1e1e;
        color: #ffa33a;
        border: 2px solid #ffa33a;
        border-radius: 6px;
        padding: 0.5rem;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
        width: calc(100% - 2rem);
        margin: 0 auto;
        display: block;
        text-align: center;
    }

    .btn-add-cart:hover {
        background-color: #e88f2e;
        color: #000000;
    }
</style>
<div class="col">
    <div class="product-card">
        <a href="product-detail.php?id=<?= htmlspecialchars($product['id']) ?>" class="text-decoration-none">
            <div class="product-img-container">
                <img src="<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>" class="product-img"
                    onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
            </div>
            <div class="product-info">
                <div class="product-brand">
                    <?= htmlspecialchars($product['brandName'] ?? 'Unknown Brand') ?>
                </div>
                <h5 class="product-title">
                    <?= htmlspecialchars($product['name']) ?>
                </h5>
                <div class="d-flex align-items-center">
                    <span class="product-price-current"><?= formatCurrency($product['currentPrice']) ?></span>
                    <?php if (!empty($product['originalPrice']) && $product['originalPrice'] > $product['currentPrice']): ?>
                        <span class="product-price-original"><?= formatCurrency($product['originalPrice']) ?></span>
                        <?php $discount = calculateDiscount($product['originalPrice'], $product['currentPrice']); ?>
                        <?php if ($discount > 0): ?>
                            <span class="discount-badge">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="product-description">
                    <?= htmlspecialchars($product['shortDescription'] ?? '') ?>
                </div>
            </div>
        </a>
        <div class="product-action">
            <!-- Form gửi dữ liệu đến add-to-cart.php -->
            <form method="POST" action="../actions/add-to-cart.php" class="w-100">
                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                <button type="submit" class="btn-add-cart mb-4">
                    <i class="bi bi-cart-plus"></i>
                    <span>Thêm vào giỏ hàng</span>
                </button>
            </form>
        </div>
    </div>
</div>