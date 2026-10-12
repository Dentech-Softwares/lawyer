<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$documents = getAllRows(
    "SELECT d.*, u.name as client_name, a.case_type 
     FROM documents d 
     LEFT JOIN users u ON d.user_id = u.id 
     LEFT JOIN appointments a ON d.appointment_id = a.id 
     ORDER BY d.uploaded_at DESC"
);

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
    <title>Manage Documents - Justice Law Firm</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--light-gray); color: var(--dark); }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: var(--primary); font-size: 1.8rem; }
        .header-actions { display: flex; gap: 1rem; }
        .logout-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: var(--danger); color: var(--white); text-decoration: none; border-radius: 8px; }
        .filter-btn { padding: 0.7rem 1.5rem; border: 2px solid var(--light-gray); background: var(--white); border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--gray); text-decoration: none; transition: 0.3s; }
        .filter-btn:hover { border-color: var(--accent); }

        .section-card { background: var(--white); border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; }
        th { background: var(--light-gray); color: var(--primary); font-weight: 600; }
        td { border-bottom: 1px solid var(--light-gray); }
        tr:hover { background: var(--light-gray); }

        .doc-icon { width: 45px; height: 45px; background: rgba(212, 175, 55, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.2rem; }
        .doc-info h4 { color: var(--primary); }
        .doc-info p { color: var(--gray); font-size: 0.9rem; }

        .action-buttons { display: flex; gap: 0.5rem; }
        .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.85rem; transition: 0.3s; }
        .btn-download { background: rgba(56, 161, 105, 0.1); color: var(--success); }
        .btn-download:hover { background: var(--success); color: var(--white); }
        .btn-delete { background: rgba(229, 62, 62, 0.1); color: var(--danger); }
        .btn-delete:hover { background: var(--danger); color: var(--white); }

        .empty-state { text-align: center; padding: 4rem; color: var(--gray); }
        .empty-state i { font-size: 4rem; color: var(--light-gray); }

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
    $sidebar_role = 'admin';
    include 'includes/sidebar.php';
    ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>

        <div class="section-card">
            <div class="table-container">
                <?php if(!empty($documents)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Client</th>
                                <th>Case Type</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <div class="doc-info" style="display:flex;align-items:center;gap:1rem;">
                                            <div class="doc-icon"><i class="fas fa-file-<?php echo strpos($doc['file_type'], 'pdf') !== false ? 'pdf' : 'word'; ?>"></i></div>
                                            <div>
                                                <h4><?php echo htmlspecialchars($doc['file_name']); ?></h4>
                                                <p><?php echo strtoupper($doc['file_type'] ?? 'FILE'); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['client_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($doc['case_type'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn-icon btn-download" download title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="delete_document.php?id=<?php echo $doc['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Delete this document?');">
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
                        <i class="fas fa-folder-open"></i>
                        <h3>No Documents Found</h3>
                        <p>Documents will appear here when clients upload them</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
    </script>
</body>
</html>
