<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Price Prediction</title>
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
            flex-direction: column;
        }

        .navbar {
            background: var(--bg-white);
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .navbar-brand {
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .navbar-brand i {
            color: var(--accent-color);
            font-size: 1.75rem;
        }

        .nav-link {
            color: var(--text-dark);
            opacity: 0.8;
            transition: all var(--transition-speed) ease;
            padding: 0.75rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .nav-link:hover {
            opacity: 1;
            background: var(--bg-light);
            color: var(--accent-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all var(--transition-speed) ease;
        }

        .btn-primary {
            background: var(--accent-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--accent-color);
            color: var(--text-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .hero-section {
            padding: 8rem 0;
            background: var(--bg-white);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            color: var(--text-dark);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .feature-section {
            padding: 6rem 0;
            background: var(--bg-light);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            height: 100%;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-speed) ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .feature-text {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        .cta-section {
            padding: 6rem 0;
            background: var(--bg-white);
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }

        .cta-text {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        footer {
            background: var(--bg-white);
            color: var(--text-muted);
            padding: 2rem 0;
            margin-top: auto;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .footer-text {
            font-size: 0.95rem;
        }

        .hero-badges {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-badge {
            background: var(--bg-light);
            color: var(--text-dark);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hero-badge i {
            color: var(--accent-color);
        }

        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .hero-feature i {
            color: var(--success-color);
            font-size: 1.1rem;
        }

        .hero-image {
            position: relative;
            padding: 2rem;
        }

        .hero-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .hero-card-header {
            background: var(--bg-light);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .hero-card-header i {
            color: var(--accent-color);
            font-size: 1.25rem;
        }

        .hero-card-body {
            padding: 1.5rem;
        }

        .hero-stat {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .hero-stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .hero-stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .hero-stat-change {
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .hero-stat-change.up {
            color: var(--success-color);
        }

        .hero-stat-change.down {
            color: var(--danger-color);
        }

        .hero-chart {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            font-size: 3rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .hero-badges {
                justify-content: center;
            }
            
            .hero-features {
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-fuel-pump"></i>
                Fuel Prediction
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php">Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="hero-badges mb-4">
                        <span class="hero-badge">
                            <i class="bi bi-graph-up-arrow"></i>
                            Accurate Predictions
                        </span>
                        <span class="hero-badge">
                            <i class="bi bi-lightning-charge"></i>
                            Real-time Updates
                        </span>
                    </div>
                    <h1 class="hero-title">Predict Fuel Prices with Confidence</h1>
                    <p class="hero-subtitle">Make informed decisions with our advanced fuel price prediction system. Get accurate forecasts and stay ahead of market changes.</p>
                    <div class="hero-features mb-4">
                        <div class="hero-feature">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Advanced AI Algorithms</span>
                        </div>
                        <div class="hero-feature">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Real-time Market Data</span>
                        </div>
                        <div class="hero-feature">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Historical Analysis</span>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="signup.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Get Started
                        </a>
                        <a href="login.php" class="btn btn-outline">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-image">
                        <div class="hero-card">
                            <div class="hero-card-header">
                                <i class="bi bi-fuel-pump"></i>
                                <span>Fuel Price Prediction</span>
                            </div>
                            <div class="hero-card-body">
                                <div class="hero-stat">
                                    <span class="hero-stat-label">Current Price</span>
                                    <span class="hero-stat-value">$3.45</span>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-label">Predicted Price</span>
                                    <span class="hero-stat-value">$3.52</span>
                                    <span class="hero-stat-change up">
                                        <i class="bi bi-arrow-up"></i>
                                        2.03%
                                    </span>
                                </div>
                                <div class="hero-chart">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="feature-section">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Us</h2>
                <p>Our platform offers advanced features to help you make better fuel price predictions</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-graph-up-arrow feature-icon"></i>
                        <h3 class="feature-title">Accurate Predictions</h3>
                        <p class="feature-text">Our advanced algorithms analyze historical data to provide precise fuel price predictions.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-shield-check feature-icon"></i>
                        <h3 class="feature-title">Real-time Updates</h3>
                        <p class="feature-text">Stay informed with real-time price updates and market trend analysis.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h3 class="feature-title">Historical Data</h3>
                        <p class="feature-text">Access comprehensive historical data to make better-informed decisions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-text">Join thousands of users who are already making better fuel price predictions with our platform.</p>
            <a href="signup.php" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Create Free Account
            </a>
        </div>
    </section>

    <footer>
        <div class="container text-center">
            <p class="footer-text mb-0">© 2024 Fuel Price Prediction. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 