<?php
// mpesa_callback.php - M-Pesa STK Push Callback Handler
// This endpoint receives callbacks from Safaricom's Daraja API when the
// customer enters their PIN on their phone. It parses the JSON payload,
// extracts the result and transaction details, and updates the
// corresponding payment and appointment records in the database.

require_once 'db.php';
require_once 'includes/helpers.php';

header('Content-Type: application/json');

// ---------------------------------------------------------------------
// 0. Ensure the payments table has all columns needed for callbacks
//    (runs safely on both fresh and existing databases)
// ---------------------------------------------------------------------
function ensurePaymentColumns() {
    global $conn;

    $required = [
        'transaction_time' => "VARCHAR(50) DEFAULT NULL AFTER `status`",
        'result_code'      => "INT DEFAULT NULL AFTER `transaction_time`",
        'result_desc'      => "TEXT DEFAULT NULL AFTER `result_code`",
        'updated_at'       => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    ];

    // Discover existing columns
    $existing = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `payments`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $existing[$row['Field']] = true;
        }
    }

    foreach ($required as $col => $def) {
        if (!isset($existing[$col])) {
            $sql = "ALTER TABLE `payments` ADD COLUMN `$col` $def";
            mysqli_query($conn, $sql);
        }
    }
}

ensurePaymentColumns();

// ---------------------------------------------------------------------
// 1. Read and log the raw callback payload
// ---------------------------------------------------------------------
$rawCallback = file_get_contents('php://input');
if ($rawCallback) {
    file_put_contents(
        'mpesa_callback.log',
        date('Y-m-d H:i:s') . ' - ' . $rawCallback . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// ---------------------------------------------------------------------
// 2. Parse the JSON payload
// Safaricom sends: { "Body": { "stkCallback": { ... } } }
// ---------------------------------------------------------------------
$callbackData = json_decode($rawCallback, true);

if (!isset($callbackData['Body']['stkCallback'])) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback format']);
    exit;
}

$callback          = $callbackData['Body']['stkCallback'];
$merchantRequestId = $callback['MerchantRequestID'] ?? '';
$checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
$resultCode        = (int) $callback['ResultCode'];
$resultDesc        = $callback['ResultDesc'] ?? '';

// ---------------------------------------------------------------------
// 3. Validate we can identify the transaction
// ---------------------------------------------------------------------
if (empty($checkoutRequestId)) {
    file_put_contents(
        'mpesa_callback.log',
        date('Y-m-d H:i:s') . ' - Callback without CheckoutRequestID' . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Missing CheckoutRequestID']);
    exit;
}

// ---------------------------------------------------------------------
// 4. Handle the callback result
// ---------------------------------------------------------------------
if ($resultCode === 0) {
    // ---------------------------------------------------------------
    // Payment SUCCESSFUL — extract transaction details from metadata
    // ---------------------------------------------------------------
    $mpesaReceiptNumber = '';
    $transactionAmount  = 0;
    $transactionTime    = '';
    $payingBank         = '';
    $payerAccount       = '';

    if (isset($callback['CallbackMetadata']['Item']) && is_array($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if (!isset($item['Name'])) continue;
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'] ?? '';
                    break;
                case 'Amount':
                    $transactionAmount = $item['Value'] ?? 0;
                    break;
                case 'PayingBank':
                    $payingBank = $item['Value'] ?? '';
                    break;
                case 'TransactionTime':
                    $transactionTime = $item['Value'] ?? '';
                    break;
                case 'PayerAccount':
                    $payerAccount = $item['Value'] ?? '';
                    break;
            }
        }
    }

    // Update the payment record to COMPLETED with transaction details
    $updateSql = "UPDATE payments
                  SET status = 'COMPLETED',
                      mpesa_receipt_number = ?,
                      amount = ?,
                      transaction_time = ?,
                      result_code = ?,
                      result_desc = ?,
                      merchant_request_id = ?,
                      updated_at = NOW()
                  WHERE checkout_request_id = ?";
    execute($updateSql, [
        $mpesaReceiptNumber ?: null,
        $transactionAmount ?: null,
        $transactionTime ?: null,
        0,
        '',
        $merchantRequestId ?: null,
        $checkoutRequestId
    ]);

    // Update the associated appointment — mark as approved (payment confirmed)
    execute(
        "UPDATE appointments SET status = 'approved'
         WHERE id = (
             SELECT appointment_id FROM payments WHERE checkout_request_id = ?
         )",
        [$checkoutRequestId]
    );

    // Notify frontend via session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mpesa_payment_success'] = $checkoutRequestId;
    $_SESSION['mpesa_receipt_number']  = $mpesaReceiptNumber;

    file_put_contents(
        'mpesa_callback.log',
        date('Y-m-d H:i:s') . ' - Payment COMPLETED: ' . $checkoutRequestId
        . ' - Receipt: ' . $mpesaReceiptNumber
        . ' - Amount: ' . $transactionAmount
        . ' - Time: ' . $transactionTime
        . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    
    // Send payment confirmation SMS
    $payInfo = getRow("SELECT p.appointment_id, p.phone_number, a.user_id FROM payments p LEFT JOIN appointments a ON p.appointment_id = a.id WHERE p.checkout_request_id = ?", [$checkoutRequestId]);
    if ($payInfo) {
        $user = getRow("SELECT name, phone FROM users WHERE id = ?", [$payInfo['user_id']]);
        if ($user && !empty($user['phone'])) {
            $smsMsg = "Dear {$user['name']}, your payment of KES {$transactionAmount} has been received (Receipt: {$mpesaReceiptNumber}). Thank you. - Justice Law Firm";
            sendSMS($user['phone'], $smsMsg);
        } elseif (!empty($payInfo['phone_number'])) {
            $smsMsg = "Dear client, your payment of KES {$transactionAmount} has been received (Receipt: {$mpesaReceiptNumber}). Thank you. - Justice Law Firm";
            sendSMS($payInfo['phone_number'], $smsMsg);
        }
    }

} else {
    // ---------------------------------------------------------------
    // Payment FAILED or CANCELLED
    // ResultCode values:
    //   1  — The receiver is invalid, or the payer has insufficient funds.
    //   2  — The request cannot be processed (e.g. user cancels).
    //   ... other non-zero codes indicate specific failures.
    // ---------------------------------------------------------------
    $failureReason = $resultDesc ?: 'Payment failed or cancelled';

    // Update the payment record to FAILED
    $updateSql = "UPDATE payments
                  SET status = 'FAILED',
                      result_code = ?,
                      result_desc = ?,
                      merchant_request_id = ?,
                      updated_at = NOW()
                  WHERE checkout_request_id = ?";
    execute($updateSql, [
        $resultCode,
        $failureReason,
        $merchantRequestId ?: null,
        $checkoutRequestId
    ]);

    // Reset the associated appointment back to 'pending' (user may retry)
    execute(
        "UPDATE appointments SET status = 'pending'
         WHERE id = (
             SELECT appointment_id FROM payments WHERE checkout_request_id = ?
         )",
        [$checkoutRequestId]
    );

    // Notify frontend via session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mpesa_payment_failed']  = $checkoutRequestId;
    $_SESSION['mpesa_failure_reason']  = $failureReason;

    file_put_contents(
        'mpesa_callback.log',
        date('Y-m-d H:i:s') . ' - Payment FAILED: ' . $checkoutRequestId
        . ' - Code: ' . $resultCode
        . ' - ' . $failureReason
        . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// ---------------------------------------------------------------------
// 5. Respond to Safaricom — MUST return HTTP 200 with ResultCode 0
// ---------------------------------------------------------------------
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Accepted'
]);
?>
