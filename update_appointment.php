<?php
/**
 * Update Appointment Status Handler
 * Admin action to approve, reject, or complete appointments
 */

require_once 'db.php';
require_once 'includes/helpers.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if ID and action are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: manage_appointments.php');
    exit;
}

$id = (int)$_GET['id'];
$action = $_GET['action'];

// Validate action
$allowedActions = ['approve', 'reject', 'complete'];
if (!in_array($action, $allowedActions)) {
    header('Location: manage_appointments.php');
    exit;
}

// Get appointment
$appointment = getRow("SELECT * FROM appointments WHERE id = ?", [$id]);

if (!$appointment) {
    header('Location: manage_appointments.php');
    exit;
}

// Update status based on action
$newStatus = '';
$notificationMessage = '';

switch ($action) {
    case 'approve':
        $newStatus = 'approved';
        $notificationMessage = 'Your appointment has been approved!';
        break;
    case 'reject':
        $newStatus = 'rejected';
        $notificationMessage = 'Unfortunately, your appointment has been rejected.';
        break;
    case 'complete':
        $newStatus = 'completed';
        $notificationMessage = 'Your case has been marked as completed. Thank you for choosing us.';
        break;
}

// Update appointment
$result = execute("UPDATE appointments SET status = ? WHERE id = ?", [$newStatus, $id]);

if ($result) {
    // Create notification for client
    execute(
        "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
        [$appointment['user_id'], $notificationMessage]
    );
    
    // Send SMS to client
    $user = getRow("SELECT phone, name FROM users WHERE id = ?", [$appointment['user_id']]);
    if ($user && !empty($user['phone'])) {
        $smsMsg = "Dear {$user['name']}, your appointment (ID: {$id}) has been {$newStatus}. - Justice Law Firm";
        sendSMS($user['phone'], $smsMsg);
    }
}

// Redirect back to appointments page
header('Location: manage_appointments.php');
exit;
?>
