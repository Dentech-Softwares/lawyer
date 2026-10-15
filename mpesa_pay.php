<?php
// mpesa_pay.php - M-Pesa STK Push Request Handler (Safaricom Daraja Sandbox)
// https://developer.safaricom.co.ke
//
// This script:
//   1. Retrieves an OAuth access token from the Daraja Sandbox.
//   2. Generates a dynamic Base64 password (Shortcode + Passkey + Timestamp).
//   3. Sends an STK Push request to the customer's phone.
//   4. Records the pending payment so the callback handler can update it.

require_once 'db.php';
require_once 'includes/helpers.php';

/**
 * Load variables from a .env file into the process environment.
 */
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

function envValue($key, $default = '') {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
    }
    return trim((string) $value);
}

function mpesaError($message, $details = [], $statusCode = 400) {
    http_response_code($statusCode);
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(['status' => 'error', 'message' => $message], $details));
    exit;
}

// ---------------------------------------------------------------------
// Sandbox environment
// Safaricom Daraja Sandbox URLs
// ---------------------------------------------------------------------
$oauthUrl       = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$stkPushUrl     = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

// Consumer Key / Secret from Sandbox credentials
$consumerKey    = 'jHG9GdLX1i9pcmh1MTYTkxnBol7XWqsY6GGGJn3qRyi4IJBe';
$consumerSecret = 'E0rK9q3K2pHBm5t4WdgJQXursk54DUcpsT0kAGCGVVeN2chFhgCeZrBeb8L1972I';

// Sandbox Shortcode and Passkey
$businessShortCode = '174379';
$passkey           = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$callbackUrl       = envValue('MPESA_CALLBACK_URL', 'https://your-domain.com/mpesa_callback.php');

// Validate required configuration
if (empty($callbackUrl)) {
    mpesaError(
        'M-Pesa sandbox configuration is incomplete: MPESA_CALLBACK_URL not set',
        ['hint' => 'Set MPESA_CALLBACK_URL in your .env file (must be a public HTTPS URL)'],
        500
    );
}

if (!filter_var($callbackUrl, FILTER_VALIDATE_URL) || stripos($callbackUrl, 'https://') !== 0) {
    mpesaError('MPESA_CALLBACK_URL must be a public HTTPS URL.', [], 500);
}

// ---------------------------------------------------------------------
// GET: poll / query a previously-initiated payment status
// ---------------------------------------------------------------------
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
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Payment request not found']);
        exit;
    }

    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status'             => 'success',
        'payment_status'     => $payment['status'],
        'amount'             => $payment['amount'],
        'phone_number'       => $payment['phone_number'],
        'mpesa_receipt_number' => $payment['mpesa_receipt_number'] ?? '',
        'created_at'         => $payment['created_at']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// ---------------------------------------------------------------------
// Payment constants
// ---------------------------------------------------------------------
define('BOOKING_FEE', 500);
define('CONTINUATION_FEE', 250);

// ---------------------------------------------------------------------
// 1. Get input data
// ---------------------------------------------------------------------
$paymentType = $_POST['payment_type'] ?? $_GET['payment_type'] ?? 'booking';
$paymentMethod = $_POST['payment_method'] ?? $_GET['payment_method'] ?? 'mpesa';

// Appointment ID — accept from POST (form submit), GET (URL param), or ?id=
$rawAppointmentId = $_POST['appointment_id']
    ?? $_GET['appointment_id']
    ?? $_GET['id']
    ?? null;

$phoneNumber = $_POST['phone_number'] ?? $_GET['phone_number'] ?? '';

// ---------------------------------------------------------------------
// 2. Validate & look up the appointment ID
// ---------------------------------------------------------------------
if (empty($rawAppointmentId)) {
    // Parameter was missing entirely
    mpesaError(
        'Appointment ID is required to process payment. '
        . 'Please go back and try again or select the Pay button for an appointment.',
        ['received' => $rawAppointmentId]
    );
}

// Sanitize to a strict integer
$appointmentId = intval($rawAppointmentId);

if ($appointmentId < 1) {
    mpesaError(
        'Invalid appointment ID. The value "' . htmlspecialchars((string) $rawAppointmentId)
        . '" is not a valid positive integer.',
        ['received' => $rawAppointmentId]
    );
}

// Look up the appointment directly with prepared statement + error checking
// (bypasses db.php helpers which silently swallow prepare/execute failures)
global $conn;

$checkApp = getRow("SELECT id FROM appointments WHERE id = ?", [$appointmentId]);

if ($conn && mysqli_errno($conn)) {
    // A database error occurred during the query
    $dbError = mysqli_error($conn);
    mysqli_clear_error($conn);
    error_log("M-Pesa: DB error while looking up appointment {$appointmentId}: {$dbError}");
    mpesaError(
        'Unable to verify the appointment due to a database error. '
        . 'Please try again later or contact support.',
        ['appointment_id' => $appointmentId],
        500
    );
}

if (!$checkApp) {
    // ID was passed but no matching record exists
    error_log("M-Pesa: Appointment not found for ID {$appointmentId}");
    mpesaError(
        'Appointment not found. The appointment ID (' . $appointmentId
        . ') does not exist in the database. Please verify the appointment and try again.',
        ['appointment_id' => $appointmentId]
    );
}

// Appointment confirmed — $appointmentId is a valid, existing record

if (empty($phoneNumber)) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
    exit;
}

// ---------------------------------------------------------------------
// 3. Calculate fee based on payment type
// ---------------------------------------------------------------------
if ($paymentType === 'continuation') {
    // First check session for pre-calculated continuation fee (set by continue_case.php)
    if (isset($_SESSION['continuation_fee']) && isset($_SESSION['continue_appointment_id']) && $_SESSION['continue_appointment_id'] == $appointmentId) {
        $amount = $_SESSION['continuation_fee'];
    } else {
        // Check if this is a continuation appointment with original_appointment_id
        $appointment = getRow("SELECT original_appointment_id FROM appointments WHERE id = ?", [$appointmentId]);
        $originalAppointmentId = $appointment['original_appointment_id'] ?? $appointmentId;
        
        // Get the original payment amount for the parent appointment
        $originalPayment = getRow(
            "SELECT amount FROM payments WHERE appointment_id = ? AND status = 'COMPLETED' ORDER BY created_at DESC LIMIT 1",
            [$originalAppointmentId]
        );
        if ($originalPayment && $originalPayment['amount'] > 0) {
            $amount = round($originalPayment['amount'] * 0.5, 2);
        } else {
            $amount = CONTINUATION_FEE; // fallback
        }
    }
} else {
    $amount = BOOKING_FEE;
}

if ($paymentMethod === 'cash') {
    global $conn;
    
    // Recalculate amount for cash payments
    if ($paymentType === 'continuation') {
        if (isset($_SESSION['continuation_fee']) && isset($_SESSION['continue_appointment_id']) && $_SESSION['continue_appointment_id'] == $appointmentId) {
            $amount = $_SESSION['continuation_fee'];
        } else {
            $appointment = getRow("SELECT original_appointment_id FROM appointments WHERE id = ?", [$appointmentId]);
            $originalAppointmentId = $appointment['original_appointment_id'] ?? $appointmentId;
            
            $originalPayment = getRow(
                "SELECT amount FROM payments WHERE appointment_id = ? AND status = 'COMPLETED' ORDER BY created_at DESC LIMIT 1",
                [$originalAppointmentId]
            );
            if ($originalPayment && $originalPayment['amount'] > 0) {
                $amount = round($originalPayment['amount'] * 0.5, 2);
            } else {
                $amount = CONTINUATION_FEE;
            }
        }
    } else {
        $amount = BOOKING_FEE;
    }
    
    // Insert payment record as COMPLETED for cash
    $sql = "INSERT INTO payments (appointment_id, amount, phone_number, status, mpesa_receipt_number, created_at, updated_at)
            VALUES (?, ?, ?, 'COMPLETED', ?, NOW(), NOW())";
    $receiptRef = 'CASH_' . time() . '_' . $appointmentId;
    execute($sql, [$appointmentId, $amount, $phoneNumber, $receiptRef]);
    
    // Send SMS confirmation (suppress any output)
    $user = getRow("SELECT u.name, u.phone FROM users u JOIN appointments a ON u.id = a.user_id WHERE a.id = ?", [$appointmentId]);
    if ($user && !empty($user['phone'])) {
        $smsMsg = "Dear {$user['name']}, cash payment of KES {$amount} has been recorded (Receipt: {$receiptRef}). - Justice Law Firm";
        @sendSMS($user['phone'], $smsMsg);
    }
    
    // Clear any output buffer and send JSON response
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Cash payment recorded successfully', 'receipt' => $receiptRef]);
    exit;
}


// ---------------------------------------------------------------------
// 4. Format phone number to 2547XXXXXXXX or 2541XXXXXXXX
// ---------------------------------------------------------------------
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

// ---------------------------------------------------------------------
// 4. Generate dynamic password: base64(Shortcode + Passkey + Timestamp)
// ---------------------------------------------------------------------
$timestamp = date('YmdHis');
$password  = base64_encode($businessShortCode . $passkey . $timestamp);

// ---------------------------------------------------------------------
// 5. Get OAuth Access Token from Daraja Sandbox
// ---------------------------------------------------------------------
// Validate credentials are loaded and non-empty
if (empty($consumerKey) || empty($consumerSecret)) {
    mpesaError(
        'M-Pesa sandbox credentials are missing. '
        . 'Set MPESA_SANDBOX_CONSUMER_KEY and MPESA_SANDBOX_CONSUMER_SECRET in your .env file '
        . '(register at https://developer.safaricom.co.ke).',
        ['oauth_url' => $oauthUrl],
        500
    );
}

// Trim any stray whitespace or newline characters from credentials
$consumerKey    = trim($consumerKey);
$consumerSecret = trim($consumerSecret);

error_log("M-Pesa OAuth request: GET $oauthUrl");
error_log("M-Pesa Consumer Key (first 8 chars): " . substr($consumerKey, 0, 8) . '...');
error_log("M-Pesa Consumer Secret (first 8 chars): " . substr($consumerSecret, 0, 8) . '...');

// Build Basic Authentication header: base64(ConsumerKey:ConsumerSecret)
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);

$ch = curl_init($oauthUrl);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials,
    'Content-Type: application/json',
    'User-Agent: JusticeLaw-Mpesa-Integration'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$authResponseRaw   = curl_exec($ch);
$authError         = curl_error($ch);
$authErrno         = curl_errno($ch);
$authHttpStatus    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug: log auth response
error_log("M-Pesa OAuth Response: HTTP $authHttpStatus - " . ($authResponseRaw ?: '(empty response)'));

if ($authResponseRaw === false || $authErrno) {
    // cURL-level error (connection refused, DNS failure, SSL error, timeout, etc.)
    error_log("M-Pesa OAuth cURL error ($authErrno): $authError");
    mpesaError(
        'Unable to connect to M-Pesa OAuth service. '
        . ($authError ? 'cURL error: ' . $authError : ''),
        [
            'curl_error'  => $authError,
            'curl_errno'  => $authErrno,
            'oauth_url'   => $oauthUrl
        ],
        502
    );
}

$authResponse = json_decode($authResponseRaw, true);

if (!is_array($authResponse) || !isset($authResponse['access_token'])) {
    // Parse Safaricom's error response
    $safaricomError    = is_array($authResponse)
        ? ($authResponse['error_description'] ?? $authResponse['error'] ?? '')
        : '';
    $safaricomErrorRaw = $authResponseRaw;

    error_log("M-Pesa OAuth failed ($authHttpStatus): $safaricomErrorRaw");

    $errorDetails = [
        'http_status'         => $authHttpStatus,
        'safaricom_error'     => $safaricomError ?: 'Unknown authentication error',
        'safaricom_response'  => is_array($authResponse) ? $authResponse : null,
        'raw_response'        => $authResponseRaw,
        'oauth_url'           => $oauthUrl
    ];

    // Provide a helpful, specific error message based on the Safaricom response
    if ($authHttpStatus == 401 || $authHttpStatus == 403) {
        $errorDetails['hint'] = 'Check that your Consumer Key and Consumer Secret are correct '
                             . 'and that the app is active in the Safaricom Developer Portal.';
    } elseif ($authHttpStatus == 400) {
        $errorDetails['hint'] = 'The request was malformed. Verify the OAuth URL and parameters.';
    }

    mpesaError(
        'M-Pesa authentication failed' . ($safaricomError ? ': ' . $safaricomError : '') . '.',
        $errorDetails,
        502
    );
}

$accessToken = $authResponse['access_token'];

// ---------------------------------------------------------------------
// 6. Build STK Push payload
// ---------------------------------------------------------------------
$payload = [
    'BusinessShortCode' => $businessShortCode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => (int) $amount,
    'PartyA'            => $phoneNumber,
    'PartyB'            => $businessShortCode,
    'PhoneNumber'       => $phoneNumber,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'JusticeLaw',
    'TransactionDesc'   => ucfirst($paymentType) . ' Fee Payment'
];

// Debug: log full STK Push request
error_log("M-Pesa STK Push Request: " . json_encode($payload, JSON_PRETTY_PRINT));
error_log("M-Pesa STK Push URL: $stkPushUrl");
error_log("M-Pesa Access Token (first 20 chars): " . substr($accessToken, 0, 20) . '...');

// ---------------------------------------------------------------------
// 7. Execute STK Push Request
// ---------------------------------------------------------------------
$ch = curl_init($stkPushUrl);
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

$response        = curl_exec($ch);
$responseError   = curl_error($ch);
$httpStatus      = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Debug: log cURL info
$curlInfo = curl_getinfo($ch);
error_log("M-Pesa STK Push cURL Info: " . json_encode([
    'http_code' => $httpStatus,
    'total_time' => $curlInfo['total_time'] ?? 0,
    'connect_time' => $curlInfo['connect_time'] ?? 0,
    'ssl_verify_result' => $curlInfo['ssl_verify_result'] ?? 0,
]));

curl_close($ch);

if ($response === false) {
    error_log("M-Pesa STK Push cURL error ($responseError): HTTP $httpStatus");
    mpesaError('Unable to connect to M-Pesa STK Push service.', ['details' => $responseError], 502);
}

// Debug: log STK Push raw response
error_log("M-Pesa STK Push Response: HTTP $httpStatus - $response");

$resData = json_decode($response, true);
if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($resData)) {
    mpesaError(
        'M-Pesa STK Push request failed.',
        [
            'http_status'  => $httpStatus,
            'response'     => is_array($resData) ? $resData : null,
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
        $appointmentId,
        $checkoutRequestId,
        $merchantRequestId,
        $amount,
        $phoneNumber
    ]);

    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status'            => 'success',
        'message'           => 'STK Push sent to ' . $phoneNumber . '. Please check your phone to complete payment.',
        'CheckoutRequestID' => $checkoutRequestId,
        'amount'            => $amount,
        'phone_number'      => $phoneNumber,
        'environment'       => 'sandbox'
    ]);
    exit;
}

mpesaError(
    'M-Pesa rejected the STK Push request.',
    [
        'response'    => $resData,
        'http_status' => $httpStatus
    ],
    502
);
?>
