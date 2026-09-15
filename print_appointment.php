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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 2rem;
            color: var(--dark);
        }

        .print-header {
            text-align: center;
            border-bottom: 3px solid var(--accent);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .print-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .print-header .logo i {
            color: var(--accent);
        }

        .print-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .print-header p {
            color: var(--secondary);
            font-size: 1rem;
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .detail-section h2 {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-section h2 i {
            color: var(--accent);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: var(--dark);
            font-size: 1rem;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
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
            border-radius: 12px;
            padding: 1.5rem;
            min-height: 120px;
            white-space: pre-wrap;
            font-size: 1rem;
            line-height: 1.8;
            color: var(--dark);
            position: relative;
        }

        .notes-box::before {
            content: '\f040';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 2.5rem;
            color: rgba(212, 175, 55, 0.15);
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        .no-notes {
            background: var(--light-gray);
            border: 2px dashed var(--gray);
            border-radius: 12px;
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 2rem;
            font-size: 1rem;
        }

        .message-box {
            background: #f7fafc;
            border: 1px solid var(--light-gray);
            border-radius: 12px;
            padding: 1.5rem;
            min-height: 60px;
            white-space: pre-wrap;
            font-size: 1rem;
            line-height: 1.8;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .print-actions {
                display: none;
            }

            body {
                background: var(--white);
            }

            .print-container {
                padding: 1rem;
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
            <h2><i class="fas fa-align-left"></i> Client Message</h2>
            <div class="message-box"><?php echo htmlspecialchars($message); ?></div>
        </div>

        <div class="detail-section">
            <h2><i class="fas fa-clipboard-edit"></i> Lawyer Notes</h2>
            <?php if (!empty($notes)): ?>
                <div class="notes-box"><?php echo htmlspecialchars($notes); ?></div>
            <?php else: ?>
                <div class="no-notes">No notes have been added for this appointment yet.</div>
            <?php endif; ?>
        </div>

        <div class="detail-section" style="margin-top: 3rem; text-align: center;">
            <p style="color: var(--gray); font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> Justice Law Firm. All rights reserved.
            </p>
        </div>

        <div class="print-actions" style="margin-top: 2rem; text-align: center;">
            <button onclick="window.print()" style="padding: 0.8rem 2rem; background: var(--accent); color: var(--primary); border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                <i class="fas fa-print"></i> Print This Page
            </button>
        </div>
    </div>
</body>
</html>
