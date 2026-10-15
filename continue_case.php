<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Payment constant
define('CONTINUATION_FEE', 250);

require_once 'db.php';

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_names = explode(' ', $user_name);
$user_initials = strtoupper(substr($user_names[0], 0, 1) . (isset($user_names[1]) ? substr($user_names[1], 0, 1) : ''));
$current_date = date('F j, Y');

// Get notifications
$notifications = getAllRows(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$user_id]
);
$unreadNotifications = getRow(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'",
    [$user_id]
);

// Get user info
$user = getRow("SELECT * FROM users WHERE id = ?", [$user_id]);

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('Location: client_dashboard.php');
    exit;
}

$id = (int)$_GET['id'];

// Get appointment with client details (verify ownership)
$appointment = getRow(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
     FROM appointments a 
     JOIN users u ON a.user_id = u.id 
     WHERE a.id = ? AND a.user_id = ? 
     AND (a.status IN ('approved', 'completed') OR (a.is_continuation = 1 AND a.original_appointment_id IS NOT NULL))",
    [$id, $user_id]
);

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found or cannot be continued.';
    header('Location: client_dashboard.php');
    exit;
}

// Get existing documents for this appointment
$documents = getAllRows(
    "SELECT * FROM documents WHERE appointment_id = ? ORDER BY uploaded_at DESC",
    [$id]
);

// Handle note addition
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['additional_note'])) {
        $additionalNote = trim($_POST['additional_note']);

        if (!empty($additionalNote)) {
            // Append new note to existing message
            $existingNotes = $appointment['notes'] ?? '';
            $newNotes = $existingNotes . "\n\n" . date('Y-m-d H:i') . " - " . $additionalNote;

            execute(
                "UPDATE appointments SET notes = ? WHERE id = ?",
                [$newNotes, $id]
            );

            // Create notification for admin
            $admin = getRow("SELECT id FROM admin_users LIMIT 1");
            if ($admin) {
                execute(
                    "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                    [$admin['id'], 'Client ' . $appointment['client_name'] . ' added additional notes to case #' . $id]
                );
            }

            // Also create notification for client
            execute(
                "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                [$user_id, 'Your additional notes have been saved for case #' . $id]
            );

            // Refresh appointment data
            $appointment = getRow(
                "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
                 FROM appointments a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.id = ? AND a.user_id = ?",
                [$id, $user_id]
            );

            $_SESSION['success'] = 'Additional notes added successfully!';
            header("Location: continue_case.php?id=" . $id);
            exit;
        } else {
            $error = 'Please enter a note before saving.';
        }
    }

    if (isset($_POST['document_note'])) {
        // This would be handled by document upload, not here
    }

    if (isset($_POST['continue_case_request'])) {
        // Get original appointment's completed payment to calculate 50% fee
        $originalPayment = getRow(
            "SELECT amount FROM payments WHERE appointment_id = ? AND status = 'COMPLETED' ORDER BY created_at DESC LIMIT 1",
            [$id]
        );
        $continuationFee = CONTINUATION_FEE; // fallback
        if ($originalPayment && $originalPayment['amount'] > 0) {
            $continuationFee = round($originalPayment['amount'] * 0.5, 2);
        }

        // Create NEW continuation appointment linked to original
        $result = execute(
            "INSERT INTO appointments (user_id, case_type, date, time, message, status, is_continuation, original_appointment_id) 
             VALUES (?, ?, NULL, NULL, ?, 'pending', 1, ?)",
            [$appointment['user_id'], $appointment['case_type'], 'Continuation requested for case #' . $id, $id]
        );

        if ($result) {
            $newAppointmentId = getLastId();

            // Store continuation fee in session for payment
            $_SESSION['continue_appointment_id'] = $newAppointmentId;
            $_SESSION['continuation_fee'] = $continuationFee;
            $_SESSION['original_appointment_id'] = $id;

            $admin = getRow("SELECT id FROM admin_users LIMIT 1");
            if ($admin) {
                execute(
                    "INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')",
                    [$admin['id'], 'Client ' . $appointment['client_name'] . ' requested to continue case #' . $id . ' (' . $appointment['case_type'] . ') - please schedule appointment #' . $newAppointmentId]
                );
            }

            $_SESSION['success'] = 'Case continuation request sent to admin. They will schedule your appointment soon. The continuation fee is KSh ' . number_format($continuationFee) . ' (50% of original).';
            header("Location: continue_case.php?id=" . $newAppointmentId);
            exit;
        } else {
            $_SESSION['error'] = 'Failed to create continuation request. Please try again.';
            header("Location: continue_case.php?id=" . $id);
            exit;
        }
    }
}

// Handle flash messages
$flashSuccess = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
$flashError = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$message = $flashSuccess ?: $flashError;
$success = $message;
$error = '';

// Calculate continuation fee (50% of original appointment payment)
$continuationFee = CONTINUATION_FEE; // fallback
$originalPayment = getRow(
    "SELECT amount FROM payments WHERE appointment_id = ? AND status = 'COMPLETED' ORDER BY created_at DESC LIMIT 1",
    [$id]
);
if ($originalPayment && $originalPayment['amount'] > 0) {
    $continuationFee = round($originalPayment['amount'] * 0.5, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continue Case - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <style>
        :root {
            --primary: #1a3a5c;
            --secondary: #2c5282;
            --accent: #d4af37;
            --accent-hover: #b8962e;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --dark: #1a202c;
            --gray: #718096;
            --success: #38a169;
            --danger: #e53e3e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light-gray) 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 120px 2rem 2rem;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--primary);
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        /* Case Container */
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .booking-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .booking-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2.5rem;
            text-align: center;
            color: var(--white);
        }

        .booking-header i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .booking-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .booking-header p {
            opacity: 0.9;
        }

        .booking-body {
            padding: 2.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Detail Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--gray);
            font-weight: 600;
        }

        .detail-value {
            color: var(--dark);
            font-size: 1rem;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background: rgba(214, 158, 46, 0.15);
            color: var(--warning);
            border: 1px solid rgba(214, 158, 46, 0.3);
        }

        .status-badge.approved {
            background: rgba(56, 161, 105, 0.15);
            color: var(--success);
            border: 1px solid rgba(56, 161, 105, 0.3);
        }

        .status-badge.rejected {
            background: rgba(229, 62, 62, 0.15);
            color: var(--danger);
            border: 1px solid rgba(229, 62, 62, 0.3);
        }

        .status-badge.completed {
            background: rgba(49, 130, 206, 0.15);
            color: var(--info);
            border: 1px solid rgba(49, 130, 206, 0.3);
        }

        /* Notes */
        .notes-container {
            margin-bottom: 2rem;
        }

        .notes-container h3 {
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .note-card {
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-left: 3px solid var(--accent);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .note-card .note-time {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .note-card .note-text {
            color: var(--dark);
            line-height: 1.5;
        }

        .no-notes {
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }

        /* Add Notes Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group label i {
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            line-height: 1.5;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            max-width: 400px;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
        }

        .submit-btn {
            flex: 1;
            padding: 0.8rem 1.5rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(212, 175, 55, 0.3);
        }

        .cancel-btn {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--light-gray);
            color: var(--dark);
            border: 1px solid var(--light-gray);
            text-decoration: none;
        }

        .cancel-btn:hover {
            background: var(--dark);
            color: var(--white);
            transform: translateY(-1px);
        }

        .submit-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.5), 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        /* Action Footer */
        .action-footer {
            display: flex;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--light-gray);
            background: var(--light-gray);
            border-radius: 0 0 20px 20px;
            margin: -1.5rem -2.5rem 1.5rem -2.5rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }

        .action-footer .btn {
            padding: 0.65rem 1.3rem;
            font-size: 0.9rem;
            border-radius: 6px;
            white-space: nowrap;
        }

        .btn-print {
            background: var(--white);
            color: var(--secondary);
            border: 2px solid var(--secondary);
            font-weight: 600;
        }

        .btn-print:hover {
            background: var(--secondary);
            color: var(--white);
            box-shadow: 0 3px 12px rgba(44, 82, 130, 0.3);
        }

        .btn-print:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.3);
        }

        .btn-back {
            background: var(--white);
            color: var(--gray);
            border: 2px solid var(--gray);
            font-weight: 600;
        }

        .btn-back:hover {
            background: var(--gray);
            color: var(--white);
            box-shadow: 0 3px 12px rgba(113, 128, 150, 0.3);
        }

        .btn-back:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(113, 128, 150, 0.3);
        }

        .btn-cancel {
            background: var(--white);
            color: var(--danger);
            border: 2px solid var(--danger);
            font-weight: 600;
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: var(--white);
            box-shadow: 0 3px 12px rgba(229, 62, 62, 0.3);
        }

        .btn-cancel:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.3);
        }

        /* Documents Table */
        .documents-section {
            margin-top: 2rem;
        }

        .documents-section h3 {
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.8rem 1.5rem;
            text-align: left;
        }

        th {
            background: var(--light-gray);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            border-bottom: 1px solid var(--light-gray);
            color: var(--dark);
        }

        tr:hover {
            background: var(--light-gray);
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .document-info i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .document-date {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .btn-icon i {
            color: inherit;
        }

        .btn-view {
            background: var(--light-gray);
            color: var(--primary);
        }

        .btn-view:hover {
            background: var(--info);
            color: var(--white);
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            body {
                padding: 100px 1rem 2rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .action-footer .btn {
                flex: 1;
                justify-content: center;
                max-width: 220px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <nav class="navbar">
                <div class="logo">
                    <i class="fas fa-gavel"></i>
                    <span>Justice Law</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="auth-buttons">
                    <a href="client_dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- Continue Case Content -->
    <div class="booking-container">
        <div class="booking-card">
            <div class="booking-header">
                <i class="fas fa-gavel"></i>
                <h1>Case #<?php echo $appointment['id']; ?> - Continue Case</h1>
                <p><?php echo htmlspecialchars($appointment['case_type']); ?> | 
                    <?php if ($appointment['date'] && $appointment['time']): ?>
                        <?php echo date('M d, Y', strtotime($appointment['date'])); ?> at <?php echo date('h:i A', strtotime($appointment['time'])); ?>
                    <?php else: ?>
                        Not scheduled yet
                    <?php endif; ?>
                </p>
            </div>

            <div class="booking-body">
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Appointment Details -->
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Case Type</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['case_type']); ?></span>
                    </div>
<div class="detail-item">
                         <span class="detail-label">Status</span>
                         <span class="detail-value">
                             <span class="status-badge <?php echo $appointment['status']; ?>">
                                 <?php echo ucfirst($appointment['status']); ?>
                             </span>
<?php if ($appointment['is_continuation']): ?>
                                  <span class="status-badge" style="background: rgba(212, 175, 55, 0.15); color: var(--accent); border: 1px solid var(--accent); margin-left: 0.5rem;">
                                      <i class="fas fa-sync-alt"></i> <?php echo ucfirst($appointment['status']); ?>
                                  </span>
                              <?php endif; ?>
                         </span>
                     </div>
<div class="detail-item">
                          <span class="detail-label">Continuation</span>
                          <span class="detail-value">
                              <?php if ($appointment['is_continuation']): ?>
                                  <span style="color: var(--accent); font-weight: 600;">
                                      Yes - <?php echo ucfirst($appointment['status']); ?>
                                  </span>
                              <?php else: ?>
                                  <span style="color: var(--gray);">No</span>
                              <?php endif; ?>
                          </span>
                      </div>
<div class="detail-item">
                         <span class="detail-label">Date & Time</span>
                         <span class="detail-value">
                             <?php if ($appointment['date'] && $appointment['time']): ?>
                                 <?php echo date('M d, Y', strtotime($appointment['date'])); ?>
                                 <small><?php echo date('h:i A', strtotime($appointment['time'])); ?></small>
                             <?php else: ?>
                                 <span style="color: var(--gray);">Not scheduled yet</span>
                             <?php endif; ?>
                         </span>
                     </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                    </div>
<div class="detail-item">
                         <span class="detail-label">Client Email</span>
                         <span class="detail-value"><?php echo htmlspecialchars($appointment['client_email'] ?? ''); ?></span>
                     </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_phone'] ?? ''); ?></span>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <span class="detail-label">Client Message</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['message'] ?? '')); ?></span>
                    </div>
</div>

                <!-- Payment Section - Always visible for continuation appointments -->
                <div class="payment-section" style="background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center; border: 2px dashed var(--accent);">
                    <h3 style="color: var(--primary); margin-bottom: 0.75rem; font-size: 1.1rem;">
                        <i class="fas fa-mobile-alt"></i> Complete Your Payment
                    </h3>
                    <p style="color: var(--gray); margin-bottom: 1rem; font-size: 0.95rem;">
                        Continuation fee: <strong>KSh <?php echo number_format($continuationFee); ?></strong> (50% of original appointment fee)
                    </p>
                    
                    <!-- Payment Method Selector -->
                    <div class="payment-method-selector" style="margin-bottom: 1rem; text-align: left;">
                        <label style="display: block; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="fas fa-money-bill-wave"></i> Payment Method
                        </label>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1rem; border: 2px solid var(--light-gray); border-radius: 8px; transition: all 0.3s; background: var(--white);" onclick="selectPaymentMethod('mpesa', this)">
                                <input type="radio" name="payment_method" value="mpesa" id="method_mpesa" checked style="display: none;">
                                <i class="fas fa-mobile-alt" style="color: var(--accent); font-size: 1.2rem;"></i>
                                <span style="font-weight: 500;">M-Pesa</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1rem; border: 2px solid var(--light-gray); border-radius: 8px; transition: all 0.3s; background: var(--white);" onclick="selectPaymentMethod('cash', this)">
                                <input type="radio" name="payment_method" value="cash" id="method_cash" style="display: none;">
                                <i class="fas fa-money-bill" style="color: var(--success); font-size: 1.2rem;"></i>
                                <span style="font-weight: 500;">Cash</span>
                            </label>
                        </div>
                        <input type="hidden" id="selected_payment_method" name="payment_method" value="mpesa">
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
                        <button class="submit-btn" id="payNowBtn" style="max-width: 300px;">
                            <i class="fas fa-money-bill-wave"></i> Pay Now
                        </button>
                        
                        <?php if ($appointment['status'] !== 'completed'): ?>
                        <form method="POST" action="" style="display: inline;">
                            <button type="submit" name="continue_case_request" class="btn btn-primary" onclick="return confirm('This will send a request to the admin to schedule your next appointment. Continue?');">
                                <i class="fas fa-gavel"></i> Continue Case
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
                    <div style="background:var(--white); border-radius:20px; padding:2rem; max-width:450px; width:90%; margin:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                        <div style="text-align:center; margin-bottom:1.5rem;">
                            <i class="fas fa-mobile-alt" style="font-size:2.5rem; color:var(--accent); margin-bottom:0.5rem;"></i>
                            <h3 style="color:var(--primary);">M-Pesa STK Push Payment</h3>
                            <p style="color:var(--gray); font-size:0.9rem;">Enter your M-Pesa number to pay KSh <?php echo number_format($continuationFee); ?></p>
                        </div>
                        <div class="form-group" style="margin-bottom:1.5rem;">
                            <label for="mpesaPhone" style="display:block; color:var(--primary); font-weight:600; margin-bottom:0.5rem;">
                                <i class="fas fa-phone"></i> M-Pesa Phone Number
                            </label>
                            <input type="tel" id="mpesaPhone" placeholder="2547XXXXXXXX"
                                   style="width:100%; padding:0.8rem; border:2px solid var(--light-gray); border-radius:8px; font-size:1rem; text-align:center;">
                        </div>
                        <div id="paymentStatus" style="display:none; text-align:center; padding:1rem; margin-bottom:1rem; border-radius:8px; background:#fff3cd; color:#856404; border:1px solid #ffeaa7;"></div>
                        <div style="display:flex; gap:1rem;">
                            <button class="btn btn-back" id="cancelPayment" style="flex:1;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button class="btn btn-primary" id="confirmPayment" style="flex:1;">
                                <i class="fas fa-check"></i> Pay
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="action-footer">
                    <a href="print_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-print" target="_blank" title="Print case details">
                        <i class="fas fa-print"></i> Print Details
                    </a>
                </div>

                <!-- Case Notes -->
                <div class="notes-container">
                    <h3><i class="fas fa-clipboard-edit"></i> Case Notes</h3>
                    <?php if(!empty($appointment['notes'])): ?>
                        <?php
                        $noteEntries = array_filter(array_map('trim', explode("\n\n", $appointment['notes'])));
                        ?>
                        <?php foreach($noteEntries as $entry): ?>
                            <?php if(!empty($entry)): ?>
                                <div class="note-card">
                                    <?php
                                    $lines = explode("\n", $entry);
                                    $noteTime = $lines[0] ?? '';
                                    $noteText = implode("\n", array_slice($lines, 1));
                                    ?>
                                    <div class="note-time"><?php echo htmlspecialchars($noteTime); ?></div>
                                    <div class="note-text"><?php echo nl2br(htmlspecialchars($noteText)); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notes">No case notes have been added yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Add Additional Notes -->
                <div class="form-group">
                    <label for="additional_note">
                        <i class="fas fa-plus"></i> Add Additional Notes
                    </label>
                    <form method="POST" action="">
                        <textarea name="additional_note" id="additional_note" placeholder="Enter any additional information, updates, or questions about your case..." required></textarea>
                        <div class="form-actions">
                            <a href="client_dashboard.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Save Notes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Documents -->
                <?php if(!empty($documents)): ?>
                    <div class="documents-section">
                        <h3><i class="fas fa-file-alt"></i> Uploaded Documents</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $doc): ?>
                                        <tr>
                                            <td>
                                                <div class="document-info">
                                                    <i class="fas fa-file-<?php echo strpos($doc['file_type'], 'pdf') !== false ? 'pdf' : 'word'; ?>"></i>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($doc['file_name']); ?></strong>
                                                        <div class="document-date"><?php echo date('M d, Y h:i A', strtotime($doc['uploaded_at'])); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo strtoupper(pathinfo($doc['file_name'], PATHINFO_EXTENSION)); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn-icon btn-view" download title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Payment method selection
        let selectedPaymentMethod = 'mpesa';
        
        function selectPaymentMethod(method, element) {
            selectedPaymentMethod = method;
            document.getElementById('selected_payment_method').value = method;
            
            // Update UI
            document.querySelectorAll('.payment-method-selector label').forEach(label => {
                label.style.borderColor = 'var(--light-gray)';
                label.style.background = 'var(--white)';
            });
            
            element.style.borderColor = 'var(--accent)';
            element.style.background = 'rgba(212, 175, 55, 0.1)';
        }

        // Payment Modal Logic
        const payNowBtn = document.getElementById('payNowBtn');
        const paymentModal = document.getElementById('paymentModal');
        const cancelPayment = document.getElementById('cancelPayment');
        const confirmPayment = document.getElementById('confirmPayment');
        const paymentStatus = document.getElementById('paymentStatus');

        if (payNowBtn) {
            payNowBtn.addEventListener('click', function() {
                paymentModal.style.display = 'flex';
                paymentStatus.style.display = 'none';
                paymentStatus.innerHTML = '';
                paymentStatus.className = '';
                
                // Update modal based on payment method
                const modalTitle = document.querySelector('#paymentModal h3');
                const modalDesc = document.querySelector('#paymentModal p');
                const phoneInput = document.getElementById('mpesaPhone');
                const phoneLabel = document.querySelector('#paymentModal label[for="mpesaPhone"]');
                
                if (selectedPaymentMethod === 'cash') {
                    modalTitle.innerHTML = '<i class="fas fa-money-bill" style="font-size:2.5rem; color:var(--success); margin-bottom:0.5rem;"></i> Cash Payment';
                    modalDesc.innerHTML = 'You selected <strong>Cash</strong> payment. Please pay KSh <?php echo number_format($continuationFee); ?> at the office.';
                    phoneLabel.innerHTML = '<i class="fas fa-user"></i> Your Name (for receipt)';
                    phoneInput.placeholder = 'Enter your name';
                    phoneInput.type = 'text';
                    phoneInput.style.textAlign = 'left';
                } else {
                    modalTitle.innerHTML = '<i class="fas fa-mobile-alt" style="font-size:2.5rem; color:var(--accent); margin-bottom:0.5rem;"></i> M-Pesa STK Push Payment';
                    modalDesc.innerHTML = 'Enter your M-Pesa number to pay KSh <?php echo number_format($continuationFee); ?>';
                    phoneLabel.innerHTML = '<i class="fas fa-phone"></i> M-Pesa Phone Number';
                    phoneInput.placeholder = '2547XXXXXXXX';
                    phoneInput.type = 'tel';
                    phoneInput.style.textAlign = 'center';
                }
            });
        }

        if (cancelPayment) {
            cancelPayment.addEventListener('click', function() {
                paymentModal.style.display = 'none';
            });
        }

        if (confirmPayment) {
            confirmPayment.addEventListener('click', function() {
                const input = document.getElementById('mpesaPhone').value.trim();
                
                if (selectedPaymentMethod === 'cash') {
                    // Cash payment - just record it
                    if (!input) {
                        paymentStatus.style.display = 'block';
                        paymentStatus.className = 'alert alert-danger';
                        paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your name for the receipt';
                        return;
                    }
                    
                    confirmPayment.disabled = true;
                    confirmPayment.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recording...';
                    paymentStatus.style.display = 'block';
                    paymentStatus.className = 'alert alert-success';
                    paymentStatus.innerHTML = '<i class="fas fa-info-circle"></i> Recording cash payment...';
                    
                    fetch('mpesa_pay.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'payment_type=continuation&appointment_id=<?php echo $appointment['id']; ?>&phone_number=' + encodeURIComponent(input) + '&payment_method=cash'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            paymentStatus.className = 'alert alert-success';
                            paymentStatus.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                            payNowBtn.innerHTML = '<i class="fas fa-check"></i> Payment Recorded';
                            payNowBtn.disabled = true;
                        } else {
                            paymentStatus.className = 'alert alert-danger';
                            paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                            confirmPayment.disabled = false;
                            confirmPayment.innerHTML = '<i class="fas fa-check"></i> Confirm';
                        }
                    })
                    .catch(error => {
                        paymentStatus.className = 'alert alert-danger';
                        paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
                        confirmPayment.disabled = false;
                        confirmPayment.innerHTML = '<i class="fas fa-check"></i> Confirm';
                    });
                } else {
                    // M-Pesa payment
                    const phone = input.replace(/\D/g, '');
                    if (!phone || phone.length < 9) {
                        paymentStatus.style.display = 'block';
                        paymentStatus.className = 'alert alert-danger';
                        paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid M-Pesa phone number';
                        return;
                    }

                    let formattedPhone = phone;
                    if (formattedPhone.startsWith('0')) {
                        formattedPhone = '254' + formattedPhone.substring(1);
                    } else if (!formattedPhone.startsWith('254')) {
                        formattedPhone = '254' + formattedPhone;
                    }

                    confirmPayment.disabled = true;
                    confirmPayment.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending STK Push...';
                    paymentStatus.style.display = 'block';
                    paymentStatus.className = 'alert alert-success';
                    paymentStatus.innerHTML = '<i class="fas fa-info-circle"></i> STK Push request sent. Check your phone to complete payment.';

                    fetch('mpesa_pay.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'payment_type=continuation&appointment_id=<?php echo $appointment['id']; ?>&phone_number=' + formattedPhone + '&payment_method=mpesa'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            paymentStatus.className = 'alert alert-success';
                            paymentStatus.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + '<br><small>STK Push sent. Please complete payment on your phone.</small>';
                            payNowBtn.innerHTML = '<i class="fas fa-clock"></i> Payment Pending...';
                            payNowBtn.disabled = true;
                        } else {
                            paymentStatus.className = 'alert alert-danger';
                            paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                            confirmPayment.disabled = false;
                            confirmPayment.innerHTML = '<i class="fas fa-check"></i> Pay';
                        }
                    })
                    .catch(error => {
                        paymentStatus.className = 'alert alert-danger';
                        paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
                        confirmPayment.disabled = false;
                        confirmPayment.innerHTML = '<i class="fas fa-check"></i> Pay';
                    });
                }
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
