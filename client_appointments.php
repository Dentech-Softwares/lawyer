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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Justice Law Firm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .sidebar { width: 280px; background: linear-gradient(135deg, var(--primary), var(--secondary)); min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000; padding: 2rem 0; }
        .sidebar-logo { text-align: center; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
        .sidebar-logo h2 { color: var(--white); font-size: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.8rem; }
        .sidebar-logo i { color: var(--accent); }
        .sidebar-user { text-align: center; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
        .sidebar-user img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent); margin-bottom: 1rem; }
        .sidebar-user h3 { color: var(--white); font-size: 1.1rem; }
        .sidebar-user p { color: rgba(255,255,255,0.7); font-size: 0.9rem; }
        .sidebar-menu { list-style: none; padding: 0 1rem; }
        .sidebar-menu li { margin-bottom: 0.5rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem; color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 8px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: var(--white); }
        .sidebar-menu a.active { border-left: 3px solid var(--accent); }
        .sidebar-menu a i { width: 20px; text-align: center; }

        .main-content { margin-left: 280px; padding: 2rem; }
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
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; }
        th { background: var(--light-gray); color: var(--primary); font-weight: 600; }
        td { border-bottom: 1px solid var(--light-gray); }
        tr:hover { background: var(--light-gray); }
        .status-badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.pending { background: rgba(214, 158, 46, 0.1); color: var(--warning); }
        .status-badge.approved { background: rgba(56, 161, 105, 0.1); color: var(--success); }
        .status-badge.rejected { background: rgba(229, 62, 62, 0.1); color: var(--danger); }
        .status-badge.completed { background: rgba(49, 130, 206, 0.1); color: var(--info); }

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
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><h2><i class="fas fa-gavel"></i> Justice Law</h2></div>
        <div class="sidebar-user">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=d4af37&color=1a3a5c" alt="User">
            <h3><?php echo htmlspecialchars($user_name); ?></h3>
            <p>Client Account</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="client_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="client_appointments.php" class="active"><i class="fas fa-calendar-check"></i> My Appointments</a></li>
            <li><a href="client_documents.php"><i class="fas fa-file-alt"></i> My Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> My Appointments</h1>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

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
                    <table>
                        <thead>
                            <tr><th>Case Type</th><th>Date</th><th>Time</th><th>Message</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $apt): ?>
                                <tr>
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
