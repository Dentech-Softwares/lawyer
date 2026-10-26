<?php
require_once 'db.php';

$success = '';
$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: client_dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation - only name, phone and password are mandatory (email is optional)
    if (empty($name) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'Full Name, Phone Number and Password are required';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if phone number already exists
        $existingPhone = getRow("SELECT id FROM users WHERE phone = ?", [$phone]);
        
        if ($existingPhone) {
            $error = 'This phone number is already registered. Please login or use a different phone number.';
        } elseif (!empty($email)) {
            // Check if email already exists (only when an email was provided)
            $existingEmail = getRow("SELECT id FROM users WHERE email = ?", [$email]);
            
            if ($existingEmail) {
                $error = 'This email address is already registered. Please login.';
            } else {
                // Proceed with registration
                $result = registerUser($name, $email, $phone, $password);
                if ($result['success']) {
                    $success = 'Registration successful! Redirecting to login...';
                    header('refresh:2;url=login.php');
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            // No email provided - proceed with registration
            $result = registerUser($name, $email, $phone, $password);
            if ($result['success']) {
                $success = 'Registration successful! Redirecting to login...';
                header('refresh:2;url=login.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

/**
 * Create a new user account.
 * Email is stored as NULL when not provided.
 */
function registerUser($name, $email, $phone, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $email = !empty($email) ? $email : null;
    
    $result = execute(
        "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)",
        [$name, $email, $phone, $hashed_password]
    );
    
    if ($result) {
        return ['success' => true, 'message' => ''];
    }
    return ['success' => false, 'message' => 'Registration failed. Please try again.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .register-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2.5rem;
            text-align: center;
            color: var(--white);
        }

        .register-header i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .register-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            opacity: 0.9;
        }

                .home-bottom-link {
            text-align: center;
            margin-top: 1.2rem;
        }

        .home-bottom-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .home-bottom-link a:hover {
            color: var(--accent);
        }

        .register-body {
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

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .form-group input::placeholder {
            color: var(--gray);
        }

        .password-strength {
            height: 4px;
            background: var(--light-gray);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger);
        }

        .password-strength-bar.medium {
            width: 66%;
            background: var(--accent);
        }

        .password-strength-bar.strong {
            width: 100%;
            background: var(--success);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .form-check input {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }

        .form-check label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .form-check label a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .form-check label a:hover {
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
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

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--light-gray);
        }

        .divider span {
            padding: 0 1rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .social-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-3px);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
        }

        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-home a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            color: var(--accent);
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .register-container {
                border-radius: 15px;
            }

            .register-header {
                padding: 2rem;
            }

                    .home-bottom-link {
            text-align: center;
            margin-top: 1.2rem;
        }

        .home-bottom-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .home-bottom-link a:hover {
            color: var(--accent);
        }

        .register-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">

            <i class="fas fa-gavel"></i>
            <h1>Create Account</h1>
            <p>Join Justice Law Firm today</p>
        
        </div>
        
        <div class="register-body">
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

            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" id="name" name="name" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address <span style="color: var(--gray); font-weight: 400;">(optional)</span>
                    </label>
                    <input type="email" id="email" name="email" placeholder="john@example.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 123-4567" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required minlength="6">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>

            <div class="home-bottom-link">
                <a href="index.php">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>

            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password Strength Indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            strengthBar.className = 'password-strength-bar';
            
            if (password.length >= 6) {
                strengthBar.classList.add('weak');
            }
            if (password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strengthBar.classList.remove('weak');
                strengthBar.classList.add('medium');
            }
            if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                strengthBar.classList.remove('medium');
                strengthBar.classList.add('strong');
            }
        });

        // Form Validation
        const registerForm = document.getElementById('registerForm');
        
        registerForm.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!name || !phone) {
                e.preventDefault();
                alert('Full Name and Phone Number are required!');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters!');
            }
        });
    </script>
</body>
</html>
