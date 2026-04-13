<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$documents = getAllRows(
    "SELECT d.*, u.name as client_name, a.case_type 
     FROM documents d 
     LEFT JOIN users u ON d.user_id = u.id 
     LEFT JOIN appointments a ON d.appointment_id = a.id 
     ORDER BY d.uploaded_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - Justice Law Firm</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--light-gray); color: var(--dark); }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            z-index: 1000;
            padding: 2rem 0;
        }

        .sidebar-logo { text-align: center; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
        .sidebar-logo h2 { color: var(--white); font-size: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.8rem; }
        .sidebar-logo i { color: var(--accent); }

        .sidebar-admin { text-align: center; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
        .sidebar-admin img { width: 70px; height: 70px; border-radius: 50%; border: 3px solid var(--accent); margin-bottom: 0.8rem; }
        .sidebar-admin h3 { color: var(--white); }

        .sidebar-menu { list-style: none; padding: 0 1rem; }
        .sidebar-menu li { margin-bottom: 0.5rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem; color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 8px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: var(--white); }
        .sidebar-menu a.active { border-left: 3px solid var(--accent); }
        .sidebar-menu a i { width: 20px; text-align: center; }

        .main-content { margin-left: 280px; padding: 2rem; }

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
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><h2><i class="fas fa-gavel"></i> Justice Law</h2></div>
        <div class="sidebar-admin">
            <img src="https://ui-avatars.com/api/?name=Admin&background=d4af37&color=1a3a5c" alt="Admin">
            <h3>Administrator</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="manage_appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
            <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="manage_documents.php" class="active"><i class="fas fa-file-alt"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-globe"></i> View Website</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Manage Documents</h1>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="filter-btn"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

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
