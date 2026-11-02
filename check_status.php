<?php
header('Content-Type: application/json');

require_once 'db.php';

$checkout_id = $_GET['checkout_id'] ?? '';

if (empty($checkout_id)) {
    echo json_encode(["status" => "Pending", "receipt" => null]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT status, mpesa_receipt_number
     FROM payments
     WHERE checkout_request_id = ?"
);

if (!$stmt) {
    echo json_encode(["status" => "Pending", "receipt" => null]);
    exit;
}

$stmt->bind_param("s", $checkout_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $dbStatus = $row['status'] ?? 'PENDING';
    $receipt   = $row['mpesa_receipt_number'] ?? null;

    // --- Friendly status for the client polling loop -------------------
    // If the M-Pesa callback has already fired (receipt number present) we
    // treat the payment as Completed even though the admin has not yet
    // verified it. The admin can still reject it later.
    if (!empty($receipt)) {
        $friendlyStatus = 'Completed';
    } else {
        $friendlyStatus = 'Pending';
    }

    echo json_encode([
        "status"  => $friendlyStatus,
        "receipt" => $receipt
    ]);
} else {
    echo json_encode(["status" => "Pending", "receipt" => null]);
}

$stmt->close();
?>