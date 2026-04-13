<?php
/**
 * Logout Handler
 * Destroys session and redirects to home page
 */

require_once 'db.php';

// Destroy session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
?>
