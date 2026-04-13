<?php
/**
 * Database Connection File
 * Lawyer Website and Appointment Management System
 */

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lawyer_system');

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
?>
