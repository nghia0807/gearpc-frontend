<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Login</title>
</head>
<body>
    <!-- Popup container -->
    <div id="loginPopup" class="modal" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Login</h5>
                    <button type="button" class="btn-close" onclick="togglePopup(false)"></button>
                </div>

                <div class="modal-body">
                    <!-- Login Form -->
                    <div id="loginForm">
                        <div class="mb-3">
                            <input type="email" id="loginEmail" class="form-control" placeholder="Email">
                        </div>
                        <div class="mb-3">
                            <input type="password" id="loginPassword" class="form-control" placeholder="Password">
                        </div>
                        <div class="p-3 position-relative">
                            <a href="#" class="pb-3 position-absolute top-50 end-0 translate-middle-y" style="text-decoration: none; color: #64676a;">Forgot password?</a>
                        </div>
                        <div class="d-grid gap-2 col-6 mx-auto">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                        <div align="center" class="p-3">
                            Don't have an account? <a href="#" onclick="showRegisterForm()" style="text-decoration: none;">Sign up!</a>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <div id="registerForm" style="display: none;">
                        <div class="mb-3">
                            <input type="text" id="registerName" class="form-control" placeholder="Full Name">
                        </div>
                        <div class="mb-3">
                            <input type="email" id="registerEmail" class="form-control" placeholder="Email">
                        </div>
                        <div class="mb-3">
                            <input type="text" id="registerPhone" class="form-control" placeholder="Phone">
                        </div>
                        <div class="mb-3">
                            <input type="password" id="registerPassword" class="form-control" placeholder="Password">
                        </div>
                        <div class="d-grid gap-2 col-6 mx-auto">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                        <div align="center" class="p-3">
                            Already have an account? <a href="#" onclick="showLoginForm()" style="text-decoration: none;">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePopup(show) {
            document.getElementById('loginPopup').style.display = show ? 'block' : 'none';
			if (show) {
				showLoginForm(); // Default to login form when popup is opened
			}
        }

        function showRegisterForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Sign Up';
        }

        function showLoginForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('modalTitle').innerText = 'Login';
        }
    </script>
</body>
</html>