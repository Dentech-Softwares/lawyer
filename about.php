<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Justice Law Firm</title>
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

        /* Lawyer Profile Section */
        .profile-section {
            padding: 5rem 5%;
            background: var(--white);
        }

        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 4rem;
            max-width: 1200px;
            margin: 0 auto;
            align-items: start;
        }

        .profile-image {
            position: relative;
        }

        .profile-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .profile-image::before {
            content: '';
            position: absolute;
            top: -15px;
            left: -15px;
            right: 15px;
            bottom: 15px;
            border: 3px solid var(--accent);
            border-radius: 10px;
            z-index: -1;
        }

        .profile-info h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .profile-info .title {
            font-size: 1.2rem;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .profile-info .bio {
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .credentials {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .credential-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .credential-item i {
            width: 50px;
            height: 50px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .credential-item h4 {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .credential-item p {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .skills {
            margin-bottom: 2rem;
        }

        .skills h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .skill-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .skill-tag {
            padding: 0.5rem 1rem;
            background: var(--light-gray);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .skill-tag:hover {
            background: var(--accent);
            color: var(--white);
        }

        .achievements {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .achievements h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .achievement-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .achievement-item i {
            color: var(--accent);
            font-size: 1.5rem;
        }

        /* Team Section */
        .team-section {
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

        .section-title .line {
            width: 80px;
            height: 4px;
            background: var(--accent);
            margin: 1rem auto;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .team-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .team-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .team-card-content {
            padding: 1.5rem;
            text-align: center;
        }

        .team-card-content h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .team-card-content p {
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-card-content .specialization {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Timeline */
        .timeline-section {
            padding: 5rem 5%;
            background: var(--white);
        }

        .timeline {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 3px;
            height: 100%;
            background: var(--accent);
        }

        .timeline-item {
            display: flex;
            justify-content: flex-end;
            padding-right: 50%;
            position: relative;
            margin-bottom: 3rem;
        }

        .timeline-item:nth-child(even) {
            justify-content: flex-start;
            padding-right: 0;
            padding-left: 50%;
        }

        .timeline-content {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            width: 90%;
            position: relative;
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            right: -10px;
            top: 20px;
            width: 20px;
            height: 20px;
            background: var(--accent);
            border-radius: 50%;
        }

        .timeline-item:nth-child(even) .timeline-content::before {
            right: auto;
            left: -10px;
        }

        .timeline-content h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .timeline-content .year {
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .timeline-content p {
            color: var(--gray);
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
            .profile-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .profile-image {
                max-width: 350px;
                margin: 0 auto;
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

            .credentials {
                grid-template-columns: 1fr;
            }

            .achievement-grid {
                grid-template-columns: 1fr;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item,
            .timeline-item:nth-child(even) {
                padding-left: 50px;
                padding-right: 0;
                justify-content: flex-start;
            }

            .timeline-content::before,
            .timeline-item:nth-child(even) .timeline-content::before {
                left: -10px;
                right: auto;
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
        <h1>About Us</h1>
        <p>Meet our team of dedicated legal professionals</p>
    </section>

    <!-- Lawyer Profile -->
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-image">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Lead Attorney">
            </div>
            <div class="profile-info">
                <h2>Robert Anderson</h2>
                <p class="title">Managing Partner & Lead Attorney</p>
                <p class="bio">
                    With over 25 years of experience in legal practice, Robert Anderson has established himself as one of the most trusted and accomplished attorneys in the region. He founded Justice Law Firm with a vision to provide accessible, high-quality legal representation to individuals and businesses alike.
                    <br><br>
                    His expertise spans across corporate law, criminal defense, family law, and civil litigation. Robert has successfully handled over 1,000 cases and has been recognized by numerous legal associations for his outstanding contributions to the field.
                </p>
                
                <div class="credentials">
                    <div class="credential-item">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <h4>Education</h4>
                            <p>Harvard Law School, J.D.</p>
                        </div>
                    </div>
                    <div class="credential-item">
                        <i class="fas fa-certificate"></i>
                        <div>
                            <h4>License</h4>
                            <p>State Bar Association</p>
                        </div>
                    </div>
                    <div class="credential-item">
                        <i class="fas fa-briefcase"></i>
                        <div>
                            <h4>Experience</h4>
                            <p>25+ Years</p>
                        </div>
                    </div>
                    <div class="credential-item">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <h4>Cases Won</h4>
                            <p>1000+</p>
                        </div>
                    </div>
                </div>

                <div class="skills">
                    <h3>Areas of Expertise</h3>
                    <div class="skill-tags">
                        <span class="skill-tag">Corporate Law</span>
                        <span class="skill-tag">Criminal Defense</span>
                        <span class="skill-tag">Family Law</span>
                        <span class="skill-tag">Civil Litigation</span>
                        <span class="skill-tag">Contract Law</span>
                        <span class="skill-tag">Real Estate</span>
                    </div>
                </div>

                <div class="achievements">
                    <h3>Achievements & Recognition</h3>
                    <div class="achievement-grid">
                        <div class="achievement-item">
                            <i class="fas fa-award"></i>
                            <p>Best Law Firm 2023</p>
                        </div>
                        <div class="achievement-item">
                            <i class="fas fa-star"></i>
                            <p>Top 100 Lawyers</p>
                        </div>
                        <div class="achievement-item">
                            <i class="fas fa-medal"></i>
                            <p>Client Choice Award</p>
                        </div>
                        <div class="achievement-item">
                            <i class="fas fa-certificate"></i>
                            <p>ISO Certified</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="section-title">
            <h2>Our Legal Team</h2>
            <div class="line"></div>
            <p>Meet the dedicated professionals behind our success</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Attorney">
                <div class="team-card-content">
                    <h3>Sarah Martinez</h3>
                    <p>Senior Associate</p>
                    <span class="specialization">Family Law & Divorce</span>
                </div>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Attorney">
                <div class="team-card-content">
                    <h3>James Wilson</h3>
                    <p>Corporate Counsel</p>
                    <span class="specialization">Business & Corporate Law</span>
                </div>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Attorney">
                <div class="team-card-content">
                    <h3>Emily Chen</h3>
                    <p>Criminal Defense</p>
                    <span class="specialization">Criminal Law & Defense</span>
                </div>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Attorney">
                <div class="team-card-content">
                    <h3>Michael Lee</h3>
                    <p>Civil Litigation</p>
                    <span class="specialization">Personal Injury & Civil</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="timeline-section">
        <div class="section-title">
            <h2>Our Journey</h2>
            <div class="line"></div>
        </div>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Firm Founded</h3>
                    <p class="year">1999</p>
                    <p>Justice Law Firm was established with a vision to provide exceptional legal services.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Expansion</h3>
                    <p class="year">2005</p>
                    <p>Opened additional offices and expanded our team to 15 attorneys.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Recognition</h3>
                    <p class="year">2010</p>
                    <p>Named "Best Law Firm" by State Bar Association.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Growth</h3>
                    <p class="year">2020</p>
                    <p>Celebrated 1000+ successful cases and 20+ years of excellence.</p>
                </div>
            </div>
        </div>
    </section>
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

        document.querySelectorAll('.team-card, .timeline-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
