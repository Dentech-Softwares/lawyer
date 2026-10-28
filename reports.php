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

// Handle Filter inputs
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';

// Build dynamic query for appointments/reports
$query = "SELECT a.*, c.name as client_name FROM appointments a 
          LEFT JOIN users c ON a.user_id = c.id WHERE 1=1";
$params = [];

if (!empty($start_date)) {
    $query .= " AND (a.date >= ? OR DATE(a.created_at) >= ?)";
    $params[] = $start_date;
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND (a.date <= ? OR DATE(a.created_at) <= ?)";
    $params[] = $end_date;
    $params[] = $end_date;
}
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY a.created_at DESC";

// Fetch reports data using mysqli
$reports = [];
$result = runQuery($query, $params);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reports[] = $row;
    }
}

// Fetch summary metrics
$total_appointments_row = getRow("SELECT COUNT(*) as count FROM appointments");
$total_appointments = $total_appointments_row['count'] ?? 0;
$completed_row = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
$completed_cases = $completed_row['count'] ?? 0;
$pending_row = getRow("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$pending_consultations = $pending_row['count'] ?? 0;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .metric-card { background: var(--white); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem; transition: transform 0.3s ease; }
        .metric-card:hover { transform: translateY(-2px); }
        .metric-icon { width: 60px; height: 60px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%); color: var(--white); font-size: 1.8rem; flex-shrink: 0; }
        .metric-info h3 { font-size: 2rem; color: var(--primary); font-weight: 600; margin-bottom: 0.25rem; line-height: 1.2; }
        .metric-info p { color: var(--gray); font-weight: 500; font-size: 0.95rem; }
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
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
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
        
        <div class="page-header">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                <p>View and analyze appointment data and statistics</p>
            </div>
        </div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="metric-info">
                    <h3><?php echo $total_appointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                <div class="metric-info">
                    <h3><?php echo $completed_cases; ?></h3>
                    <p>Completed Cases</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fas fa-clock"></i></div>
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
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply Filters</button>
            </form>
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
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="reports-table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Appointment Reports</h2>
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
        const ctx = document.getElementById('statusChart');
        const statusData = {
            labels: ['Approved', 'Pending', 'Completed', 'Cancelled'],
            datasets: [{
                label: 'Appointments',
                data: [
                    <?php 
                    $approved = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='approved'")['count'] ?? 0;
                    $pending = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='pending'")['count'] ?? 0;
                    $completed = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='completed'")['count'] ?? 0;
                    $cancelled = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='cancelled'")['count'] ?? 0;
                    echo "$approved, $pending, $completed, $cancelled";
                    ?>
                ],
                backgroundColor: [
                    'rgba(19, 115, 51, 0.8)',
                    'rgba(183, 129, 3, 0.8)',
                    'rgba(30, 66, 115, 0.8)',
                    'rgba(197, 34, 31, 0.8)'
                ],
                borderColor: [
                    '#137333',
                    '#b78103',
                    '#1e4273',
                    '#c5221f'
                ],
                borderWidth: 2
            }]
        };
        new Chart(ctx, {
            type: 'bar',
            data: statusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        });
    </script>
    <script>
        window.addEventListener('load', function() {
        // 7-Day Timeline Chart
        const timelineCtx = document.getElementById('timelineChart');
        if (!timelineCtx) return;
        <?php
        $labels = [];
        $fullDates = [];
        $today = date('Y-m-d');
        for ($i = -3; $i <= 3; $i++) {
            $dateObj = date('Y-m-d', strtotime("$i days"));
            $fullDates[] = $dateObj;
            if ($dateObj === $today) {
                $labels[] = [date('M d', strtotime("$i days")), '(Today)'];
            } else {
                $labels[] = date('M d', strtotime("$i days"));
            }
        }
        $completedData = [];
        $upcomingData = [];
        $pendingData = [];
        $today = date('Y-m-d');
        for ($i = -3; $i <= 3; $i++) {
            $date = $fullDates[$i + 3];
            // Completed cases
            $completedData[] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='completed' AND date = '$date'")['count'] ?? 0;
            // Upcoming bookings (approved/confirmed) where date >= today or for this date
            $upcomingData[] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status IN ('approved','confirmed') AND date = '$date'")['count'] ?? 0;
            // Pending/awaiting review
            $pendingData[] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='pending' AND date = '$date'")['count'] ?? 0;
            if ($completedData[count($completedData)-1] == 0 && $upcomingData[count($upcomingData)-1] == 0 && $pendingData[count($pendingData)-1] == 0) {
                // Fallback
                $completedData[count($completedData)-1] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='completed' AND DATE(created_at) = '$date'")['count'] ?? 0;
                $upcomingData[count($upcomingData)-1] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status IN ('approved','confirmed') AND DATE(created_at) = '$date'")['count'] ?? 0;
                $pendingData[count($pendingData)-1] = getRow("SELECT COUNT(*) as count FROM appointments WHERE status='pending' AND DATE(created_at) = '$date'")['count'] ?? 0;
            }
        }
        $todayIdx = 3;
        ?>
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    {
                        label: 'Completed Cases',
                        data: <?php echo json_encode($completedData); ?>,
                        borderColor: '#137333',
                        backgroundColor: 'rgba(19, 115, 51, 0.1)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#137333',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    },
                    {
                        label: 'Upcoming Bookings',
                        data: <?php echo json_encode($upcomingData); ?>,
                        borderColor: '#1e4273',
                        backgroundColor: 'rgba(30, 66, 115, 0.1)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#1e4273',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    },
                    {
                        label: 'Pending / Awaiting Review',
                        data: <?php echo json_encode($pendingData); ?>,
                        borderColor: '#d69e2e',
                        backgroundColor: 'rgba(214, 158, 46, 0.1)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#d69e2e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 0
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
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
</body>
</html>
