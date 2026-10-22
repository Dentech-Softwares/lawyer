<?php
// admin_header.php - Reusable admin header include
// Requires these variables to be set before including:
// $admin_username, $admin_email, $current_date
// $unreadAdminNotifications (array with 'count'), $adminNotifications (array of rows)
require_once __DIR__ . '/includes/helpers.php';

// Short name for the compact mobile header bar: first two names only
$nameParts = array_filter(explode(' ', trim($admin_username ?? '')));
$displayName = implode(' ', array_slice($nameParts, 0, 2));
?>
<?php include __DIR__ . '/includes/dashboard_header_styles.php'; ?>

<div class="header">
    <div class="mobile-header-left">
        <button class="header-menu-btn" type="button" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
            <i class="fas fa-bars"></i>
        </button>
        <h1>
            <span class="header-greeting">Welcome back, <?php echo htmlspecialchars($admin_username ?? ''); ?>!</span>
            <span class="header-greeting-mobile"><?php echo htmlspecialchars($displayName); ?></span>
        </h1>
    </div>
    <div class="header-actions">
        <div class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <?php if(!empty($unreadAdminNotifications) && $unreadAdminNotifications['count'] > 0): ?>
                <span class="notification-badge"><?php echo $unreadAdminNotifications['count']; ?></span>
            <?php endif; ?>
        </div>

        <div class="profile-container">
            <button type="button" class="profile-trigger" onclick="toggleProfileDropdown()">
                <span class="profile-badge"><?php echo htmlspecialchars(get_user_initials($admin_username ?? '')); ?></span>
                <div class="profile-name-row">
                    <span class="profile-name"><?php echo htmlspecialchars($admin_username ?? ''); ?></span>
                    <span class="profile-role role-admin">Administrator</span>
                </div>
                <i class="fas fa-chevron-down profile-chevron"></i>
            </button>
        </div>
    </div>

    <!-- Dropdowns hang below the card, anchored to its right edge -->
    <div class="notification-dropdown" id="notificationDropdown">
        <h3>Notifications</h3>
        <div class="notification-list">
            <?php if(!empty($adminNotifications)): ?>
                <?php foreach($adminNotifications as $notif): ?>
                    <a href="admin_schedule.php" style="text-decoration: none; color: inherit; display: block; padding: 0.8rem 1rem; border-bottom: 1px solid var(--light-gray); cursor: pointer;">
                        <div class="notification-item unread" style="padding: 0; border: none;">
                            <i class="fas fa-bell"></i>
                            <div>
                                <p>New appointment request from <?php echo htmlspecialchars($notif['client_name']); ?> - <?php echo htmlspecialchars($notif['case_type']); ?></p>
                                <small><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-item">
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-dropdown" id="profileDropdownMenu">
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Full Name</span>
            <span class="profile-dropdown-value"><?php echo htmlspecialchars($admin_username ?? ''); ?></span>
        </div>
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Phone Number</span>
            <span class="profile-dropdown-value"><?php echo htmlspecialchars($admin_phone ?? 'N/A'); ?></span>
        </div>
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Email Address</span>
            <span class="profile-dropdown-value <?php echo empty($admin_email) ? 'muted' : ''; ?>">
            <?php echo !empty($admin_email) ? htmlspecialchars($admin_email) : 'No Email provided'; ?>
            </span>
        </div>
        <div class="profile-divider"></div>
        <a href="logout.php" class="profile-dropdown-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
    function toggleNotifications() {
        var nd = document.getElementById('notificationDropdown');
        if (!nd) return;
        var opening = !nd.classList.contains('active');
        nd.classList.toggle('active', opening);
        var pd = document.getElementById('profileDropdownMenu');
        if (pd) pd.classList.remove('active');
        var bell = document.getElementById('notificationBell');
        if (bell) bell.classList.toggle('active', opening);
    }

    function toggleProfileDropdown() {
        var pd = document.getElementById('profileDropdownMenu');
        if (!pd) return;
        var opening = !pd.classList.contains('active');
        pd.classList.toggle('active', opening);
        var nd = document.getElementById('notificationDropdown');
        if (nd) nd.classList.remove('active');
        var bell = document.getElementById('notificationBell');
        if (bell) bell.classList.remove('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-bell') && !e.target.closest('.notification-dropdown')) {
            var nd = document.getElementById('notificationDropdown');
            if (nd) nd.classList.remove('active');
            var bell = document.getElementById('notificationBell');
            if (bell) bell.classList.remove('active');
        }
        if (!e.target.closest('.profile-container') && !e.target.closest('.profile-dropdown')) {
            var pd = document.getElementById('profileDropdownMenu');
            if (pd) pd.classList.remove('active');
        }
    });
</script>
