<?php
require_once 'db.php';

$success = '';
$error = '';

// Check for session expiry message
$sessionExpired = checkSessionTimeout();
if ($sessionExpired) {
    $error = $sessionExpired;
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: client_dashboard.php');
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation - accept either email address or phone number
    if (empty($login_input) || empty($password)) {
        $error = 'Email/Phone and password are required';
    } else {
        // First check admin users table (admins always log in with email)
        $admin = null;
        if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
            $admin = getRow("SELECT * FROM admin_users WHERE email = ?", [$login_input]);
        }
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login successful
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_phone'] = $admin['phone'] ?? '';
            $_SESSION['last_activity'] = time();
            
            // Redirect to admin dashboard
            header('Location: admin_dashboard.php');
            exit;
        }
        
        // Then check regular users table - match by email OR phone
        $user = getRow(
            "SELECT * FROM users WHERE email = ? OR phone = ?",
            [$login_input, $login_input]
        );
        
        if ($user) {
            // Check if user is blocked
            if ($user['is_blocked']) {
                $error = 'Your account has been blocked. Please contact support.';
            } elseif (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to dashboard
                header('Location: client_dashboard.php');
                exit;
            } else {
                $error = 'Invalid email/phone or password';
            }
        } else {
            $error = 'Invalid email/phone or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
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

        .login-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
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

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2.5rem;
            text-align: center;
            color: var(--white);
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-header i {
            font-size: 2rem;
            color: var(--accent);
        }

        .login-header h1 {
            font-size: 1.8rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            opacity: 0.9;
        }

        .login-body {
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
        }

        .remember-me label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .forgot-password {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .forgot-password:hover {
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

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
        }

        .register-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
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

        .admin-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--light-gray);
        }

        .admin-link a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .admin-link a:hover {
            color: var(--primary);
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .login-container {
                border-radius: 15px;
            }

            .login-header {
                padding: 2rem;
            }

            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
<div class="login-header">
            <i class="fas fa-gavel"></i>
            <h1>Welcome Back</h1>
            <p>Login with your email address or phone number</p>
        </div>
        
        <div class="login-body">
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="login">
                        <i class="fas fa-user"></i> Email Address or Phone Number
                    </label>
                    <input type="text" id="login" name="login" placeholder="john@example.com or +1 (555) 123-4567" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            

            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>

            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        const loginForm = document.querySelector('form');
        
        loginForm.addEventListener('submit', function(e) {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            
            if (!login || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });

        // Add animation to input fields
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('active');
            });
        });
    </script>
</body>
</html>
