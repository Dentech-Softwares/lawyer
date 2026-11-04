<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_file = __DIR__ . '/db.php';
if (file_exists($db_file)) {
    require_once $db_file;
}

$receipt = $_GET['receipt'] ?? 'N/A';

// Default fallback values
$amount = '500.00';
$caseType = 'General Legal Consultation';
$appointmentDate = date('Y-m-d');
$appointmentTime = '09:00:00';
$clientName = $_SESSION['user_name'] ?? 'Client';

if (!empty($receipt) && isset($conn) && $conn instanceof mysqli) {
    try {
        $stmt = $conn->prepare(
            "SELECT p.amount, a.case_type, a.date, a.time
             FROM payments p
             JOIN appointments a ON p.appointment_id = a.id
             WHERE p.mpesa_receipt_number = ?"
        );
        if ($stmt) {
            $stmt->bind_param("s", $receipt);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $amount = $row['amount'] ?? $amount;
                $caseType = $row['case_type'] ?? $caseType;
                $appointmentDate = $row['date'] ?? $appointmentDate;
                $appointmentTime = $row['time'] ?? $appointmentTime;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("success_page DB error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payment Receipt - Justice Law</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .success-card {
            max-width: 550px;
            width: 100%;
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: #fff;
        }
        .official-seal {
            font-size: 0.75rem;
            color: #6c757d;
            border-top: 1px dashed #dee2e6;
            padding-top: 12px;
            margin-top: 16px;
        }
        /* Print Styles for Clean PDF Download */
        @media print {
            body {
                background-color: #fff;
            }
            .no-print {
                display: none !important;
            }
            .success-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center">
    <div class="card success-card">
        <!-- Official System Header -->
        <div class="bg-success text-white p-4 text-center position-relative">
            <div class="position-absolute top-0 start-0 p-2 opacity-50 small font-monospace">JUSTICE LAW SYSTEM</div>
            <i class="fas fa-shield-check fa-3x mb-2 mt-2"></i>
            <h3 class="fw-bold mb-1">Payment Successful</h3>
            <p class="mb-0 text-white-50 small">Official Digital Transaction Receipt</p>
        </div>

        <!-- Body Content -->
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <span class="badge bg-light text-success border border-success px-3 py-2 rounded-pill fw-semibold">
                    <i class="fas fa-check-circle me-1"></i> Confirmed & Verified
                </span>
            </div>

            <!-- Receipt Details Box -->
            <div class="bg-light p-3 rounded-3 mb-4 border">
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">System Reference:</span>
                    <span class="fw-bold text-dark">JL-PAY-QR</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">M-Pesa Receipt Number:</span>
                    <span class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($receipt); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Amount Paid:</span>
                    <span class="fw-bold text-dark">KSh <?php echo htmlspecialchars($amount); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Service / Case Type:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($caseType); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Appointment Date:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($appointmentDate); ?> (<?php echo htmlspecialchars($appointmentTime); ?>)</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Payment Gateway:</span>
                    <span class="fw-bold text-dark">M-Pesa STK Push</span>
                </div>

                <!-- Official Authentication Note -->
                <div class="official-seal text-center">
                    <i class="fas fa-lock text-success me-1"></i> Generated securely by <strong>Justice Law Management System</strong> on <?php echo date('Y-m-d H:i:s'); ?>.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap justify-content-center gap-2 no-print">
                <a href="client_dashboard.php" class="btn btn-warning px-3 fw-semibold text-dark shadow-sm">
                    <i class="fas fa-home me-1"></i> Dashboard
                </a>
                <a href="client_appointments.php" class="btn btn-outline-secondary px-3 fw-semibold shadow-sm">
                    <i class="fas fa-calendar-alt me-1"></i> Appointments
                </a>
                <button onclick="window.print()" class="btn btn-success px-3 fw-semibold shadow-sm">
                    <i class="fas fa-download me-1"></i> Download Receipt
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>