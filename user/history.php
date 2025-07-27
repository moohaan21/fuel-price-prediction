<?php
include '../includes/auth.php';
require_login();
include '../includes/header.php';
include '../includes/db.php';
?>
<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h2>My Prediction History</h2>
        <div class="card mt-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Input Data</th>
                                <th>Result</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $user_id = $_SESSION['user_id'];
                        $stmt = $pdo->prepare('SELECT * FROM predictions WHERE user_id = ? ORDER BY created_at DESC');
                        $stmt->execute([$user_id]);
                        $rows = $stmt->fetchAll();
                        $i = 1;
                        foreach ($rows as $row) {
                            $input = htmlspecialchars($row['input_data']);
                            $result = htmlspecialchars($row['prediction_result']);
                            $date = htmlspecialchars($row['created_at']);
                            echo "<tr><td>$i</td><td><pre style='white-space:pre-wrap;'>$input</pre></td><td>$result</td><td>$date</td></tr>";
                            $i++;
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 