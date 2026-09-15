<?php
// client_header.php - Reusable client header include
// Requires these variables to be set before including:
// $user_name, $user_initials, $user (with 'phone'), $current_date
// $unreadNotifications (array with 'count'), $notifications (array of rows), $user_id
?>
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
        --warning: #d69e2e;
        --info: #3182ce;
    }

    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: var(--white);
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .header h1 {
        color: var(--primary);
        font-size: 1.25rem;
        font-weight: 600;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    /* Notification Bell */
    .notification-bell {
        position: relative;
        width: 45px;
        height: 45px;
        background: var(--light-gray);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .notification-bell:hover {
        background: var(--accent);
        color: var(--white);
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        background: var(--danger);
        color: var(--white);
        border-radius: 50%;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-dropdown {
        position: absolute;
        top: 60px;
        right: 0;
        width: 350px;
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        z-index: 1001;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .notification-bell.active + .notification-dropdown,
    .notification-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-dropdown h3 {
        padding: 1rem;
        border-bottom: 1px solid var(--light-gray);
        color: var(--primary);
    }

    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--light-gray);
        display: flex;
        align-items: flex-start;
        gap: 0.8rem;
    }

    .notification-item:hover {
        background: var(--light-gray);
    }

    .notification-item.unread {
        background: rgba(49, 130, 206, 0.05);
    }

    .notification-item i {
        color: var(--info);
        margin-top: 0.2rem;
    }

    .notification-item p {
        color: var(--dark);
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }

    .notification-item small {
        color: var(--gray);
        font-size: 0.8rem;
    }

    .notification-item a {
        color: var(--accent);
        text-decoration: none;
    }

    /* Profile Dropdown */
    .profile-container {
        position: relative;
        display: inline-block;
    }

    .profile-trigger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: transparent;
        border: none;
    }

    .profile-trigger:hover {
        background: var(--light-gray);
    }

    .profile-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--accent);
        color: var(--primary);
        font-size: 0.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-name-row {
        display: flex;
        flex-direction: column;
    }

    .profile-name {
        font-weight: 700;
        color: var(--dark);
        font-size: 0.95rem;
    }

    .profile-role {
        background: rgba(49, 130, 206, 0.1);
        color: var(--info);
        padding: 0.15rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .profile-chevron {
        color: var(--gray);
        font-size: 0.7rem;
    }

    .profile-container.active .profile-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .profile-dropdown {
        position: absolute;
        top: 60px;
        right: 0;
        width: 240px;
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        z-index: 1001;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .profile-dropdown-section {
        padding: 1rem 1.25rem;
    }

    .profile-dropdown-label {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray);
        margin-bottom: 0.25rem;
    }

    .profile-dropdown-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--dark);
    }

    .profile-divider {
        height: 1px;
        background: var(--light-gray);
        margin: 0 1.25rem;
    }

    .profile-dropdown-logout {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.25rem;
        color: var(--danger);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .profile-dropdown-logout:hover {
        background: rgba(229, 62, 62, 0.1);
    }

    .profile-dropdown-logout i {
        width: 18px;
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<div class="header">
    <h1>Welcome back, <?php echo htmlspecialchars($user_name ?? ''); ?>!</h1>
    <div class="header-actions">
        <div class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <?php if(!empty($unreadNotifications) && $unreadNotifications['count'] > 0): ?>
                <span class="notification-badge"><?php echo $unreadNotifications['count']; ?></span>
            <?php endif; ?>

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
        </div>

        <div class="profile-container" id="profileDropdown">
            <button type="button" class="profile-trigger" onclick="toggleProfileDropdown()">
                <span class="profile-badge"><?php echo htmlspecialchars($user_initials ?? 'US'); ?></span>
                <div class="profile-name-row">
                    <span class="profile-name"><?php echo htmlspecialchars($user_name ?? ''); ?></span>
                    <span class="profile-role">Client Account</span>
                </div>
                <i class="fas fa-chevron-down profile-chevron"></i>
            </button>
            <div class="profile-dropdown">
                <div class="profile-dropdown-section">
                    <span class="profile-dropdown-label">Full Name</span>
                    <span class="profile-dropdown-value"><?php echo htmlspecialchars($user_name ?? ''); ?></span>
                </div>
                <div class="profile-dropdown-section">
                    <span class="profile-dropdown-label">Phone Number</span>
                    <span class="profile-dropdown-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="profile-dropdown-section">
                    <span class="profile-dropdown-label">Current Date</span>
                    <span class="profile-dropdown-value"><?php echo $current_date ?? ''; ?></span>
                </div>
                <div class="profile-divider"></div>
                <a href="logout.php" class="profile-dropdown-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleNotifications() {
        document.getElementById('notificationDropdown').classList.toggle('active');
    }

    function toggleProfileDropdown() {
        document.getElementById('profileDropdown').classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-bell')) {
            var nd = document.getElementById('notificationDropdown');
            if (nd) nd.classList.remove('active');
        }
        if (!e.target.closest('.profile-container')) {
            var pd = document.getElementById('profileDropdown');
            if (pd) pd.classList.remove('active');
        }
    });
</script>
