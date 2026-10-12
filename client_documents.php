<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle file upload
$uploadSuccess = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = basename($file['name']);
            $filePath = $uploadDir . time() . '_' . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                execute(
                    "INSERT INTO documents (user_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)",
                    [$user_id, $fileName, $filePath, $file['type']]
                );
                $uploadSuccess = 'Document uploaded successfully!';
            } else {
                $uploadError = 'Failed to upload document.';
            }
        } else {
            $uploadError = 'Only PDF and DOCX files are allowed.';
        }
    } else {
        $uploadError = 'Error uploading file.';
    }
}

$documents = getAllRows(
    "SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC",
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
<title>My Documents - Justice Law Firm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--light-gray); color: var(--dark); }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: var(--primary); font-size: 1.8rem; }
        .logout-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: var(--danger); color: var(--white); text-decoration: none; border-radius: 8px; }

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.8rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .upload-section { background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .upload-section h2 { color: var(--primary); margin-bottom: 1.5rem; }
        .upload-form { display: flex; gap: 1rem; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.8rem; border: 2px solid var(--light-gray); border-radius: 8px; }
        .form-group input:focus { outline: none; border-color: var(--accent); }
        .btn { padding: 0.7rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 600; transition: 0.3s; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .btn-primary { background: var(--accent); color: var(--primary); }
        .btn-primary:hover { background: var(--accent-hover); }

        .section-card { background: var(--white); border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .section-header { padding: 1.5rem; border-bottom: 1px solid var(--light-gray); }
        .section-header h2 { color: var(--primary); font-size: 1.3rem; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; }
        th { background: var(--light-gray); color: var(--primary); font-weight: 600; }
        td { border-bottom: 1px solid var(--light-gray); }
        tr:hover { background: var(--light-gray); }

        .doc-icon { width: 40px; height: 40px; background: rgba(212, 175, 55, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent); }
        .doc-info h4 { color: var(--primary); }
        .doc-info p { color: var(--gray); font-size: 0.9rem; }
        .action-buttons { display: flex; gap: 0.5rem; }
        .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.85rem; transition: 0.3s; }
        .btn-download { background: rgba(56, 161, 105, 0.1); color: var(--success); }
        .btn-download:hover { background: var(--success); color: var(--white); }

        .empty-state { text-align: center; padding: 4rem; color: var(--gray); }
        .empty-state i { font-size: 4rem; color: var(--light-gray); margin-bottom: 1rem; }

        .mobile-menu-btn { display: none; }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; position: fixed; top: 1rem; left: 1rem; z-index: 1001; width: 45px; height: 45px; background: var(--primary); border: none; border-radius: 8px; color: var(--white); font-size: 1.2rem; cursor: pointer; }
            .upload-form { flex-direction: column; }
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

        <?php if($uploadSuccess): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $uploadSuccess; ?></div>
        <?php endif; ?>
        <?php if($uploadError): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $uploadError; ?></div>
        <?php endif; ?>

        <div class="upload-section">
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload Document</h2>
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="document">Select File (PDF or DOCX)</label>
                    <input type="file" id="document" name="document" accept=".pdf,.doc,.docx" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
            </form>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2>My Uploaded Documents</h2>
            </div>
            <div class="table-container">
                <?php if(!empty($documents)): ?>
                    <table>
                        <thead>
                            <tr><th>Document</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <div class="doc-info" style="display:flex;align-items:center;gap:1rem;">
                                            <div class="doc-icon"><i class="fas fa-file-<?php echo strpos($doc['file_type'], 'pdf') !== false ? 'pdf' : 'word'; ?>"></i></div>
                                            <div><h4><?php echo htmlspecialchars($doc['file_name']); ?></h4></div>
                                        </div>
                                    </td>
                                    <td><?php echo strtoupper(pathinfo($doc['file_name'], PATHINFO_EXTENSION)); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn-icon btn-download" download title="Download"><i class="fas fa-download"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Documents</h3>
                        <p>Upload your first document above</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('active');}</script>
</body>
</html>
