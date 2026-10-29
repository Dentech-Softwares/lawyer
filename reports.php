<?php
require_once 'db.php';
require_once 'includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restrict access to admins only
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? '';
$admin_email = $_SESSION['admin_email'] ?? '';
$admin_phone = $_SESSION['admin_phone'] ?? '';
$current_date = date('F j, Y');

// Get status counts
$status_counts_raw = getAllRows("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
$status_counts = [];
foreach ($status_counts_raw as $row) {
    if (!empty($row['status'])) {
        $status_counts[$row['status']] = (int)$row['count'];
    }
}
$completed_total = $status_counts['completed'] ?? 0;
$approved_total  = $status_counts['approved'] ?? 0;
$pending_total   = $status_counts['pending'] ?? 0;
$rejected_total  = $status_counts['rejected'] ?? 0;
$cancelled_total = $status_counts['cancelled'] ?? 0;

// Build dynamic query for appointments/reports
$query = "SELECT a.*, c.name as client_name FROM appointments a 
          LEFT JOIN users c ON a.user_id = c.id WHERE 1=1";
$params = [];
$types = '';

if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
} else {
    $start_date = date('Y-m-01');
}
if (isset($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
} else {
    $end_date = date('Y-m-t');
}
$status = $_GET['status'] ?? '';

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

// Fetch reports data using mysqli
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
                    $reports[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $reports[] = $row;
            }
            mysqli_free_result($result);
        }
    }
} else {
    $result = runQuery($query, $params);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row;
        }
    }
}

// Fetch summary metrics
$total_appointments_row = getRow("SELECT COUNT(*) as count FROM appointments");
$total_appointments = $total_appointments_row['count'] ?? 0;
$completed_row = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
$completed_cases = $completed_row['count'] ?? 0;
$pending_row = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$pending_consultations = $pending_row['count'] ?? 0;

// ---------------------------------------------------------------------------
// Efficient status-distribution query (GROUP BY status, single scan)
// ---------------------------------------------------------------------------
$statusDistRaw = getAllRows("
    SELECT status, COUNT(*) as count
    FROM appointments
    GROUP BY status
");
$statusDist = [];
foreach ($statusDistRaw as $row) {
    if (!empty($row['status'])) {
        $statusDist[$row['status']] = (int)$row['count'];
    }
}

// Map raw statuses to the four chart categories requested
$completed_cases       = $statusDist['completed'] ?? 0;
$upcoming_bookings     = ($statusDist['approved'] ?? 0) + ($statusDist['confirmed'] ?? 0);
$pending_confirmation  = $statusDist['pending'] ?? 0;
$cancelled_rescheduled = ($statusDist['cancelled'] ?? 0)
                       + ($statusDist['rescheduled'] ?? 0)
                       + ($statusDist['rejected'] ?? 0);

// ---------------------------------------------------------------------------
// Efficient 7-day timeline query (GROUP BY date + status, single scan)
// Range: last 3 days • today • next 3 days
// ---------------------------------------------------------------------------
$timelineStart = date('Y-m-d', strtotime('-3 days'));
$timelineEnd   = date('Y-m-d', strtotime('+3 days'));

// Build label / date-index arrays first so we always have a full 7-point series
$labels     = [];
$fullDates  = [];
$today      = date('Y-m-d');
for ($i = -3; $i <= 3; $i++) {
    $dateObj = date('Y-m-d', strtotime("$i days"));
    $fullDates[] = $dateObj;
    $labels[] = ($dateObj === $today)
        ? date('M d', strtotime("$i days")) . ' (Today)'
        : date('M d', strtotime("$i days"));
}

// Single grouped query – far cheaper than 7×3×2 individual SELECTs
$timelineRaw = getAllRows("
    SELECT DATE(date) AS appointment_date,
           status,
           COUNT(*) AS count
    FROM appointments
    WHERE DATE(date) BETWEEN ? AND ?
    GROUP BY DATE(date), status
    ORDER BY appointment_date ASC, status ASC
", [$timelineStart, $timelineEnd]);

// Seed every bucket with zero so empty tenants still render a clean chart
$completed_data = array_fill(0, 7, 0);
$upcoming_data  = array_fill(0, 7, 0);
$pending_data   = array_fill(0, 7, 0);
$date_to_index  = array_flip($fullDates);

foreach ($timelineRaw as $row) {
    $date  = $row['appointment_date'];
    $count = (int)$row['count'];
    if (!isset($date_to_index[$date])) {
        continue;
    }
    $idx = $date_to_index[$date];
    switch ($row['status']) {
        case 'completed':
            $completed_data[$idx] += $count;
            break;
        case 'approved':
        case 'confirmed':
            $upcoming_data[$idx] += $count;
            break;
        case 'pending':
            $pending_data[$idx] += $count;
            break;
        default:
            // cancelled / rejected / rescheduled → count as "pending-style"
            // activity but keep them out of the three main lines
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Justice Law</title>
    <link rel="stylesheet" href="fontawesome-free-7.3.1-web/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/page-transition.css">
    <link rel="stylesheet" href="assets/css/status-badges.css">
    <script src="assets/js/chart.min.js"></script>
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
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--light-gray); line-height: 1.6; }
        .main-content { margin-left: 280px; padding: 2rem; transition: margin-left 0.3s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 1rem; } }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { color: var(--primary); font-size: 1.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .page-header p { color: var(--gray); margin-top: 0.5rem; }
        /* Stats Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
            border: none;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .metric-icon.total {
            background: rgba(49, 130, 206, 0.1);
            color: var(--info);
        }

        .metric-icon.completed {
            background: rgba(212, 175, 55, 0.1);
            color: var(--accent);
        }

        .metric-icon.pending {
            background: rgba(214, 158, 46, 0.1);
            color: var(--warning);
        }

        .metric-info h3 {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .metric-info p {
            color: var(--gray);
            font-weight: 500;
            font-size: 0.9rem;
        }
        .filter-card { background: var(--white); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; border: 1px solid #e2e8f0; }
        .filter-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; min-width: 200px; }
        .form-group label { color: var(--primary); font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select { padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .btn-filter { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: var(--white); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; }
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
        .status-badge { padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #065f46 }
        .status-completed { background: #d1fae5; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        @media (max-width: 768px) { .reports-table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
    <?php 
    $sidebar_role = 'admin';
    include 'includes/sidebar.php'; 
    ?>
    
    <div class="main-content">
        <?php include 'admin_header.php'; ?>
        
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="metric-info">
                    <h3><?php echo $total_appointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-info">
                    <h3><?php echo $completed_cases; ?></h3>
                    <p>Completed Cases</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="metric-info">
                    <h3><?php echo $pending_consultations; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>
        
        <div class="chart-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(26, 58, 92, 0.08); margin-bottom: 2rem; border: 1px solid rgba(226, 232, 240, 0.8);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: var(--primary); font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        7-Day Appointment Timeline
                    </h2>
                    <p style="color: var(--gray); font-size: 0.9rem; margin-left: 3.25rem;">Last 3 days • Today • Next 3 days</p>
                </div>
            </div>
            <div style="position: relative; height: 320px; width: 100%; display: block;">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>

        <div class="chart-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(26, 58, 92, 0.08); margin-bottom: 2rem; border: 1px solid rgba(226, 232, 240, 0.8);">
            <div style="margin-bottom: 1.5rem;">
                <h2 style="color: var(--primary); font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent) 0%, #b8962e 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    Overall Status Distribution
                </h2>
            </div>
            <div style="position: relative; height: 280px; width: 100%; display: block;">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>
        
        <div class="filter-card">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" onchange="applyFilters()">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" onchange="applyFilters()">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="reports-table-container" id="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Appointment Reports</h2>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="export_reports.php?type=appointments&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($status); ?>" class="btn-filter" style="text-decoration: none;">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="export_reports_pdf.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($status); ?>" class="btn-filter" style="text-decoration: none;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Case Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray);">No records found</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>#<?php echo $report['id']; ?></td>
                                <td><?php echo htmlspecialchars($report['client_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($report['case_type'] ?? 'N/A'); ?></td>
                                <td><?php echo !empty($report['date']) ? date('M d, Y', strtotime($report['date'])) : ($report['appointment_date'] ?? ''); ?></td>
                                <td><?php echo !empty($report['time']) ? date('h:i A', strtotime($report['time'])) : ($report['appointment_time'] ?? ''); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars($report['status']); ?>"><?php echo ucfirst($report['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="assets/js/page-transition.js"></script>
    <script>
        const ctxStatus = document.getElementById('statusDistributionChart');
        if (ctxStatus) {
            const statusDistData = {
                labels: ['Completed Cases', 'Upcoming Bookings', 'Pending Confirmation', 'Cancelled / Rescheduled'],
                datasets: [{
                    label: 'Appointments',
                    data: [
                        <?php echo $completed_cases; ?>,
                        <?php echo $upcoming_bookings; ?>,
                        <?php echo $pending_confirmation; ?>,
                        <?php echo $cancelled_rescheduled; ?>
                    ],
                    backgroundColor: [
                        'rgba(56, 161, 105, 0.85)',   // Completed Cases  – Success Green
                        'rgba(49, 130, 206, 0.85)',   // Upcoming Bookings – Active Blue
                        'rgba(214, 158, 46, 0.85)',   // Pending Confirmation – Warning Yellow
                        'rgba(229, 62, 62, 0.85)'     // Cancelled / Rescheduled – Danger Red
                    ],
                    borderColor: [
                        '#2f855a',
                        '#3182ce',
                        '#b7791f',
                        '#c53030'
                    ],
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            };
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: statusDistData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 58, 92, 0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#d4af37',
                            borderWidth: 2,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const value = context.parsed;
                                    const pct = total ? Math.round((value / total) * 100) : 0;
                                    return context.label + ': ' + value + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
    <script>
        window.addEventListener('load', function() {
        // 7-Day Timeline Chart – data pre-computed server-side for efficiency
        const timelineCtx = document.getElementById('timelineChart');
        if (!timelineCtx) return;

        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    {
                        label: 'Completed Cases',
                        data: <?php echo json_encode($completed_data); ?>,
                        borderColor: '#2f855a',
                        backgroundColor: 'rgba(47, 133, 90, 0.15)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#2f855a',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Upcoming Bookings',
                        data: <?php echo json_encode($upcoming_data); ?>,
                        borderColor: '#1e4273',
                        backgroundColor: 'rgba(30, 66, 115, 0.15)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#1e4273',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Pending / Awaiting Review',
                        data: <?php echo json_encode($pending_data); ?>,
                        borderColor: '#d69e2e',
                        backgroundColor: 'rgba(214, 158, 46, 0.15)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#d69e2e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26, 58, 92, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#d4af37',
                        borderWidth: 2,
                        padding: 12,
                        titleFont: {
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                            size: 12
                        },
                        callbacks: {
                            title: function(context) {
                                return 'Date: ' + context[0].label;
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' appointment(s)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        title: {
                            display: true,
                            text: 'Number of Appointments',
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                                weight: '600'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            lineWidth: 1
                        }
                    }
                }
            }
        });
        });
    </script>
<script>
function applyFilters() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form));
    const url = window.location.pathname + '?' + params.toString();
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('table-container');
            if (newContainer) {
                document.getElementById('table-container').innerHTML = newContainer.innerHTML;
            }
            history.pushState({}, '', url);
        });
}
</script>
</body>
</html>
