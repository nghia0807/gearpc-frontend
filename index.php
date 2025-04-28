<?php
/**
 * GearPC Website - Main Entry Point
 * 
 * This file serves as the main entry point for the GearPC website.
 * It redirects users to the home page in the pages directory.
 */

// Start session if needed
session_name('user_session');
session_set_cookie_params(['path' => '/']);
session_start();

// Redirect to the home page
header('Location: pages/home.php');
exit();
?>