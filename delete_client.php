<?php
/**
 * Delete Client Handler
 */

require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: manage_clients.php');
    exit;
}

$id = (int)$_GET['id'];

execute("DELETE FROM users WHERE id = ?", [$id]);

header('Location: manage_clients.php');
exit;
?>
