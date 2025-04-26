<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">GearPC Admin</a>
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_categories.php' ? ' active' : '' ?>" href="admin_categories.php">Categories</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_brands.php' ? ' active' : '' ?>" href="admin_brands.php">Brands</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_products.php' ? ' active' : '' ?>" href="admin_products.php">Products</a>
            </li>
        </ul>
        <?php if (isset($_SESSION['admin_user']['fullName'])): ?>
            <span class="navbar-text text-light me-3">
                <?= htmlspecialchars($_SESSION['admin_user']['fullName']) ?>
            </span>
        <?php endif; ?>
        <a href="manage_login.php?logout=1" class="btn btn-outline-light btn-sm">Đăng xuất</a>
    </div>
</nav>
