<?php
require_once 'db.php';

// Get stats for the hero section
$totalAppointments = getRow("SELECT COUNT(*) as count FROM appointments");
$totalClients = getRow("SELECT COUNT(*) as count FROM users");
$completedCases = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justice Law Firm - Expert Legal Services</title>
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
            background-color: var(--light-gray);
            color: var(--dark);
            line-height: 1.6;
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

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(26, 58, 92, 0.95) 0%, rgba(44, 82, 130, 0.9) 100%),
                        url('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 5% 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }

        .hero h1 {
            font-size: 3.5rem;
            color: var(--white);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            animation: fadeInUp 1s ease;
        }

        .hero h1 span {
            color: var(--accent);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .btn-large {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }

        /* Stats Section */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            padding: 4rem 5%;
            background: var(--white);
            margin-top: -50px;
            position: relative;
            z-index: 10;
            margin-left: 5%;
            margin-right: 5%;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-card i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: var(--gray);
            font-weight: 500;
        }

        /* Services Preview */
        .services-preview {
            padding: 5rem 5%;
            background: var(--light-gray);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .section-title .line {
            width: 80px;
            height: 4px;
            background: var(--accent);
            margin: 1rem auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .service-card:hover::before {
            transform: scaleY(1);
        }

        .service-card i {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .service-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .service-card p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .service-card a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: gap 0.3s ease;
        }

        .service-card a:hover {
            gap: 1rem;
        }

        /* Why Choose Us */
        .why-us {
            padding: 5rem 5%;
            background: var(--white);
        }

        .why-us-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .why-us-card {
            text-align: center;
            padding: 2rem;
        }

        .why-us-card .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s ease;
        }

        .why-us-card:hover .icon {
            transform: scale(1.1);
        }

        .why-us-card .icon i {
            font-size: 2rem;
            color: var(--white);
        }

        .why-us-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .why-us-card p {
            color: var(--gray);
        }

        /* Testimonials */
        .testimonials {
            padding: 5rem 5%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .testimonials .section-title h2,
        .testimonials .section-title p {
            color: var(--white);
        }

        .testimonials .line {
            background: var(--accent);
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
        }

        .testimonial-card .quote {
            font-size: 4rem;
            color: var(--accent);
            line-height: 1;
            opacity: 0.5;
        }

        .testimonial-card p {
            color: rgba(255,255,255,0.9);
            font-style: italic;
            margin: 1rem 0;
        }

        .testimonial-card .client {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .testimonial-card .client img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--accent);
        }

        .testimonial-card .client h4 {
            color: var(--white);
        }

        .testimonial-card .client span {
            color: var(--accent);
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 5%;
            background: var(--light-gray);
            text-align: center;
        }

        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .cta p {
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1.1rem;
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

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .stats {
                grid-template-columns: 1fr;
                margin-left: 1rem;
                margin-right: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
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
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Expert Legal Solutions for <span>Your Peace of Mind</span></h1>
            <p>With over 20 years of experience, we provide top-tier legal representation across all practice areas. Your justice is our priority.</p>
            <div class="hero-buttons">
                <a href="book_appointment.php" class="btn btn-primary btn-large">
                    <i class="fas fa-calendar-check"></i> Book Appointment
                </a>
                <a href="services.php" class="btn btn-outline btn-large">
                    <i class="fas fa-briefcase"></i> Our Services
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stat-card">
            <i class="fas fa-calendar-check"></i>
            <h3><?php echo $totalAppointments['count'] ?? 0; ?></h3>
            <p>Total Appointments</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h3><?php echo $totalClients['count'] ?? 0; ?></h3>
            <p>Happy Clients</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <h3><?php echo $completedCases['count'] ?? 0; ?></h3>
            <p>Cases Won</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-award"></i>
            <h3>20+</h3>
            <p>Years Experience</p>
        </div>
    </section>

    <!-- Services Preview -->
    <section class="services-preview">
        <div class="section-title">
            <h2>Our Practice Areas</h2>
            <div class="line"></div>
            <p>We offer comprehensive legal services tailored to meet your unique needs</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-briefcase"></i>
                <h3>Corporate Law</h3>
                <p>Expert legal counsel for businesses of all sizes, from startups to established corporations.</p>
                <a href="services.php">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="service-card">
                <i class="fas fa-home"></i>
                <h3>Family Law</h3>
                <p>Compassionate representation in divorce, custody, and family matters.</p>
                <a href="services.php">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="service-card">
                <i class="fas fa-landmark"></i>
                <h3>Criminal Defense</h3>
                <p>Protecting your rights and freedom with aggressive criminal defense strategies.</p>
                <a href="services.php">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-us">
        <div class="section-title">
            <h2>Why Choose Us</h2>
            <div class="line"></div>
        </div>
        <div class="why-us-grid">
            <div class="why-us-card">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Round-the-clock legal support whenever you need us</p>
            </div>
            <div class="why-us-card">
                <div class="icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3>Licensed Experts</h3>
                <p>Team of highly qualified and licensed legal professionals</p>
            </div>
            <div class="why-us-card">
                <div class="icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>Flexible Payments</h3>
                <p>Affordable payment plans to suit your budget</p>
            </div>
            <div class="why-us-card">
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Confidentiality</h3>
                <p>Your privacy and information are always protected</p>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="section-title">
            <h2>Client Testimonials</h2>
            <div class="line"></div>
            <p>What our clients say about our services</p>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="quote"><i class="fas fa-quote-left"></i></div>
                <p>"Justice Law Firm provided exceptional representation in my case. Their professionalism and dedication resulted in a favorable outcome."</p>
                <div class="client">
                    <h4>Michael Johnson</h4>
                    <span>Corporate Client</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote"><i class="fas fa-quote-left"></i></div>
                <p>"They handled my family law case with compassion and expertise. I felt supported throughout the entire process."</p>
                <div class="client">
                    <h4>Sarah Williams</h4>
                    <span>Family Law Client</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote"><i class="fas fa-quote-left"></i></div>
                <p>"Outstanding criminal defense attorney. They fought for my rights and achieved the best possible result."</p>
                <div class="client">
                    <h4>David Brown</h4>
                    <span>Criminal Defense Client</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Discuss Your Case?</h2>
            <p>Schedule a consultation with our experienced attorneys today and get the legal representation you deserve.</p>
            <a href="book_appointment.php" class="btn btn-primary btn-large">
                <i class="fas fa-calendar-plus"></i> Book Appointment Now
            </a>
        </div>
    </section>

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

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.service-card, .why-us-card, .testimonial-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
