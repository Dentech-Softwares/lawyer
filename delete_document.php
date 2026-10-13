<?php
/**
 * Delete Document Handler
 */

require_once 'db.php';

$isAdmin = isset($_SESSION['admin_id']);
$isClient = isset($_SESSION['user_id']);

if (!$isAdmin && !$isClient) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ' . ($isAdmin ? 'manage_documents.php' : 'client_documents.php'));
    exit;
}

$id = (int)$_GET['id'];

// Verify ownership/access
if ($isAdmin) {
    $doc = getRow("SELECT * FROM documents WHERE id = ?", [$id]);
} else {
    $doc = getRow("SELECT * FROM documents WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']]);
}

if (!$doc) {
    header('Location: ' . ($isAdmin ? 'manage_documents.php' : 'client_documents.php') . '?error=not_found');
    exit;
}

// Delete file from filesystem
if (file_exists($doc['file_path'])) {
    @unlink($doc['file_path']);
}

// Delete from database
execute("DELETE FROM documents WHERE id = ?", [$id]);

// Handle redirect
$redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
if ($redirect) {
    header('Location: ' . $redirect);
} else {
    header('Location: ' . ($isAdmin ? 'manage_documents.php' : 'client_documents.php'));
}
exit;
?>
