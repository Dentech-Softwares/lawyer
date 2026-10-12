<?php
/**
 * Sidebar Component
 * Usage: include 'includes/sidebar.php';
 * Requires: $sidebar_role = 'admin' | 'client' (set before include)
 *           $current_page = basename($_SERVER['SCRIPT_NAME']) (optional, auto-detected)
 */

$sidebar_role = $sidebar_role ?? 'client';
$current_page = $current_page ?? basename($_SERVER['SCRIPT_NAME']);

$admin_nav = [
    ['href' => 'admin_dashboard.php',       'icon' => 'fa-home',         'label' => 'Dashboard'],
    ['href' => 'manage_appointments.php',   'icon' => 'fa-calendar',     'label' => 'Appointments'],
    ['href' => 'admin_schedule.php',        'icon' => 'fa-clock',        'label' => 'Schedule Continuations'],
    ['href' => 'manage_clients.php',        'icon' => 'fa-users',        'label' => 'Clients'],
    ['href' => 'manage_documents.php',      'icon' => 'fa-file-alt',     'label' => 'Documents'],
    ['href' => 'index.php',                 'icon' => 'fa-globe',        'label' => 'View Website'],
    ['href' => 'logout.php',                'icon' => 'fa-sign-out-alt', 'label' => 'Logout'],
];

$client_nav = [
    ['href' => 'client_dashboard.php',      'icon' => 'fa-home',         'label' => 'Dashboard'],
    ['href' => 'book_appointment.php',      'icon' => 'fa-calendar-plus','label' => 'Book Appointment'],
    ['href' => 'client_appointments.php',   'icon' => 'fa-calendar-check','label' => 'My Appointments'],
    ['href' => 'client_documents.php',      'icon' => 'fa-file-alt',     'label' => 'My Documents'],
    ['href' => 'logout.php',                'icon' => 'fa-sign-out-alt', 'label' => 'Logout'],
];

$nav = $sidebar_role === 'admin' ? $admin_nav : $client_nav;
$brand_title = 'Justice Law';
$user_name = $sidebar_role === 'admin' 
    ? ($_SESSION['admin_username'] ?? 'Administrator')
    : ($_SESSION['user_name'] ?? 'Client');
$user_initials = $sidebar_role === 'admin'
    ? (strtoupper(substr(explode(' ', $_SESSION['admin_username'] ?? 'AD')[0], 0, 1) . substr(explode(' ', $_SESSION['admin_username'] ?? 'AD')[1] ?? '', 0, 1)))
    : (strtoupper(substr(explode(' ', $_SESSION['user_name'] ?? 'CL')[0], 0, 1) . substr(explode(' ', $_SESSION['user_name'] ?? 'CL')[1] ?? '', 0, 1)));
$user_role_label = $sidebar_role === 'admin' ? 'Administrator' : 'Client Account';
?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-gavel"></i> <?php echo htmlspecialchars($brand_title); ?></h2>
    </div>

    <div class="sidebar-profile">
        <div class="avatar">
            <?php echo htmlspecialchars($user_initials); ?>
        </div>
        <h3><?php echo htmlspecialchars($user_name); ?></h3>
        <p><?php echo htmlspecialchars($user_role_label); ?></p>
    </div>

    <ul class="sidebar-nav">
        <?php foreach ($nav as $item): ?>
            <?php $is_active = ($item['href'] === $current_page); ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>