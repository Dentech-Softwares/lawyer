<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Unified Header Component -->
<header class="site-header">
    <div class="header-container">
        <div class="header-inner" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="logo">
                <i class="fas fa-gavel"></i>
                <span>Justice Law</span>
            </div>
            <nav class="nav">
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="client_dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php elseif(isset($_SESSION['admin_id'])): ?>
                    <a href="admin_dashboard.php" class="btn btn-outline">Admin Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
            <button class="mobile-menu-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-overlay"></div>
    <div class="mobile-drawer-content">
        <div class="mobile-drawer-header">
            <div class="logo">
                <i class="fas fa-gavel"></i>
                <span>Justice Law</span>
            </div>
            <button class="mobile-drawer-close" type="button" aria-label="Close navigation menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="mobile-nav">
            <ul class="mobile-nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="mobile-auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="client_dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php elseif(isset($_SESSION['admin_id'])): ?>
                    <a href="admin_dashboard.php" class="btn btn-outline">Admin Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</div>

<script>
(function() {
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const drawer = document.querySelector('.mobile-drawer');
    const closeBtn = document.querySelector('.mobile-drawer-close');
    const overlay = document.querySelector('.mobile-drawer-overlay');
    
    function openDrawer() {
        if (drawer) {
            drawer.classList.add('active');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeDrawer() {
        if (drawer) {
            drawer.classList.remove('active');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    }
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (drawer && drawer.classList.contains('active')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeDrawer);
    }
    
    // Close drawer when clicking on mobile nav links
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-links a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', closeDrawer);
    });
})();
</script>
