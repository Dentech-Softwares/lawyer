<?php
/**
 * Client Sidebar Component
 * Usage: include 'includes/client_sidebar.php';
 * Dynamically highlights active menu based on current script name
 */

$sidebar_role = 'client';
$current_page = $current_page ?? basename($_SERVER['SCRIPT_NAME']);
$sidebar_badges = $sidebar_badges ?? [];

include __DIR__ . '/sidebar.php';
