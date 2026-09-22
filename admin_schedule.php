<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle scheduling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_id'])) {
    $scheduleId = (int)$_POST['schedule_id'];
    $newDate = $_POST['schedule_date'] ?? '';
    $newTime = $_POST['schedule_time'] ?? '';

    $appointment = getRow("SELECT * FROM appointments WHERE id = ?", [$scheduleId]);

    if (!$appointment) {
        $error = 'Appointment not found.';
    } elseif (empty($newDate) || empty($newTime)) {
        $error = 'Please provide both date and time.';
    } else {
        $result = execute(
            "UPDATE appointments SET date = ?, time = ?, status = 'approved' WHERE id = ?",
            [$newDate, $newTime, $scheduleId]
        );

        if ($result) {
            // Notify client
            execute(
                "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                [$appointment['user_id'], 'Your appointment for case #' . $scheduleId . ' has been scheduled for ' . date('M d, Y', strtotime($newDate)) . ' at ' . date('h:i A', strtotime($newTime)) . '.']
            );

            // Insert admin notification log
            $admin = getRow("SELECT id FROM admin_users LIMIT 1");
            if ($admin) {
                execute(
                    "INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')",
                    [$admin['id'], 'Scheduled continuation for case #' . $scheduleId . ' for ' . date('M d, Y', strtotime($newDate)) . ' at ' . date('h:i A', strtotime($newTime))]
                );
            }

            $success = 'Appointment scheduled successfully for ' . date('M d, Y', strtotime($newDate)) . ' at ' . date('h:i A', strtotime($newTime)) . '.';
            header("Location: admin_schedule.php");
            exit;
        } else {
            $error = 'Failed to schedule appointment. Please try again.';
        }
    }
}

// Get pending appointments (continuation requests)
$pendingAppointments = getAllRows(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     WHERE a.status = 'pending'
     ORDER BY a.created_at DESC"
);

// Admin header data
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_names = explode(' ', $admin_username);
$admin_initials = strtoupper(substr($admin_names[0], 0, 1) . (isset($admin_names[1]) ? substr($admin_names[1], 0, 1) : ''));
$current_date = date('F j, Y');

// Admin notifications
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointments - Justice Law Firm</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .section-header h2 {
            color: var(--primary);
            font-size: 1.3rem;
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

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning);
        }

        .status-badge.approved {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .form-input {
            padding: 0.6rem 0.8rem;
            border: 2px solid var(--light-gray);
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .schedule-form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .schedule-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .schedule-form .form-group label {
            font-size: 0.72rem;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
        }

        .schedule-form .form-input {
            min-width: 120px;
        }

        .btn-schedule {
            padding: 0.6rem 1rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .btn-schedule:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.3);
        }

        .btn-view {
            background: var(--light-gray);
            color: var(--primary);
            border: none;
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: var(--info);
            color: var(--white);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

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

            th, td {
                padding: 0.8rem 1rem;
            }

            .schedule-form {
                flex-direction: column;
                align-items: stretch;
            }

            .schedule-form .form-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
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
            <li><a href="admin_schedule.php" class="active"><i class="fas fa-clock"></i> Schedule Continuations</a></li>
            <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-clock" style="color: var(--warning); font-size: 1.4rem;"></i> Schedule Continuations</h1>
        </div>

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

        <div class="section-card">
            <div class="section-header">
                <h2><i class="fas fa-calendar-check" style="color: var(--warning);"></i> Pending Continuations (<?php echo count($pendingAppointments); ?>)</h2>
            </div>

            <div class="table-container">
                <?php if(!empty($pendingAppointments)): ?>
                    <?php foreach($pendingAppointments as $apt): ?>
                        <form method="POST" action="admin_schedule.php" style="border-bottom: 1px solid var(--light-gray);">
                            <input type="hidden" name="schedule_id" value="<?php echo $apt['id']; ?>">
                            <table style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th>Case #</th>
                                        <th>Client</th>
                                        <th>Case Type</th>
                                        <th>Current Date</th>
                                        <th>New Date</th>
                                        <th>New Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>#<?php echo $apt['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($apt['client_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($apt['client_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($apt['case_type']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($apt['date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($apt['time'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <input type="date" name="schedule_date" class="form-input" required>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <input type="time" name="schedule_time" class="form-input" required>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                <button type="submit" name="schedule_action" class="btn-schedule">
                                                    <i class="fas fa-calendar-plus"></i> Schedule
                                                </button>
                                                <a href="appointment_notes.php?id=<?php echo $apt['id']; ?>" class="btn-view" title="View Notes">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>No Pending Continuations</h3>
                        <p>All continuation requests have been scheduled.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.setAttribute('min', today);
            });
        });
    </script>
</body>
</html>