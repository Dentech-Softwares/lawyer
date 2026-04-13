<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - Justice Law Firm</title>
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
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h2><i class="fas fa-gavel"></i> Justice Law</h2>
        </div>
        <div class="sidebar-admin">
            <img src="https://ui-avatars.com/api/?name=Admin&background=d4af37&color=1a3a5c" alt="Admin">
            <h3>Administrator</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="manage_appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
            <li><a href="manage_clients.php" class="active"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Manage Clients</h1>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="filter-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

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
