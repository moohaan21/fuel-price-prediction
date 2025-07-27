<?php
require_once 'config.php';

$error = null;
$success = null;
$show_form = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT id, reset_expires FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user && strtotime($user['reset_expires']) > time()) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if ($password && $confirm && $password === $confirm) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed, $user['id']);
                if ($stmt->execute()) {
                    $success = "Your password has been reset. Redirecting to <a href='login.php'>login</a>...";
                    $show_form = false;
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
                } else {
                    $error = "Failed to reset password. Please try again.";
                }
            } else {
                $error = "Passwords do not match or are empty.";
            }
        }
    } else {
        $error = "Invalid or expired token.";
    }
} else {
    $error = "No token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Fuel Price Prediction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
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
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: flex;
            min-height: 600px;
        }
        .login-image {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--text-light);
            position: relative;
            overflow: hidden;
        }
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><circle cx=\"50\" cy=\"50\" r=\"40\" fill=\"none\" stroke=\"rgba(255,255,255,0.1)\" stroke-width=\"2\"/></svg>') center/cover;
            opacity: 0.1;
        }
        .login-image-content {
            position: relative;
            z-index: 1;
        }
        .login-image-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .login-image-text {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .login-image-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .login-image-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .login-image-feature i {
            font-size: 1.5rem;
            color: var(--accent-color);
        }
        .login-form {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }
        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            font-size: 0.95rem;
            transition: all var(--transition-speed) ease;
        }
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.1);
        }
        .input-group-text {
            background: var(--bg-light);
            border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-muted);
        }
        .btn-success {
            background: var(--success-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all var(--transition-speed) ease;
        }
        .btn-success:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .alert-danger {
            background: rgba(246, 78, 96, 0.1);
            color: var(--danger-color);
        }
        .alert-success {
            background: rgba(27, 197, 189, 0.1);
            color: var(--success-color);
        }
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .signup-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            .login-image {
                display: none;
            }
            .login-form {
                padding: 2rem;
            }
        }
        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <div class="login-image-content">
                <h1 class="login-image-title">Welcome to Fuel Prediction</h1>
                <p class="login-image-text">Make informed decisions with our advanced fuel price prediction system. Get accurate forecasts and stay ahead of market changes.</p>
                <div class="login-image-features">
                    <div class="login-image-feature">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Advanced AI Algorithms</span>
                    </div>
                    <div class="login-image-feature">
                        <i class="bi bi-lightning-charge"></i>
                        <span>Real-time Market Data</span>
                    </div>
                    <div class="login-image-feature">
                        <i class="bi bi-calendar-check"></i>
                        <span>Historical Analysis</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="login-form">
            <div class="login-header">
                <h1 class="login-title">Reset Password</h1>
                <p class="login-subtitle">Enter your new password below</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($show_form): ?>
            <form method="POST">
                <div class="mb-4">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-arrow-repeat me-2"></i>Reset Password
                </button>
            </form>
            <?php endif; ?>
            <div class="signup-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 