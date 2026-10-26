<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In a real application, you would send an email or store in database
        $success = 'Thank you for contacting us! We will get back to you within 24 hours.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Justice Law Firm</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="assets/css/page-transition.css">
    <link rel="stylesheet" href="assets/css/unified-header.css">
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
            background-color: var(--light-gray);
            color: var(--dark);
            line-height: 1.6;
                    padding-top: 70px;
        }

        /* Header & Navigation */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
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
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }

        /* Page Title */
        .page-title {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 150px 5% 80px;
            text-align: center;
            color: var(--white);
        }

        .page-title h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .page-title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Contact Section */
        .contact-section {
            padding: 5rem 5%;
            background: var(--white);
        }

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 4rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-info {
            padding: 2rem;
        }

        .contact-info h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .contact-info > p {
            color: var(--gray);
            margin-bottom: 2rem;
        }

        .contact-details {
            list-style: none;
        }

        .contact-details li {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .contact-details li:last-child {
            border-bottom: none;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-icon i {
            font-size: 1.2rem;
            color: var(--accent);
        }

        .contact-text h3 {
            color: var(--primary);
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .contact-text p {
            color: var(--gray);
        }

        .social-connect {
            margin-top: 2rem;
        }

        .social-connect h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
        }

        .social-icons a {
            width: 45px;
            height: 45px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            background: var(--accent);
            color: var(--white);
            transform: translateY(-5px);
        }

        /* Contact Form */
        .contact-form-container {
            background: var(--light-gray);
            padding: 3rem;
            border-radius: 15px;
        }

        .contact-form-container h2 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
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

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid transparent;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
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

        /* Map Section */
        .map-section {
            height: 400px;
            background: var(--light-gray);
        }

        .map-section iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Live Chat */
        .chat-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .chat-btn {
            width: 60px;
            height: 60px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .chat-btn:hover {
            transform: scale(1.1);
        }

        .chat-btn i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .chat-popup {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: none;
            overflow: hidden;
        }

        .chat-popup.active {
            display: block;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-header {
            background: var(--primary);
            color: var(--white);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--accent);
        }

        .chat-header h4 {
            flex: 1;
        }

        .chat-header .close-chat {
            cursor: pointer;
            font-size: 1.2rem;
        }

        .chat-body {
            padding: 1rem;
            height: 250px;
            overflow-y: auto;
                    padding-top: 70px;
        }

        .chat-message {
            margin-bottom: 1rem;
            max-width: 80%;
        }

        .chat-message.received {
            margin-right: auto;
        }

        .chat-message.sent {
            margin-left: auto;
            text-align: right;
        }

        .chat-bubble {
            padding: 0.8rem 1rem;
            border-radius: 15px;
            display: inline-block;
        }

        .chat-message.received .chat-bubble {
            background: var(--light-gray);
            color: var(--dark);
            border-bottom-left-radius: 2px;
        }

        .chat-message.sent .chat-bubble {
            background: var(--primary);
            color: var(--white);
            border-bottom-right-radius: 2px;
        }

        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            gap: 0.5rem;
        }

        .chat-input input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: 20px;
            outline: none;
        }

        .chat-input button {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border: none;
            border-radius: 50%;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chat-input button:hover {
            background: var(--accent-hover);
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: var(--white);
            padding: 4rem 5% 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-about .logo {
            margin-bottom: 1rem;
        }

        .footer-about p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-5px);
        }

        .footer-links h3,
        .footer-contact h3 {
            color: var(--accent);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links ul li {
            margin-bottom: 0.8rem;
        }

        .footer-links ul li a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links ul li a:hover {
            color: var(--accent);
        }

        .footer-contact p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .footer-contact p i {
            color: var(--accent);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: rgba(255,255,255,0.5);
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .contact-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: var(--primary);
                flex-direction: column;
                padding: 2rem;
                gap: 1rem;
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .chat-popup {
                width: 300px;
                right: -10px;
            }
        }
    </style>
</head>
<body>
    <!-- Unified Header -->
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
    <!-- Page Title -->
    <section class="page-title">
        <h1>Contact Us</h1>
        <p>We're here to help with your legal needs</p>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Have a legal question or need representation? Contact us today for a free consultation.</p>
                
                <ul class="contact-details">
                    <li>
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Our Office</h3>
                            <p>123 Legal Street, Suite 500<br>New York, NY 10001</p>
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Phone</h3>
                            <p>+1 (555) 123-4567<br>+1 (555) 987-6543</p>
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Email</h3>
                            <p>info@justicelawfirm.com<br>support@justicelawfirm.com</p>
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Office Hours</h3>
                            <p>Monday - Friday: 9AM - 6PM<br>Saturday: 10AM - 2PM</p>
                        </div>
                    </li>
                </ul>

                <div class="social-connect">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>

            <div class="contact-form-container">
                <h2>Send Us a Message</h2>
                
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
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="john@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a topic</option>
                            <option value="consultation">Request Consultation</option>
                            <option value="general">General Inquiry</option>
                            <option value="appointment">Book Appointment</option>
                            <option value="feedback">Feedback</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" placeholder="Describe your legal matter..." required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.1422937950147!2d-73.98513018459391!3d40.74881737932847!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b3117469%3A0xd134e199a405a163!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1234567890!5m2!1sen!2sus" 
        allowfullscreen="" loading="lazy"></iframe>
    </section>
    </div>

    <!-- Live Chat Widget -->
    <div class="chat-widget">
        <div class="chat-popup" id="chatPopup">
            <div class="chat-header">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="Attorney">
                <div>
                    <h4>Robert Anderson</h4>
                    <small>Online now</small>
                </div>
                <span class="close-chat" onclick="toggleChat()">&times;</span>
            </div>
            <div class="chat-body">
                <div class="chat-message received">
                    <div class="chat-bubble">
                        Hello! How can I help you today?
                    </div>
                </div>
            </div>
            <div class="chat-input">
                <input type="text" placeholder="Type your message...">
                <button><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <div class="chat-btn" onclick="toggleChat()">
            <i class="fas fa-comments"></i>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-about">
                <div class="logo">
                    <i class="fas fa-gavel"></i>
                    <span>Justice Law</span>
                </div>
                <p>Providing expert legal solutions with integrity and dedication for over 20 years.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="book_appointment.php">Book Appointment</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Legal Street, New York, NY 10001</p>
                <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                <p><i class="fas fa-envelope"></i> info@justicelawfirm.com</p>
                <p><i class="fas fa-clock"></i> Mon-Fri: 9AM - 6PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Justice Law Firm. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Live Chat Toggle
        function toggleChat() {
            const chatPopup = document.getElementById('chatPopup');
            chatPopup.classList.toggle('active');
        }

        // Chat Input Handler
        const chatInput = document.querySelector('.chat-input input');
        const chatSendBtn = document.querySelector('.chat-input button');
        const chatBody = document.querySelector('.chat-body');

        function sendMessage() {
            const message = chatInput.value.trim();
            if (message) {
                // Add user message
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message sent';
                userMessage.innerHTML = `<div class="chat-bubble">${message}</div>`;
                chatBody.appendChild(userMessage);

                // Clear input
                chatInput.value = '';

                // Scroll to bottom
                chatBody.scrollTop = chatBody.scrollHeight;

                // Simulate response after 1 second
                setTimeout(() => {
                    const response = document.createElement('div');
                    response.className = 'chat-message received';
                    response.innerHTML = `<div class="chat-bubble">Thank you for your message. An attorney will contact you shortly.</div>`;
                    chatBody.appendChild(response);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);
            }
        }

        chatSendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
