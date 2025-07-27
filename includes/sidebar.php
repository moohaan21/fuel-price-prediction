<?php
// sidebar.php
if (!isset($_SESSION)) session_start();
$role = $_SESSION['role'] ?? 'user';
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="width: 220px; min-height: 100vh;">
    <a href="/fuel%20price%20prediction/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <span class="fs-4"><i class="bi bi-fuel-pump"></i> Fuel App</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item"><a href="/fuel%20price%20prediction/admin/dashboard.php" class="nav-link"><i class="bi bi-bar-chart"></i> Analytics</a></li>
            <li><a href="/fuel%20price%20prediction/admin/users.php" class="nav-link"><i class="bi bi-people"></i> Manage Users</a></li>
            <li><a href="/fuel%20price%20prediction/admin/predictions.php" class="nav-link"><i class="bi bi-graph-up"></i> Predictions</a></li>
            <li><a href="/fuel%20price%20prediction/admin/reports.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Reports</a></li>
            <li><a href="/fuel%20price%20prediction/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        <?php else: ?>
            <li class="nav-item"><a href="/fuel%20price%20prediction/user/dashboard.php" class="nav-link"><i class="bi bi-house"></i> Dashboard</a></li>
            <li><a href="/fuel%20price%20prediction/user/predict.php" class="nav-link"><i class="bi bi-graph-up"></i> Prediction</a></li>
            <li><a href="/fuel%20price%20prediction/user/profile.php" class="nav-link"><i class="bi bi-person"></i> Profile</a></li>
            <li><a href="/fuel%20price%20prediction/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        <?php endif; ?>
    </ul>
</div> 