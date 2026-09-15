<?php
/**
 * Toggle Client Block Status Handler
 */

require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: manage_clients.php');
    exit;
}

$id = (int)$_GET['id'];
$action = $_GET['action'];

if ($action === 'block') {
    execute("UPDATE users SET is_blocked = 1 WHERE id = ?", [$id]);
} elseif ($action === 'unblock') {
    execute("UPDATE users SET is_blocked = 0 WHERE id = ?", [$id]);
}

header('Location: manage_clients.php');
exit;
?>
