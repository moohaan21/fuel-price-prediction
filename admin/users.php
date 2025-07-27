<?php
include '../includes/auth.php';
require_login();
require_admin();
include '../includes/db.php';

// Handle role update
if (isset($_POST['update_role'], $_POST['user_id'], $_POST['role'])) {
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$_POST['role'], $_POST['user_id']]);
}
// Handle delete
if (isset($_POST['delete_user'], $_POST['user_id'])) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$_POST['user_id']]);
}
$rows = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #1E1E2D;
            --accent-color: #3699FF;
            --success-color: #1BC5BD;
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
        .table-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .table-card .card-header {
            background: var(--bg-light);
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .badge-admin { background: var(--success-color); color: #fff; }
        .badge-user { background: var(--accent-color); color: #fff; }
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
            <a class="nav-link active" href="users.php">
                <i class="bi bi-people"></i>
                <span>Manage Users</span>
            </a>
            <a class="nav-link" href="predictions.php">
                <i class="bi bi-graph-up"></i>
                <span>Predictions</span>
            </a>
            <a class="nav-link" href="reports.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reports</span>
            </a>
            <a class="nav-link" href="/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
    <div class="main-content">
        <h2 class="mb-4">User Management</h2>
        <div class="table-card card">
            <div class="card-header">All Users</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <select name="role" class="form-select form-select-sm d-inline w-auto">
                                            <option value="user"<?php if($row['role']=='user') echo ' selected'; ?>>user</option>
                                            <option value="admin"<?php if($row['role']=='admin') echo ' selected'; ?>>admin</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 