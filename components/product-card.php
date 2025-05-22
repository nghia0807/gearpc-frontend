<?php
/**
 * Component: Product Card
 * @param array $product
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
        min-height: 520px;
        /* Add fixed minimum height */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
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
        flex-grow: 1;
        display: flex;
        flex-direction: column;
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
        flex-grow: 1;
    }

    .product-action {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: auto;
        padding: 0 1rem;
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
        background-color: #000000;
        color: #e88f2e;
    }

    .btn-buy-now {
        background-color: #ffa33a;
        color: #1e1e1e;
        border: none;
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

    .btn-buy-now:hover {
        background-color: #e88f2e;
        color: #000000;
    }
</style>
<div class="col">
    <div class="product-card">
        <a href="index.php?page=product-detail&id=<?= htmlspecialchars($product['id']) ?>" class="text-decoration-none">
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
                        <?php 
                        $discountPercentage = calculateDiscount($product['originalPrice'], $product['currentPrice']);
                        if ($discountPercentage > 0):
                        ?>
                        <span class="discount-badge">-<?= $discountPercentage ?>%</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="product-description">
                    <?= htmlspecialchars($product['shortDescription'] ?? '') ?>
                </div>
            </div>
        </a>
        <div class="product-action">
            <div class="w-100 d-flex justify-content-between gap-2">
                <button type="button" class="btn-add-cart mb-3" id="addToCart-<?= htmlspecialchars($product['id']) ?>">
                    <i class="bi bi-cart-plus"></i>
                    <span>Add to cart</span>
                </button>
                <button type="button" class="btn-buy-now mb-3" id="buyNow-<?= htmlspecialchars($product['id']) ?>">
                    <i class="bi bi-bag-check"></i>
                    <span>Buy now</span>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Login Modal -->
<div class="modal fade" id="loginConfirmModal" tabindex="-1" aria-labelledby="loginConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title" id="loginConfirmModalLabel"><i class="bi bi-person-circle me-2"></i>Login
                    required</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please login to use this fearture.</p>
            </div>
            <div class="modal-footer border-top border-secondary">
                <a href="pages/login.php" class="btn"
                    style="background-color: #ffa33a; color: #000000; font-weight: 600;">Login</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index: 1080; margin-bottom: 1.5rem; margin-right: 1.5rem; width: max-content; min-width: 300px; max-width: 90vw;">
</div>

<script>
    // Check if user is logged in
    function isUserLoggedIn() {
        return <?= isset($_SESSION['token']) ? 'true' : 'false' ?>;
    }

    // Show login confirmation modal
    function showLoginConfirmModal() {
        const modal = new bootstrap.Modal(document.getElementById('loginConfirmModal'));
        modal.show();
    }

    function addToCartAsync(productId) {
        // Check if user is logged in
        if (!isUserLoggedIn()) {
            showLoginConfirmModal();
            return;
        }

        fetch('actions/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                itemId: productId,
                itemType: 'Product',
                quantity: 1
            })
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'danger');
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while processing your request.', 'danger');
            });
    }

    function buyNowAsync(productId) {
        // Check if user is logged in
        if (!isUserLoggedIn()) {
            showLoginConfirmModal();
            return;
        }

        // Redirect to order page with 'Buy Now' parameters
        window.location.href = 'index.php?page=order&buyNow=true&itemId=' + encodeURIComponent(productId);
    }

    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-delay', '3500');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl);
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    // Initialize event listeners for Add to Cart and Buy Now buttons
    document.addEventListener('DOMContentLoaded', function () {
        // Add event handler for Add to Cart button
        const addToCartBtn = document.getElementById('addToCart-<?= htmlspecialchars($product['id']) ?>');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function () {
                addToCartAsync('<?= htmlspecialchars($product['id']) ?>');
            });
        }

        // Add event handler for Buy Now button
        const buyNowBtn = document.getElementById('buyNow-<?= htmlspecialchars($product['id']) ?>');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function () {
                buyNowAsync('<?= htmlspecialchars($product['id']) ?>');
            });
        }
    });
</script>