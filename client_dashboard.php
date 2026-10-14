<?php
require_once 'db.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$current_date = date('F j, Y');

// Handle flash messages
$flashSuccess = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Get user info
$user = getRow("SELECT * FROM users WHERE id = ?", [$user_id]);

// Get appointments
$appointments = getAllRows(
    "SELECT a.*, u.name as lawyer_name 
     FROM appointments a 
     LEFT JOIN users u ON u.id = a.user_id 
     WHERE a.user_id = ? 
     ORDER BY a.date DESC, a.time DESC", 
    [$user_id]
);

// Get notifications
$notifications = getAllRows(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", 
    [$user_id]
);

// Count notifications
$unreadNotifications = getRow(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'",
    [$user_id]
);

// Client pending appointments
$lastView = $_SESSION['user_last_appointments_view'] ?? '1970-01-01 00:00:00';
$userPendingCount = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending' AND created_at > ?", [$user_id, $lastView])['count'] ?? 0;

// Handle notification read
if (isset($_GET['mark_read'])) {
    execute("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?", 
        [$_GET['mark_read'], $user_id]);
    header('Location: client_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Justice Law Firm</title>
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
            line-height: 1.6;
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

        .notification-bell {
            position: relative;
            width: 45px;
            height: 45px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-bell:hover {
            background: var(--accent);
            color: var(--white);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: var(--danger);
            color: var(--white);
            border-radius: 50%;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            width: 350px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            z-index: 1001;
        }

        .notification-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-dropdown h3 {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            color: var(--primary);
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            gap: 1rem;
            transition: background 0.3s ease;
        }

        .notification-item:hover {
            background: var(--light-gray);
        }

        .notification-item.unread {
            background: rgba(212, 175, 55, 0.1);
        }

        .notification-item i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .notification-item p {
            flex: 1;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .notification-item small {
            color: var(--gray);
            font-size: 0.8rem;
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
            transform: translateY(-2px);
        }

        /* Profile Dropdown */
        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
        }

        .profile-trigger:hover {
            background: var(--light-gray);
        }

        .profile-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent);
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-name-row {
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .profile-role {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .profile-chevron {
            color: var(--gray);
            font-size: 0.7rem;
        }

        .profile-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            width: 240px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            z-index: 1001;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .profile-container.active .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-section {
            padding: 1rem 1.25rem;
        }

        .profile-dropdown-label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .profile-dropdown-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
        }

        .profile-divider {
            height: 1px;
            background: var(--light-gray);
            margin: 0 1.25rem;
        }

        .profile-dropdown-logout {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.25rem;
            color: var(--danger);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .profile-dropdown-logout:hover {
            background: rgba(229, 62, 62, 0.1);
        }

        .profile-dropdown-logout i {
            width: 18px;
        }

        /* Flash Alert */
        .flash-alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
        }

        .flash-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .flash-alert-success i {
            color: #38a169;
            font-size: 1.2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.pending {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning);
        }

        .stat-icon.approved {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .stat-icon.rejected {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .stat-icon.completed {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
        }

        .stat-info h3 {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-info p {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 700;
        }

        /* Appointments Section */
        .section-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: var(--primary);
            font-size: 1.3rem;
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

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table.appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.appointments-table th,
        table.appointments-table td {
            padding: 1rem 1.5rem;
            text-align: left;
        }

        table.appointments-table th {
            background: var(--light-gray);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        table.appointments-table td {
            border-bottom: 1px solid var(--light-gray);
            color: var(--dark);
        }

        table.appointments-table tr.appointment-row {
            cursor: pointer;
            transition: background 0.2s ease;
        }

        table.appointments-table tr.appointment-row:hover {
            background: var(--light-gray);
        }

        table.appointments-table tr.appointment-row:active {
            background: #e8eef5;
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

        .status-badge.rejected {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .status-badge.completed {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

         .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
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

        .btn-edit {
            background: var(--light-gray);
            color: var(--warning);
        }

        .btn-edit:hover {
            background: var(--warning);
            color: var(--white);
        }

        .btn-delete {
            background: var(--light-gray);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: var(--white);
        }

        /* Empty State */
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

        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
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
                gap: 1rem;
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $sidebar_role = 'client';
    $sidebar_badges = [
        'appointments' => $userPendingCount,
    ];
    include 'includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include 'client_header.php'; ?>

        <?php if($flashSuccess): ?>
            <div class="flash-alert flash-alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $flashSuccess; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <?php
            $pending = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending'", [$user_id]);
            $approved = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'approved'", [$user_id]);
            $rejected = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'rejected'", [$user_id]);
            $completed = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'completed'", [$user_id]);
            ?>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <p><?php echo $pending['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Approved</h3>
                    <p><?php echo $approved['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-info">
                    <h3>Rejected</h3>
                    <p><?php echo $rejected['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-info">
                    <h3>Completed</h3>
                    <p><?php echo $completed['count']; ?></p>
                </div>
            </div>
        </div>

        <!-- Appointments -->
        <div class="section-card">
            <div class="section-header">
                <h2><i class="fas fa-calendar"></i> Recent Appointments</h2>
                <a href="book_appointment.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Appointment
                </a>
            </div>
            
<div class="table-container">
                <?php if(!empty($appointments)): ?>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Case Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $apt): ?>
                                <tr class="appointment-row" onclick="window.location.href='appointment_details.php?id=<?php echo $apt['id']; ?>'">
                                    <td><?php echo htmlspecialchars($apt['case_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($apt['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($apt['time'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $apt['status']; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <h3>No Appointments Yet</h3>
                        <p>Book your first appointment to get started</p>
                        <a href="book_appointment.php" class="btn btn-primary" style="margin-top: 1rem;">
                            Book Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
