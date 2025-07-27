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

// Handle form submission
$prediction_result = null;
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
    $current_price = filter_input(INPUT_POST, 'current_price', FILTER_VALIDATE_FLOAT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);

    if (!$fuel_type || !$current_price || !$date) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Get historical data for the selected fuel type
        $sql = "SELECT prediction_result, created_at 
                FROM predictions 
                WHERE user_id = ? 
                AND JSON_EXTRACT(input_data, '$.fuel_type') = ? 
                ORDER BY created_at DESC 
                LIMIT 30";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $fuel_type);
        $stmt->execute();
        $historical_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate prediction based on historical data
        if (!empty($historical_data)) {
            // Calculate average price change
            $price_changes = [];
            for ($i = 0; $i < count($historical_data) - 1; $i++) {
                $price_changes[] = $historical_data[$i]['prediction_result'] - $historical_data[$i + 1]['prediction_result'];
            }
            $avg_change = !empty($price_changes) ? array_sum($price_changes) / count($price_changes) : 0;

            // Calculate volatility
            $volatility = 0;
            if (!empty($price_changes)) {
                $mean = array_sum($price_changes) / count($price_changes);
                $squared_diffs = array_map(function($x) use ($mean) {
                    return pow($x - $mean, 2);
                }, $price_changes);
                $volatility = sqrt(array_sum($squared_diffs) / count($squared_diffs));
            }

            // Calculate days difference
            $days_diff = (strtotime($date) - time()) / (60 * 60 * 24);

            // Calculate prediction with fuel-type specific factors
            $fuel_factors = [
                'gas' => 1.2,    // Gasoline is more volatile
                'oil' => 1.0,    // Diesel is moderately volatile
                'petroleum' => 0.8 // Petroleum is less volatile
            ];

            $factor = $fuel_factors[$fuel_type] ?? 1.0;
            $prediction_change = $avg_change * $factor * (1 + ($volatility * 0.1)) * ($days_diff / 30);
            
            // Add some randomness to the prediction
            $random_factor = 1 + (rand(-10, 10) / 100);
            $prediction_result = $current_price * (1 + ($prediction_change * $random_factor));

            // Add safety limits
            $max_change = 0.15; // Maximum 15% change
            $prediction_result = max(
                $current_price * (1 - $max_change),
                min($current_price * (1 + $max_change), $prediction_result)
            );
        } else {
            // If no historical data, use a simple prediction
            $prediction_result = $current_price * (1 + (rand(-5, 5) / 100));
        }

        // Store the prediction
        $input_data = json_encode([
            'fuel_type' => $fuel_type,
            'current_price' => $current_price,
            'date' => $date
        ]);

        $sql = "INSERT INTO predictions (user_id, input_data, prediction_result) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isd", $user_id, $input_data, $prediction_result);
        
        if ($stmt->execute()) {
            $success = "Prediction saved successfully!";
        } else {
            $error = "Error saving prediction.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Prediction - Fuel Price Prediction</title>
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

        .prediction-form {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 12px 16px;
            border: 1px solid rgba(0,0,0,0.1);
            font-size: 0.95rem;
            transition: all var(--transition-speed) ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .input-group-text {
            background: var(--bg-light);
            border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-muted);
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

        .prediction-result {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 32px;
            margin-top: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
            text-align: center;
        }

        .prediction-price {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--accent-color);
            margin: 24px 0;
            letter-spacing: -1px;
        }

        .confidence-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: var(--bg-light);
            color: var(--accent-color);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 16px;
            margin-bottom: 24px;
            border: none;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
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
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link active" href="prediction.php">
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
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="prediction-form">
                        <h2 class="mb-4">Make a Prediction</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label">Fuel Type</label>
                                <select name="fuel_type" class="form-select" required>
                                    <option value="">Select fuel type</option>
                                    <option value="gas">Gasoline (Petrol)</option>
                                    <option value="oil">Diesel Oil</option>
                                    <option value="petroleum">Petroleum</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Current Price (per liter)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="current_price" class="form-control" step="0.01" min="0" required>
                                    <span class="input-group-text">/L</span>
                                </div>
                                <small class="text-muted">Enter the current price per liter</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Prediction Date</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-graph-up me-2"></i>Generate Prediction
                            </button>
                        </form>

                        <?php if ($prediction_result !== null): ?>
                            <div class="prediction-result text-center">
                                <h3>Prediction Result</h3>
                                <div class="prediction-price">
                                    $<?php echo number_format($prediction_result, 2); ?>/L
                                </div>
                                <div class="confidence-indicator">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <?php echo !empty($historical_data) ? 'High Confidence' : 'Medium Confidence'; ?>
                                </div>
                                <p class="mt-3 mb-0">
                                    Based on <?php echo count($historical_data); ?> historical data points
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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