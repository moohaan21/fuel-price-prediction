<?php
include '../includes/auth.php';
require_login();
include '../includes/header.php';
?>
<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h2>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Predictions</h5>
                        <p class="card-text">View and manage your fuel price predictions here.</p>
                        <a href="predict.php" class="btn btn-primary">Go to Prediction</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 