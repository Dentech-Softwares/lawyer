<?php
require_once 'db.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle file upload
$uploadSuccess = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $typeValid = in_array($file['type'], $allowedTypes);
        $extValid = in_array($fileExtension, $allowedExtensions);
        
        if ($typeValid || $extValid) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = basename($file['name']);
            $filePath = $uploadDir . time() . '_' . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $storedType = $typeValid ? $file['type'] : mime_content_type($filePath);
                execute(
                    "INSERT INTO documents (user_id, appointment_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?)",
                    [$user_id, $appointment_id, $fileName, $filePath, $storedType]
                );
                $uploadSuccess = 'Document uploaded successfully!';
            } else {
                $uploadError = 'Failed to upload document. Check directory permissions.';
            }
        } else {
            $uploadError = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, JPG, PNG, GIF.';
        }
    } else {
        $uploadError = 'Error uploading file. Code: ' . $file['error'];
    }
}

// Get clients and appointments for dropdown
$clients = getAllRows("SELECT id, name FROM users ORDER BY name");
$appointments = getAllRows("SELECT a.id, a.case_type, u.name as client_name FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC");

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

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.8rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

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

        .upload-section { background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .upload-section h2 { color: var(--primary); margin-bottom: 1rem; }
        .upload-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem; }
        .form-group select, .form-group input { width: 100%; padding: 0.8rem; border: 2px solid var(--light-gray); border-radius: 8px; }
        .btn { padding: 0.7rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 600; transition: 0.3s; border: none; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .btn-primary { background: var(--accent); color: var(--primary); }
        .btn-primary:hover { background: var(--accent-hover); }

        .doc-icon { width: 45px; height: 45px; background: rgba(212, 175, 55, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.2rem; }
        .doc-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }

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
    $sidebar_badges = [
        'appointments' => $pendingAppointmentsCount,
        'schedule' => $pendingAppointmentsCount,
    ];
    include 'includes/sidebar.php';
    ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>

        <?php if($uploadSuccess): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $uploadSuccess; ?></div>
        <?php endif; ?>
        <?php if($uploadError): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $uploadError; ?></div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-section">
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload New Document</h2>
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="min-width: 200px;">
                    <label for="user_id">Client <span style="color: var(--danger);">*</span></label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="min-width: 250px;">
                    <label for="appointment_id">Appointment (Optional)</label>
                    <select id="appointment_id" name="appointment_id">
                        <option value="">-- Select Appointment --</option>
                        <?php foreach ($appointments as $apt): ?>
                            <option value="<?php echo $apt['id']; ?>"><?php echo htmlspecialchars($apt['client_name'] . ' - ' . $apt['case_type'] . ' (' . date('M d, Y', strtotime($apt['date'])) . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="document">File <span style="color: var(--danger);">*</span></label>
                    <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
            </form>
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
                                            <?php if (strpos($doc['file_type'], 'image/') === 0): ?>
                                                <div class="doc-icon" style="background: #e8f5e9;">
                                                    <img src="<?php echo htmlspecialchars($doc['file_path']); ?>" alt="<?php echo htmlspecialchars($doc['file_name']); ?>" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px;">
                                                </div>
                                            <?php else: ?>
                                                <div class="doc-icon"><i class="fas fa-file-<?php echo strpos($doc['file_type'], 'pdf') !== false ? 'pdf' : 'word'; ?>"></i></div>
                                            <?php endif; ?>
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
