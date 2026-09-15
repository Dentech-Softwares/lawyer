<?php
/**
 * Delete Appointment Handler
 */

require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_appointments.php');
    exit;
}

$id = (int)$_GET['id'];

// Delete appointment
$result = execute("DELETE FROM appointments WHERE id = ?", [$id]);

// Redirect back to appointments page
header('Location: manage_appointments.php');
exit;
?>
