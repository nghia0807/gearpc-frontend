<style>
/* Sidebar styles */
#adminSidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 220px;
    background: #1a2238; /* deep blue */
    color: #fff;
    z-index: 1040;
    padding-top: 1rem;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 12px rgba(26,34,56,0.08);
}
#adminSidebar .sidebar-header {
    font-size: 1.3rem;
    font-weight: bold;
    padding: 0 1.5rem 1rem 1.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #28304a;
    color: #e0e6ed;
    letter-spacing: 1px;
}
#adminSidebar .nav-link {
    color: #bfc9da;
    padding: 0.75rem 1.5rem;
    border-radius: 0.35rem;
    margin-bottom: 0.25rem;
    transition: background 0.2s, color 0.2s;
    font-weight: 500;
}
#adminSidebar .nav-link.active, #adminSidebar .nav-link:hover {
    background: #28304a;
    color: #f7ca18;
}
#adminSidebar .sidebar-footer {
    margin-top: auto;
    padding: 1rem 1.5rem;
    border-top: 1px solid #28304a;
    background: #20294a;
}
#adminSidebar .btn {
    width: 100%;
    margin-bottom: 0.5rem;
    border-radius: 0.35rem;
    font-weight: 500;
    box-shadow: none;
}
#adminSidebar .btn-outline-info {
    color: #3abff8;
    border-color: #3abff8;
}
#adminSidebar .btn-outline-info:hover, #adminSidebar .btn-outline-info:focus {
    background: #3abff8;
    color: #1a2238;
}
#adminSidebar .btn-outline-light {
    color: #fff;
    border-color: #fff;
}
#adminSidebar .btn-outline-light:hover, #adminSidebar .btn-outline-light:focus {
    background: #f7ca18;
    color: #1a2238;
    border-color: #f7ca18;
}
#adminSidebar .navbar-text {
    display: block;
    margin-bottom: 0.5rem;
    color: #f7ca18;
    font-size: 0.98rem;
    font-weight: 500;
}
body {
    margin-left: 220px;
    background: linear-gradient(120deg, #f7f7ff 0%, #dbeafe 100%);
}
@media (max-width: 768px) {
    #adminSidebar {
        width: 100vw;
        height: auto;
        position: static;
        flex-direction: row;
        padding-top: 0;
        box-shadow: none;
    }
    body {
        margin-left: 0;
    }
}
</style>

<div id="adminSidebar">
    <div class="sidebar-header">
        GearPC Admin
    </div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_categories.php' ? ' active' : '' ?>" href="admin_categories.php">Categories</a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_brands.php' ? ' active' : '' ?>" href="admin_brands.php">Brands</a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_products.php' ? ' active' : '' ?>" href="admin_products.php">Products</a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'admin_gifts.php' ? ' active' : '' ?>" href="admin_gifts.php">Gifts</a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <?php if (isset($_SESSION['user']['fullName'])): ?>
            <span class="navbar-text">
                <?= htmlspecialchars($_SESSION['user']['fullName']) ?>
            </span>
        <?php endif; ?>
        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#createManagerModal">
                Create Manager Account
            </button>
        <?php endif; ?>
        <a href="manage_login.php?logout=1" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</div>

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
            url: 'http://phpbe_app_service:5000/api/admin/create-manager',
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
