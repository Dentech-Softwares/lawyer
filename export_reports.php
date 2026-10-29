<?php
require_once 'db.php';
require_once 'includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? 'appointments';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="appointment_reports_' . date('YmdHis') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Client Name', 'Case Type', 'Date', 'Time', 'Status', 'Message', 'Created At']);

$query = "SELECT a.*, c.name as client_name FROM appointments a 
          LEFT JOIN users c ON a.user_id = c.id WHERE 1=1";
$params = [];
$types = '';

if (!empty($start_date)) {
    $query .= " AND (DATE(COALESCE(a.date, a.created_at)) >= ?)";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $query .= " AND (DATE(COALESCE(a.date, a.created_at)) <= ?)";
    $params[] = $end_date;
    $types .= 's';
}
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY COALESCE(a.date, DATE(a.created_at)) DESC, a.created_at DESC";

$reports = [];
if (isset($conn)) {
    if (!empty($params) && !empty($types)) {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    fputcsv($output, [
                        $row['id'],
                        $row['client_name'] ?? 'N/A',
                        $row['case_type'] ?? '',
                        $row['date'] ?? '',
                        $row['time'] ?? '',
                        $row['status'] ?? '',
                        $row['message'] ?? '',
                        $row['created_at'] ?? ''
                    ]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                fputcsv($output, [
                    $row['id'],
                    $row['client_name'] ?? 'N/A',
                    $row['case_type'] ?? '',
                    $row['date'] ?? '',
                    $row['time'] ?? '',
                    $row['status'] ?? '',
                    $row['message'] ?? '',
                    $row['created_at'] ?? ''
                ]);
            }
            mysqli_free_result($result);
        }
    }
}
fclose($output);
exit;
