<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Justice Law Firm</title>
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

        /* Services Section */
        .services-section {
            padding: 5rem 5%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .section-title .line {
            width: 80px;
            height: 4px;
            background: var(--accent);
            margin: 1rem auto;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .service-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            position: relative;
        }

        .service-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .service-icon {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .service-icon::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.3) 0%, transparent 50%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }

        .service-icon i {
            font-size: 4rem;
            color: var(--white);
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .service-card:hover .service-icon i {
            transform: scale(1.2);
        }

        .service-content {
            padding: 2rem;
        }

        .service-content h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .service-content p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .service-features {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .service-features li {
            padding: 0.5rem 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .service-features li i {
            color: var(--accent);
        }

        .service-price {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .service-price span {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 400;
        }

        .service-btn {
            display: inline-block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: var(--white);
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .service-btn:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* Process Section */
        .process-section {
            padding: 5rem 5%;
            background: var(--white);
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .process-card {
            text-align: center;
            padding: 2rem;
            position: relative;
        }

        .process-number {
            width: 60px;
            height: 60px;
            background: var(--accent);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }

        .process-card:hover .process-number {
            transform: scale(1.1);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.4);
        }

        .process-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .process-card p {
            color: var(--gray);
        }

        /* FAQ Section */
        .faq-section {
            padding: 5rem 5%;
            background: var(--light-gray);
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .faq-item {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .faq-item h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .faq-item h3 i {
            color: var(--accent);
        }

        .faq-item p {
            color: var(--gray);
            line-height: 1.7;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 5%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            text-align: center;
            color: var(--white);
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta p {
            margin-bottom: 2rem;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .btn-large {
            padding: 1rem 2.5rem;
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

            .page-title h1 {
                font-size: 2rem;
            }

            .services-grid,
            .faq-grid {
                grid-template-columns: 1fr;
            }

            .cta h2 {
                font-size: 2rem;
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

    <!-- Page Title -->
    <section class="page-title">
        <h1>Our Legal Services</h1>
        <p>Comprehensive legal solutions tailored to your needs</p>
    </section>

    <!-- Services Grid -->
    <section class="services-section">
        <div class="section-title">
            <h2>What We Offer</h2>
            <div class="line"></div>
            <p>Expert legal representation across a wide range of practice areas</p>
        </div>
        
        <div class="services-grid">
            <!-- Corporate Law -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="service-content">
                    <h3>Corporate Law</h3>
                    <p>Comprehensive legal solutions for businesses of all sizes, from startups to established corporations.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Business Formation</li>
                        <li><i class="fas fa-check"></i> Contract Negotiation</li>
                        <li><i class="fas fa-check"></i> Mergers & Acquisitions</li>
                        <li><i class="fas fa-check"></i> Corporate Governance</li>
                    </ul>
                    <div class="service-price">Custom Pricing <span>/ consultation</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>

            <!-- Family Law -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="service-content">
                    <h3>Family Law</h3>
                    <p>Compassionate legal support for families navigating difficult transitions and legal matters.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Divorce & Separation</li>
                        <li><i class="fas fa-check"></i> Child Custody</li>
                        <li><i class="fas fa-check"></i> Adoption</li>
                        <li><i class="fas fa-check"></i> Prenuptial Agreements</li>
                    </ul>
                    <div class="service-price">Custom Pricing <span>/ consultation</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>

            <!-- Criminal Defense -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="service-content">
                    <h3>Criminal Defense</h3>
                    <p>Aggressive defense strategies to protect your rights and freedom when facing criminal charges.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> DUI Defense</li>
                        <li><i class="fas fa-check"></i> Drug Crimes</li>
                        <li><i class="fas fa-check"></i> White Collar Crime</li>
                        <li><i class="fas fa-check"></i> Juvenile Defense</li>
                    </ul>
                    <div class="service-price">Custom Pricing <span>/ consultation</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>

            <!-- Real Estate -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-land-marks"></i>
                </div>
                <div class="service-content">
                    <h3>Real Estate Law</h3>
                    <p>Expert guidance through all aspects of real estate transactions and disputes.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Property Transactions</li>
                        <li><i class="fas fa-check"></i> Landlord/Tenant</li>
                        <li><i class="fas fa-check"></i> Zoning & Land Use</li>
                        <li><i class="fas fa-check"></i> Title Issues</li>
                    </ul>
                    <div class="service-price">Custom Pricing <span>/ consultation</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>

            <!-- Personal Injury -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="service-content">
                    <h3>Personal Injury</h3>
                    <p>Compassionate representation to help you recover fair compensation for your injuries.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Car Accidents</li>
                        <li><i class="fas fa-check"></i> Medical Malpractice</li>
                        <li><i class="fas fa-check"></i> Slip & Fall</li>
                        <li><i class="fas fa-check"></i> Wrongful Death</li>
                    </ul>
                    <div class="service-price">Contingency Fee <span>/ no win no fee</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>

            <!-- Immigration -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-passport"></i>
                </div>
                <div class="service-content">
                    <h3>Immigration Law</h3>
                    <p>Guiding individuals and families through complex immigration processes and visa applications.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Visa Applications</li>
                        <li><i class="fas fa-check"></i> Green Cards</li>
                        <li><i class="fas fa-check"></i> Citizenship</li>
                        <li><i class="fas fa-check"></i> Deportation Defense</li>
                    </ul>
                    <div class="service-price">Custom Pricing <span>/ consultation</span></div>
                    <a href="book_appointment.php" class="service-btn">Book Consultation</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="section-title">
            <h2>How It Works</h2>
            <div class="line"></div>
            <p>Simple steps to get started with your legal case</p>
        </div>
        
        <div class="process-grid">
            <div class="process-card">
                <div class="process-number">1</div>
                <h3>Schedule Consultation</h3>
                <p>Book an appointment to discuss your legal matter with one of our experienced attorneys.</p>
            </div>
            <div class="process-card">
                <div class="process-number">2</div>
                <h3>Case Evaluation</h3>
                <p>We thoroughly evaluate your case and provide honest assessment of your legal options.</p>
            </div>
            <div class="process-card">
                <div class="process-number">3</div>
                <h3>Legal Strategy</h3>
                <p>We develop a personalized legal strategy tailored to achieve the best possible outcome.</p>
            </div>
            <div class="process-card">
                <div class="process-number">4</div>
                <h3>Resolution</h3>
                <p>We work diligently to resolve your case efficiently and effectively.</p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="section-title">
            <h2>Frequently Asked Questions</h2>
            <div class="line"></div>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h3><i class="fas fa-question-circle"></i> How much does a consultation cost?</h3>
                <p>Initial consultations are complimentary for most cases. We offer flexible payment plans and work on contingency for personal injury cases.</p>
            </div>
            <div class="faq-item">
                <h3><i class="fas fa-question-circle"></i> How long will my case take?</h3>
                <p>Case duration varies depending on complexity. Simple cases may resolve in weeks, while complex litigation can take months or years.</p>
            </div>
            <div class="faq-item">
                <h3><i class="fas fa-question-circle"></i> Do you offer payment plans?</h3>
                <p>Yes, we offer flexible payment plans tailored to your financial situation. We believe quality legal representation should be accessible to everyone.</p>
            </div>
            <div class="faq-item">
                <h3><i class="fas fa-question-circle"></i> What should I bring to my first meeting?</h3>
                <p>Bring any relevant documents such as contracts, police reports, medical records, and correspondence related to your case. The more information, the better we can assist you.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Need Legal Assistance?</h2>
        <p>Schedule a consultation with our expert attorneys today.</p>
        <a href="book_appointment.php" class="btn btn-primary btn-large">
            <i class="fas fa-calendar-plus"></i> Book Appointment Now
        </a>
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

        document.querySelectorAll('.service-card, .process-card, .faq-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
