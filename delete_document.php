<?php
/**
 * Delete Document Handler
 */

require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: manage_documents.php');
    exit;
}

$id = (int)$_GET['id'];

execute("DELETE FROM documents WHERE id = ?", [$id]);

header('Location: manage_documents.php');
exit;
?>
