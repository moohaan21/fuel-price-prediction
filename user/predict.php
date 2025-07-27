<?php
include '../includes/auth.php';
require_login();
include '../includes/header.php';
include '../includes/db.php';
?>
<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h2>Fuel Price Prediction (ML Model)</h2>
        <div class="card mt-4 mb-4">
            <div class="card-body">
                <h5 class="card-title">Predict Price Per Liter (using trained model)</h5>
                <form method="POST" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="price_per_barrel" class="form-label">Price Per Barrel (USD)</label>
                        <input type="number" step="0.01" class="form-control" id="price_per_barrel" name="price_per_barrel" required>
                    </div>
                    <div class="col-md-3">
                        <label for="fuel_type" class="form-label">Fuel Type</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" required>
                            <option value="">Select Fuel Type</option>
                            <option value="Petroleum">Petroleum</option>
                            <option value="Oil">Oil</option>
                            <option value="Gas">Gas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="import_volume" class="form-label">Import Volume (Barrels)</label>
                        <input type="number" step="1" class="form-control" id="import_volume" name="import_volume" required>
                    </div>
                    <div class="col-md-3">
                        <label for="transportation_cost" class="form-label">Transportation Cost (USD)</label>
                        <input type="number" step="0.01" class="form-control" id="transportation_cost" name="transportation_cost" required>
                    </div>
                    <div class="col-md-3">
                        <label for="taxes" class="form-label">Taxes (%)</label>
                        <input type="number" step="0.01" class="form-control" id="taxes" name="taxes" required>
                    </div>
                    <div class="col-md-3">
                        <label for="storage_cost" class="form-label">Storage Cost (USD)</label>
                        <input type="number" step="0.01" class="form-control" id="storage_cost" name="storage_cost" required>
                    </div>
                    <div class="col-md-3">
                        <label for="exchange_rate_fluctuations" class="form-label">Exchange Rate Fluctuations (%)</label>
                        <input type="number" step="0.01" class="form-control" id="exchange_rate_fluctuations" name="exchange_rate_fluctuations" required>
                    </div>
                    <div class="col-md-3">
                        <label for="seasonal_demand_variations" class="form-label">Seasonal Demand Variations (Scale)</label>
                        <input type="number" step="0.01" class="form-control" id="seasonal_demand_variations" name="seasonal_demand_variations" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100">Predict</button>
                    </div>
                </form>
                <?php
                $prediction = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $inputs = [
                        $_POST['price_per_barrel'],
                        $_POST['fuel_type'],
                        $_POST['import_volume'],
                        $_POST['transportation_cost'],
                        $_POST['taxes'],
                        $_POST['storage_cost'],
                        $_POST['exchange_rate_fluctuations'],
                        $_POST['seasonal_demand_variations']
                    ];
                    // Escape shell arguments
                    $escaped = array_map('escapeshellarg', $inputs);
                    $cmd = "python predict.py " . implode(' ', $escaped);
                    $output = shell_exec($cmd);
                    if ($output !== null) {
                        $prediction = trim($output);
                        // Validate prediction range
                        if ($prediction < 0.7 || $prediction > 2.5) {
                            echo "<div class='alert alert-danger'><strong>Error:</strong> Predicted price per liter must be between $0.7 and $2.5 USD.</div>";
                        } else {
                        echo "<div class='alert alert-info'><strong>Predicted Price Per Liter:</strong> $$prediction USD</div>";
                        // Save to database
                        $stmt = $pdo->prepare("INSERT INTO predictions (user_id, input_data, prediction_result) VALUES (?, ?, ?)");
                        $user_id = $_SESSION['user_id'];
                        $input_data = json_encode($inputs);
                        $stmt->execute([$user_id, $input_data, $prediction]);
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Prediction failed. Please check your Python setup.</div>";
                    }
                }
                ?>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Dataset Preview</h5>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-bordered table-sm">
                        <thead>
                        <?php
                        $file = fopen('../../dataset/dataset.csv', 'r');
                        if ($file) {
                            $headers = fgetcsv($file);
                            echo '<tr>';
                            foreach ($headers as $header) {
                                echo '<th>' . htmlspecialchars($header) . '</th>';
                            }
                            echo '</tr>';
                        ?>
                        </thead>
                        <tbody>
                        <?php
                        $rowCount = 0;
                        while (($row = fgetcsv($file)) !== false && $rowCount < 20) {
                            echo '<tr>';
                            foreach ($row as $cell) {
                                echo '<td>' . htmlspecialchars($cell) . '</td>';
                            }
                            echo '</tr>';
                            $rowCount++;
                        }
                        fclose($file);
                        ?>
                        </tbody>
                        <?php } else { echo '<tr><td colspan="100%">Unable to read dataset.</td></tr>'; } ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 