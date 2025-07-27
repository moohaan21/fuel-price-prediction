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

// Get all predictions for the user
$sql = "SELECT p.*, u.username 
        FROM predictions p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction History - Fuel Price Prediction</title>
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

        .history-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .history-table th {
            background: var(--bg-light);
            padding: 16px;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid rgba(0,0,0,0.05);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .history-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .history-table tr:hover {
            background: var(--bg-light);
        }

        .fuel-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .fuel-type.gas {
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent-color);
        }

        .fuel-type.oil {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .fuel-type.petroleum {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .price-change {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .price-change.up {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .price-change.down {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .price-change.neutral {
            background: var(--bg-light);
            color: var(--text-muted);
        }

        .accuracy-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .accuracy-badge.high {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .accuracy-badge.medium {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .accuracy-badge.low {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: var(--text-dark);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 24px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--accent-color);
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all var(--transition-speed) ease;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

            .history-table {
                display: block;
                overflow-x: auto;
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
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="prediction.php">
                <i class="bi bi-graph-up"></i>
                <span>Make Prediction</span>
            </a>
            <a class="nav-link active" href="history.php">
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
        <div class="container">
            <div class="history-card">
                <h2 class="mb-4">Prediction History</h2>
                
                <?php if (empty($predictions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No Predictions Yet</h4>
                        <p>Start making predictions to see them here</p>
                        <a href="prediction.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Make Prediction
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Fuel Type</th>
                                    <th>Current Price</th>
                                    <th>Predicted Price</th>
                                    <th>Change</th>
                                    <th>Accuracy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($predictions as $prediction): 
                                    $input_data = json_decode($prediction['input_data'], true);
                                    $fuel_type = $input_data['fuel_type'] ?? 'unknown';
                                    $current_price = $input_data['current_price'] ?? 0;
                                    $predicted_price = $prediction['prediction_result'];
                                    $price_change = $predicted_price - $current_price;
                                    $change_percentage = ($price_change / $current_price) * 100;
                                    $accuracy = abs($predicted_price - $current_price) <= 0.1 ? 'high' : 
                                              (abs($predicted_price - $current_price) <= 0.2 ? 'medium' : 'low');
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar me-2"></i>
                                                <?php echo date('M d, Y', strtotime($prediction['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fuel-type <?php echo $fuel_type; ?>">
                                                <i class="bi bi-fuel-pump"></i>
                                                <?php echo ucfirst($fuel_type); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($current_price, 2); ?>/L</td>
                                        <td>$<?php echo number_format($predicted_price, 2); ?>/L</td>
                                        <td>
                                            <span class="price-change <?php 
                                                echo $price_change > 0 ? 'up' : 
                                                    ($price_change < 0 ? 'down' : 'neutral'); 
                                            ?>">
                                                <i class="bi bi-<?php 
                                                    echo $price_change > 0 ? 'arrow-up' : 
                                                        ($price_change < 0 ? 'arrow-down' : 'dash'); 
                                                ?>"></i>
                                                <?php echo number_format(abs($change_percentage), 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="accuracy-badge <?php echo $accuracy; ?>">
                                                <i class="bi bi-shield-check"></i>
                                                <?php echo ucfirst($accuracy); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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