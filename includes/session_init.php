<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('user_session');
    session_start();
}

// --- Logout logic for header logout button ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // Clear session and cookie for user_session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: pages/login.php');
    exit();
}
?>