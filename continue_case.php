<?php
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
     AND a.status IN ('approved', 'completed')",
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
        $admin = getRow("SELECT id FROM admin_users LIMIT 1");
        if ($admin) {
            execute(
                "INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')",
                [$admin['id'], 'Client ' . $appointment['client_name'] . ' requested to continue case #' . $id . ' (' . $appointment['case_type'] . ') - please schedule an appointment']
            );
        }
        $_SESSION['success'] = 'Case continuation request sent to admin. They will contact you to schedule an appointment.';
        header("Location: continue_case.php?id=" . $id);
        exit;
    }
}

// Handle flash messages
$flashSuccess = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
$flashError = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$message = $flashSuccess ?: $flashError;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continue Case - Justice Law Firm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .case-detail-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .section-header h2 {
            color: var(--primary);
            font-size: 1.3rem;
        }

        .appointment-detail {
            padding: 1.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .detail-value textarea {
            width: 100%;
            padding: 1.5rem;
            border: 2px solid var(--accent);
            border-radius: 12px;
            font-size: 1rem;
            resize: vertical;
            min-height: 200px;
            font-family: inherit;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .detail-value textarea:focus {
            outline: none;
            border-color: var(--info);
            box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.2);
            background: var(--white);
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

        .btn-outline {
            background: transparent;
            border: 2px solid var(--light-gray);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
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

        .notes-section {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .notes-section h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notes-section .note-entry {
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-left: 3px solid var(--accent);
        }

        .notes-section .note-entry .note-time {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .notes-section .note-entry .note-text {
            color: var(--dark);
            line-height: 1.5;
        }

        .notes-section .no-notes {
            color: var(--gray);
            font-style: italic;
            text-align: center;
            padding: 1rem;
        }

        .add-note-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .add-note-section h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .add-note-section textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            line-height: 1.5;
            resize: vertical;
            background: var(--white);
            transition: all 0.3s ease;
        }

        .add-note-section textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.2);
        }

        .add-note-section .btn-submit {
            margin-top: 1rem;
            padding: 0.8rem 2rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-note-section .btn-submit:hover {
            background: var(--accent-hover);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 1px solid var(--light-gray);
        }

        .documents-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .documents-section .section-header {
            background: var(--light-gray);
        }

        .documents-section .document-padding {
            padding: 1.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
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

        .document-list {
            list-style: none;
        }

        .document-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .document-list li:last-child {
            border-bottom: none;
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

        .action-bar {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .action-bar {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'client_header.php'; ?>

    <main class="main-content">
        <?php if($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'error') !== false || strpos($message, 'cannot') !== false || strpos($message, 'not found') !== false ? 'danger' : 'success'; ?>">
                <i class="fas fa-<?php echo strpos($message, 'error') !== false || strpos($message, 'cannot') !== false || strpos($message, 'not found') !== false ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="case-detail-card">
            <div class="section-header">
                <h2><i class="fas fa-gavel"></i> Case #<?php echo $appointment['id']; ?> - Continue Case</h2>
            </div>

            <div class="appointment-detail">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Case Type</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['case_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Client Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">
                            <?php echo date('M d, Y', strtotime($appointment['date'])); ?>
                            <small><?php echo date('h:i A', strtotime($appointment['time'])); ?></small>
                        </span>
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
                        <span class="detail-label">Client Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_email']); ?></span>
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

                <div class="action-bar">
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="continue_case_request" class="btn btn-primary" onclick="return confirm('This will send a request to the admin to schedule your next appointment. Continue?');">
                            <i class="fas fa-gavel"></i> Continue Case
                        </button>
                    </form>
                    <a href="print_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-outline" target="_blank">
                        <i class="fas fa-print"></i> Print Details
                    </a>
                    <a href="client_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="case-detail-card">
            <div class="section-header">
                <h2><i class="fas fa-clipboard-edit"></i> Case Notes</h2>
            </div>
            <div class="appointment-detail">
                <?php if(!empty($appointment['notes'])): ?>
                    <?php
                    $noteEntries = array_filter(array_map('trim', explode("\n\n", $appointment['notes'])));
                    ?>
                    <?php foreach($noteEntries as $entry): ?>
                        <?php if(!empty($entry)): ?>
                            <div class="notes-section">
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
                    <div class="notes-section">
                        <div class="no-notes">No case notes have been added yet.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="case-detail-card">
            <div class="section-header">
                <h2><i class="fas fa-plus"></i> Add Additional Notes</h2>
            </div>
            <div class="appointment-detail">
                <form method="POST" action="">
                    <textarea name="additional_note" placeholder="Enter any additional information, updates, or questions about your case..." required></textarea>
                    <div class="form-actions">
                        <a href="client_dashboard.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if(!empty($documents)): ?>
            <div class="documents-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-alt"></i> Uploaded Documents</h2>
                </div>
                <div class="document-padding">
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
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar') && document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
