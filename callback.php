<?php
header("Content-Type: application/json");

// --- Read raw callback payload from Safaricom --------------------------
$rawCallbackData = file_get_contents('php://input');

// Log every incoming callback for debugging
file_put_contents(
    'error_log.txt',
    date('Y-m-d H:i:s') . ' - RAW CALLBACK: ' . $rawCallbackData . PHP_EOL,
    FILE_APPEND
);

require_once 'db.php';

$data = json_decode($rawCallbackData);

// Safaricom wraps the real data inside Body > stkCallback
if (!isset($data->Body->stkCallback)) {
    file_put_contents(
        'error_log.txt',
        date('Y-m-d H:i:s') . ' - Invalid callback format' . PHP_EOL,
        FILE_APPEND
    );
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
    exit;
}

$callback        = $data->Body->stkCallback;
$resultCode      = $callback->ResultCode ?? 1;
$checkoutRequestID = $callback->CheckoutRequestID ?? '';

if (empty($checkoutRequestID)) {
    file_put_contents(
        'error_log.txt',
        date('Y-m-d H:i:s') . ' - Callback without CheckoutRequestID' . PHP_EOL,
        FILE_APPEND
    );
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
    exit;
}

// 3. Extract MpesaReceiptNumber from CallbackMetadata->Item
$receiptNumber = '';
$resultDesc = $callback->ResultDesc ?? '';

if ($resultCode == 0 && isset($callback->CallbackMetadata->Item)) {
    $items = $callback->CallbackMetadata->Item;

    // Ensure we have an array to iterate
    if (!is_array($items)) {
        $items = [$items];
    }

    foreach ($items as $item) {
        $name = '';
        $value = '';

        // Handle both object and array formats
        if (is_object($item)) {
            $name = $item->Name ?? '';
            $value = $item->Value ?? '';
        } elseif (is_array($item)) {
            $name = $item['Name'] ?? '';
            $value = $item['Value'] ?? '';
        }

        if ($name === 'MpesaReceiptNumber') {
            $receiptNumber = $value;
        }
    }
}

// Fallback for top-level receipt if present
if (empty($receiptNumber) && isset($callback->MpesaReceiptNumber)) {
    $receiptNumber = $callback->MpesaReceiptNumber;
}

file_put_contents(
    'callback_debug.log',
    date('Y-m-d H:i:s') . " - ResultCode=$resultCode CheckoutRequestID=$checkoutRequestID Receipt=$receiptNumber" . PHP_EOL,
    FILE_APPEND
);

// 4. Update database immediately
if ($resultCode == 0) {
    $status = 'COMPLETED';
} else {
    $status = 'FAILED';
    $receiptNumber = null;
}

$stmt = $conn->prepare(
    "UPDATE payments
       SET status = ?,
           mpesa_receipt_number = ?,
           result_code = ?,
           result_desc = ?,
           updated_at = NOW()
     WHERE checkout_request_id = ?"
);

if ($stmt) {
    $stmt->bind_param(
        "ssiss",
        $status,
        $receiptNumber,
        $resultCode,
        $resultDesc,
        $checkoutRequestID
    );
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    file_put_contents(
        'callback_debug.log',
        date('Y-m-d H:i:s') . " - DB Updated: $affected rows affected" . PHP_EOL,
        FILE_APPEND
    );
}

// Update appointments table if linked
$row = getRow("SELECT appointment_id FROM payments WHERE checkout_request_id = ?", [$checkoutRequestID]);
$appointmentId = $row['appointment_id'] ?? null;

if ($appointmentId) {
    if ($resultCode == 0) {
        $stmt2 = $conn->prepare("UPDATE appointments SET payment_status = 'Paid' WHERE id = ?");
    } else {
        $stmt2 = $conn->prepare("UPDATE appointments SET payment_status = 'Unpaid' WHERE id = ?");
    }
    if ($stmt2) {
        $stmt2->bind_param("i", $appointmentId);
        $stmt2->execute();
        $stmt2->close();
    }
}

// --- Always respond with a clean JSON acknowledgement -------------------
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
?>