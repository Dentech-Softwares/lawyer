<?php
require_once 'db.php';
require_once 'includes/helpers.php';

$receipt = $_GET['receipt'] ?? '';
$appointmentId = $_SESSION['booking_appointment_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Fetch appointment details if available
$appointment = null;
if ($appointmentId) {
    $appointment = getRow("SELECT * FROM appointments WHERE id = ?", [$appointmentId]);
}

// Fetch payment details if available
$payment = null;
if ($appointmentId) {
    $payment = getRow("SELECT * FROM payments WHERE appointment_id = ? ORDER BY id DESC LIMIT 1", [$appointmentId]);
}

// If we have a receipt parameter, look up the payment by receipt number
if (!$payment && !empty($receipt)) {
    $payment = getRow("SELECT * FROM payments WHERE mpesa_receipt_number = ?", [$receipt]);
    if ($payment) {
        $appointment = getRow("SELECT * FROM appointments WHERE id = ?", [$payment['appointment_id']]);
    }
}

$receiptNumber = $payment['mpesa_receipt_number'] ?? $receipt;
$amount = $payment['amount'] ?? 500;
$caseType = $appointment['case_type'] ?? 'Consultation';
$apptDate = $appointment['date'] ?? date('Y-m-d');
$apptTime = $appointment['time'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <style>
        :root {
            --primary: #1a3a5c;
            --secondary: #2c5282;
            --accent: #d4af37;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --success: #38a169;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light-gray) 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }
        .success-header {
            background: linear-gradient(135deg, var(--success) 0%, #2f855a 100%);
            color: var(--white);
            padding: 3rem 2rem;
        }
        .success-header i {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .success-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .success-body {
            padding: 2.5rem;
        }
        .receipt-info {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        .receipt-info p {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        .receipt-info strong {
            color: var(--secondary);
        }
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #b8962e;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--light-gray);
            color: var(--primary);
            margin-left: 1rem;
        }
        .btn-secondary:hover {
            background: #d0d7e0;
        }
        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>Payment Successful!</h1>
            <p>Your payment has been confirmed</p>
        </div>
        <div class="success-body">
            <p style="color: var(--gray); margin-bottom: 1rem;">
                Thank you for your payment. Your appointment is now confirmed.
            </p>

            <div class="receipt-info">
                <p><strong>M-Pesa Receipt Number:</strong> <?php echo htmlspecialchars($receiptNumber ?: 'N/A'); ?></p>
                <p><strong>Amount Paid:</strong> KSh <?php echo number_format($amount, 2); ?></p>
                <p><strong>Case Type:</strong> <?php echo htmlspecialchars($caseType); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($apptDate); ?></p>
                <?php if ($apptTime): ?>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($apptTime); ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> <span style="color: var(--success); font-weight: 600;">Confirmed</span></p>
            </div>

            <div class="actions">
                <a href="client_dashboard.php" class="btn">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
                <a href="client_appointments.php" class="btn btn-secondary">
                    <i class="fas fa-calendar-alt"></i> View Appointments
                </a>
            </div>
        </div>
    </div>
</body>
</html>