<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();
$message = "";

// Handle depreciation posting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['run_depreciation'])) {
        $as_of_date = $_POST['depreciation_date'] ?? date('Y-m-d');
        $result = $assetManager->postMonthlyDepreciation($as_of_date);
        
        if (strpos($result, 'Successfully') !== false) {
            $message = "<div class='alert alert-success'>$result</div>";
        } else {
            $message = "<div class='alert alert-info'>$result</div>";
        }
    }
}

// Get current month depreciation preview
$current_month = date('Y-m-d');
$depreciation_preview = $assetManager->calculateMonthlyDepreciation($current_month);

// Get depreciation schedule for last 6 months
$six_months_ago = date('Y-m-d', strtotime('-6 months'));
$depreciation_history = $assetManager->getDepreciationSchedule(null, $six_months_ago, $current_month);

// Get asset summary
$asset_summary = $assetManager->getAssetSummaryByCategory();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa fa-calendar"></i> Depreciation Management</h6>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <!-- Depreciation Control Panel -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Run Monthly Depreciation</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="depreciation_date" class="form-label">Depreciation Date:</label>
                                            <input type="date" class="form-control" id="depreciation_date" 
                                                   name="depreciation_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            <small class="form-text text-muted">Usually the last day of the month</small>
                                        </div>
                                        
                                        <?php if (!empty($depreciation_preview)): ?>
                                        <div class="alert alert-warning">
                                            <strong>Preview:</strong> <?php echo count($depreciation_preview); ?> assets 
                                            will have depreciation posted totaling 
                                            ₹<?php echo number_format(array_sum(array_column($depreciation_preview, 'monthly_depreciation')), 2); ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-info">
                                            No depreciation entries to post for <?php echo date('F Y'); ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="run_depreciation" class="btn btn-info btn-block"
                                                <?php echo empty($depreciation_preview) ? 'disabled' : ''; ?>>
                                            <i class="fa fa-play"></i> Run Monthly Depreciation
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Asset Summary -->
                        <div class="col-md-6">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Asset Summary</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($asset_summary && $asset_summary->num_rows > 0): ?>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Assets</th>
                                                <th>Book Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_assets = 0;
                                            $total_book_value = 0;
                                            while($summary = $asset_summary->fetch_assoc()): 
                                                $total_assets += $summary['asset_count'];
                                                $total_book_value += $summary['total_book_value'];
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($summary['category']); ?></td>
                                                <td><?php echo $summary['asset_count']; ?></td>
                                                <td>₹<?php echo number_format($summary['total_book_value'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <tr class="table-info">
                                                <th>Total</th>
                                                <th><?php echo $total_assets; ?></th>
                                                <th>₹<?php echo number_format($total_book_value, 2); ?></th>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <?php else: ?>
                                    <p class="text-muted">No assets found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Depreciation Preview -->
    <?php if (!empty($depreciation_preview)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fa fa-eye"></i> Depreciation Preview - <?php echo date('F Y'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Monthly Depreciation</th>
                                    <th>Current Book Value</th>
                                    <th>Book Value After</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($depreciation_preview as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['asset_code']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['asset_name']); ?></td>
                                    <td>₹<?php echo number_format($entry['monthly_depreciation'], 2); ?></td>
                                    <td>₹<?php echo number_format($entry['accumulated_before'], 2); ?></td>
                                    <td>₹<?php echo number_format($entry['book_value_after'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Depreciation History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fa fa-history"></i> Recent Depreciation History</h6>
                </div>
                <div class="card-body">
                    <?php if ($depreciation_history && $depreciation_history->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Depreciation Amount</th>
                                    <th>Accounting Ref</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($history = $depreciation_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($history['depreciation_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($history['asset_code']); ?></td>
                                    <td><?php echo htmlspecialchars($history['asset_name']); ?></td>
                                    <td>₹<?php echo number_format($history['depreciation_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($history['accounting_ref']): ?>
                                            <span class="badge bg-success"><?php echo $history['accounting_ref']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">No Ref</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $history['status'] == 'Posted' ? 'success' : 'warning'; ?>">
                                            <?php echo $history['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No depreciation history found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fa fa-cogs"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="add_fixed_asset.php" class="btn btn-primary btn-block">
                                <i class="fa fa-plus"></i> Add New Asset
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="asset_list.php" class="btn btn-info btn-block">
                                <i class="fa fa-list"></i> View All Assets
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="asset_reports.php" class="btn btn-success btn-block">
                                <i class="fa fa-chart-bar"></i> Asset Reports
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="balance_sheet.php" class="btn btn-secondary btn-block">
                                <i class="fa fa-file-alt"></i> Balance Sheet
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-block {
    width: 100%;
    margin-bottom: 10px;
}
</style>

<?php include_once "inc/footer.php"; ?>