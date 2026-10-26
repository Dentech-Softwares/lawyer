<?php
/**
 * Logout Handler
 * Releases the account session lock, destroys the session and redirects
 * to the home page
 */

require_once 'db.php';
require_once 'includes/auth_check.php';

// Destroy session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Free the account session lock so the account can be signed in again
if (!empty($_SESSION['admin_id'])) {
    release_account_session('admin_users', (int)$_SESSION['admin_id']);
} elseif (!empty($_SESSION['user_id'])) {
    release_account_session('users', (int)$_SESSION['user_id']);
}

// Clear all session variables, drop the cookie and destroy the session
destroy_session_and_redirect('index.php');
