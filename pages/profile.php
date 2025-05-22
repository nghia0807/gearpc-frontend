<?php
require_once __DIR__ . '/../includes/session_init.php';

// Check login
if (!isset($_SESSION['token'])) {
    // Redirect to login page if not logged in
    header("Location: index.php?page=login");
    exit;
}

// Get login token from session
$token = $_SESSION['token'];

// Variables to store user info and messages
$user = null;
$errorMessage = "";
$successMessage = "";

// Check for messages from previous actions
if (isset($_SESSION['profile_success'])) {
    $successMessage = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}
if (isset($_SESSION['profile_error'])) {
    $errorMessage = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}

// Call API to get user information
function getUserProfile($token)
{
    $ch = curl_init("http://localhost:5000/api/auth/me");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'Connection error: ' . $error];
    }

    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'API error: HTTP code ' . $httpCode];
    }

    $result = json_decode($response, true);

    if (!$result || !isset($result['success'])) {
        return ['success' => false, 'message' => 'Invalid API response'];
    }

    return $result;
}

// Get user information
$profileResponse = getUserProfile($token);

if ($profileResponse['success'] && isset($profileResponse['data'])) {
    $user = $profileResponse['data'];
} else {
    $errorMessage = $profileResponse['message'] ?? "Could not load user information";
}

// Handle user information update and password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Get form data
        $fullName = trim($_POST['fullName']);
        $email = trim($_POST['email']);
        $newPassword = trim($_POST['newPassword'] ?? '');

        // Check if there are any changes
        $hasFullNameChange = $fullName !== ($user['fullName'] ?? '');
        $hasEmailChange = $email !== ($user['email'] ?? '');
        $hasPasswordChange = !empty($newPassword);

        // Validate form data
        $isValid = true;

        // Email validation
        if ($hasEmailChange && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Invalid email address format";
            $isValid = false;
        }

        // Only proceed if there are changes and validation passed
        if ($isValid && ($hasFullNameChange || $hasEmailChange || $hasPasswordChange)) {
            // Prepare data for API request
            $updateData = [
                'fullName' => $hasFullNameChange ? $fullName : null,
                'email' => $hasEmailChange ? $email : null,
                'password' => $hasPasswordChange ? $newPassword : null,
            ];

            // Send API request
            $ch = curl_init("http://localhost:5000/api/auth/me/update");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            $result = json_decode($response, true);

            if ($result && isset($result['success']) && $result['success']) {
                // Update successful
                $successMessage = "Profile information updated successfully!";

                // Update session data if needed
                if ($hasFullNameChange && isset($_SESSION['user'])) {
                    $_SESSION['user']['fullName'] = $fullName;
                }

                // Reload user data to reflect changes
                $profileResponse = getUserProfile($token);
                if ($profileResponse['success'] && isset($profileResponse['data'])) {
                    $user = $profileResponse['data'];
                }
            } else {
                // Update failed
                $errorMessage = $result['message'] ?? "Profile update failed, please check your information";
            }
        } elseif (!$isValid) {
            // Error message already set during validation
        } else {
            $successMessage = "No changes were detected";
        }
    }
}
?>

<style>
    /* CSS color variables and parameters */
    :root {
        /* Essential custom variables - keep only what Bootstrap doesn't provide */
        --border-radius: 8px;
        --transition-speed: 0.25s;
    }

    /* Banner profile - keep custom gradient */
    .profile-banner {
        background: linear-gradient(135deg, #000000 0%, #333333 70%, #555555 100%);
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .profile-title {
        font-weight: 700;
        font-size: 2rem;
    }

    .profile-subtitle {
        font-size: 1rem;
        opacity: 0.9;
    }

    /* Profile content container */
    .profile-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .user-role {
        font-weight: 600;
        border-radius: 30px;
        padding: 5px 12px;
        animation: fadeInUp 0.6s;
    }

    .user-since {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    /* Sidebar menu - keeping custom animations */
    .profile-sidebar {
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform var(--transition-speed);
        animation: fadeInLeft 0.6s;
        width: fit-content;
        min-width: 100%;
    }

    .side-nav-item {
        border: none !important;
        padding: 12px 16px;
        position: relative;
        transition: all var(--transition-speed);
    }

    .side-nav-item:hover {
        background-color: rgba(52, 152, 219, 0.1) !important;
        color: var(--primary-color) !important;
    }

    .side-nav-item.active {
        background-color: white !important;
        color: black !important;
        font-weight: 600;
    }

    .side-nav-arrow {
        opacity: 0;
        transition: transform var(--transition-speed), opacity var(--transition-speed);
    }

    .side-nav-item:hover .side-nav-arrow {
        opacity: 1;
        transform: translateX(5px);
    }

    /* Stats Card - keeping hover animations */
    .profile-stats {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.8s;
    }

    .stat-item {
        padding: 10px;
        transition: transform var(--transition-speed);
    }

    .stat-item:hover {
        transform: translateY(-5px);
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--primary-color);
    }

    /* Profile Card - keeping animations */
    .profile-card {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: transform var(--transition-speed);
        animation: fadeInRight 0.6s;
    }

    .profile-card-body {
        padding: 25px;
    }

    /* Form styling */
    .profile-input-icon {
        background-color: var(--primary-color) !important;
        color: white;
        border: 0;
    }

    /* Password form styling */
    .password-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .password-input-icon {
        background-color: var(--primary-color) !important;
        color: white;
        border: 0;
    }

    .toggle-password {
        background: transparent !important;
        border: none !important;
        color: var(--primary-color) !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .toggle-password:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .save-profile-btn {
        padding: 12px 30px;
        font-weight: 600;
        transition: all var(--transition-speed);
    }

    .save-profile-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(5, 8, 10, 0.4);
    }

    /* Security tips - keep the custom styling */
    .security-tips-list li i {
        color: var(--primary-color);
    }

    /* Custom Alerts - keeping the animations */
    .alert-custom {
        border-radius: var(--border-radius);
        animation: fadeInDown 0.5s;
    }

    .alert-content {
        display: flex;
        align-items: center;
    }

    .alert-icon {
        font-size: 1.5rem;
        margin-right: 10px;
    }

    /* Animations - keeping all custom animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .profile-title {
            font-size: 1.5rem;
            text-align: center;
        }

        .profile-subtitle {
            text-align: center;
        }
    }

    /* Override Bootstrap styles to match our theme */
    .bg-dark {
        background-color: var(--primary-color) !important;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover,
    .btn-primary:focus {
        background-color: var(--primary-hover);
        border-color: var(--primary-hover);
    }
</style>


<!-- Bootstrap CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons CSS from CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<div class="profile-banner bg-dark text-white py-4 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-12">
                <h1 class="mb-0 profile-title">
                    <?php echo htmlspecialchars($user['fullName'] ?? $user['username'] ?? ''); ?>
                </h1>
                <p class="profile-subtitle mb-1">
                    <span class="badge bg-light text-dark me-2 user-role">
                        <i class="bi bi-shield-check me-1"></i>
                        <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'Customer')); ?>
                    </span>
                    <span class="user-since">
                        <i class="bi bi-calendar-check me-1"></i>
                        Member since
                        <?php echo isset($user['createdAt']) ? date('m/d/Y', strtotime($user['createdAt'])) : ''; ?>
                    </span>
                </p>
                <p class="profile-subtitle mb-0">
                    <i class="bi bi-envelope me-1"></i>
                    <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid profile-content">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <!-- Sidebar menu with hover and active effects -->
            <div class="card profile-sidebar">
                <div class="card-header bg-dark profile-sidebar-header">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-person-lines-fill me-2"></i>Account
                    </h5>
                </div>
                <div class="list-group list-group-flush profile-nav">
                    <a href="#profile" class="list-group-item list-group-item-action side-nav-item active"
                        data-bs-toggle="list">
                        <i class="bi bi-person-circle me-2"></i> Personal Information
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a> <a href="index.php?page=my-orders"
                        class="list-group-item list-group-item-action side-nav-item">
                        <i class="bi bi-box-seam me-2"></i> My Orders
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action side-nav-item" data-bs-toggle="modal"
                        data-bs-target="#logoutConfirmModal">
                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                        <i class="bi bi-chevron-right float-end side-nav-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- User stats card -->
            <div class="card profile-stats mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-graph-up me-2"></i>Activity
                    </h5>
                    <div class="d-flex justify-content-around text-center">
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <!-- Show alerts if available -->
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-custom" role="alert">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div class="alert-message">
                            <strong>Error!</strong> <?php echo $errorMessage; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show alert-custom" role="alert">
                    <div class="alert-content">
                        <i class="bi bi-check-circle-fill alert-icon"></i>
                        <div class="alert-message">
                            <strong>Success!</strong> <?php echo $successMessage; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content">
                <!-- Personal information tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card profile-card">
                        <div class="card-header bg-dark profile-card-header">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-person-vcard me-2"></i>Personal Information
                            </h5>
                        </div>
                        <div class="card-body profile-card-body">
                            <?php if ($user): ?>
                                <form id="profileForm" method="POST" action="">
                                    <div class="row profile-info-container">
                                        <div class="col-md-6 mb-4">
                                            <div class="profile-field">
                                                <label class="profile-field-label" for="username">Username</label>
                                                <div class="input-group profile-input-group">
                                                    <span class="input-group-text profile-input-icon">
                                                        <i class="bi bi-person-badge"></i>
                                                    </span>
                                                    <input type="text" class="form-control profile-input" id="username"
                                                        value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                                        readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <div class="profile-field">
                                                <label class="profile-field-label" for="fullName">Full Name</label>
                                                <div class="input-group profile-input-group">
                                                    <span class="input-group-text profile-input-icon">
                                                        <i class="bi bi-person"></i>
                                                    </span>
                                                    <input type="text" class="form-control profile-input" id="fullName"
                                                        name="fullName"
                                                        value="<?php echo htmlspecialchars($user['fullName'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <div class="profile-field">
                                                <label class="profile-field-label" for="email">Email</label>
                                                <div class="input-group profile-input-group">
                                                    <span class="input-group-text profile-input-icon">
                                                        <i class="bi bi-envelope"></i>
                                                    </span>
                                                    <input type="email" class="form-control profile-input" id="email"
                                                        name="email"
                                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="password-section">
                                        <h4 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Change Password (optional)
                                        </h4>
                                        <p class="text-muted mb-4">Leave this field empty if you don't want to change your
                                            password.</p>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div class="password-field">
                                                    <label for="newPassword" class="form-label password-label">
                                                        <i class="bi bi-key me-2"></i>New Password
                                                    </label>
                                                    <div class="input-group password-input-group">
                                                        <span class="input-group-text password-input-icon">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </span>
                                                        <input type="password" class="form-control password-input"
                                                            id="newPassword" name="newPassword"
                                                            placeholder="Enter new password">
                                                        <button class="btn toggle-password" type="button">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="submit" name="update_profile"
                                            class="btn btn-dark btn-lg save-profile-btn">
                                            <i class="bi bi-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Unable to load user information. Please try again later.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card profile-card mt-4">
                        <div class="card-header bg-dark profile-card-header">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-shield-shaded me-2"></i>Account Security
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="security-tips">
                                <h6><i class="bi bi-info-circle me-2"></i>Security Tips</h6>
                                <ul class="security-tips-list">
                                    <li><i class="bi bi-check-circle-fill me-2"></i>Use strong, unique passwords for
                                        different accounts.</li>
                                    <li><i class="bi bi-check-circle-fill me-2"></i>Change your password periodically
                                        (every 3-6 months).</li>
                                    <li><i class="bi bi-check-circle-fill me-2"></i>Never share your password or login
                                        details with others.</li>
                                    <li><i class="bi bi-check-circle-fill me-2"></i>Always sign out when using public
                                        computers.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logout confirmation modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content logout-modal">
            <div class="modal-header logout-modal-header">
                <h5 class="modal-title" id="logoutConfirmModalLabel">
                    <i class="bi bi-box-arrow-right me-2"></i>Confirm Sign Out
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body logout-modal-body">
                <p>Are you sure you want to sign out?</p>
                <p class="text-muted"><small>You'll need to sign in again to access your account features.</small></p>
            </div>
            <div class="modal-footer logout-modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form method="post" style="margin:0;">
                    <button type="submit" name="logout" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper from CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Password show/hide effect
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                // Change icon
                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Hover and active effects for side menu items
        const sideNavItems = document.querySelectorAll('.side-nav-item');
        sideNavItems.forEach(item => {
            item.addEventListener('mouseenter', function () {
                this.querySelector('.side-nav-arrow').style.opacity = '1';
                this.querySelector('.side-nav-arrow').style.transform = 'translateX(5px)';
            });

            item.addEventListener('mouseleave', function () {
                if (!this.classList.contains('active')) {
                    this.querySelector('.side-nav-arrow').style.opacity = '0';
                    this.querySelector('.side-nav-arrow').style.transform = 'translateX(0)';
                }
            });
        });
    });
</script>