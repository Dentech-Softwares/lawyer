<?php
/**
 * Sidebar Component
 * Usage: include 'includes/sidebar.php';
 * Requires: $sidebar_role = 'admin' | 'client' (set before include)
 *           $current_page = basename($_SERVER['SCRIPT_NAME']) (optional, auto-detected)
 *           $sidebar_badges = ['page_name' => count, ...] (optional, notification counts)
 *
 * This file is the single source of truth for the portal sidebar markup.
 * Client pages must include 'includes/client_sidebar.php', which delegates
 * here with the client role already applied.
 */

require_once __DIR__ . '/helpers.php';

$sidebar_role = $sidebar_role ?? 'client';
$sidebar_badges = $sidebar_badges ?? [];

// Resolve the active page from the script name (query string stripped) so the
// highlight is identical no matter which page includes the component.
if (!isset($current_page) || $current_page === '') {
    $script_path = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH);
    $current_page = basename($script_path ?: '');
}

$admin_nav = [
    ['key' => 'dashboard',       'href' => 'admin_dashboard.php',       'icon' => 'fa-home',         'label' => 'Dashboard'],
    ['key' => 'appointments',    'href' => 'manage_appointments.php',   'icon' => 'fa-calendar',     'label' => 'Appointments'],
    ['key' => 'schedule',        'href' => 'admin_schedule.php',        'icon' => 'fa-clock',        'label' => 'Continuations'],
    ['key' => 'clients',         'href' => 'manage_clients.php',        'icon' => 'fa-users',        'label' => 'Clients'],
    ['key' => 'documents',       'href' => 'manage_documents.php',      'icon' => 'fa-file-alt',     'label' => 'Documents'],
    ['key' => 'website',         'href' => 'index.php',                 'icon' => 'fa-globe',        'label' => 'View Website'],
    ['key' => 'logout',          'href' => 'logout.php',                'icon' => 'fa-sign-out-alt', 'label' => 'Logout'],
];

$client_nav = [
    ['key' => 'dashboard',       'href' => 'client_dashboard.php',      'icon' => 'fa-home',         'label' => 'Dashboard'],
    ['key' => 'book',            'href' => 'book_appointment.php',      'icon' => 'fa-calendar-plus','label' => 'Book Appointment'],
    ['key' => 'appointments',    'href' => 'client_appointments.php',   'icon' => 'fa-calendar-check','label' => 'My Appointments'],
    ['key' => 'documents',       'href' => 'client_documents.php',      'icon' => 'fa-file-alt',     'label' => 'My Documents'],
    ['key' => 'logout',          'href' => 'logout.php',                'icon' => 'fa-sign-out-alt', 'label' => 'Logout'],
];

$nav = $sidebar_role === 'admin' ? $admin_nav : $client_nav;
$brand_title = 'Justice Law';
$user_name = get_current_user_name();
$user_initials = get_current_user_initials();
$user_role_label = get_current_user_role();
?>

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
            <?php 
            $is_active = ($item['href'] === $current_page);
            $badge_count = $sidebar_badges[$item['key']] ?? 0;
            ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-link<?php echo $is_active ? ' active' : ''; ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <?php echo htmlspecialchars($item['label']); ?>
                    <?php if ($badge_count > 0): ?>
                        <span class="badge"><?php echo (int)$badge_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>

<script>
    /*
     * Shared sidebar behaviour. Guarded so a page that already defines
     * toggleSidebar() keeps its own implementation without a redeclaration
     * clash, and so the sidebar behaves identically on every page.
     */
    if (typeof window.toggleSidebar !== 'function') {
        window.toggleSidebar = function () {
            var sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        };
    }
</script>
