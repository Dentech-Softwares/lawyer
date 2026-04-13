<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

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
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            padding: 2rem 0;
            transition: all 0.3s ease;
        }

        .sidebar-logo {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }

        .sidebar-logo h2 {
            color: var(--white);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .sidebar-logo i {
            color: var(--accent);
        }

        .sidebar-user {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }

        .sidebar-user img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            margin-bottom: 1rem;
        }

        .sidebar-user h3 {
            color: var(--white);
            font-size: 1.1rem;
        }

        .sidebar-user p {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
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

        /* Main Content */
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h2><i class="fas fa-gavel"></i> Justice Law</h2>
        </div>
        
        <div class="sidebar-user">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=d4af37&color=1a3a5c" alt="User">
            <h3><?php echo htmlspecialchars($user_name); ?></h3>
            <p>Client Account</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="client_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="client_appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a></li>
            <li><a href="client_documents.php"><i class="fas fa-file-alt"></i> My Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <div class="header-actions">
                <div class="notification-bell" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php if($unreadNotifications['count'] > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotifications['count']; ?></span>
                    <?php endif; ?>
                    
                    <div class="notification-dropdown" id="notificationDropdown">
                        <h3>Notifications</h3>
                        <div class="notification-list">
                            <?php if(!empty($notifications)): ?>
                                <?php foreach($notifications as $notif): ?>
                                    <div class="notification-item <?php echo $notif['status']; ?>">
                                        <i class="fas fa-bell"></i>
                                        <div>
                                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <small><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                                        </div>
                                        <?php if($notif['status'] == 'unread'): ?>
                                            <a href="?mark_read=<?php echo $notif['id']; ?>" style="color: var(--accent);">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-item">
                                    <p>No notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

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
                    <table>
                        <thead>
                            <tr>
                                <th>Case Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $apt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apt['case_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($apt['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($apt['time'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $apt['status']; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-view" title="View Details" onclick="viewAppointment(<?php echo $apt['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($apt['status'] == 'pending'): ?>
                                                <button class="btn-icon btn-edit" title="Edit" onclick="editAppointment(<?php echo $apt['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" title="Cancel" onclick="cancelAppointment(<?php echo $apt['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

        // Toggle Notifications
        function toggleNotifications() {
            document.getElementById('notificationDropdown').classList.toggle('active');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-bell')) {
                document.getElementById('notificationDropdown').classList.remove('active');
            }
        });

        // View Appointment
        function viewAppointment(id) {
            // In a full implementation, this would open a modal
            alert('Viewing appointment #' + id);
        }

        // Edit Appointment
        function editAppointment(id) {
            window.location.href = 'edit_appointment.php?id=' + id;
        }

        // Cancel Appointment
        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                window.location.href = 'cancel_appointment.php?id=' + id;
            }
        }
    </script>
</body>
</html>
