<?php
include '../includes/auth.php';
require_login();
include '../includes/header.php';
?>
<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h2>Profile</h2>
        <div class="card mt-4" style="max-width: 400px;">
            <div class="card-body">
                <h5 class="card-title">User Information</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></li>
                    <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 