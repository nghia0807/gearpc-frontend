<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Sign In</title>
    <style>
    .modalSlideDown {
        animation: slideDownFadeIn 0.2s ease-out;
    }
    @keyframes slideDownFadeIn {
        0% { transform: translateY(-50px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    </style>
</head>
<body>
    <!-- Popup container -->
    <div id="loginPopup" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modalSlideDown">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Sign In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- Login Form -->
                    <form id="loginForm" method="POST" action="" style="display: block;">
                        <div class="mb-3">
                            <input type="text" name="loginEmail" id="loginEmail" class="form-control" placeholder="Email Address">
                        </div>
                        <div class="mb-3">
                            <input type="password" name="loginPassword" id="loginPassword" class="form-control" placeholder="Password">
                        </div>
                        <div class="p-3 position-relative">
                            <a href="#" class="pb-3 position-absolute top-50 end-0 translate-middle-y" style="text-decoration: none; color: #64676a;">Forgot Password?</a>
                        </div>
                        <div class="d-grid gap-2 col-6 mx-auto">
                            <button type="submit" name="login" class="btn btn-primary">Sign In</button>
                        </div>
                        <div align="center" class="p-3">
                            Don't have an account? <a href="#" onclick="showRegisterForm()" style="text-decoration: none;">Create an account!</a>
                        </div>
                    </form>

                    <!-- Registration Form -->
                    <div id="registerForm" style="display: none;">
                        <div class="mb-3">
                            <input type="text" id="registerName" class="form-control" placeholder="Full Name">
                        </div>
                        <div class="mb-3">
                            <input type="email" id="registerEmail" class="form-control" placeholder="Email Address">
                        </div>
                        <div class="mb-3">
                            <input type="text" id="registerPhone" class="form-control" placeholder="Phone Number">
                        </div>
                        <div class="mb-3">
                            <input type="password" id="registerPassword" class="form-control" placeholder="Password">
                        </div>
                        <div class="d-grid gap-2 col-6 mx-auto">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                        <div align="center" class="p-3">
                            Already have an account? <a href="#" onclick="showLoginForm()" style="text-decoration: none;">Sign in!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLoginForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('modalTitle').innerText = 'Sign In';
        }
        function showRegisterForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Create Tech Zone Account';
        }
    </script>
</body>
</html>