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

// --- Ensure the payments table has the columns we rely on ---------------
function ensureColumns() {
    global $conn;

    $required = [
        'merchant_request_id' => "VARCHAR(255) DEFAULT NULL",
        'mpesa_receipt_number' => "VARCHAR(100) DEFAULT NULL",
        'transaction_time'    => "VARCHAR(50) DEFAULT NULL",
        'result_code'          => "INT DEFAULT NULL",
        'result_desc'          => "TEXT DEFAULT NULL",
        'updated_at'           => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];

    $existing = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `payments`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $existing[$row['Field']] = true;
        }
    }

    foreach ($required as $col => $def) {
        if (!isset($existing[$col])) {
            mysqli_query($conn, "ALTER TABLE `payments` ADD COLUMN `$col` $def");
        }
    }

    // Ensure the appointments table has a payment_status column
    $appCols = [];
    $res2 = mysqli_query($conn, "SHOW COLUMNS FROM `appointments`");
    if ($res2) {
        while ($row = mysqli_fetch_assoc($res2)) {
            $appCols[$row['Field']] = true;
        }
    }
    if (!isset($appCols['payment_status'])) {
        mysqli_query($conn, "ALTER TABLE `appointments` ADD COLUMN payment_status VARCHAR(20) DEFAULT 'Unpaid' AFTER `status`");
    }
}

ensureColumns();

// --- Handle the callback result ----------------------------------------
if ($resultCode == 0) {
    // Payment SUCCESSFUL — extract transaction details from metadata
    $metaData = $callback->CallbackMetadata->Item ?? [];
    $receiptNumber = '';
    $transactionAmount = 0;
    $transactionTime = '';

    foreach ($metaData as $item) {
        if (isset($item->Name) && $item->Name === 'MpesaReceiptNumber') {
            $receiptNumber = $item->Value ?? '';
        }
        if (isset($item->Name) && $item->Name === 'Amount') {
            $transactionAmount = $item->Value ?? 0;
        }
        if (isset($item->Name) && $item->Name === 'TransactionTime') {
            $transactionTime = $item->Value ?? '';
        }
    }

    file_put_contents(
        'error_log.txt',
        date('Y-m-d H:i:s') . " - Payment SUCCESS: CheckoutRequestID=$checkoutRequestID Receipt=$receiptNumber Amount=$transactionAmount" . PHP_EOL,
        FILE_APPEND
    );

    try {
        // Set to 'PENDING' so the admin can still verify/reject.
        // The client's polling treats 'PENDING' + receipt number as "paid".
        $stmt = $conn->prepare(
            "UPDATE payments
               SET status = 'PENDING',
                   mpesa_receipt_number = ?,
                   amount = ?,
                   transaction_time = ?,
                   result_code = ?,
                   result_desc = ?,
                   updated_at = NOW()
             WHERE checkout_request_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param(
                "ssdiss",
                $receiptNumber,
                $transactionAmount,
                $transactionTime,
                $resultCode,
                $callback->ResultDesc ?? '',
                $checkoutRequestID
            );
            $stmt->execute();
            $stmt->close();
        }

        // Fetch appointment_id linked to this payment
        $row = getRow("SELECT appointment_id FROM payments WHERE checkout_request_id = ?", [$checkoutRequestID]);
        $appointmentId = $row['appointment_id'] ?? null;

        if ($appointmentId) {
            // Update the appointments table — mark payment as Paid
            $stmt2 = $conn->prepare("UPDATE appointments SET payment_status = 'Paid' WHERE id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $appointmentId);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    } catch (Exception $e) {
        file_put_contents('error_log.txt', "DB Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
} else {
    // Payment FAILED or CANCELLED
    file_put_contents(
        'error_log.txt',
        date('Y-m-d H:i:s') . " - Payment FAILED: CheckoutRequestID=$checkoutRequestID ResultCode=$resultCode Desc=" . ($callback->ResultDesc ?? '') . PHP_EOL,
        FILE_APPEND
    );

    try {
        $stmt = $conn->prepare(
            "UPDATE payments
               SET status = 'FAILED',
                   result_code = ?,
                   result_desc = ?,
                   updated_at = NOW()
             WHERE checkout_request_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param(
                "iss",
                $resultCode,
                $callback->ResultDesc ?? '',
                $checkoutRequestID
            );
            $stmt->execute();
            $stmt->close();
        }

        // Fetch appointment_id and reset payment status
        $row = getRow("SELECT appointment_id FROM payments WHERE checkout_request_id = ?", [$checkoutRequestID]);
        $appointmentId = $row['appointment_id'] ?? null;

        if ($appointmentId) {
            $stmt2 = $conn->prepare("UPDATE appointments SET payment_status = 'Unpaid' WHERE id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $appointmentId);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    } catch (Exception $e) {
        file_put_contents('error_log.txt', "DB Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}

// --- Always respond with a clean JSON acknowledgement -------------------
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
?>