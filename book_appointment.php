<?php
require_once 'db.php';

$success = '';
$error = '';

// Check if user is logged in (optional - can allow guest booking too)
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = null;
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
    if (empty($full_name) || empty($email) || empty($phone) || empty($case_type) || empty($date) || empty($time)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a valid future date';
    } else {
        // Check for duplicate booking at same date and time
        $existing = getRow(
            "SELECT id FROM appointments WHERE date = ? AND time = ? AND status != 'rejected'",
            [$date, $time]
        );
        
        if ($existing) {
            $error = 'This time slot is already booked. Please select another time.';
        } else {
            // If not logged in, create or find user
            if (!$user_id) {
                // Check if email exists
                $existingUser = getRow("SELECT id FROM users WHERE email = ?", [$email]);
                
                if ($existingUser) {
                    $user_id = $existingUser['id'];
                } else {
                    // Create temporary user (in real app, require registration)
                    $tempPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    execute(
                        "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)",
                        [$full_name, $email, $phone, $tempPassword]
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
                // Create notification
                execute(
                    "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                    [$user_id, 'Your appointment has been submitted successfully. We will review it shortly.']
                );
                
                $success = 'Appointment booked successfully! You will receive a notification once reviewed.';
                
                // Clear form
                $full_name = $email = $phone = $case_type = $date = $time = $description = '';
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
            background: linear-gradient(135deg, var(--light-gray) 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 120px 2rem 2rem;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 5%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        .mobile-menu-btn {
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

            .mobile-menu-btn {
                display: block;
            }

            body {
                padding: 100px 1rem 2rem;
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
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-gavel"></i>
                <span>Justice Law</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="client_dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <!-- Booking Form -->
    <div class="booking-container">
        <div class="booking-card">
            <div class="booking-header">
                <i class="fas fa-calendar-check"></i>
                <h1>Book an Appointment</h1>
                <p>Schedule a consultation with our expert attorneys</p>
            </div>
            
            <div class="booking-body">
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
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                   placeholder="john@example.com" required>
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
                                <div class="time-slot" data-time="09:00:00">9:00 AM</div>
                                <div class="time-slot" data-time="10:00:00">10:00 AM</div>
                                <div class="time-slot" data-time="11:00:00">11:00 AM</div>
                                <div class="time-slot" data-time="12:00:00">12:00 PM</div>
                                <div class="time-slot" data-time="13:00:00">1:00 PM</div>
                                <div class="time-slot" data-time="14:00:00">2:00 PM</div>
                                <div class="time-slot" data-time="15:00:00">3:00 PM</div>
                                <div class="time-slot" data-time="16:00:00">4:00 PM</div>
                                <div class="time-slot" data-time="17:00:00">5:00 PM</div>
                            </div>
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

    <script>
        // Time Slot Selection
        const timeSlots = document.querySelectorAll('.time-slot');
        const selectedTimeInput = document.getElementById('selectedTime');
        const selectedDateInput = document.getElementById('date');

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
                // Remove selected from all
                timeSlots.forEach(s => s.classList.remove('selected'));
                // Add to clicked
                this.classList.add('selected');
                // Set hidden input
                selectedTimeInput.value = this.dataset.time;
            });
        });

        // Check availability when date changes
        selectedDateInput.addEventListener('change', function() {
            const date = this.value;
            if (date) {
                // In a real app, fetch booked times from server
                // For demo, randomly mark some as unavailable
                timeSlots.forEach(slot => {
                    slot.classList.remove('unavailable');
                    if (Math.random() < 0.2) {
                        slot.classList.add('unavailable');
                    }
                });
            }
        });

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
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    </script>
</body>
</html>
