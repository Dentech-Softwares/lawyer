<?php
require_once 'db.php';
require_once 'includes/helpers.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter
$blockFilter = $_GET['filter'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($blockFilter === 'blocked') {
    $sql .= " AND is_blocked = 1";
} elseif ($blockFilter === 'active') {
    $sql .= " AND is_blocked = 0";
}

$sql .= " ORDER BY created_at DESC";

$clients = getAllRows($sql, $params);

// Admin header data
    $admin_username = $_SESSION['admin_username'];
    $admin_email = $_SESSION['admin_email'] ?? '';
    $admin_phone = $_SESSION['admin_phone'] ?? '';
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
    "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending' AND created_at > ?",
    [$_SESSION['admin_last_appointments_view'] ?? '1970-01-01 00:00:00']
);
$pendingAppointmentsCount = $unreadAdminNotifications['count'] ?? 0;
    $blockedClientsCount = getRow("SELECT COUNT(*) as count FROM users WHERE is_blocked = 1")['count'] ?? 0;

    // Count of flagged continuations for the sidebar badge
    $continuationBadgeCount = getRow(
        "SELECT COUNT(*) as count FROM appointments WHERE is_continuation = 1"
    )['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - Justice Law Firm</title>
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
        }

        /* Filters */
        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid var(--light-gray);
            background: var(--white);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--accent);
            background: rgba(212, 175, 55, 0.1);
            color: var(--primary);
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
        }

        td {
            border-bottom: 1px solid var(--light-gray);
        }

        tr:hover {
            background: var(--light-gray);
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .client-info img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--accent);
        }

        .client-info h4 {
            color: var(--primary);
        }

        .client-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .status-badge.blocked {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
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

        .btn-block {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning);
        }

        .btn-block:hover {
            background: var(--warning);
            color: var(--white);
        }

        .btn-unblock {
            background: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .btn-unblock:hover {
            background: var(--success);
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

        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--light-gray);
        }

        /* Mobile Menu Button */
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
    </style>
</head>
<body>
    <?php
    $sidebar_role = 'admin';
    $sidebar_badges = [
        'appointments' => $pendingAppointmentsCount,
        'schedule' => $continuationBadgeCount,
        'clients' => $blockedClientsCount,
    ];
    include 'includes/sidebar.php';
    ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>

        <div class="filters">
            <div class="filter-buttons">
                <a href="manage_clients.php" class="filter-btn <?php echo !$blockFilter ? 'active' : ''; ?>">All</a>
                <a href="manage_clients.php?filter=active" class="filter-btn <?php echo $blockFilter === 'active' ? 'active' : ''; ?>">Active</a>
                <a href="manage_clients.php?filter=blocked" class="filter-btn <?php echo $blockFilter === 'blocked' ? 'active' : ''; ?>">Blocked</a>
            </div>
        </div>

        <div class="section-card">
            <div class="table-container">
                <?php if(!empty($clients)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clients as $client): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($client['name']); ?>&background=d4af37&color=1a3a5c" alt="<?php echo htmlspecialchars($client['name']); ?>">
                                            <div>
                                                <h4><?php echo htmlspecialchars($client['name']); ?></h4>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $client['is_blocked'] ? 'blocked' : 'active'; ?>">
                                            <?php echo $client['is_blocked'] ? 'Blocked' : 'Active'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($client['is_blocked']): ?>
                                                <a href="toggle_block.php?id=<?php echo $client['id']; ?>&action=unblock" class="btn-icon btn-unblock" title="Unblock">
                                                    <i class="fas fa-unlock"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_block.php?id=<?php echo $client['id']; ?>&action=block" class="btn-icon btn-block" title="Block">
                                                    <i class="fas fa-lock"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="delete_client.php?id=<?php echo $client['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this client?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Clients Found</h3>
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
