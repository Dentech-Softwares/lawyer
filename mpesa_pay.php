<?php
header('Content-Type: application/json');

// --- Accept phone + appointment_id from the frontend ----------------
$phone          = $_POST['phone_number'] ?? '';
$appointmentId  = (int)($_POST['appointment_id'] ?? 0);

if (empty($phone) || $appointmentId <= 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Missing phone number or appointment ID"
    ]);
    exit;
}

// Normalise the phone number to the 254XXXXXXXXX format Daraja expects
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);
} elseif (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') {
    $phone = '254' . $phone;
} elseif (substr($phone, 0, 3) !== '254') {
    $phone = '254' . $phone;
}

require_once 'db.php';

// Verify the appointment exists
$appointment = getRow("SELECT * FROM appointments WHERE id = ?", [$appointmentId]);
if (!$appointment) {
    echo json_encode([
        "status"  => "error",
        "message" => "Appointment not found"
    ]);
    exit;
}

$amount = 500; // Booking fee (KSh)

// --- Daraja credentials ------------------------------------------------
$consumerKey    = 'jHG9GdLX1i9pcmh1MTYTkxnBol7XWqsY6GGGJn3qRyi4IJBe';
$consumerSecret = 'E0rK9q3K2pHBm5t4WdgJQXursk54DUcpsT0kAGCGVVeN2chFhgCeZrBeb8L1972l';
$businessShortCode = '174379';
$passkey       = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$callbackUrl   = 'https://mud-neglector-emu.ngrok-free.dev/lawyer/callback.php';

// --- Step 1: Get access token -----------------------------------------
$credentials = base64_encode(trim($consumerKey) . ':' . trim($consumerSecret));
$tokenUrl    = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Auth Failed. HTTP Code: $httpCode | cURL Error: $curlError | Response: " . print_r($response, true)
    ]);
    exit;
}

$tokenData     = json_decode($response);
$access_token  = $tokenData->access_token ?? null;

if (!$access_token) {
    echo json_encode([
        "status"  => "error",
        "message" => "Token missing from Safaricom response: " . $response
    ]);
    exit;
}

// --- Step 2: Initiate STK Push ----------------------------------------
$timestamp  = date('YmdHis');
$password   = base64_encode($businessShortCode . $passkey . $timestamp);
$stk_url    = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$payload = [
    "BusinessShortCode"  => $businessShortCode,
    "Password"           => $password,
    "Timestamp"          => $timestamp,
    "TransactionType"    => "CustomerPayBillOnline",
    "Amount"             => "1",
    "PartyA"             => $phone,
    "PartyB"             => $businessShortCode,
    "PhoneNumber"        => $phone,
    "CallBackURL"        => $callbackUrl,
    "AccountReference"   => "JusticeLaw",
    "TransactionDesc"    => "Appointment Booking"
];

$ch = curl_init($stk_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$stk_response = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log everything for debugging
file_put_contents('outgoing_stk_payload.log', json_encode($payload) . PHP_EOL, FILE_APPEND);
file_put_contents('stk_response.log', $stk_response . PHP_EOL, FILE_APPEND);

$stk_data = json_decode($stk_response, true);
$checkoutRequestId = $stk_data['CheckoutRequestID'] ?? null;
$merchantRequestId = $stk_data['MerchantRequestID'] ?? null;

// --- Step 3: Persist the payment record so polling works immediately ----
if ($checkoutRequestId) {
    execute(
        "INSERT INTO payments
            (appointment_id, checkout_request_id, merchant_request_id, amount, phone_number, payment_method, status)
         VALUES
            (?, ?, ?, ?, ?, 'mpesa', 'PENDING')",
        [
            $appointmentId,
            $checkoutRequestId,
            $merchantRequestId ?: null,
            $amount,
            $phone
        ]
    );
} else {
    // Safaricom rejected the request – still log it so we can diagnose
    file_put_contents('stk_error.log', $stk_response . PHP_EOL, FILE_APPEND);
}

// --- Step 4: Return the checkout_request_id so the frontend can poll ----
echo json_encode([
    "status"              => "success",
    "http_code"           => $httpCode,
    "message"             => "STK Push sent successfully. Please complete the payment on your phone.",
    "checkout_request_id" => $checkoutRequestId,
    "safaricom_response"  => $stk_data
]);
exit;