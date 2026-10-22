<?php
/**
 * Client Sidebar Component
 * Usage: include 'includes/client_sidebar.php';
 *
 * Single entry point for the client portal sidebar. Every client page
 * (client_dashboard.php, client_appointments.php, client_documents.php,
 * book_appointment.php) includes this file, so the markup, the active-item
 * highlight and the typography come from one place only.
 *
 * Optional variables set before the include:
 *   $sidebar_badges = ['appointments' => 3, ...]  notification counters
 *   $current_page   = 'client_documents.php'       override the auto-detected page
 *
 * The page must also load 'assets/css/sidebar.css'.
 */

$sidebar_role = 'client';
$sidebar_badges = $sidebar_badges ?? [];

// require_once keeps the sidebar to a single instance per request even if a
// page also includes includes/sidebar.php directly.
require_once __DIR__ . '/sidebar.php';
