<?php
require_once '/opt/lampp/lib/php/fpdf.php';
require_once 'db.php';
require_once 'includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

$query = "SELECT a.*, c.name as client_name FROM appointments a 
          LEFT JOIN users c ON a.user_id = c.id WHERE 1=1";
$params = [];
$types = '';
if (!empty($start_date)) { $query .= " AND (DATE(COALESCE(a.date, a.created_at)) >= ?)"; $params[] = $start_date; $types .= 's'; }
if (!empty($end_date)) { $query .= " AND (DATE(COALESCE(a.date, a.created_at)) <= ?)"; $params[] = $end_date; $types .= 's'; }
if (!empty($status)) { $query .= " AND a.status = ?"; $params[] = $status; $types .= 's'; }
$query .= " ORDER BY COALESCE(a.date, DATE(a.created_at)) DESC, a.created_at DESC";

$reports = [];
if (isset($conn)) {
    if (!empty($params) && !empty($types)) {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) { while ($row = mysqli_fetch_assoc($result)) $reports[] = $row; }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $query);
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $reports[] = $row; mysqli_free_result($result); }
    }
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Appointment Reports', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Period: ' . $start_date . ' to ' . $end_date . '  Status: ' . ($status ?: 'All'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 9);
$headers = ['ID', 'Client', 'Case Type', 'Date', 'Time', 'Status'];
$widths = [12, 45, 40, 28, 22, 25];
foreach ($headers as $i => $h) $pdf->Cell($widths[$i], 8, $h, 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
foreach ($reports as $r) {
    $pdf->Cell($widths[0], 7, '#' . $r['id'], 1);
    $pdf->Cell($widths[1], 7, substr($r['client_name'] ?? 'N/A', 0, 22), 1);
    $pdf->Cell($widths[2], 7, substr($r['case_type'] ?? '', 0, 20), 1);
    $pdf->Cell($widths[3], 7, !empty($r['date']) ? date('M d, Y', strtotime($r['date'])) : '', 1);
    $pdf->Cell($widths[4], 7, !empty($r['time']) ? date('h:i A', strtotime($r['time'])) : '', 1);
    $pdf->Cell($widths[5], 7, $r['status'] ?? '', 1);
    $pdf->Ln();
}

$pdf->Output('D', 'appointment_reports_' . date('YmdHis') . '.pdf');
exit;
