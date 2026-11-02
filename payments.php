<?php
require_once 'db.php';
require_once 'includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$admin_username = $_SESSION['admin_username'] ?? '';
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_phone = $_SESSION['admin_phone'] ?? '';

if (isset($_GET['verify'])) { mysqli_query($conn, "UPDATE payments SET status='COMPLETED' WHERE id=".(int)$_GET['verify']); header('Location: payments.php'); exit; }
if (isset($_GET['reject'])) { mysqli_query($conn, "UPDATE payments SET status='FAILED' WHERE id=".(int)$_GET['reject']); header('Location: payments.php'); exit; }

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

$query = "SELECT p.*, u.name as client_name FROM payments p LEFT JOIN appointments a ON p.appointment_id=a.id LEFT JOIN users u ON a.user_id=u.id WHERE 1=1";
$params=[]; $types='';
if (!empty($start_date)) { $query .= " AND DATE(p.created_at) >= ?"; $params[]=$start_date; $types.='s'; }
if (!empty($end_date)) { $query .= " AND DATE(p.created_at) <= ?"; $params[]=$end_date; $types.='s'; }
if (!empty($status)) { $query .= " AND p.status = ?"; $params[]=$status; $types.='s'; }
$query .= " ORDER BY p.created_at DESC";

$rows=[];
if (isset($conn)) {
    if (!empty($params)) {
        $stmt=mysqli_prepare($conn,$query);
        mysqli_stmt_bind_param($stmt,$types,...$params);
        mysqli_stmt_execute($stmt);
        $res=mysqli_stmt_get_result($stmt);
        while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
        mysqli_stmt_close($stmt);
    } else {
        $res=mysqli_query($conn,$query);
        while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
    }
}

$totalRevenue = 0; $pending=0; $completed=0; $mpesa=0; $cash=0; $totalPayments=0;
foreach($rows as $r){
    $totalPayments++;
    if($r['status']==='COMPLETED') { $totalRevenue += (float)$r['amount']; $completed++; }
    if($r['status']==='PENDING') $pending++;
    if($r['payment_method']==='mpesa') $mpesa++;
    if($r['payment_method']==='cash') $cash++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payments - Justice Law</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/page-transition.css">
    <link rel="stylesheet" href="assets/css/status-badges.css">
    <style>
        :root { --primary: #1a3a5c; --secondary: #2c5282; --accent: #d4af37; --accent-hover: #b8962e; --white: #ffffff; --light-gray: #f5f7fa; --dark: #1a202c; --gray: #718096; --success: #38a169; --danger: #e53e3e; --warning: #d69e2e; --info: #3182ce; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--light-gray); line-height: 1.6; }
        .main-content { margin-left: 280px; padding: 2rem; transition: margin-left 0.3s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 1rem; } }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { color: var(--primary); font-size: 1.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .page-header p { color: var(--gray); margin-top: 0.5rem; }
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .metric-card { background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 1.5rem; transition: all 0.3s ease; border: none; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .metric-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .metric-info h3 { color: var(--primary); font-size: 1.8rem; font-weight: 600; margin-bottom: 0.25rem; line-height: 1.2; }
        .metric-info p { color: var(--gray); font-weight: 500; font-size: 0.9rem; }
        .filter-card { background: var(--white); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; border: 1px solid #e2e8f0; }
        .filter-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; min-width: 200px; }
        .form-group label { color: var(--primary); font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select { padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .btn-filter { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: var(--white); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; text-decoration: none; }
        .btn-filter:hover { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3); }
        .reports-table-container { background: var(--white); border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
        .table-header { padding: 1.5rem; border-bottom: 2px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(90deg, rgba(30, 66, 115, 0.02) 0%, transparent 100%); }
        .table-header h2 { color: var(--primary); font-size: 1.3rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .reports-table { width: 100%; border-collapse: collapse; }
        .reports-table thead { background: var(--light-gray); }
        .reports-table th { padding: 1rem 1.5rem; text-align: left; color: var(--primary); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .reports-table td { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; color: var(--dark); font-size: 0.95rem; }
        .reports-table tbody tr:nth-child(even) { background: #f9fafb; }
        .reports-table tbody tr:hover { background: rgba(245, 247, 250, 0.8); }
        .reports-table tbody tr:last-child td { border-bottom: none; }
        @media (max-width: 768px) { .reports-table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
    <?php $sidebar_role = 'admin'; include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'admin_header.php'; ?>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(49, 130, 206, 0.1); color: var(--info);"><i class="fas fa-dollar-sign"></i></div>
                <div class="metric-info"><h3><?php echo number_format($totalRevenue,2); ?></h3><p>Total Revenue</p></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(214, 158, 46, 0.1); color: var(--warning);"><i class="fas fa-clock"></i></div>
                <div class="metric-info"><h3><?php echo $pending; ?></h3><p>Pending</p></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(56, 161, 105, 0.1); color: var(--success);"><i class="fas fa-mobile-alt"></i></div>
                <div class="metric-info"><h3><?php echo $mpesa; ?></h3><p>M-Pesa</p></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: rgba(212, 175, 55, 0.1); color: var(--accent);"><i class="fas fa-money-bill"></i></div>
                <div class="metric-info"><h3><?php echo $cash; ?></h3><p>Cash</p></div>
            </div>
        </div>
        <div class="filter-card">
            <form method="GET" id="filterForm" class="filter-form">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" onchange="applyFilters()"></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" onchange="applyFilters()"></div>
                <div class="form-group"><label>Status</label><select name="status" onchange="applyFilters()"><option value="">All Status</option><option value="PENDING" <?php echo $status==='PENDING'?'selected':'';?>>Pending</option><option value="COMPLETED" <?php echo $status==='COMPLETED'?'selected':'';?>>Completed</option><option value="FAILED" <?php echo $status==='FAILED'?'selected':'';?>>Failed</option></select></div>
            </form>
        </div>
        <div class="reports-table-container" id="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Payment Records</h2>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="export_reports.php" class="btn-filter" style="text-decoration: none;"><i class="fas fa-file-csv"></i> CSV</a>
                    <a href="export_reports_pdf.php" class="btn-filter" style="text-decoration: none;"><i class="fas fa-file-pdf"></i> PDF</a>
                </div>
            </div>
            <table class="reports-table">
                <thead><tr><th>ID</th><th>Client</th><th>Amount</th><th>Method</th><th>Transaction Code</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td>#<?php echo $r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['client_name']??'N/A'); ?></td>
                        <td><?php echo $r['amount']; ?></td>
                        <td>M-Pesa</td>
                        <td><?php echo htmlspecialchars($r['mpesa_receipt_number']??$r['checkout_request_id']); ?></td>
                        <td><?php echo $r['status']; ?></td>
                        <td><?php echo $r['created_at']; ?></td>
                        <td>
                            <?php if($r['status']==='PENDING'): ?>
                                <a class="btn-filter" style="padding:0.4rem 0.8rem;font-size:0.85rem;text-decoration:none;" href="?verify=<?php echo $r['id']; ?>">Verify</a>
                                <a class="btn-filter" style="padding:0.4rem 0.8rem;font-size:0.85rem;text-decoration:none;background:linear-gradient(135deg,#e53e3e,#c53030);" href="?reject=<?php echo $r['id']; ?>">Reject</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="assets/js/page-transition.js"></script>
    <script src="assets/js/auto-update.js"></script>
    <script>
    function applyFilters(){const form=document.getElementById('filterForm');const params=new URLSearchParams(new FormData(form));const url=window.location.pathname+'?'+params.toString();fetch(url).then(r=>r.text()).then(html=>{const d=new DOMParser().parseFromString(html,'text/html');const n=d.getElementById('table-container');if(n)document.getElementById('table-container').innerHTML=n.innerHTML;history.pushState({},'',url);});}
    </script>
</body>
</html>