<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$query = "SELECT p.*, a.id as case_id, a.case_type, u.name as client_name
          FROM payments p
          LEFT JOIN appointments a ON p.appointment_id = a.id
          LEFT JOIN users u ON a.user_id = u.id
          WHERE 1=1";
$params = [];
$types = '';
if (!empty($start_date)) { $query .= " AND DATE(p.created_at) >= ?"; $params[] = $start_date; $types .= 's'; }
if (!empty($end_date)) { $query .= " AND DATE(p.created_at) <= ?"; $params[] = $end_date; $types .= 's'; }
if (!empty($status)) { $query .= " AND p.status = ?"; $params[] = $status; $types .= 's'; }
$query .= " ORDER BY p.created_at DESC";

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

require_once '/opt/lampp/phpmyadmin/vendor/tecnickcom/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Justice Law Firm');
$pdf->SetTitle('Payment Reports');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Payment Reports', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Period: ' . $start_date . ' to ' . $end_date . '  |  Status: ' . ($status ?: 'All'), 0, 1, 'C');
$pdf->Ln(4);

$totalRevenue = 0; $pending = 0; $completed = 0;
foreach ($reports as $r) {
    if ($r['status'] === 'COMPLETED') $totalRevenue += (float)$r['amount'];
    if ($r['status'] === 'PENDING') $pending++;
    if ($r['status'] === 'COMPLETED') $completed++;
}
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Total Revenue: KSh ' . number_format($totalRevenue, 2) . '  |  Completed: ' . $completed . '  |  Pending: ' . $pending, 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 8);
$widths = [12, 35, 35, 28, 22, 22, 28];
$headers = ['Case ID', 'Client', 'Receipt No', 'Amount', 'Method', 'Status', 'Date'];
foreach ($headers as $i => $h) {
    $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('helvetica', '', 8);
foreach ($reports as $r) {
    $receipt = !empty($r['mpesa_receipt_number']) ? $r['mpesa_receipt_number'] : 'Pending';
    $pdf->Cell($widths[0], 6, '#' . ($r['case_id'] ?? 'N/A'), 1, 0, 'C');
    $pdf->Cell($widths[1], 6, substr($r['client_name'] ?? 'N/A', 0, 20), 1, 0, 'L');
    $pdf->Cell($widths[2], 6, substr($receipt, 0, 20), 1, 0, 'L');
    $pdf->Cell($widths[3], 6, number_format($r['amount'], 2), 1, 0, 'R');
    $pdf->Cell($widths[4], 6, $r['payment_method'] ?? 'M-Pesa', 1, 0, 'C');
    $pdf->Cell($widths[5], 6, $r['status'] ?? '', 1, 0, 'C');
    $pdf->Cell($widths[6], 6, date('M d, Y', strtotime($r['created_at'])), 1, 0, 'C');
    $pdf->Ln();
}

$pdf->setY(-15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('M d, Y h:i A') . '  |  Justice Law Firm', 0, 0, 'C');

$filename = 'payment_reports_' . date('YmdHis') . '.pdf';
$pdf->Output($filename, 'D');
exit;