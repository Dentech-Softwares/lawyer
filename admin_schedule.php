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
            "UPDATE appointments SET date = ?, time = ?, status = 'approved', is_continuation = 1 WHERE id = ?",
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

// Get all appointments for the Continuations page (list all, but badge only for flagged)
$allAppointments = getAllRows(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     ORDER BY a.created_at DESC"
);

// Get continuation-flagged appointments for badge count
$continuationAppointments = getAllRows(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     WHERE a.is_continuation = 1
     ORDER BY a.created_at DESC"
);

// Admin header data
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'] ?? '';
$current_date = date('F j, Y');

// Admin notifications - count of flagged continuations for the sidebar badge
$continuationBadgeCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE is_continuation = 1 AND status != 'completed' AND is_read = 0")["count"] ?? 0;

$pendingAppointmentsCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending' AND is_read = 0")["count"] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointments - Justice Law Firm</title>
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

        .main-content {
            margin-left: var(--sidebar-width);
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

        .continuations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .continuations-table th,
        .continuations-table td {
            padding: 1rem 1.5rem;
            text-align: left;
        }

        .continuations-table th {
            background: var(--light-gray);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .continuations-table td {
            border-bottom: 1px solid var(--light-gray);
            color: var(--dark);
        }

        .continuations-table tbody tr {
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .continuations-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .continuations-table tbody tr:last-child td {
            border-bottom: none;
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

            .continuations-table th,
            .continuations-table td {
                padding: 0.8rem 1rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $sidebar_role = 'admin';
    $sidebar_badges = [
        'appointments' => $pendingAppointmentsCount,
        'schedule' => $continuationBadgeCount,
    ];
    include 'includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-clock" style="color: var(--warning); font-size: 1.4rem;"></i> Continuations</h1>
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
                <h2><i class="fas fa-calendar-check" style="color: var(--warning);"></i> All Appointments (<?php echo count($allAppointments); ?>) — <span style="color: var(--gray); font-weight: 400;"><?php echo count($continuationAppointments); ?> flagged as continuation</span></h2>
            </div>

            <div class="table-container">
                <?php if(!empty($allAppointments)): ?>
                    <table class="continuations-table">
                        <thead>
                            <tr>
                                <th>Case #</th>
                                <th>Client</th>
                                <th>Case Type</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Continuation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allAppointments as $apt): ?>
                            <tr onclick="window.location.href='appointment_details.php?id=<?php echo $apt['id']; ?>'" style="cursor: pointer;">
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
                                    <span class="status-badge <?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($apt['is_continuation']): ?>
                                        <span class="status-badge" style="background: rgba(212, 175, 55, 0.15); color: var(--accent); border: 1px solid var(--accent);">
                                            Yes
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: var(--light-gray); color: var(--gray);">
                                            No
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments</h3>
                        <p>No appointments have been created yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>