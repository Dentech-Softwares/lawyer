<?php
header('Content-Type: application/json');
require_once 'db.php';

$appointmentId = $_GET['appointment_id'] ?? null;

if ($appointmentId) {
    $row = getRow("SELECT status FROM payments WHERE appointment_id = ? ORDER BY id DESC LIMIT 1", [$appointmentId]);
    if ($row && $row['status'] === 'COMPLETED') {
        echo json_encode(['status' => 'paid']);
        exit;
    }
}

echo json_encode(['status' => 'pending']);
?>