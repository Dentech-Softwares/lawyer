<?php
/**
 * Edit Appointment Details
 * Admin can modify appointment date, time, case type, and message
 */

require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_appointments.php');
    exit;
}

$id = (int)$_GET['id'];

// Get appointment with client info
$appointment = getRow(
    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
     FROM appointments a 
     JOIN users u ON a.user_id = u.id 
     WHERE a.id = ?",
    [$id]
);

if (!$appointment) {
    header('Location: manage_appointments.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $case_type = $_POST['case_type'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($case_type) || empty($date) || empty($time)) {
        $error = 'All fields are required';
    } elseif (strtotime($date) < strtotime(date('Y-m-d')) && $date !== $appointment['date']) {
        $error = 'Please select a valid future date';
    } else {
        // Check for duplicate booking at same date and time (excluding current appointment)
        $existing = getRow(
            "SELECT id FROM appointments WHERE date = ? AND time = ? AND status != 'rejected' AND id != ?",
            [$date, $time, $id]
        );
        
        if ($existing) {
            $error = 'This time slot is already booked. Please select another time.';
        } else {
            // Update appointment
            $result = execute(
                "UPDATE appointments SET case_type = ?, date = ?, time = ?, message = ? WHERE id = ?",
                [$case_type, $date, $time, $message, $id]
            );
            
            if ($result) {
                // Create notification for client
                execute(
                    "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                    [$appointment['user_id'], 'Your appointment details have been updated by the admin.']
                );
                
                $success = 'Appointment updated successfully!';
                
                // Refresh appointment data
                $appointment = getRow(
                    "SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
                     FROM appointments a 
                     JOIN users u ON a.user_id = u.id 
                     WHERE a.id = ?",
                    [$id]
                );
            } else {
                $error = 'Failed to update appointment. Please try again.';
            }
        }
    }
}

// Admin header data
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_names = explode(' ', $admin_username);
$admin_initials = strtoupper(substr($admin_names[0], 0, 1) . (isset($admin_names[1]) ? substr($admin_names[1], 0, 1) : ''));
$current_date = date('F j, Y');

// Get admin notifications
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
    <title>Edit Appointment - Justice Law Firm</title>
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        /* Header */
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
            font-size: 1.25rem;
            font-weight: 600;
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

        /* Form Styles */
        .form-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1.5rem;
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .form-header h2 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .form-header i {
            color: var(--accent);
        }

        .form-body {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .client-info {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .client-info h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .client-info p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .client-info i {
            color: var(--accent);
            width: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group label i {
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Time Slots */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.8rem;
            margin-top: 0.5rem;
        }

        .time-slot {
            padding: 0.8rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .time-slot:hover {
            border-color: var(--accent);
            background: rgba(212, 175, 55, 0.1);
        }

        .time-slot.selected {
            background: var(--accent);
            color: var(--primary);
            border-color: var(--accent);
        }

        .time-slot.unavailable {
            opacity: 0.5;
            cursor: not-allowed;
            background: var(--light-gray);
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

        .btn-secondary {
            background: var(--light-gray);
            color: var(--primary);
            border: 2px solid var(--light-gray);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
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

            .form-row {
                grid-template-columns: 1fr;
            }

            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php
    $sidebar_role = 'admin';
    include 'includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include 'admin_header.php'; ?>

        <!-- Edit Form -->
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Edit Appointment</h2>
                <a href="manage_appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
            </div>
            
            <div class="form-body">
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Client Info -->
                <div class="client-info">
                    <h3><i class="fas fa-user"></i> Client Information</h3>
                    <p><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($appointment['client_name']); ?></strong></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($appointment['client_email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['client_phone']); ?></p>
                    <p><i class="fas fa-tag"></i> Current Status: <span class="status-badge <?php echo $appointment['status']; ?>" style="padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(214, 158, 46, 0.1); color: var(--warning);"><?php echo ucfirst($appointment['status']); ?></span></p>
                </div>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="case_type">
                                <i class="fas fa-briefcase"></i> Case Type
                            </label>
                            <select id="case_type" name="case_type" required>
                                <option value="">Select case type</option>
                                <option value="Corporate Law" <?php echo $appointment['case_type'] == 'Corporate Law' ? 'selected' : ''; ?>>Corporate Law</option>
                                <option value="Family Law" <?php echo $appointment['case_type'] == 'Family Law' ? 'selected' : ''; ?>>Family Law</option>
                                <option value="Criminal Defense" <?php echo $appointment['case_type'] == 'Criminal Defense' ? 'selected' : ''; ?>>Criminal Defense</option>
                                <option value="Real Estate" <?php echo $appointment['case_type'] == 'Real Estate' ? 'selected' : ''; ?>>Real Estate</option>
                                <option value="Personal Injury" <?php echo $appointment['case_type'] == 'Personal Injury' ? 'selected' : ''; ?>>Personal Injury</option>
                                <option value="Immigration" <?php echo $appointment['case_type'] == 'Immigration' ? 'selected' : ''; ?>>Immigration</option>
                                <option value="Civil Litigation" <?php echo $appointment['case_type'] == 'Civil Litigation' ? 'selected' : ''; ?>>Civil Litigation</option>
                                <option value="Other" <?php echo $appointment['case_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">
                                <i class="fas fa-calendar"></i> Appointment Date
                            </label>
                            <input type="date" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($appointment['date']); ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-clock"></i> Appointment Time
                            </label>
                            <div class="time-slots" id="timeSlots">
                                <input type="hidden" name="time" id="selectedTime" value="<?php echo htmlspecialchars($appointment['time']); ?>">
                                <div class="time-slot <?php echo $appointment['time'] == '09:00:00' ? 'selected' : ''; ?>" data-time="09:00:00">9:00 AM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '10:00:00' ? 'selected' : ''; ?>" data-time="10:00:00">10:00 AM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '11:00:00' ? 'selected' : ''; ?>" data-time="11:00:00">11:00 AM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '12:00:00' ? 'selected' : ''; ?>" data-time="12:00:00">12:00 PM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '13:00:00' ? 'selected' : ''; ?>" data-time="13:00:00">1:00 PM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '14:00:00' ? 'selected' : ''; ?>" data-time="14:00:00">2:00 PM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '15:00:00' ? 'selected' : ''; ?>" data-time="15:00:00">3:00 PM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '16:00:00' ? 'selected' : ''; ?>" data-time="16:00:00">4:00 PM</div>
                                <div class="time-slot <?php echo $appointment['time'] == '17:00:00' ? 'selected' : ''; ?>" data-time="17:00:00">5:00 PM</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">
                                <i class="fas fa-flag"></i> Status (Current: <?php echo ucfirst($appointment['status']); ?>)
                            </label>
                            <select id="status" name="status" disabled style="background: var(--light-gray);">
                                <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $appointment['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $appointment['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <small style="color: var(--gray);">Use "Update Status" actions in the appointments list to change status.</small>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="message">
                            <i class="fas fa-align-left"></i> Case Description / Notes
                        </label>
                        <textarea id="message" name="message" 
                                  placeholder="Briefly describe the legal matter or add notes..."><?php echo htmlspecialchars($appointment['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="manage_appointments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Time Slot Selection
        const timeSlots = document.querySelectorAll('.time-slot');
        const selectedTimeInput = document.getElementById('selectedTime');
        const selectedDateInput = document.getElementById('date');

        timeSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                if (this.classList.contains('unavailable')) return;
                
                // Remove selected from all
                timeSlots.forEach(s => s.classList.remove('selected'));
                // Add to clicked
                this.classList.add('selected');
                // Set hidden input
                selectedTimeInput.value = this.dataset.time;
            });
        });

        // Check availability when date changes (demo - in real app fetch from server)
        selectedDateInput.addEventListener('change', function() {
            const date = this.value;
            if (date) {
                timeSlots.forEach(slot => {
                    slot.classList.remove('unavailable');
                    // In a real app, fetch booked times from server for this date
                    // For demo, randomly mark some as unavailable
                    if (Math.random() < 0.2) {
                        slot.classList.add('unavailable');
                    }
                });
                
                // If currently selected slot becomes unavailable, clear selection
                const currentSelected = document.querySelector('.time-slot.selected');
                if (currentSelected && currentSelected.classList.contains('unavailable')) {
                    currentSelected.classList.remove('selected');
                    selectedTimeInput.value = '';
                }
            }
        });

        // Form Validation
        const editForm = document.querySelector('form[method="POST"]');
        
        editForm.addEventListener('submit', function(e) {
            const time = selectedTimeInput.value;
            if (!time) {
                e.preventDefault();
                alert('Please select an appointment time slot');
                return;
            }

            const date = new Date(selectedDateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (date < today) {
                e.preventDefault();
                alert('Please select a valid future date');
                return;
            }
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>