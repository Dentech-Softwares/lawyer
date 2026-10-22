<?php
// client_header.php - Reusable client header include
// Requires these variables to be set before including:
// $user_name, $user (with 'phone'), $current_date
// $unreadNotifications (array with 'count'), $notifications (array of rows), $user_id
require_once __DIR__ . '/includes/helpers.php';

// Short name for the compact mobile header bar: first two names only
$nameParts = array_filter(explode(' ', trim($user_name ?? '')));
$displayName = implode(' ', array_slice($nameParts, 0, 2));
?>
<?php include __DIR__ . '/includes/dashboard_header_styles.php'; ?>

<div class="header">
    <div class="mobile-header-left">
        <button class="header-menu-btn" type="button" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
            <i class="fas fa-bars"></i>
        </button>
        <h1>
            <span class="header-greeting">Welcome back, <?php echo htmlspecialchars($user_name ?? ''); ?>!</span>
            <span class="header-greeting-mobile"><?php echo htmlspecialchars($displayName); ?></span>
        </h1>
    </div>
    <div class="header-actions">
        <div class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <?php if(!empty($unreadNotifications) && $unreadNotifications['count'] > 0): ?>
                <span class="notification-badge"><?php echo $unreadNotifications['count']; ?></span>
            <?php endif; ?>
        </div>

        <div class="profile-container">
            <button type="button" class="profile-trigger" onclick="toggleProfileDropdown()">
                <span class="profile-badge"><?php echo htmlspecialchars(get_user_initials($user_name ?? '')); ?></span>
                <div class="profile-name-row">
                    <span class="profile-name"><?php echo htmlspecialchars($user_name ?? ''); ?></span>
                    <span class="profile-role">Client Account</span>
                </div>
                <i class="fas fa-chevron-down profile-chevron"></i>
            </button>
        </div>
    </div>

    <!-- Dropdowns hang below the card, anchored to its right edge -->
    <div class="notification-dropdown" id="notificationDropdown">
        <h3>Notifications</h3>
        <div class="notification-list">
            <?php if(!empty($notifications)): ?>
                <?php foreach($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['status']; ?>">
                        <i class="fas fa-bell"></i>
                        <div>
                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                        </div>
                        <?php if($notif['status'] == 'unread'): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" style="color: var(--accent);">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-item">
                    <p>No notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-dropdown" id="profileDropdownMenu">
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Full Name</span>
            <span class="profile-dropdown-value"><?php echo htmlspecialchars($user_name ?? ''); ?></span>
        </div>
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Phone Number</span>
            <span class="profile-dropdown-value"><?php echo htmlspecialchars(($user['phone'] ?? $_SESSION['user_phone'] ?? 'N/A')); ?></span>
        </div>
        <div class="profile-dropdown-section">
            <span class="profile-dropdown-label">Email Address</span>
            <?php
            $profile_email = is_array($user ?? null) ? ($user['email'] ?? null) : null;
            $profile_email = $profile_email ?: ($_SESSION['user_email'] ?? '');
            ?>
            <span class="profile-dropdown-value <?php echo empty($profile_email) ? 'muted' : ''; ?>">
                <?php echo !empty($profile_email) ? htmlspecialchars($profile_email) : 'No Email provided'; ?>
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
