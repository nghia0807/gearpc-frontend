<?php
if (!isset($_SESSION['expiration']) || !is_numeric($_SESSION['expiration']) || $_SESSION['expiration'] < time()) {
    // For development/testing: set session expiration to 1 hour from now if not set or expired
    $_SESSION['expiration'] = time() + 3600;
}
?>
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
        <?php if (isset($_SESSION['user']['fullName'])): ?>
            <span class="navbar-text text-light me-3">
                <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
            </span>
        <?php endif; ?>
        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
            <button type="button" class="btn btn-outline-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#createManagerModal">
                Create Manager Account
            </button>
        <?php endif; ?>
        <a href="manage_login.php?logout=1" class="btn btn-outline-light btn-sm">Đăng xuất</a>
    </div>
</nav>

<?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
<!-- Create Manager Modal -->
<div class="modal fade" id="createManagerModal" tabindex="-1" aria-labelledby="createManagerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="createManagerForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="createManagerModalLabel">Create Manager Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="managerAlert"></div>
          <div class="mb-3">
            <label for="managerUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="managerUsername" name="username" required>
          </div>
          <div class="mb-3">
            <label for="managerPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="managerPassword" name="password" required>
          </div>
          <div class="mb-3">
            <label for="managerEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="managerEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label for="managerFullName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="managerFullName" name="fullName" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
<?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
$(function() {
    $('#createManagerForm').on('submit', function(e) {
        e.preventDefault();
        $('#managerAlert').html('');
        // Client-side validation
        var username = $('#managerUsername').val().trim();
        var password = $('#managerPassword').val().trim();
        var email = $('#managerEmail').val().trim();
        var fullName = $('#managerFullName').val().trim();
        if (!username || !password || !email || !fullName) {
            $('#managerAlert').html('<div class="alert alert-danger">All fields are required.</div>');
            return;
        }
        // Check session expiration (PHP value injected)
        <?php
        $expiration = isset($_SESSION['expiration']) ? (int)$_SESSION['expiration'] : 0;
        ?>
        var expiration = <?= $expiration ?> * 1000;
        if (Date.now() > expiration) {
            $('#managerAlert').html('<div class="alert alert-danger">Session expired. Please log in again.</div>');
            return;
        }
        // Prepare data
        var data = {
            username: username,
            password: password,
            email: email,
            fullName: fullName
        };
        // API call
        $.ajax({
            url: 'http://localhost:5000/api/admin/create-manager',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            headers: {
                'Authorization': 'Bearer <?= htmlspecialchars($_SESSION['token']) ?>'
            },
            success: function(response) {
                $('#managerAlert').html('<div class="alert alert-success">Manager account created successfully.</div>');
                $('#createManagerForm')[0].reset();
            },
            error: function(xhr) {
                var msg = 'Failed to create manager account.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = $('<div>').text(xhr.responseJSON.message).html();
                }
                $('#managerAlert').html('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    });
    // Clear alert on modal open
    $('#createManagerModal').on('show.bs.modal', function() {
        $('#managerAlert').html('');
        $('#createManagerForm')[0].reset();
    });
});
<?php endif; ?>
</script>
