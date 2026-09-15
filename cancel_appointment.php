<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$appointment_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify the appointment belongs to the logged-in user and is pending
$appointment = getRow(
    "SELECT id FROM appointments WHERE id = ? AND user_id = ? AND status = 'pending'",
    [$appointment_id, $user_id]
);

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found or cannot be cancelled.';
    header('Location: client_dashboard.php');
    exit;
}

// Cancel the appointment
execute(
    "UPDATE appointments SET status = 'rejected' WHERE id = ? AND user_id = ?",
    [$appointment_id, $user_id]
);

// Create notification for admin
$admin = getRow("SELECT id FROM admin_users LIMIT 1");
if ($admin) {
    execute(
        "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
        [$admin['id'], 'An appointment has been cancelled by the client.']
    );
}

// Create notification for the client
execute(
    "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
    [$user_id, 'Your appointment has been cancelled successfully.']
);

$_SESSION['success'] = 'Appointment cancelled successfully.';
header("Location: client_dashboard.php");
exit;
?>
