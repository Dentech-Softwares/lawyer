<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter values
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$caseTypeFilter = $_GET['case_type'] ?? '';

// Build query
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        WHERE 1=1";

$params = [];

if ($statusFilter) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    $sql .= " AND a.date = ?";
    $params[] = $dateFilter;
}

if ($caseTypeFilter) {
    $sql .= " AND a.case_type = ?";
    $params[] = $caseTypeFilter;
}

$sql .= " ORDER BY a.date DESC, a.time DESC";

$appointments = getAllRows($sql, $params);

// Get unique case types for filter
$caseTypes = getAllRows("SELECT DISTINCT case_type FROM appointments ORDER BY case_type");

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Justice Law Firm</title>
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

        /* Filters */
        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filters h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input {
            padding: 0.8rem 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 0.95rem;
            min-width: 150px;
        }

        .filter-form select:focus,
        .filter-form input:focus {
            outline: none;
            border-color: var(--accent);
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

        /* Table */
        .section-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
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
            flex-wrap: wrap;
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

        .btn-delete {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
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

        .message-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help;
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

            .filter-form {
                flex-direction: column;
            }

            .filter-form select,
            .filter-form input {
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
            <li><a href="manage_appointments.php" class="active"><i class="fas fa-calendar"></i> Appointments</a></li>
            <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php include 'admin_header.php'; ?>

        <!-- Filters -->
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filter Appointments</h3>
            <form class="filter-form" method="GET" action="">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $statusFilter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                
                <select name="case_type">
                    <option value="">All Case Types</option>
                    <?php foreach($caseTypes as $ct): ?>
                        <option value="<?php echo $ct['case_type']; ?>" <?php echo $caseTypeFilter == $ct['case_type'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ct['case_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date" value="<?php echo $dateFilter; ?>" placeholder="Filter by date">
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                
                <a href="manage_appointments.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="section-card">
            <div class="table-container">
                <?php if(!empty($appointments)): ?>
                    <table>
                        <thead>
                                <tr>
                                    <th>Client Info</th>
                                    <th>Case Type</th>
                                    <th>Date & Time</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($apt['client_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($apt['client_email']); ?></small><br>
                                        <small><?php echo htmlspecialchars($apt['client_phone']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['case_type']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($apt['date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($apt['time'])); ?></small>
                                     </td>
                                     <td class="message-cell" title="<?php echo htmlspecialchars($apt['message'] ?? ''); ?>">
                                         <?php echo htmlspecialchars(substr($apt['message'] ?? '', 0, 40)); ?>
                                         <?php echo strlen($apt['message'] ?? '') > 40 ? '...' : ''; ?>
                                     </td>
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
                                              <a href="admin_schedule.php" class="btn-icon btn-view" title="Schedule Continuation">
                                                  <i class="fas fa-clock"></i>
                                              </a>
                                              <a href="delete_appointment.php?id=<?php echo $apt['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this appointment?');">
                                                  <i class="fas fa-trash"></i>
                                              </a>
                                              <a href="appointment_notes.php?id=<?php echo $apt['id']; ?>" class="btn-icon btn-view" title="Notes">
                                                  <i class="fas fa-eye"></i>
                                              </a>
                                          </div>
                                     </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments Found</h3>
                        <p>No appointments match your filter criteria</p>
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
