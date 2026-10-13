<?php
/**
 * Helper Functions
 * Shared utilities for the Justice Law Firm application
 */

if (!function_exists('get_user_initials')) {
    /**
     * Generate avatar initials from a full name
     * @param string $name Full name (e.g., "Dennis Njenga")
     * @param int $maxLength Maximum number of characters (default 2)
     * @return string Uppercase initials (e.g., "DN")
     */
    function get_user_initials(string $name, int $maxLength = 2): string
    {
        $name = trim($name);
        if (empty($name)) {
            return '??';
        }
        
        $parts = preg_split('/\s+/', $name);
        $initials = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                if (mb_strlen($initials) >= $maxLength) {
                    break;
                }
            }
        }
        
        return $initials ?: '??';
    }
}

if (!function_exists('get_current_user_name')) {
    /**
     * Get the current logged-in user's display name
     * @return string
     */
    function get_current_user_name(): string
    {
        if (isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username'])) {
            return $_SESSION['admin_username'];
        }
        if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
            return $_SESSION['user_name'];
        }
        if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
            return $_SESSION['full_name'];
        }
        return 'User';
    }
}

if (!function_exists('get_current_user_initials')) {
    /**
     * Get the current logged-in user's avatar initials
     * @return string
     */
    function get_current_user_initials(): string
    {
        return get_user_initials(get_current_user_name());
    }
}

if (!function_exists('get_current_user_role')) {
    /**
     * Get the current logged-in user's role label
     * @return string
     */
    function get_current_user_role(): string
    {
        if (isset($_SESSION['admin_id'])) {
            return 'Administrator';
        }
        if (isset($_SESSION['user_id'])) {
            return 'Client Account';
        }
        return 'Guest';
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if current user is admin
     * @return bool
     */
    function is_admin(): bool
    {
        return isset($_SESSION['admin_id']);
    }
}

if (!function_exists('is_client')) {
    /**
     * Check if current user is client
     * @return bool
     */
    function is_client(): bool
    {
        return isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
    }
}