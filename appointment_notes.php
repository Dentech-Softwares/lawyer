<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Admin header data
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_names = explode(' ', $admin_username);
$admin_initials = strtoupper(substr($admin_names[0], 0, 1) . (isset($admin_names[1]) ? substr($admin_names[1], 0, 1) : ''));
$current_date = date('F j, Y');

// Get admin notifications (pending appointment requests)
$adminNotifications = getAllRows(
    "SELECT a.id, u.name as client_name, a.case_type, a.created_at
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     WHERE a.status = 'pending' 
     ORDER BY a.created_at DESC LIMIT 5"
);
$unreadAdminNotifications = getRow(
    "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'"
);

$success = '';
$error = '';

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_appointments.php');
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
    header('Location: manage_appointments.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = trim($_POST['notes'] ?? '');
    
    $result = execute("UPDATE appointments SET notes = ? WHERE id = ?", [$notes, $id]);
    
    if ($result) {
        $success = 'Notes updated successfully!';
        $appointment['notes'] = $notes;
    } else {
        $error = 'Failed to update notes. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Notes - Justice Law Firm</title>
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

        body {
            background-color: var(--light-gray);
            color: var(--dark);
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            padding: 2rem 0;
        }

        .sidebar-admin {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }

        .sidebar-admin h2 {
            color: var(--white);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .sidebar-admin h2 i {
            color: var(--accent);
        }

        .sidebar-admin img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            margin-bottom: 0.8rem;
        }

        .sidebar-admin h3 {
            color: var(--white);
            font-size: 1.1rem;
        }

        .sidebar-admin p {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: var(--white);
        }

        .sidebar-menu a.active {
            border-left: 3px solid var(--accent);
        }

        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

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
            font-size: 1.8rem;
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

        .section-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            border-radius: 20px;
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .detail-value {
            color: var(--primary);
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

        #notes {
            width: 100%;
            min-height: 140px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            line-height: 1.5;
            resize: vertical;
            color: var(--dark);
            background-color: var(--white);
            transition: all 0.3s ease;
        }

        #notes:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.2);
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

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 1px solid var(--light-gray);
        }

        .mobile-menu-btn {
            display: none;
        }

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
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-admin">
            <h2><i class="fas fa-gavel"></i> Justice Law</h2>
            <img src="https://ui-avatars.com/api/?name=Dennis+Njenga&background=d4af37&color=1a3a5c" alt="Admin">
            <h3>Dennis Njenga</h3>
            <p>Administrator</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="manage_appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
            <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>

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

        <form method="POST" action="">
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-info-circle"></i> Appointment Details</h2>
                </div>
                <div class="appointment-detail">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Client Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Client Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment['client_email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Case Type</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment['case_type']); ?></span>
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
                            <span class="detail-label">Client Message</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['message'] ?? '')); ?></span>
                        </div>
                    </div>

                    <div class="detail-item" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label for="notes" class="detail-label" style="margin-bottom: 0;">
                                <i class="fas fa-clipboard-edit" style="color: var(--accent);"></i> Case Notes
                            </label>
                            <span id="notesCount" style="font-size: 0.8rem; color: var(--gray); font-weight: 500;">
                                <?php echo strlen($appointment['notes'] ?? ''); ?> characters
                            </span>
                        </div>
                        <textarea id="notes" name="notes" rows="5"
                            onkeyup="updateNotesCount()"
                            placeholder="Enter detailed case notes here... These notes will be visible to both admin and clients and included in printable appointment details."><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                        <div id="notesHelp" style="margin-top: 0.75rem; padding: 0.75rem; background: var(--light-gray); border-radius: 8px; font-size: 0.85rem; color: var(--gray);">
                            <i class="fas fa-info-circle" style="color: var(--info);"></i> Add case notes that will be visible to both admin and clients. These notes will also appear on the printable appointment details page.
                        </div>
                    </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="manage_appointments.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Notes
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function updateNotesCount() {
            const textarea = document.getElementById('notes');
            const count = textarea.value.length;
            const countEl = document.getElementById('notesCount');
            if (countEl) {
                countEl.textContent = count + ' characters';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateNotesCount();
        });
    </script>
</body>
</html>
