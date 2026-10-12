<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$appointments = getAllRows(
    "SELECT * FROM appointments WHERE user_id = ? ORDER BY date DESC, time DESC",
    [$user_id]
);

// Client header data
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Justice Law Firm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <style>
        :root {
            --primary: #1a3a5c;
            --secondary: #2c5282;
            --accent: #d4af37;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --dark: #1a202c;
            --gray: #718096;
            --success: #38a169;
            --danger: #e53e3e;
            --warning: #d69e2e;
            --info: #3182ce;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--light-gray); color: var(--dark); }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: var(--primary); font-size: 1.8rem; }
        .logout-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: var(--danger); color: var(--white); text-decoration: none; border-radius: 8px; transition: 0.3s; }
        .logout-btn:hover { background: #c53030; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 1.5rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-icon.pending { background: rgba(214, 158, 46, 0.1); color: var(--warning); }
        .stat-icon.approved { background: rgba(56, 161, 105, 0.1); color: var(--success); }
        .stat-icon.rejected { background: rgba(229, 62, 62, 0.1); color: var(--danger); }
        .stat-icon.completed { background: rgba(49, 130, 206, 0.1); color: var(--info); }
        .stat-info h3 { color: var(--gray); font-size: 0.9rem; font-weight: 500; }
        .stat-info p { color: var(--primary); font-size: 1.5rem; font-weight: 700; }

        .section-card { background: var(--white); border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .section-header { padding: 1.5rem; border-bottom: 1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; }
        .section-header h2 { color: var(--primary); font-size: 1.3rem; }
        .btn { padding: 0.7rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 600; transition: 0.3s; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .btn-primary { background: var(--accent); color: var(--primary); }
        .btn-primary:hover { background: var(--accent-hover); }
        .table-container { overflow-x: auto; }
        table.appointments-table { width: 100%; border-collapse: collapse; }
        table.appointments-table th, table.appointments-table td { padding: 1rem 1.5rem; text-align: left; }
        table.appointments-table th { background: var(--light-gray); color: var(--primary); font-weight: 600; }
        table.appointments-table td { border-bottom: 1px solid var(--light-gray); }
        table.appointments-table tr.appointment-row { cursor: pointer; transition: background 0.2s ease; }
        table.appointments-table tr.appointment-row:hover { background: var(--light-gray); }
        table.appointments-table tr.appointment-row:active { background: #e8eef5; }
        .status-badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.pending { background: rgba(214, 158, 46, 0.1); color: var(--warning); }
        .status-badge.approved { background: rgba(56, 161, 105, 0.1); color: var(--success); }
        .status-badge.rejected { background: rgba(229, 62, 62, 0.1); color: var(--danger); }
        .status-badge.completed { background: rgba(49, 130, 206, 0.1); color: var(--info); }

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

        .empty-state { text-align: center; padding: 4rem; color: var(--gray); }
        .empty-state i { font-size: 4rem; color: var(--light-gray); margin-bottom: 1rem; }

        .mobile-menu-btn { display: none; }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; position: fixed; top: 1rem; left: 1rem; z-index: 1001; width: 45px; height: 45px; background: var(--primary); border: none; border-radius: 8px; color: var(--white); font-size: 1.2rem; cursor: pointer; }
        }
    </style>
</head>
<body>
    <?php
    $sidebar_role = 'client';
    include 'includes/sidebar.php';
    ?>

    <main class="main-content">
        <?php include 'client_header.php'; ?>

        <?php
        $pending = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending'", [$user_id]);
        $approved = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'approved'", [$user_id]);
        $rejected = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'rejected'", [$user_id]);
        $completed = getRow("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'completed'", [$user_id]);
        ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon pending"><i class="fas fa-clock"></i></div><div class="stat-info"><h3>Pending</h3><p><?php echo $pending['count']; ?></p></div></div>
            <div class="stat-card"><div class="stat-icon approved"><i class="fas fa-check"></i></div><div class="stat-info"><h3>Approved</h3><p><?php echo $approved['count']; ?></p></div></div>
            <div class="stat-card"><div class="stat-icon rejected"><i class="fas fa-times"></i></div><div class="stat-info"><h3>Rejected</h3><p><?php echo $rejected['count']; ?></p></div></div>
            <div class="stat-card"><div class="stat-icon completed"><i class="fas fa-flag-checkered"></i></div><div class="stat-info"><h3>Completed</h3><p><?php echo $completed['count']; ?></p></div></div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2>All Appointments</h2>
                <a href="book_appointment.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Appointment</a>
            </div>
            <div class="table-container">
                <?php if(!empty($appointments)): ?>
                    <table class="appointments-table">
                        <thead>
                             <tr><th>Case Type</th><th>Date</th><th>Time</th><th>Message</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $apt): ?>
                                <tr class="appointment-row" onclick="window.location.href='appointment_details.php?id=<?php echo $apt['id']; ?>'">
                                    <td><?php echo htmlspecialchars($apt['case_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($apt['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($apt['time'])); ?></td>
                                     <td><?php echo nl2br(htmlspecialchars(substr($apt['message'] ?? '', 0, 50))); ?><?php echo strlen($apt['message'] ?? '') > 50 ? '...' : ''; ?></td>
<td><span class="status-badge <?php echo $apt['status']; ?>"><?php echo ucfirst($apt['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <h3>No Appointments Yet</h3>
                        <p>Book your first appointment to get started</p>
                        <a href="book_appointment.php" class="btn btn-primary" style="margin-top:1rem;">Book Appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('active');}</script>
</body>
</html>
