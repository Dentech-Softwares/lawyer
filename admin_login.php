<?php
/**
 * Admin Login - Redirects to unified login page
 * Both clients and admins now use login.php with email addresses
 */
require_once 'db.php';

// Redirect all admin login attempts to the unified login page
header('Location: login.php');
exit;
?>
