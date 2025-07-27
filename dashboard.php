<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user role
$role = $user['role'] ?? 'user';

// Get recent predictions for the user
$sql = "SELECT p.*, u.username 
        FROM predictions p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate prediction accuracy
$sql = "SELECT COUNT(*) as total, 
        SUM(CASE WHEN ABS(prediction_result - JSON_EXTRACT(input_data, '$.current_price')) <= 0.1 THEN 1 ELSE 0 END) as accurate 
        FROM predictions 
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accuracy_result = $stmt->get_result()->fetch_assoc();
$accuracy = $accuracy_result['total'] > 0 ? round(($accuracy_result['accurate'] / $accuracy_result['total']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fuel Price Prediction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
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

        .sidebar-brand span {
            transition: opacity var(--transition-speed) ease;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            display: none;
        }

        .sidebar.collapsed .sidebar-brand {
            justify-content: center;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 8px;
            transition: transform var(--transition-speed) ease;
            opacity: 0.8;
        }

        .toggle-btn:hover {
            transform: rotate(180deg);
            opacity: 1;
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

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: var(--accent-color);
            color: var(--text-light);
        }

        .sidebar .nav-link i {
            font-size: 1.25rem;
            min-width: 32px;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 32px;
            transition: margin var(--transition-speed) ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
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

        .prediction-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-speed) ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .prediction-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .prediction-icon {
            font-size: 1.75rem;
            color: var(--accent-color);
            margin-bottom: 16px;
        }

        .prediction-date {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .prediction-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }

        .prediction-type {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-transform: capitalize;
        }

        .accuracy-badge {
            background: var(--bg-light);
            color: var(--accent-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--accent-color);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 16px;
        }

        h2, h3, h4, h5, h6 {
            font-weight: 600;
            letter-spacing: -0.5px;
            color: var(--text-dark);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 24px;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 24px;
                left: 24px;
                z-index: 1001;
                background: var(--primary-color);
                color: var(--text-light);
                border: none;
                border-radius: var(--border-radius);
                padding: 12px;
                cursor: pointer;
                box-shadow: var(--shadow-md);
            }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle d-md-none">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-fuel-pump"></i>
                <span>Fuel Prediction</span>
            </a>
            <button class="toggle-btn d-none d-md-block">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="prediction.php">
                <i class="bi bi-graph-up"></i>
                <span>Make Prediction</span>
            </a>
            <a class="nav-link" href="history.php">
                <i class="bi bi-calendar3"></i>
                <span>History</span>
            </a>
            <a class="nav-link" href="profile.php">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
            </a>
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="welcome-card">
            <div class="d-flex align-items-center">
                <div class="user-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
                    <p class="mb-0">Track your fuel price predictions</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Prediction Accuracy</h6>
                            <h3 class="mb-0"><?php echo $accuracy; ?>%</h3>
                            <div class="text-muted small">Last 30 days</div>
                        </div>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Predictions</h6>
                            <h3 class="mb-0"><?php echo count($predictions); ?></h3>
                            <div class="text-muted small">Recent predictions</div>
                        </div>
                        <i class="bi bi-bar-chart-fill text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Last Prediction</h6>
                            <h3 class="mb-0">
                                <?php 
                                if (!empty($predictions)) {
                                    echo date('M d', strtotime($predictions[0]['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </h3>
                            <div class="text-muted small">Date</div>
                        </div>
                        <i class="bi bi-clock-fill text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3">Recent Predictions</h4>
                <?php if (empty($predictions)): ?>
                    <div class="prediction-card text-center">
                        <i class="bi bi-graph-up prediction-icon"></i>
                        <h5>No predictions yet</h5>
                        <p class="text-muted">Start making predictions to see them here</p>
                        <a href="prediction.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Make Prediction
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($predictions as $prediction): 
                        $input_data = json_decode($prediction['input_data'], true);
                        $fuel_type = $input_data['fuel_type'] ?? 'unknown';
                        $current_price = $input_data['current_price'] ?? 0;
                        $predicted_price = $prediction['prediction_result'];
                        $accuracy = abs($predicted_price - $current_price) <= 0.1 ? 'High' : 'Medium';
                    ?>
                        <div class="prediction-card">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <i class="bi bi-fuel-pump prediction-icon"></i>
                                </div>
                                <div class="col-md-3">
                                    <div class="prediction-type"><?php echo ucfirst($fuel_type); ?></div>
                                    <div class="prediction-date">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($prediction['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Current Price</div>
                                    <div class="prediction-price">$<?php echo number_format($current_price, 2); ?>/L</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Predicted Price</div>
                                    <div class="prediction-price">$<?php echo number_format($predicted_price, 2); ?>/L</div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <span class="accuracy-badge">
                                        <i class="bi bi-check-circle me-1"></i><?php echo $accuracy; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.querySelector('.toggle-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Mobile sidebar toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth < 768 && 
                !sidebar.contains(event.target) && 
                !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html> 