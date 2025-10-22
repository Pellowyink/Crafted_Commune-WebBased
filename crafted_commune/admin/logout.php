<?php
/**
 * Admin Logout
 */
require_once '../config.php';

if (isAdminLoggedIn()) {
    // Log activity
    logActivity($_SESSION['admin_id'], 'logout', 'Admin logged out');
}

// Destroy session
session_destroy();

// Redirect to login
redirect('login.php');
?>