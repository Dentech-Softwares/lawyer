<?php
// TEMPORARY probe helper - creates an authenticated session for visual testing.
require_once 'db.php';

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 1;

if (!isset($_SESSION['user_id'])) {
    $u = getRow("SELECT * FROM users WHERE id = ?", [$uid]);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_phone'] = $u['phone'];
    $_SESSION['last_activity'] = time();
}
if (!isset($_SESSION['admin_id'])) {
    $a = getRow("SELECT * FROM admin_users WHERE id = 1");
    $_SESSION['admin_id'] = $a['id'];
    $_SESSION['admin_username'] = $a['username'];
    $_SESSION['admin_email'] = $a['email'];
    $_SESSION['last_activity'] = time();
}
header('Content-Type: text/plain');
echo "SID=" . session_id() . "\n";
echo "user=" . ($_SESSION['user_name'] ?? '') . " admin=" . ($_SESSION['admin_username'] ?? '') . "\n";
