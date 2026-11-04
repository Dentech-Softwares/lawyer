<?php
require_once 'db.php';
require_once 'includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$case_id = (int)($_GET['case_id'] ?? 0);

$appointment = getRow("SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone
                       FROM appointments a
                       LEFT JOIN users u ON a.user_id = u.id
                       WHERE a.id = ?", [$case_id]);

$payment = getRow("SELECT * FROM payments WHERE appointment_id = ? ORDER BY id DESC LIMIT 1", [$case_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/i.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #1a3a5c; }
        .detail-value { color: #1a202c; text-align: right; }
        .status-badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.pending { background: rgba(214, 158, 46, 0.1); color: #d69e2e; }
        .status-badge.completed { background: rgba(56, 161, 105, 0.1); color: #38a169; }
        .status-badge.failed { background: rgba(229, 62, 62, 0.1); color: #e53e3e; }
        .section-title { font-size: 1rem; font-weight: 600; color: #1a3a5c; margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .section-title:first-child { margin-top: 0; }
    </style>
</head>
<body style="padding: 1rem;">
    <?php if (!$appointment): ?>
        <p style="text-align:center; color:var(--danger); padding:2rem;">Appointment/Case not found.</p>
    <?php else: ?>
        <div class="section-title"><i class="fas fa-balance-scale"></i> Case Information</div>
        <div class="detail-row"><span class="detail-label">Case ID</span><span class="detail-value">#<?php echo $appointment['id']; ?></span></div>
        <div class="detail-row"><span class="detail-label">Case Type</span><span class="detail-value"><?php echo htmlspecialchars($appointment['case_type']); ?></span></div>
        <div class="detail-row"><span class="detail-label">Client</span><span class="detail-value"><?php echo htmlspecialchars($appointment['client_name'] ?? 'N/A'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Client Email</span><span class="detail-value"><?php echo htmlspecialchars($appointment['client_email'] ?? 'N/A'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Client Phone</span><span class="detail-value"><?php echo htmlspecialchars($appointment['client_phone'] ?? 'N/A'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Appointment Date</span><span class="detail-value"><?php echo htmlspecialchars($appointment['date'] ?? 'N/A'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Appointment Time</span><span class="detail-value"><?php echo htmlspecialchars($appointment['time'] ?? 'N/A'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="status-badge <?php echo $appointment['status']; ?>"><?php echo htmlspecialchars($appointment['status']); ?></span></span></div>

        <div class="section-title"><i class="fas fa-money-bill-wave"></i> Payment Information</div>
        <?php if ($payment): ?>
            <div class="detail-row"><span class="detail-label">Payment ID</span><span class="detail-value">#<?php echo $payment['id']; ?></span></div>
            <div class="detail-row"><span class="detail-label">Amount</span><span class="detail-value">KSh <?php echo number_format($payment['amount'], 2); ?></span></div>
            <div class="detail-row"><span class="detail-label">Payment Method</span><span class="detail-value"><?php echo htmlspecialchars($payment['payment_method']); ?></span></div>
            <div class="detail-row"><span class="detail-label">M-Pesa Receipt Number</span><span class="detail-value"><?php echo $payment['mpesa_receipt_number'] ? htmlspecialchars($payment['mpesa_receipt_number']) : '<span style="color:#718096;font-style:italic;">Pending</span>'; ?></span></div>
            <div class="detail-row"><span class="detail-label">Checkout Request ID</span><span class="detail-value" style="font-size:0.85rem; font-family:monospace; cursor:help; border-bottom:1px dotted #ccc;" title="<?php echo htmlspecialchars($payment['checkout_request_id'] ?? 'N/A'); ?>">REQ-<?php echo !empty($payment['checkout_request_id']) ? htmlspecialchars(substr($payment['checkout_request_id'], -6)) : 'N/A'; ?></span></div>
            <div class="detail-row"><span class="detail-label">Payment Status</span><span class="detail-value"><span class="status-badge <?php echo strtolower($payment['status']); ?>"> <?php echo $payment['status']; ?></span></span></div>
            <div class="detail-row"><span class="detail-label">Phone Number</span><span class="detail-value"><?php echo htmlspecialchars($payment['phone_number'] ?? 'N/A'); ?></span></div>
            <div class="detail-row"><span class="detail-label">Date Paid</span><span class="detail-value"><?php echo $payment['created_at'] ? date('M d, Y h:i A', strtotime($payment['created_at'])) : 'N/A'; ?></span></div>
        <?php else: ?>
            <p style="text-align:center; color:var(--gray); padding:1rem;">No payment record found for this case.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>