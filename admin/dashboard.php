<?php
include '../includes/auth.php';
require_login();
require_admin();
include '../includes/db.php';

// Get admin info rmation
$admin_name = htmlspecialchars($_SESSION['username'] ?? 'Admin');

// Get stats
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalRegular = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalPreds = $pdo->query('SELECT COUNT(*) FROM predictions')->fetchColumn();
$recentActivity = $pdo->query("SELECT COUNT(*) FROM predictions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// For analytics: get user/admin growth (last 7 days)
$userGrowth = $pdo->query("SELECT DATE(created_at) as date, SUM(role='admin') as admins, SUM(role='user') as users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetchAll();
// For analytics: get predictions per day (last 7 days)
$predsGrowth = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM predictions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fuel Price Prediction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --primary-color: #1E1E2D;
            --secondary-color: #2D2D3F;
            --accent-color: #3699FF;
            --success-color: #1BC5BD;
            --warning-color: #FFA800;
            --danger-color: #F64E60;
            --text-light: #ffffff;
            --text-dark: #181C32;
            --text-muted: #7E8299;
            --bg-light: #F5F8FA;
            --bg-white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
            --border-radius: 12px;
        }
        body {
            overflow-x: hidden;
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: var(--text-light);
            padding: 24px;
            transition: all var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }
        .sidebar .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .sidebar-brand i {
            font-size: 1.75rem;
            color: var(--accent-color);
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.7);
            padding: 12px 16px;
            border-radius: var(--border-radius);
            margin-bottom: 8px;
            transition: all var(--transition-speed) ease;
            text-decoration: none;
            font-weight: 500;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: var(--accent-color);
            color: var(--text-light);
        }
        .sidebar .nav-link i {
            font-size: 1.25rem;
            min-width: 32px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 32px;
            transition: margin var(--transition-speed) ease;
            min-height: 100vh;
        }
        .welcome-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            transition: transform var(--transition-speed) ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .analytics-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
            padding: 24px;
        }
        .analytics-title {
            font-weight: 600;
            margin-bottom: 16px;
        }
        .report-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
            padding: 24px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-fuel-pump"></i>
                <span>Fuel Prediction</span>
            </a>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="users.php">
                <i class="bi bi-people"></i>
                <span>Manage Users</span>
            </a>
            <a class="nav-link" href="predictions.php">
                <i class="bi bi-graph-up"></i>
                <span>Predictions</span>
            </a>
            <a class="nav-link" href="reports.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reports</span>
            </a>
            <a class="nav-link" href="/fuel%20price%20prediction/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
    <div class="main-content">
        <div class="welcome-card d-flex align-items-center mb-4">
            <div class="me-4" style="font-size:2.5rem;color:var(--accent-color)"><i class="bi bi-person-badge"></i></div>
            <div>
                <h2 class="mb-1">Welcome, Admin <?php echo $admin_name; ?>!</h2>
                <p class="mb-0">Admin Dashboard - Manage users, predictions, and reports</p>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0"><?php echo $totalUsers; ?></h3>
                        </div>
                        <i class="bi bi-people text-primary" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Admins</h6>
                            <h3 class="mb-0"><?php echo $totalAdmins; ?></h3>
                        </div>
                        <i class="bi bi-person-gear text-success" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Regular Users</h6>
                            <h3 class="mb-0"><?php echo $totalRegular; ?></h3>
                        </div>
                        <i class="bi bi-person text-info" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Recent Activity</h6>
                            <h3 class="mb-0"><?php echo $recentActivity; ?></h3>
                            <div class="text-muted small">Predictions (7 days)</div>
                        </div>
                        <i class="bi bi-lightning-charge text-warning" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="analytics-title"><i class="bi bi-bar-chart"></i> User/Admin Growth (7 days)</div>
                    <canvas id="userGrowthChart" height="120"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="analytics-title"><i class="bi bi-graph-up"></i> Predictions Per Day (7 days)</div>
                    <canvas id="predsGrowthChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="report-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Download Reports</h5>
                            <p class="mb-2 text-muted">Export all predictions as CSV for further analysis.</p>
                        </div>
                        <a href="reports.php?download=1" class="btn btn-success"><i class="bi bi-download"></i> Download CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User/Admin Growth Chart
        const userGrowthLabels = <?php echo json_encode(array_column($userGrowth, 'date')); ?>;
        const adminData = <?php echo json_encode(array_map('intval', array_column($userGrowth, 'admins'))); ?>;
        const userData = <?php echo json_encode(array_map('intval', array_column($userGrowth, 'users'))); ?>;
        new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: userGrowthLabels,
                datasets: [
                    {
                        label: 'Admins',
                        data: adminData,
                        backgroundColor: 'rgba(27,197,189,0.7)',
                        borderRadius: 8
                    },
                    {
                        label: 'Users',
                        data: userData,
                        backgroundColor: 'rgba(54,153,255,0.7)',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, precision: 0 } }
            }
        });
        // Predictions Per Day Chart
        const predsGrowthLabels = <?php echo json_encode(array_column($predsGrowth, 'date')); ?>;
        const predsGrowthData = <?php echo json_encode(array_map('intval', array_column($predsGrowth, 'count'))); ?>;
        new Chart(document.getElementById('predsGrowthChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: predsGrowthLabels,
                datasets: [
                    {
                        label: 'Predictions',
                        data: predsGrowthData,
                        fill: true,
                        borderColor: 'rgba(54,153,255,1)',
                        backgroundColor: 'rgba(54,153,255,0.1)',
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: 'rgba(54,153,255,1)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, precision: 0 } }
            }
        });
    </script>
</body>
</html> 
