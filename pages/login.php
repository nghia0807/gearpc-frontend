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
    <!-- Popup container (display: none by default) -->
    <div id="loginPopup" class="modal" tabindex="-1" style="display: none;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Login or Sign up</h5>
            <button type="button" class="btn-close" onclick="togglePopup(false)"></button>
          </div>

          <div class="modal-body">
            <!-- Email input -->
            <div id="emailInput" class="mb-3" style="display: block;">
              <input type="email" id="email" class="form-control" placeholder="Email">
            </div>

            <!-- Phone input -->
            <div id="phoneInput" class="mb-3" style="display: none;">
              <input type="text" id="phone" class="form-control" placeholder="Phone">
            </div>

            <!-- Password -->
            <div>
              <input type="password" id="password" class="form-control" placeholder="Password">
            </div>
          </div>

          <div class="p-3 position-relative">
            <a href="#" class="pb-4 pe-4 position-absolute top-50 end-0 translate-middle-y" style="text-decoration: none; color: #64676a;">Forgot password?</a>
          </div>
          <div class="d-grid gap-2 col-6 mx-auto">
              <button type="submit" class="btn btn-primary">Login</button>
          </div>
          <div align="center" class="p-3">
            <h>Don't have an account? </h>
            <a href="#" style="text-decoration: none;">Sign up!</a>
          </div>
        </div>
      </div>
    </div>

    <script>
        function togglePopup(show) {
        document.getElementById('loginPopup').style.display = show ? 'block' : 'none';
        }
    </script>
</body>
</html>