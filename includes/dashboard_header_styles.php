<?php
/**
 * Dashboard Header Styles (Admin & Client portals)
 *
 * Single source of truth for the top header card used by both portals.
 * Included by admin_header.php, client_header.php and admin_schedule.php so
 * the two portals always share the exact same look and behaviour.
 */
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
        --header-border: #eef2f5;
    }

    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        background: var(--white);
        padding: 1.25rem 1.5rem;
        border: 1px solid var(--header-border);
        border-bottom: 1px solid var(--header-border);
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        /* anchors the notification / profile dropdowns to the card */
        position: relative;
    }

    .header h1 {
        color: var(--primary);
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        line-height: 1.3;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    /* Sidebar toggle - lives in the header bar, far left on mobile */
    .header-menu-btn {
        display: none;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        background: var(--light-gray);
        border: none;
        border-radius: 10px;
        color: var(--primary);
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .header-menu-btn:hover {
        background: var(--accent);
        color: var(--white);
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

    .notification-bell.active {
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
        top: 100%;
        right: 0;
        margin-top: 0.5rem;
        width: 350px;
        max-width: calc(100vw - 2rem);
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        z-index: 1001;
        overflow: hidden;
        transition: all 0.3s ease;
    }

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
        flex-shrink: 0;
    }

    .profile-name-row {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .profile-name {
        font-weight: 700;
        color: var(--dark);
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
    }

    .profile-role {
        background: rgba(49, 130, 206, 0.1);
        color: var(--info);
        padding: 0.15rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        align-self: flex-start;
    }

    /* Administrator flavour of the role chip */
    .profile-role.role-admin {
        background: rgba(56, 161, 105, 0.1);
        color: var(--success);
    }

    .profile-chevron {
        color: var(--gray);
        font-size: 0.7rem;
    }

    .profile-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .profile-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.5rem;
        width: 240px;
        max-width: calc(100vw - 2rem);
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    .profile-dropdown-value.muted {
        font-weight: 400;
        color: var(--gray);
        font-style: italic;
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

    /* ==========================================================
       Mobile: a single-line sticky top bar
       ========================================================== */
    @media (max-width: 768px) {
        .header {
            /* Hamburger far left, notification + profile far right */
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .header-menu-btn {
            display: flex;
        }

        .header-actions {
            gap: 0.5rem;
            margin-left: auto;
        }

        /* The welcome line moves under the bar so it never crowds it */
        .header h1,
        .header h1.title-large {
            order: 3;
            flex-basis: 100%;
            font-size: 1.1rem;
            line-height: 1.35;
            margin: 0.5rem 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Keep an inline title icon in step with the compact title */
        .header h1 i {
            font-size: 1rem !important;
        }

        .notification-bell {
            width: 40px;
            height: 40px;
        }

        /* Dropdowns align with the card edges so they never leave the screen */
        .notification-dropdown {
            left: -1px;
            right: -1px;
            width: auto;
            max-width: none;
        }

        .profile-dropdown {
            left: auto;
            right: -1px;
            width: 240px;
            max-width: calc(100vw - 2rem);
        }

        .profile-trigger {
            padding: 0.25rem;
            gap: 0.5rem;
        }

        .profile-badge {
            width: 34px;
            height: 34px;
            font-size: 0.75rem;
        }
    }

    /* Very small screens: initials badge + bell only */
    @media (max-width: 576px) {
        .profile-name-row,
        .profile-chevron {
            display: none;
        }

        .profile-trigger {
            padding: 0.25rem;
            gap: 0;
        }

        .profile-badge {
            width: 32px;
            height: 32px;
            font-size: 0.7rem;
        }

        .notification-bell {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }
    }
</style>
