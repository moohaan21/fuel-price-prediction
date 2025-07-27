<?php
include '../includes/auth.php';
require_login();
require_admin();
include '../includes/db.php';

// Handle prediction form submission
$success = null;
$predicted_price = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fuel_type'], $_POST['date'], $_POST['current_price'])) {
    $fuel_type = $_POST['fuel_type'];
    $date = $_POST['date'];
    $current_price = $_POST['current_price'];
    if ($current_price < 0) {
        $success = '<span class="text-danger">Error: Current Price ($/L) cannot be negative.</span>';
    } else {
        $admin_id = $_SESSION['user_id'];
        // Dummy prediction logic (replace with your model if needed)
        $predicted_price = round($current_price * (0.98 + rand(0, 4) / 100), 2); // random small change
        // Validate prediction range
        if ($predicted_price < 0.7 || $predicted_price > 2.5) {
            $success = '<span class="text-danger">Error: Predicted price per liter must be between $0.7 and $2.5 USD.</span>';
        } else {
            $input_data = json_encode([
                'fuel_type' => $fuel_type,
                'date' => $date,
                'current_price' => $current_price
            ]);
            $stmt = $pdo->prepare('INSERT INTO predictions (user_id, input_data, prediction_result) VALUES (?, ?, ?)');
            $stmt->execute([$admin_id, $input_data, $predicted_price]);
            $success = 'Prediction submitted successfully!';
        }
    }
}
$stmt = $pdo->query('SELECT p.*, u.username FROM predictions p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5');
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Predictions</title>
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
        .form-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
            padding: 32px;
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
        .result-card {
            background: var(--success-color);
            color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 24px 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .result-icon {
            font-size: 2.5rem;
            margin-right: 16px;
        }
        .result-label {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .result-value {
            font-size: 2rem;
            font-weight: 700;
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
            <a class="nav-link active" href="predictions.php">
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
        <h2 class="mb-4">Make Prediction (Admin)</h2>
        <?php if ($predicted_price !== null): ?>
        <div class="result-card">
            <div class="result-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
                <div class="result-label">Predicted Price</div>
                <div class="result-value">$<?php echo htmlspecialchars($predicted_price); ?>/L</div>
            </div>
        </div>
        <?php endif; ?>
        <div class="form-card mb-4">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type" class="form-select" required>
                            <option value="Gas">Gas</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Petrol">Petrol</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Current Price ($/L)</label>
                        <input type="number" step="0.01" name="current_price" class="form-control" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-graph-up"></i> Predict</button>
            </form>
        </div>
        <h4 class="mb-3">Recent Predictions</h4>
        <?php foreach ($rows as $row): 
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 