<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Appointment Details View - Test Version (Bypass Login) + Full PHP Block + HTML Structure
 */

require_once 'db.php';

// Bypass login check for testing
$isAdmin = true;  // Simulate admin logged in
$isClient = false;

// Check if ID is provided
if (!isset($_GET['id'])) {
    $redirect = 'manage_appointments.php'; // dummy
    header('Location: ' . $redirect);
    exit;
}
$id = (int)$_GET['id'];

// Get appointment with client info
$whereClause = $isAdmin ? "" : "AND a.user_id = " . (int)$_SESSION['user_id'];
$appointment = getRow(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
     FROM appointments a 
     JOIN users u ON a.user_id = u.id 
     WHERE a.id = ? $whereClause",
    [$id]
);

if (!$appointment) {
    $redirect = 'manage_appointments.php'; // dummy
    header('Location: ' . $redirect);
    exit;
}

// Mark appointment as read when viewed
if ($appointment['is_read'] == 0) {
    execute("UPDATE appointments SET is_read = 1 WHERE id = ?", [$id]);
}

// Get notes
$notes = $appointment['notes'] ?? '';

// Handle notes update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_notes'])) {
    $notes = trim($_POST['notes'] ?? '');
    
    $result = execute("UPDATE appointments SET notes = ? WHERE id = ?", [$notes, $id]);
    
    if ($result) {
        $success = 'Notes updated successfully!';
        $appointment['notes'] = $notes;
    } else {
        $error = 'Failed to update notes. Please try again.';
    }
}

// Get documents for this appointment
$documents = getAllRows(
    "SELECT * FROM documents WHERE appointment_id = ? ORDER BY uploaded_at DESC",
    [$id]
);

// Header data based on role
if ($isAdmin) {
    $admin_username = $_SESSION['admin_username'] ?? '';
    $admin_email = $_SESSION['admin_email'] ?? '';
    $admin_phone = $_SESSION['admin_phone'] ?? '';
    $current_date = date('F j, Y');
    
    $adminNotifications = getAllRows(
        "SELECT a.id, u.name as client_name, a.case_type, a.created_at
         FROM appointments a
         JOIN users u ON a.user_id = u.id
         WHERE a.status = 'pending' 
         ORDER BY a.created_at DESC LIMIT 5"
    );
    $unreadAdminNotifications = getRow(
        "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending' AND created_at > ?",
        [$_SESSION['admin_last_appointments_view'] ?? '1970-01-01 00:00:00']
    );
    $pendingAppointmentsCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending' AND is_read = 0")["count"] ?? 0;
    $continuationBadgeCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE is_continuation = 1 AND status != 'completed' AND is_read = 0")["count"] ?? 0;
} else {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? '';
    $user_email = $_SESSION['user_email'] ?? '';
    $current_date = date('F j, Y');
    $user = getRow("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    $notifications = getAllRows(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
        [$user_id]
    );
    $unreadNotifications = getRow(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'",
        [$user_id]
    );
    $userPendingCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending' AND is_read = 0", [$user_id])["count"] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
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

        body {
            background-color: var(--light-gray);
            color: var(--dark);
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: var(--danger);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c53030;
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1.5rem;
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h2 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .card-header i {
            color: var(--accent);
        }

        .card-body {
            padding: 2rem;
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

        .detail-value.full-width {
            grid-column: 1 / -1;
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
            margin-bottom: 1.5rem;
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

        /* Form Styles */
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

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--primary);
            border: 2px solid var(--light-gray);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background: #2f855a;
        }

        .btn-info {
            background: var(--info);
            color: var(--white);
        }

        .btn-info:hover {
            background: #2c7ad6;
        }

        .btn-warning {
            background: var(--warning);
            color: var(--primary);
        }

        .btn-warning:hover {
            background: #b7791f;
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--light-gray);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-icon i {
            color: inherit;
        }

        /* Documents Table */
        .table-container {
            overflow-x: auto;
        }

        table.documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.documents-table th,
        table.documents-table td {
            padding: 0.8rem 1.5rem;
            text-align: left;
        }

        table.documents-table th {
            background: var(--light-gray);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        table.documents-table td {
            border-bottom: 1px solid var(--light-gray);
            color: var(--dark);
        }

        table.documents-table tr:hover {
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

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            width: 45px;
            height: 45px;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            color: var(--white);
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .action-butts {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php
    $sidebar_role = $isAdmin ? 'admin' : 'client';
    $sidebar_badges = $isAdmin 
        ? ['appointments' => $pendingAppointmentsCount, 'schedule' => $continuationBadgeCount]
        : ['appointments' => $userPendingCount];
    include 'includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($isAdmin): ?>
            <?php include 'admin_header.php'; ?>
        <?php else: ?>
            <?php include 'client_header.php'; ?>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Appointment Overview Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Appointment #<?php echo $appointment['id']; ?> Details</h2>
                <div class="action-buttons">
                    <?php if ($isAdmin): ?>
                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-primary" title="Edit Appointment">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="appointment_notes.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-info" title="Manage Notes">
                            <i class="fas fa-clipboard-edit"></i>
                        </a>
                        <a href="continue_case.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-success" title="Continue Case">
                            <i class="fas fa-gavel"></i>
                        </a>
                        <a href="print_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-secondary" target="_blank" title="Print">
                            <i class="fas fa-print"></i>
                        </a>
                    <?php else: ?>
                        <a href="continue_case.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-success" title="Continue Case">
                            <i class="fas fa-gavel"></i>
                        </a>
                        <a href="print_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-secondary" target="_blank" title="Print">
                            <i class="fas fa-print"></i>
                        </a>
                        <?php if($appointment['status'] == 'pending'): ?>
                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-primary" title="Edit Appointment">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-icon btn-danger" title="Cancel Appointment" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Case Type</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['case_type'] ?? ''); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-badge <?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
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
                            <?php echo date('M d, Y', strtotime($appointment['date'])); ?>
                            <small><?php echo date('h:i A', strtotime($appointment['time'])); ?></small>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created</span>
                        <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($appointment['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_name'] ?? ''); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_email'] ?? ''); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item full-width">
                        <span class="detail-label">Client Message</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['message'] ?? '')); ?></span>
                    </div>
                </div>

                <!-- Status Actions (Admin only) -->
                <?php if ($isAdmin): ?>
                <div style="border-top: 1px solid var(--light-gray); padding-top: 1.5rem; margin-top: 1.5rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.1rem;"><i class="fas fa-bolt"></i> Quick Status Actions</h3>
                    <div class="action-buttons">
                        <?php if($appointment['status'] == 'pending'): ?>
                            <a href="update_appointment.php?id=<?php echo $appointment['id']; ?>&action=approve" class="btn btn-success" onclick="return confirm('Approve this appointment?');">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="update_appointment.php?id=<?php echo $appointment['id']; ?>&action=reject" class="btn btn-danger" onclick="return confirm('Reject this appointment?');">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        <?php endif; ?>
                        <?php if($appointment['status'] == 'approved'): ?>
                            <a href="update_appointment.php?id=<?php echo $appointment['id']; ?>&action=complete" class="btn btn-info" onclick="return confirm('Mark this appointment as completed?');">
                                <i class="fas fa-flag-checkered"></i> Complete
                            </a>
                        <?php endif; ?>
                        <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this appointment?');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Case Notes Card -->
        <!-- Case Notes Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-clipboard-edit"></i> Case Notes</h2>
            </div>
            <div class="card-body">
                <div class="notes-container">
                    <?php if(!empty($notes)): ?>
                        <?php
                        $noteEntries = array_filter(array_map('trim', explode("\n\n", $notes)));
                        ?>
                        <?php foreach($noteEntries as $entry): ?>
                            <?php if(!empty($entry)): ?>
                                <div class="note-card">
                                    <?php
                                    $lines = explode("\n", $entry);
                                    $noteTime = $lines[0] ?? '';
                                    $noteText = implode("\n", array_slice($lines, 1));
                                    ?>
                                    <div class="note-time"><?php echo htmlspecialchars($noteTime ?? ''); ?></div>
                                    <div class="note-text"><?php echo nl2br(htmlspecialchars($noteText ?? '')); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notes">No case notes have been added yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Update Notes Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="notes">
                            <i class="fas fa-plus"></i> Add / Update Case Notes
                        </label>
                        <textarea id="notes" name="notes" 
                                  placeholder="Enter case notes, updates, or observations... Each entry will be timestamped."><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_notes" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>

