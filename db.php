<?php
/**
 * Database Connection File
 * Lawyer Website and Appointment Management System
 */

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lawyer_system');

// Maximum number of clients that may be booked into the same date + time slot
define('MAX_APPOINTMENTS_PER_SLOT', 5);

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Helper function to run queries
function runQuery($sql, $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    return false;
}

// Helper function to get single row
function getRow($sql, $params = []) {
    $result = runQuery($sql, $params);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Helper function to get all rows
function getAllRows($sql, $params = []) {
    $result = runQuery($sql, $params);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Helper function to execute insert/update/delete
function execute($sql, $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    
    return false;
}

// Get last inserted ID
function getLastId() {
    global $conn;
    return mysqli_insert_id($conn);
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session inactivity timeout: 5 minutes (300 seconds)
$inactiveLimit = 300;

if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    if ($inactiveTime > $inactiveLimit) {
        // Session expired due to inactivity
        session_unset();
        session_destroy();

        // Start a new session to store the flash message
        session_start();
        $_SESSION['session_expired'] = 'Your session has expired due to inactivity. Please log in again.';
    }
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// Single active session: only the device that logged in may keep using the
// account, so every request is checked against the stored session id.
// The login page is exempt - that is where a session gets established.
require_once __DIR__ . '/includes/auth_check.php';
if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'login.php') {
    enforce_single_session();
}

/**
 * Check if the current session has expired due to inactivity.
 * Returns true if expired (and redirects), false otherwise.
 */
function checkSessionTimeout() {
    if (isset($_SESSION['session_expired'])) {
        $expired = $_SESSION['session_expired'];
        unset($_SESSION['session_expired']);
        return $expired;
    }
    return false;
}
?>
