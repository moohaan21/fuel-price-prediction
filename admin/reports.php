<?php
include '../includes/auth.php';
require_login();
require_admin();
include '../includes/db.php';

// Handle date range filter
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$where = '';
$params = [];
if ($from && $to) {
    $where = 'WHERE DATE(p.created_at) BETWEEN ? AND ?';
    $params = [$from, $to];
}

// For CSV download
if (isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="predictions_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User', 'Fuel Type', 'Date', 'Current Price', 'Result', 'Created At']);
    $sql = 'SELECT p.*, u.username FROM predictions p JOIN users u ON p.user_id = u.id ' . ($where ? $where : '') . ' ORDER BY p.created_at DESC';
    $stmt = $pdo->prepare($sql);
    if ($params) $stmt->execute($params); else $stmt->execute();
    while ($row = $stmt->fetch()) {
        $input = json_decode($row['input_data'], true);
        fputcsv($out, [
            $row['username'],
            $input['fuel_type'] ?? '',
            $input['date'] ?? '',
            $input['current_price'] ?? '',
            $row['prediction_result'],
            $row['created_at']
        ]);
    }
    fclose($out);
    exit;
}

// Summary and report queries with filter
$sql = 'SELECT p.*, u.username FROM predictions p JOIN users u ON p.user_id = u.id ' . ($where ? $where : '') . ' ORDER BY p.created_at DESC LIMIT 8';
$stmt = $pdo->prepare($sql);
if ($params) $stmt->execute($params); else $stmt->execute();
$recentPreds = $stmt->fetchAll();

$sql = 'SELECT COUNT(*) FROM predictions p ' . ($where ? $where : '');
$stmt = $pdo->prepare($sql);
if ($params) $stmt->execute($params); else $stmt->execute();
$totalPreds = $stmt->fetchColumn();

$sql = 'SELECT COUNT(DISTINCT user_id) FROM predictions p ' . ($where ? $where : '');
$stmt = $pdo->prepare($sql);
if ($params) $stmt->execute($params); else $stmt->execute();
$uniqueUsers = $stmt->fetchColumn();

$sql = 'SELECT created_at FROM predictions p ' . ($where ? $where : '') . ' ORDER BY p.created_at DESC LIMIT 1';
$stmt = $pdo->prepare($sql);
if ($params) $stmt->execute($params); else $stmt->execute();
$lastPred = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #1E1E2D;
            --accent-color: #3699FF;
            --success-color: #1BC5BD;
            --warning-color: #FFA800;
            --danger-color: #F64E60;
            --text-light: #ffffff;
            --text-dark: #181C32;
            --bg-light: #F5F8FA;
            --bg-white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --border-radius: 12px;
        }
        body {
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
            z-index: 1000;
            box-shadow: var(--shadow-md);
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
            min-height: 100vh;
        }
        .summary-row {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .summary-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            flex: 1 1 200px;
            min-width: 200px;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .summary-icon {
            font-size: 2.2rem;
            color: var(--accent-color);
        }
        .summary-label {
            color: var(--text-muted);
            font-size: 1rem;
        }
        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .prediction-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .prediction-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-right: 16px;
        }
        .prediction-details {
            flex: 1;
        }
        .prediction-meta {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .prediction-price {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .report-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
            padding: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .report-label {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .report-desc {
            color: var(--text-muted);
            font-size: 0.98rem;
        }
        .btn-download {
            font-size: 1.1rem;
            padding: 12px 28px;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-excel {
            background: #217346 !important;
            color: #fff !important;
            border: none !important;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(33,115,70,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .btn-excel:hover, .btn-excel:focus {
            background: #185c37 !important;
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(33,115,70,0.15);
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
            <a class="nav-link" href="dashboard.php">
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
            <a class="nav-link active" href="reports.php">
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
        <h2 class="mb-4">Reports</h2>
        <form class="row g-3 mb-4 align-items-end" method="get" style="background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); padding: 24px 32px;">
            <div class="col-md-4">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                <a href="reports.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
                <a href="reports.php?download=1<?php echo $from && $to ? '&from=' . urlencode($from) . '&to=' . urlencode($to) : ''; ?>" class="btn btn-excel btn-download w-100 d-flex align-items-center justify-content-center">
                     <i class="bi bi-file-earmark-excel me-2"></i> Download Excel
                 </a>
            </div>
        </form>
        <div class="summary-row">
            <div class="summary-card">
                <span class="summary-icon"><i class="bi bi-bar-chart"></i></span>
                <div>
                    <div class="summary-label">Total Predictions</div>
                    <div class="summary-value"><?php echo $totalPreds; ?></div>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon"><i class="bi bi-people"></i></span>
                <div>
                    <div class="summary-label">Unique Users</div>
                    <div class="summary-value"><?php echo $uniqueUsers; ?></div>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <div class="summary-label">Last Prediction</div>
                    <div class="summary-value"><?php echo $lastPred ? date('M d, Y H:i', strtotime($lastPred)) : 'N/A'; ?></div>
                </div>
            </div>
        </div>
        <h4 class="mb-3">Recent Predictions</h4>
        <?php foreach ($recentPreds as $row): 
            $input = json_decode($row['input_data'], true);
        ?>
        <div class="prediction-card">
            <div class="prediction-icon"><i class="bi bi-fuel-pump"></i></div>
            <div class="prediction-details">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold"><?php echo htmlspecialchars($input['fuel_type'] ?? 'Unknown'); ?></div>
                    <div class="prediction-meta">
                        <?php echo date('M d, Y', strtotime($input['date'] ?? $row['created_at'])); ?>
                    </div>
                </div>
                <div class="d-flex gap-4 align-items-center">
                    <div>
                        <div class="text-muted">Current Price</div>
                        <div class="prediction-price">$<?php echo htmlspecialchars($input['current_price'] ?? '-'); ?>/L</div>
                    </div>
                    <div>
                        <div class="text-muted">Predicted Price</div>
                        <div class="prediction-price">$<?php echo htmlspecialchars($row['prediction_result']); ?>/L</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="report-card">
            <div>
                <div class="report-label"><i class="bi bi-download"></i> Download All Predictions</div>
                <div class="report-desc">Export all predictions as CSV for further analysis.</div>
            </div>
            <a href="reports.php?download=1<?php echo $from && $to ? '&from=' . urlencode($from) . '&to=' . urlencode($to) : ''; ?>" class="btn btn-success btn-download"><i class="bi bi-download"></i> Download CSV</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 