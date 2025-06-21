<?php
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();

// Get asset ID from request
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$asset_id) {
    echo "<div class='alert alert-danger'>Invalid asset ID</div>";
    exit;
}

// Get asset details
$asset = $assetManager->getFixedAssetById($asset_id);

if (!$asset) {
    echo "<div class='alert alert-danger'>Asset not found</div>";
    exit;
}

// Get depreciation history for this asset
$depreciation_history = $assetManager->getDepreciationSchedule($asset_id);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary">Basic Information</h6>
        <table class="table table-sm">
            <tr><th>Asset Code:</th><td><?php echo $asset['asset_code']; ?></td></tr>
            <tr><th>Asset Name:</th><td><?php echo $asset['asset_name']; ?></td></tr>
            <tr><th>Category:</th><td><?php echo $asset['category']; ?></td></tr>
            <tr><th>Status:</th><td>
                <span class="badge bg-<?php echo $asset['status'] == 'Active' ? 'success' : 'warning'; ?>">
                    <?php echo $asset['status']; ?>
                </span>
            </td></tr>
            <tr><th>Location:</th><td><?php echo $asset['location'] ?: 'Not specified'; ?></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-success">Financial Information</h6>
        <table class="table table-sm">
            <tr><th>Purchase Date:</th><td><?php echo date('d/m/Y', strtotime($asset['purchase_date'])); ?></td></tr>
            <tr><th>Purchase Amount:</th><td>₹<?php echo number_format($asset['purchase_amount'], 2); ?></td></tr>
            <tr><th>Depreciation Rate:</th><td><?php echo $asset['depreciation_rate']; ?>% per year</td></tr>
            <tr><th>Accumulated Depreciation:</th><td>₹<?php echo number_format($asset['accumulated_depreciation'], 2); ?></td></tr>
            <tr><th>Current Book Value:</th><td><strong>₹<?php echo number_format($asset['current_book_value'], 2); ?></strong></td></tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h6 class="text-info">Purchase Details</h6>
        <table class="table table-sm">
            <tr><th>Vendor:</th><td><?php echo $asset['vendor_name'] ?: 'Not specified'; ?></td></tr>
            <tr><th>Invoice Number:</th><td><?php echo $asset['invoice_number'] ?: 'Not specified'; ?></td></tr>
            <tr><th>Warranty Period:</th><td><?php echo $asset['warranty_period']; ?> years</td></tr>
            <tr><th>Asset Age:</th><td>
                <?php 
                $purchase_date = new DateTime($asset['purchase_date']);
                $current_date = new DateTime();
                $age = $current_date->diff($purchase_date);
                echo $age->y . ' years, ' . $age->m . ' months, ' . $age->d . ' days';
                ?>
            </td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-warning">System Information</h6>
        <table class="table table-sm">
            <tr><th>Created:</th><td><?php echo date('d/m/Y H:i', strtotime($asset['created_at'])); ?></td></tr>
            <tr><th>Last Updated:</th><td><?php echo date('d/m/Y H:i', strtotime($asset['updated_at'])); ?></td></tr>
            <tr><th>Accounting Status:</th><td>
                <span class="badge bg-<?php echo $asset['accounting_status'] == 'Posted' ? 'success' : 'warning'; ?>">
                    <?php echo $asset['accounting_status']; ?>
                </span>
            </td></tr>
        </table>
    </div>
</div>

<?php if ($asset['remarks']): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-secondary">Remarks</h6>
        <p class="border p-2 bg-light"><?php echo nl2br(htmlspecialchars($asset['remarks'])); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($depreciation_history && $depreciation_history->num_rows > 0): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-dark">Depreciation History</h6>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Accumulated</th>
                        <th>Book Value After</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($dep = $depreciation_history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($dep['depreciation_date'])); ?></td>
                        <td>₹<?php echo number_format($dep['depreciation_amount'], 2); ?></td>
                        <td>₹<?php echo number_format($dep['accumulated_depreciation'], 2); ?></td>
                        <td>₹<?php echo number_format($dep['book_value_after'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $dep['status'] == 'Posted' ? 'success' : 'warning'; ?>">
                                <?php echo $dep['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-center">
            <a href="edit_asset.php?id=<?php echo $asset['id']; ?>" class="btn btn-warning btn-sm">
                <i class="fa fa-edit"></i> Edit Asset
            </a>
        </div>
    </div>
</div>