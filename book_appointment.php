<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/helpers.php';

$success = '';
$error = '';

// Check if user is logged in (optional - can allow guest booking too)
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = null;
}

// Payment status from session
$paymentSuccess = $_SESSION['mpesa_payment_success'] ?? '';
unset($_SESSION['mpesa_payment_success']);

// Appointment ID from URL (set after successful booking & redirect) or session fallback
$createdAppointmentId = $_GET['appointment_id'] ?? $_SESSION['booking_appointment_id'] ?? null;

// Flash messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Payment amount constant
define('BOOKING_FEE', 500);

// Client portal layout: logged-in clients get the same shared sidebar as the
// other client pages, so the navigation is pixel-identical everywhere.
$showClientPortal = isset($_SESSION['user_id']);
$notifications = [];
$unreadNotifications = ['count' => 0];
$current_date = date('F j, Y');

if ($showClientPortal) {
    $user_name = $_SESSION['user_name'] ?? '';
    $user = getRow("SELECT * FROM users WHERE id = ?", [$user_id]);

    $notifications = getAllRows(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
        [$user_id]
    );

    $unreadNotifications = getRow(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'",
        [$user_id]
    );

    $portalPendingCount = getRow(
        "SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending' AND is_read = 0",
        [$user_id]
    )['count'] ?? 0;

    $sidebar_badges = [
        'appointments' => $portalPendingCount,
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $case_type = $_POST['case_type'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($phone) || empty($case_type) || empty($date) || empty($time)) {
        $error = 'All required fields must be filled';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a valid future date';
    } else {
        // Capacity check: a time slot can hold up to MAX_APPOINTMENTS_PER_SLOT clients
        $slotBooked = getRow(
            "SELECT COUNT(*) as count FROM appointments WHERE date = ? AND time = ? AND status != 'rejected'",
            [$date, $time]
        );

        if ((int)($slotBooked['count'] ?? 0) >= MAX_APPOINTMENTS_PER_SLOT) {
            $error = 'This time slot is now fully booked (' . MAX_APPOINTMENTS_PER_SLOT . ' appointments). Please select another time.';
        } else {
            // If not logged in, create or find user
            if (!$user_id) {
                if (!empty($email)) {
                    // Check if email exists
                    $existingUser = getRow("SELECT id FROM users WHERE email = ?", [$email]);
                    
                    if ($existingUser) {
                        $user_id = $existingUser['id'];
                    }
                }
                
                if (!$user_id) {
                    // Create temporary user (in real app, require registration)
                    $tempPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    execute(
                        "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)",
                        [$full_name, $email ?: null, $phone, $tempPassword]
                    );
                    $user_id = getLastId();
                }
            }
            
            // Insert appointment
            $result = execute(
                "INSERT INTO appointments (user_id, case_type, date, time, message) VALUES (?, ?, ?, ?, ?)",
                [$user_id, $case_type, $date, $time, $description]
            );
            
            if ($result) {
                // Capture the appointment ID immediately after insertion,
                // BEFORE any subsequent INSERTs overwrite mysqli_insert_id.
                $appointmentId = getLastId();

                // Create notification
                execute(
                    "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                    [$user_id, 'Your appointment has been submitted successfully. We will review it shortly.']
                );
                
                // Send SMS confirmation
                if (!empty($phone)) {
                    $smsMsg = "Dear {$full_name}, your appointment for {$case_type} on {$date} at {$time} has been submitted. We will review it shortly. - Justice Law Firm";
                    sendSMS($phone, $smsMsg);
                }
                
                $_SESSION['success'] = 'Appointment booked successfully! Proceed to payment.';
                $_SESSION['booking_appointment_id'] = $appointmentId;
                header("Location: book_appointment.php?appointment_id=" . $appointmentId);
                exit();
            } else {
                $error = 'Failed to book appointment. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <?php if ($showClientPortal): ?>
        <link rel="stylesheet" href="assets/css/sidebar.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/page-transition.css">
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
            background: linear-gradient(135deg, var(--light-gray) 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 120px 2rem 2rem;
        }

        /* Client portal layout: the shared sidebar owns the left gutter */
        body.client-portal {
            padding: 0;
        }

        /* The portal wrapper already animates in; avoid a nested animation */
        body.client-portal .booking-card {
            animation: none;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.5;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--primary);
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        /* Booking Container */
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .booking-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .booking-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2.5rem;
            text-align: center;
            color: var(--white);
        }

        .booking-header i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .booking-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .booking-header p {
            opacity: 0.9;
        }

        .booking-body {
            padding: 2.5rem;
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

        /* Remaining capacity shown under each time slot */
        .time-slot .slot-note {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray);
            letter-spacing: 0.01em;
        }

        .time-slot.unavailable .slot-note {
            color: var(--danger);
        }

        .slot-hint {
            margin-top: 0.6rem;
            font-size: 0.8rem;
            color: var(--gray);
            min-height: 1rem;
        }

        .slot-hint.warning {
            color: var(--danger);
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .submit-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        /* Login Prompt */
        .login-prompt {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-prompt p {
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .login-prompt a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        /* Mobile Menu */
        .navbar-toggle-btn {
            display: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .navbar-toggle-btn {
                display: block;
            }

            body {
                padding: 100px 1rem 2rem;
            }

            /* Content spans the full width with a clean edge gutter */
            .booking-container {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            .header-container {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
                padding-left: 12px;
                padding-right: 12px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .booking-header h1 {
                font-size: 1.5rem;
            }

            .booking-body {
                padding: 1.5rem;
            }

            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body class="<?php echo $showClientPortal ? 'client-portal' : ''; ?>">
    <?php if ($showClientPortal): ?>
        <?php include 'includes/client_sidebar.php'; ?>
    <?php endif; ?>

    <?php if (!$showClientPortal): ?>
    <!-- Unified Header -->
    <?php include 'includes/header.php'; ?>
    <?php endif; ?>

    <?php if ($showClientPortal): ?>
    <main class="main-content">
        <?php include 'client_header.php'; ?>
    <?php endif; ?>

    <!-- Booking Form -->
    <div class="booking-container">
        <div class="booking-card">
            <div class="booking-header">
                <i class="fas fa-calendar-check"></i>
                <h1>Book an Appointment</h1>
                <p>Schedule a consultation with our expert attorneys</p>
            </div>
            
            <div class="booking-body">
                <?php if($paymentSuccess): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $paymentSuccess; ?>
                    </div>
                <?php endif; ?>

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

                <?php if($createdAppointmentId): ?>
                    <div class="payment-section" style="background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center; border: 2px dashed var(--accent);">
                        <h3 style="color: var(--primary); margin-bottom: 0.75rem; font-size: 1.1rem;">
                            <i class="fas fa-mobile-alt"></i> Complete Your Payment
                        </h3>
                        <p style="color: var(--gray); margin-bottom: 1rem; font-size: 0.95rem;">
                            Booking fee: <strong>KSh <?php echo number_format(BOOKING_FEE); ?></strong> (Pay via M-Pesa)
                        </p>
                        <button class="submit-btn" id="payNowBtn" style="max-width: 300px; margin: 0 auto;">
                            <i class="fas fa-money-bill-wave"></i> Pay Now
                        </button>
                    </div>

                    <div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
                        <div style="background:var(--white); border-radius:20px; padding:2rem; max-width:450px; width:90%; margin:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                            <div style="text-align:center; margin-bottom:1.5rem;">
                                <i class="fas fa-mobile-alt" style="font-size:2.5rem; color:var(--accent); margin-bottom:0.5rem;"></i>
                                <h3 style="color:var(--primary);">M-Pesa STK Push Payment</h3>
                                <p style="color:var(--gray); font-size:0.9rem;">Enter your M-Pesa number to pay KSh <?php echo number_format(BOOKING_FEE); ?></p>
                            </div>
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label for="mpesaPhone" style="display:block; color:var(--primary); font-weight:600; margin-bottom:0.5rem;">
                                    <i class="fas fa-phone"></i> M-Pesa Phone Number
                                </label>
                                <input type="tel" id="mpesaPhone" placeholder="2547XXXXXXXX"
                                       style="width:100%; padding:0.8rem; border:2px solid var(--light-gray); border-radius:8px; font-size:1rem; text-align:center;">
                            </div>
                            <div id="paymentStatus" style="display:none; text-align:center; padding:1rem; margin-bottom:1rem; border-radius:8px; background:#fff3cd; color:#856404; border:1px solid #ffeaa7;"></div>
                            <div style="display:flex; gap:1rem;">
                                <button class="btn btn-back" id="cancelPayment" style="flex:1;">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button class="btn btn-primary" id="confirmPayment" style="flex:1;">
                                    <i class="fas fa-check"></i> Pay
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(!$isLoggedIn): ?>
                    <div class="login-prompt">
                        <p>Already have an account? <a href="login.php">Login</a> for faster booking.</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="bookingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>"
                                   placeholder="John Doe" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address <span style="color: var(--gray); font-weight: normal; font-size: 0.85rem;">(Optional)</span>
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                   placeholder="john@example.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                                   placeholder="+1 (555) 123-4567" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="case_type">
                                <i class="fas fa-briefcase"></i> Case Type
                            </label>
                            <select id="case_type" name="case_type" required>
                                <option value="">Select case type</option>
                                <option value="Corporate Law" <?php echo (isset($case_type) && $case_type == 'Corporate Law') ? 'selected' : ''; ?>>Corporate Law</option>
                                <option value="Family Law" <?php echo (isset($case_type) && $case_type == 'Family Law') ? 'selected' : ''; ?>>Family Law</option>
                                <option value="Criminal Defense" <?php echo (isset($case_type) && $case_type == 'Criminal Defense') ? 'selected' : ''; ?>>Criminal Defense</option>
                                <option value="Real Estate" <?php echo (isset($case_type) && $case_type == 'Real Estate') ? 'selected' : ''; ?>>Real Estate</option>
                                <option value="Personal Injury" <?php echo (isset($case_type) && $case_type == 'Personal Injury') ? 'selected' : ''; ?>>Personal Injury</option>
                                <option value="Immigration" <?php echo (isset($case_type) && $case_type == 'Immigration') ? 'selected' : ''; ?>>Immigration</option>
                                <option value="Civil Litigation" <?php echo (isset($case_type) && $case_type == 'Civil Litigation') ? 'selected' : ''; ?>>Civil Litigation</option>
                                <option value="Other" <?php echo (isset($case_type) && $case_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">
                                <i class="fas fa-calendar"></i> Preferred Date
                            </label>
                            <input type="date" id="date" name="date" 
                                   value="<?php echo isset($date) ? htmlspecialchars($date) : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-clock"></i> Preferred Time
                            </label>
                            <div class="time-slots" id="timeSlots">
                                <input type="hidden" name="time" id="selectedTime" value="<?php echo isset($time) ? htmlspecialchars($time) : ''; ?>">
                                <div class="time-slot" data-time="09:00:00">09:00</div>
                                <div class="time-slot" data-time="10:00:00">10:00</div>
                                <div class="time-slot" data-time="11:00:00">11:00</div>
                                <div class="time-slot" data-time="12:00:00">12:00</div>
                                <div class="time-slot" data-time="13:00:00">13:00</div>
                                <div class="time-slot" data-time="14:00:00">14:00</div>
                                <div class="time-slot" data-time="15:00:00">15:00</div>
                                <div class="time-slot" data-time="16:00:00">16:00</div>
                                <div class="time-slot" data-time="17:00:00">17:00</div>
                            </div>
                            <p class="slot-hint" id="slotHint"></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-align-left"></i> Case Description
                        </label>
                        <textarea id="description" name="description" 
                                  placeholder="Briefly describe your legal matter..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($showClientPortal): ?>
    </main>
    <?php endif; ?>

    <script>
        // Time Slot Selection
        const timeSlots = document.querySelectorAll('.time-slot');
        const selectedTimeInput = document.getElementById('selectedTime');
        const selectedDateInput = document.getElementById('date');
        const slotHint = document.getElementById('slotHint');
        const MAX_PER_SLOT = <?php echo (int)MAX_APPOINTMENTS_PER_SLOT; ?>;

        // Every slot carries a small note with the remaining capacity
        timeSlots.forEach(slot => {
            const note = document.createElement('small');
            note.className = 'slot-note';
            note.textContent = MAX_PER_SLOT + ' spots';
            slot.appendChild(note);
        });

        // Pre-select time if set
        const currentTime = '<?php echo isset($time) ? $time : ''; ?>';
        if (currentTime) {
            const matchingSlot = document.querySelector(`.time-slot[data-time="${currentTime}"]`);
            if (matchingSlot) {
                matchingSlot.classList.add('selected');
            }
        }

        timeSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                // A full slot cannot be picked
                if (this.classList.contains('unavailable')) {
                    slotHint.textContent = 'This time slot is fully booked (' + MAX_PER_SLOT + ' appointments). Please pick another time.';
                    slotHint.classList.add('warning');
                    return;
                }

                // Remove selected from all
                timeSlots.forEach(s => s.classList.remove('selected'));
                // Add to clicked
                this.classList.add('selected');
                // Set hidden input
                selectedTimeInput.value = this.dataset.time;

                slotHint.textContent = '';
                slotHint.classList.remove('warning');
            });
        });

        // Load the real availability for the chosen date
        function loadSlotAvailability(date) {
            if (!date) return;

            fetch('ajax_slot_availability.php?date=' + encodeURIComponent(date))
                .then(response => response.json())
                .then(data => {
                    const availability = {};
                    (data.slots || []).forEach(slot => {
                        availability[slot.time] = slot;
                    });

                    timeSlots.forEach(slot => {
                        const info = availability[slot.dataset.time];
                        const note = slot.querySelector('.slot-note');

                        if (!info) {
                            slot.classList.remove('unavailable');
                            if (note) note.textContent = MAX_PER_SLOT + ' spots';
                            return;
                        }

                        const full = !info.available;
                        slot.classList.toggle('unavailable', full);

                        if (note) {
                            if (full) {
                                note.textContent = 'Fully booked';
                            } else if (info.booked > 0) {
                                note.textContent = info.remaining + ' of ' + MAX_PER_SLOT + ' left';
                            } else {
                                note.textContent = MAX_PER_SLOT + ' spots';
                            }
                        }

                        // Drop a selection that is no longer possible
                        if (full && slot.classList.contains('selected')) {
                            slot.classList.remove('selected');
                            selectedTimeInput.value = '';
                        }
                    });
                })
                .catch(() => {
                    // Keep the slots as they are if the availability cannot be loaded
                });
        }

        // Check availability when date changes
        selectedDateInput.addEventListener('change', function() {
            slotHint.textContent = '';
            slotHint.classList.remove('warning');
            loadSlotAvailability(this.value);
        });

        // The date may already be filled in (e.g. after a failed submission)
        if (selectedDateInput.value) {
            loadSlotAvailability(selectedDateInput.value);
        }

        // Form Validation
        const bookingForm = document.getElementById('bookingForm');
        
        bookingForm.addEventListener('submit', function(e) {
            const time = selectedTimeInput.value;
            if (!time) {
                e.preventDefault();
                alert('Please select a preferred time slot');
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

        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.navbar-toggle-btn');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Payment Modal Logic
        const payNowBtn = document.getElementById('payNowBtn');
        const paymentModal = document.getElementById('paymentModal');
        const cancelPayment = document.getElementById('cancelPayment');
        const confirmPayment = document.getElementById('confirmPayment');
        const paymentStatus = document.getElementById('paymentStatus');

        if (payNowBtn) {
            payNowBtn.addEventListener('click', function() {
                paymentModal.style.display = 'flex';
                paymentStatus.style.display = 'none';
                paymentStatus.innerHTML = '';
                paymentStatus.className = '';
            });
        }

        if (cancelPayment) {
            cancelPayment.addEventListener('click', function() {
                paymentModal.style.display = 'none';
            });
        }

        if (confirmPayment) {
            confirmPayment.addEventListener('click', function() {
                const phone = document.getElementById('mpesaPhone').value.replace(/\D/g, '');
                if (!phone || phone.length < 9) {
                    paymentStatus.style.display = 'block';
                    paymentStatus.className = 'alert alert-danger';
                    paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid M-Pesa phone number';
                    return;
                }

                let formattedPhone = phone;
                if (formattedPhone.startsWith('0')) {
                    formattedPhone = '254' + formattedPhone.substring(1);
                } else if (!formattedPhone.startsWith('254')) {
                    formattedPhone = '254' + formattedPhone;
                }

                confirmPayment.disabled = true;
                confirmPayment.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending STK Push...';
                paymentStatus.style.display = 'block';
                paymentStatus.className = 'alert';
                paymentStatus.innerHTML = '<i class="fas fa-info-circle"></i> Sending STK Push...';

                fetch('mpesa_pay.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'payment_type=booking&appointment_id=<?php echo $createdAppointmentId; ?>&phone_number=' + formattedPhone
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        paymentStatus.className = 'alert alert-success';
                        paymentStatus.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + '<br><small>STK Push sent. Please complete payment on your phone.</small>';
                        payNowBtn.innerHTML = '<i class="fas fa-clock"></i> Payment Pending...';
                        payNowBtn.disabled = true;
                    } else {
                        paymentStatus.className = 'alert alert-danger';
                        paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                        confirmPayment.disabled = false;
                        confirmPayment.innerHTML = '<i class="fas fa-check"></i> Pay';
                    }
                })
                .catch(error => {
                    paymentStatus.className = 'alert alert-danger';
                    paymentStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
                    confirmPayment.disabled = false;
                    confirmPayment.innerHTML = '<i class="fas fa-check"></i> Pay';
                });
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
