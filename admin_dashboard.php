<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'];

// Get stats
$totalAppointments = getRow("SELECT COUNT(*) as count FROM appointments");
$pendingAppointments = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$approvedAppointments = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'approved'");
$completedAppointments = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
$totalClients = getRow("SELECT COUNT(*) as count FROM users");
$totalDocuments = getRow("SELECT COUNT(*) as count FROM documents");

// Get recent appointments
$recentAppointments = getAllRows(
    "SELECT a.*, u.name as client_name, u.email as client_email 
     FROM appointments a 
     JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Justice Law Firm</title>
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

        .sidebar-admin {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-icon.appointments {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
        }

        .stat-icon.pending {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning);
        }

        .stat-icon.approved {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .stat-icon.completed {
            background: rgba(212, 175, 55, 0.1);
            color: var(--accent);
        }

        .stat-icon.clients {
            background: rgba(26, 54, 92, 0.1);
            color: var(--primary);
        }

        .stat-icon.documents {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .stat-info h3 {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stat-info p {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 700;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            padding: 1.2rem;
            background: var(--white);
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .quick-action-btn:hover {
            border-color: var(--accent);
            background: rgba(212, 175, 55, 0.1);
            transform: translateY(-3px);
        }

        .quick-action-btn i {
            font-size: 1.2rem;
            color: var(--accent);
        }

        /* Table */
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
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

        .btn-approve {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .btn-approve:hover {
            background: var(--success);
            color: var(--white);
        }

        .btn-reject {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .btn-reject:hover {
            background: var(--danger);
            color: var(--white);
        }

        .btn-complete {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
        }

        .btn-complete:hover {
            background: var(--info);
            color: var(--white);
        }

        .btn-view {
            background: var(--light-gray);
            color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: var(--white);
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
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
        
        <div class="sidebar-admin">
            <img src="https://ui-avatars.com/api/?name=Admin&background=d4af37&color=1a3a5c" alt="Admin">
            <h3><?php echo htmlspecialchars($admin_username); ?></h3>
            <p>Administrator</p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="manage_appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
            <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-globe"></i> View Website
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon appointments">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Appointments</h3>
                    <p><?php echo $totalAppointments['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <p><?php echo $pendingAppointments['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Approved</h3>
                    <p><?php echo $approvedAppointments['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-info">
                    <h3>Completed</h3>
                    <p><?php echo $completedAppointments['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon clients">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Clients</h3>
                    <p><?php echo $totalClients['count']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon documents">
                    <i class="fas fa-file"></i>
                </div>
                <div class="stat-info">
                    <h3>Documents</h3>
                    <p><?php echo $totalDocuments['count']; ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="manage_appointments.php" class="quick-action-btn">
                <i class="fas fa-calendar-plus"></i>
                Manage Appointments
            </a>
            <a href="manage_clients.php" class="quick-action-btn">
                <i class="fas fa-user-plus"></i>
                Manage Clients
            </a>
            <a href="manage_documents.php" class="quick-action-btn">
                <i class="fas fa-folder-open"></i>
                View Documents
            </a>
        </div>

        <!-- Recent Appointments -->
        <div class="section-card">
            <div class="section-header">
                <h2><i class="fas fa-calendar"></i> Recent Appointments</h2>
                <a href="manage_appointments.php" class="btn btn-primary btn-sm">
                    View All
                </a>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Case Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($recentAppointments)): ?>
                            <?php foreach($recentAppointments as $apt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($apt['client_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($apt['client_email']); ?></small>
                                    </td>
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
                                            <?php if($apt['status'] == 'pending'): ?>
                                                <a href="update_appointment.php?id=<?php echo $apt['id']; ?>&action=approve" class="btn-icon btn-approve" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="update_appointment.php?id=<?php echo $apt['id']; ?>&action=reject" class="btn-icon btn-reject" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if($apt['status'] == 'approved'): ?>
                                                <a href="update_appointment.php?id=<?php echo $apt['id']; ?>&action=complete" class="btn-icon btn-complete" title="Mark Complete">
                                                    <i class="fas fa-check-double"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    No appointments yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
