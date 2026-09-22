<?php
// mpesa_pay.php - M-Pesa STK Push Payment Handler

require_once 'db.php';

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['checkout_request_id'])) {
    $checkoutRequestId = trim((string) ($_GET['checkout_request_id'] ?? ''));
    if ($checkoutRequestId === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Checkout request ID is required']);
        exit;
    }

    $payment = getRow(
        "SELECT status, amount, phone_number, mpesa_receipt_number, created_at
         FROM payments
         WHERE checkout_request_id = ?",
        [$checkoutRequestId]
    );

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Payment request not found']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'payment_status' => $payment['status'],
        'amount' => $payment['amount'],
        'phone_number' => $payment['phone_number'],
        'mpesa_receipt_number' => $payment['mpesa_receipt_number'] ?? '',
        'created_at' => $payment['created_at']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

function envValue($key, $default = '') {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
    }
    return trim((string) $value);
}

function mpesaError($message, $details = [], $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['status' => 'error', 'message' => $message], $details));
    exit;
}

$environment = strtolower(envValue('MPESA_ENVIRONMENT', 'production'));
$apiBase = $environment === 'production'
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke';

$consumerKey = 'N3JAdyKUBStxVD0VGfA6teG5Md8u8s4QRUAhbwQMBNXGsrFo';
$consumerSecret = 'yx4ztulAbjVDbh9Ufcf6AH9F1Y7y2MhJ11ERZ1YKFZM7W546XGnk2kRR6EwFcy4j';
$businessShortCode = envValue('MPESA_BUSINESS_SHORTCODE', '174379');
$passkey = envValue('MPESA_PASSKEY', 'bfb279f9aa9bdbc158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c9192');
$callbackUrl = envValue('MPESA_CALLBACK_URL', 'https://mydomain.com/mpesa-express-simulate/');

if (!in_array($environment, ['sandbox', 'production'], true)) {
    mpesaError('Invalid M-Pesa environment. Use sandbox or production.');
}

// For sandbox, use Safaricom's test credentials
if ($environment === 'sandbox') {
    // Sandbox test credentials - user MUST register at developer.safaricom.co.ke to get their own
    $sandboxConsumerKey = envValue('MPESA_SANDBOX_CONSUMER_KEY');
    $sandboxConsumerSecret = envValue('MPESA_SANDBOX_CONSUMER_SECRET');
    
    // Check for placeholder values
    $isPlaceholder = false;
    if (strpos($sandboxConsumerKey, 'your_') === 0 || strpos($sandboxConsumerSecret, 'your_') === 0) {
        $isPlaceholder = true;
    }
    
    if ($sandboxConsumerKey && $sandboxConsumerSecret && !$isPlaceholder) {
        $consumerKey = $sandboxConsumerKey;
        $consumerSecret = $sandboxConsumerSecret;
    } else {
        mpesaError(
            'Sandbox credentials required. Register at https://developer.safaricom.co.ke, create a sandbox app, and set MPESA_SANDBOX_CONSUMER_KEY and MPESA_SANDBOX_CONSUMER_SECRET in your .env file.',
            ['environment' => $environment, 'hint' => 'Replace placeholder values in .env with real credentials from Safaricom Developer Portal'],
            500
        );
    }
    
    $businessShortCode = '174379';
    $passkey = 'bfb279f9aa9bdbc158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c9192';
} else {
    // Production: require user to provide their BusinessShortCode and Passkey
    $businessShortCode = envValue('MPESA_BUSINESS_SHORTCODE', '174379');
    $passkey = envValue('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
}

$callbackUrl = envValue('MPESA_CALLBACK_URL', 'https://mydomain.com/mpesa-express-simulate/');

$missingConfig = [];
if ($environment === 'production') {
    if ($businessShortCode === '') {
        $missingConfig[] = 'MPESA_BUSINESS_SHORTCODE (your PayBill/Till Number)';
    }
    if ($passkey === '') {
        $missingConfig[] = 'MPESA_PASSKEY (your Lipa Na M-Pesa Passkey)';
    }
}
if ($callbackUrl === '' || $callbackUrl === 'https://mydomain.com/mpesa-express-simulate/' || $callbackUrl === 'https://yourdomain.com/mpesa_callback.php') {
    $missingConfig[] = 'MPESA_CALLBACK_URL';
}

if (!empty($missingConfig)) {
    mpesaError(
        'M-Pesa configuration is incomplete: ' . implode(', ', $missingConfig),
        ['environment' => $environment],
        500
    );
}

if (!filter_var($callbackUrl, FILTER_VALIDATE_URL) || stripos($callbackUrl, 'https://') !== 0) {
    mpesaError('MPESA_CALLBACK_URL must be a public HTTPS URL.', ['environment' => $environment], 500);
}

// Allow the specific callback URL provided by user
if (preg_match('/^https:\/\/(localhost|127\.0\.0\.1|\[::1\])([:\/]|$)/i', $callbackUrl) && $environment === 'production') {
    mpesaError('MPESA_CALLBACK_URL must be publicly reachable; use a public HTTPS domain or tunnel for local testing.', [], 500);
}

define('BOOKING_FEE', 500);
define('CONTINUATION_FEE', 250);

// 2. Get input data
$paymentType    = $_POST['payment_type'] ?? 'booking';
$rawAppointmentId = $_POST['appointment_id'] ?? null;
$phoneNumber    = $_POST['phone_number'] ?? '';

// Sanitize and verify appointment_id against database foreign key constraint
$appointmentId = null;
if (!empty($rawAppointmentId)) {
    $checkApp = getRow("SELECT id FROM appointments WHERE id = ?", [(int)$rawAppointmentId]);
    if ($checkApp) {
        $appointmentId = (int)$rawAppointmentId;
    }
}

if (empty($phoneNumber)) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
    exit;
}

// 3. Calculate fee based on payment type
if ($paymentType === 'continuation') {
    $amount = CONTINUATION_FEE;
} else {
    $amount = BOOKING_FEE;
}

// 4. Format phone number to 2547XXXXXXXX or 2541XXXXXXXX
$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
if (strlen($phoneNumber) === 10 && (strpos($phoneNumber, '07') === 0 || strpos($phoneNumber, '01') === 0)) {
    $phoneNumber = '254' . substr($phoneNumber, 1);
} elseif (strlen($phoneNumber) === 9 && (strpos($phoneNumber, '7') === 0 || strpos($phoneNumber, '1') === 0)) {
    $phoneNumber = '254' . $phoneNumber;
}

if (strlen($phoneNumber) !== 12 || !preg_match('/^254[71]/', $phoneNumber)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format. Use 07XXXXXXXX, 01XXXXXXXX or 254XXXXXXXXX']);
    exit;
}

// 5. Generate OAuth Access Token
$authUrl = $apiBase . '/oauth/v1/generate?grant_type=client_credentials';
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);

$ch = curl_init($authUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; M-Pesa Integration)');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$authResponseRaw = curl_exec($ch);
$authError = curl_error($ch);
$authHttpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($authResponseRaw === false) {
    mpesaError('Unable to connect to M-Pesa OAuth service.', ['details' => $authError], 502);
}

// Debug: log auth response
error_log("M-Pesa Auth Response: HTTP $authHttpStatus - $authResponseRaw");

$authResponse = json_decode($authResponseRaw, true);
if (!isset($authResponse['access_token'])) {
    mpesaError(
        'M-Pesa authentication failed.',
        [
            'http_status' => $authHttpStatus,
            'response' => is_array($authResponse) ? $authResponse : null,
            'raw_response' => $authResponseRaw
        ],
        502
    );
}

$accessToken = $authResponse['access_token'];

// 7. Generate Dynamic Password and Timestamp
$timestamp   = date('YmdHis');
$password    = base64_encode($businessShortCode . $passkey . $timestamp);

// 8. Build STK Push Payload
$stkUrl  = $apiBase . '/mpesa/stkpush/v1/processrequest';
$payload = [
    'BusinessShortCode' => $businessShortCode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => (int)$amount,
    'PartyA'            => $phoneNumber,
    'PartyB'            => $businessShortCode,
    'PhoneNumber'       => $phoneNumber,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'JusticeLaw',
    'TransactionDesc'   => ucfirst($paymentType) . ' Fee Payment'
];

// 9. Execute STK Push Request
$ch = curl_init($stkUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
    'User-Agent: Mozilla/5.0 (compatible; M-Pesa Integration)'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response   = curl_exec($ch);
$responseError = curl_error($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    mpesaError('Unable to connect to M-Pesa STK Push service.', ['details' => $responseError], 502);
}

// Debug: log STK response
error_log("M-Pesa STK Response: HTTP $httpStatus - $response");

$resData = json_decode($response, true);
if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($resData)) {
    mpesaError(
        'M-Pesa STK Push request failed.',
        [
            'http_status' => $httpStatus,
            'response' => is_array($resData) ? $resData : null,
            'raw_response' => $response
        ],
        502
    );
}

if (isset($resData['ResponseCode']) && $resData['ResponseCode'] === '0' && !empty($resData['CheckoutRequestID'])) {
    $checkoutRequestId = $resData['CheckoutRequestID'];
    $merchantRequestId = $resData['MerchantRequestID'] ?? '';

    $insertSql = "INSERT INTO payments (appointment_id, checkout_request_id, merchant_request_id, amount, phone_number, status) VALUES (?, ?, ?, ?, ?, 'PENDING')";
    execute($insertSql, [
        $appointmentId ?: null,
        $checkoutRequestId,
        $merchantRequestId,
        $amount,
        $phoneNumber
    ]);

    echo json_encode([
        'status'            => 'success',
        'message'           => 'STK Push sent to ' . $phoneNumber . '. Please check your phone to complete payment.',
        'CheckoutRequestID' => $checkoutRequestId,
        'amount'            => $amount,
        'phone_number'      => $phoneNumber,
        'environment'       => $environment
    ]);
    exit;
}

mpesaError(
    'M-Pesa rejected the STK Push request.',
    [
        'response' => $resData,
        'http_status' => $httpStatus
    ],
    502
);
?>
