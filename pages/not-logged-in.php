<?php
require_once __DIR__ . '/../includes/session_init.php';

// Redirect to login page if user decides to log in
define('LOGIN_PAGE', 'login.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Logged In - GearPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #121212;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background-color: #1e1e1e;
            color: #ffffff;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0 10px #ffa33a;
        }

        .btn-primary {
            background-color: #ffa33a;
            color: #1e1e1e;
            border: none;
        }

        .btn-primary:hover {
            background-color: #e88f2e;
            color: #1e1e1e;
        }

        .btn-secondary {
            background-color: #30363d;
            color: #ffa33a;
            border: solid #30363d 1px;
        }

        .btn-secondary:hover {
            background-color: #1e1e1e;
            color: #ffa33a;
            border: solid #ffa33a 1px;
        }

        .icon-warning {
            font-size: 4rem;
            color: #ffa33a;
        }
    </style>
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card text-center p-5 w-100" style="max-width: 500px;">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle icon-warning"></i>
            </div>
            <h2 class="mb-3">Access Denied</h2>
            <p class="mb-4">You must be logged in to access this feature. Please log in or return to the homepage.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="<?= LOGIN_PAGE ?>" class="btn btn-primary px-4">Log In</a>
                <a href="javascript:history.back()" class="btn btn-secondary px-4">Go Back</a>
            </div>
        </div>
    </div>
</body>

</html>