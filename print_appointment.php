<?php
require_once 'db.php';

// Check if either admin or user is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    if (isset($_SESSION['admin_id'])) {
        header('Location: manage_appointments.php');
    } else {
        header('Location: client_appointments.php');
    }
    exit;
}

$id = (int)$_GET['id'];

// Get appointment with client details
$appointment = getRow(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
     FROM appointments a 
     JOIN users u ON a.user_id = u.id 
     WHERE a.id = ?",
    [$id]
);

if (!$appointment) {
    if (isset($_SESSION['admin_id'])) {
        header('Location: manage_appointments.php');
    } else {
        header('Location: client_appointments.php');
    }
    exit;
}

// If user is logged in (not admin), ensure the appointment belongs to them
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    if ($appointment['user_id'] != $_SESSION['user_id']) {
        header('Location: client_appointments.php');
        exit;
    }
}

$clientName = $appointment['client_name'];
$clientEmail = $appointment['client_email'];
$clientPhone = $appointment['client_phone'];
$caseType = $appointment['case_type'];
$date = date('F d, Y', strtotime($appointment['date']));
$time = date('h:i A', strtotime($appointment['time']));
$status = ucfirst($appointment['status']);
$message = $appointment['message'] ?? '';
$notes = $appointment['notes'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Appointment - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="assets/css/page-transition.css">
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
            --warning: #d69e2e;
            --info: #3182ce;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .print-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 1.2rem 1.5rem;
            color: var(--dark);
        }

        .print-header {
            text-align: center;
            border-bottom: 3px solid var(--accent);
            padding-bottom: 0.8rem;
            margin-bottom: 1rem;
        }

        .print-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .print-header .logo i {
            color: var(--accent);
        }

        .print-header h1 {
            color: var(--primary);
            font-size: 1.6rem;
            margin-bottom: 0.25rem;
        }

        .print-header p {
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .detail-section {
            margin-bottom: 0.8rem;
        }

        .detail-section h2 {
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.3rem;
            border-bottom: 2px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .detail-section h2 i {
            color: var(--accent);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem 1.2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 0.4rem 0;
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .detail-value {
            color: var(--dark);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.pending {
            background: rgba(214, 158, 46, 0.15);
            color: #d69e2e;
            border: 1px solid rgba(214, 158, 46, 0.3);
        }

        .status-badge.approved {
            background: rgba(56, 161, 105, 0.15);
            color: #38a169;
            border: 1px solid rgba(56, 161, 105, 0.3);
        }

        .status-badge.rejected {
            background: rgba(229, 62, 62, 0.15);
            color: #e53e3e;
            border: 1px solid rgba(229, 62, 62, 0.3);
        }

        .status-badge.completed {
            background: rgba(49, 130, 206, 0.15);
            color: #3182ce;
            border: 1px solid rgba(49, 130, 206, 0.3);
        }

        .notes-box {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f7ff 100%);
            border: 2px solid var(--accent);
            border-radius: 8px;
            padding: 0.8rem 1rem;
            min-height: 60px;
            white-space: pre-wrap;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--dark);
            position: relative;
        }

        .notes-box::before {
            content: '\f040';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 1.8rem;
            color: rgba(212, 175, 55, 0.15);
            position: absolute;
            top: 0.5rem;
            right: 0.8rem;
        }

        .no-notes {
            background: var(--light-gray);
            border: 2px dashed var(--gray);
            border-radius: 8px;
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
        }

        .message-box {
            background: #f7fafc;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            padding: 0.8rem 1rem;
            min-height: 40px;
            white-space: pre-wrap;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .print-actions {
                display: none !important;
            }

            body {
                background: var(--white) !important;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-container {
                padding: 0.8rem 1rem !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .print-header {
                margin-bottom: 0.8rem !important;
                padding-bottom: 0.5rem !important;
            }

            .print-header h1 {
                font-size: 1.4rem !important;
            }

            .print-header .logo {
                font-size: 1.3rem !important;
            }

            .detail-section {
                margin-bottom: 0.6rem !important;
                page-break-inside: avoid;
            }

            .detail-section h2 {
                font-size: 1rem !important;
                margin-bottom: 0.4rem !important;
            }

            /* Force the 2-column grid to stay side-by-side during print.
               Browsers sometimes reset grid/flex on print; re-assert explicitly. */
            .detail-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 0.4rem 1.2rem !important;
                width: 100% !important;
            }

            .detail-item {
                display: flex !important;
                flex-direction: column !important;
                width: 50% !important;
                padding: 0.2rem 0 !important;
                box-sizing: border-box !important;
            }

            .detail-label {
                font-size: 0.72rem !important;
            }

            .detail-value {
                font-size: 0.85rem !important;
            }

            .notes-box,
            .message-box,
            .no-notes {
                font-size: 0.85rem !important;
                padding: 0.6rem 0.8rem !important;
                page-break-inside: avoid;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .status-badge {
                font-size: 0.72rem !important;
                padding: 0.2rem 0.6rem !important;
            }

            .print-footer {
                page-break-before: auto;
            }

            @page {
                margin: 1.2cm 1.5cm;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-header">
            <div class="logo">
                <i class="fas fa-gavel"></i>
                Justice Law Firm
            </div>
            <h1>Appointment Details</h1>
            <p>Printed on <?php echo date('F d, Y h:i A'); ?></p>
        </div>

        <div class="detail-section">
            <h2><i class="fas fa-user"></i> Client Information</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Client Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($clientName); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value"><?php echo htmlspecialchars($clientEmail); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value"><?php echo htmlspecialchars($clientPhone); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo strtolower($appointment['status']); ?>">
                            <?php echo $status; ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h2><i class="fas fa-briefcase"></i> Case Information</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Case Type</span>
                    <span class="detail-value"><?php echo htmlspecialchars($caseType); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Appointment Date</span>
                    <span class="detail-value"><?php echo $date; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Appointment Time</span>
                    <span class="detail-value"><?php echo $time; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Appointment ID</span>
                    <span class="detail-value">#<?php echo $appointment['id']; ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h2><i class="fas fa-align-left"></i> Client Message</h2>
            <div class="message-box"><?php echo htmlspecialchars($message); ?></div>
        </div>

        <div class="detail-section">
            <h2><i class="fas fa-clipboard-list"></i> Lawyer Notes</h2>
            <?php if (!empty($notes)): ?>
                <div class="notes-box"><?php echo htmlspecialchars($notes); ?></div>
            <?php else: ?>
                <div class="no-notes">No notes have been added for this appointment yet.</div>
            <?php endif; ?>
        </div>

        <div class="detail-section print-footer" style="margin-top: 1.5rem; text-align: center;">
            <p style="color: var(--gray); font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> Justice Law Firm. All rights reserved.
            </p>
        </div>

        <div class="print-actions" style="margin-top: 1.5rem; text-align: center;">
            <button onclick="window.print()" style="padding: 0.7rem 1.8rem; background: var(--accent); color: var(--primary); border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem;">
                <i class="fas fa-print"></i> Print This Page
            </button>
        </div>
    </div>
</body>
</html>
