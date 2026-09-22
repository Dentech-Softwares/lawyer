<?php
// mpesa_callback.php - M-Pesa Callback Handler
// This endpoint receives callbacks from Safaricom's Daraja API

require_once 'db.php';

header('Content-Type: application/json');

// Log raw callback for debugging
$rawCallback = file_get_contents('php://input');
if ($rawCallback) {
    file_put_contents('mpesa_callback.log', date('Y-m-d H:i:s') . ' - ' . $rawCallback . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Parse JSON
$callbackData = json_decode($rawCallback, true);

if (!isset($callbackData['Body']['stkCallback'])) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback format']);
    exit;
}

$callback         = $callbackData['Body']['stkCallback'];
$resultCode       = $callback['ResultCode'];
$checkoutRequestId = $callback['CheckoutRequestID'];
$merchantRequestId = $callback['MerchantRequestID'] ?? '';

if ($resultCode == 0) {
    // Payment successful - extract additional info
    $mpesaReceiptNumber = '';
    $transactionAmount  = 0;
    $payingBank         = '';
    $transactionTime    = '';

    if (isset($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'Amount':
                    $transactionAmount = $item['Value'];
                    break;
                case 'PayingBank':
                    $payingBank = $item['Value'];
                    break;
                case 'TransactionTime':
                    $transactionTime = $item['Value'];
                    break;
            }
        }
    }

    // Update payment record
    $updateSql = "UPDATE payments SET status = 'COMPLETED', mpesa_receipt_number = ? WHERE checkout_request_id = ?";
    execute($updateSql, [$mpesaReceiptNumber, $checkoutRequestId]);

    // Update session to notify frontend
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mpesa_payment_success'] = $checkoutRequestId;

    // Log success
    file_put_contents('mpesa_callback.log', date('Y-m-d H:i:s') . ' - Payment COMPLETED: ' . $checkoutRequestId . PHP_EOL, FILE_APPEND | LOCK_EX);

} else {
    // Payment failed or cancelled
    $resultDesc = $callback['ResultDesc'] ?? 'Payment failed';

    // Update payment record
    $updateSql = "UPDATE payments SET status = 'FAILED' WHERE checkout_request_id = ?";
    execute($updateSql, [$checkoutRequestId]);

    // Log failure
    file_put_contents('mpesa_callback.log', date('Y-m-d H:i:s') . ' - Payment FAILED: ' . $checkoutRequestId . ' - ' . $resultDesc . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Respond to Safaricom
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
?>
