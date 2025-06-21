<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();
$message = "";

// Get asset ID from URL
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$asset_id) {
    header("Location: asset_list.php");
    exit;
}

// Get asset details
$asset = $assetManager->getFixedAssetById($asset_id);

if (!$asset) {
    $message = "<div class='alert alert-danger'>Asset not found!</div>";
} else {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $result = $assetManager->updateAsset($asset_id, $_POST);
        
        if ($result) {
            $message = "<div class='alert alert-success'>Asset updated successfully!</div>";
            // Refresh asset data
            $asset = $assetManager->getFixedAssetById($asset_id);
        } else {
            $message = "<div class='alert alert-danger'>Failed to update asset. Please try again.</div>";
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fa fa-edit"></i> Edit Fixed Asset</h6>
                    <a href="asset_list.php" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <?php if ($asset): ?>
                    <!-- Asset Information Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Asset Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Asset Code:</strong> <?php echo $asset['asset_code']; ?></p>
                                    <p><strong>Purchase Date:</strong> <?php echo date('d/m/Y', strtotime($asset['purchase_date'])); ?></p>
                                    <p><strong>Purchase Amount:</strong> ₹<?php echo number_format($asset['purchase_amount'], 2); ?></p>
                                    <p><strong>Current Book Value:</strong> ₹<?php echo number_format($asset['current_book_value'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Depreciation Status</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Depreciation Rate:</strong> <?php echo $asset['depreciation_rate']; ?>% per year</p>
                                    <p><strong>Accumulated Depreciation:</strong> ₹<?php echo number_format($asset['accumulated_depreciation'], 2); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $asset['status'] == 'Active' ? 'success' : 'warning'; ?>">
                                            <?php echo $asset['status']; ?>
                                        </span>
                                    </p>
                                    <p><strong>Accounting:</strong> 
                                        <span class="badge bg-<?php echo $asset['accounting_status'] == 'Posted' ? 'success' : 'warning'; ?>">
                                            <?php echo $asset['accounting_status']; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <form method="POST" action="">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Editable Fields</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="asset_name" class="form-label">Asset Name *</label>
                                            <input type="text" class="form-control" id="asset_name" name="asset_name" 
                                                   value="<?php echo htmlspecialchars($asset['asset_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="location" name="location" 
                                                   value="<?php echo htmlspecialchars($asset['location']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="depreciation_rate" class="form-label">Depreciation Rate (%) *</label>
                                            <input type="number" step="0.01" class="form-control" id="depreciation_rate" 
                                                   name="depreciation_rate" value="<?php echo $asset['depreciation_rate']; ?>" required>
                                            <small class="form-text text-muted">Changes will affect future depreciation calculations</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <input type="text" class="form-control" value="<?php echo $asset['category']; ?>" readonly>
                                            <small class="form-text text-muted">Category cannot be changed after creation</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="4"><?php echo htmlspecialchars($asset['remarks']); ?></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fa fa-save"></i> Update Asset
                                        </button>
                                        <a href="asset_list.php" class="btn btn-secondary">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                    <div>
                                        <?php if ($asset['status'] == 'Active'): ?>
                                        <button type="button" class="btn btn-danger" onclick="showDisposeModal()">
                                            <i class="fa fa-trash"></i> Dispose Asset
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Read-Only Information -->
                    <div class="card mt-4">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">Additional Information (Read-Only)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Vendor:</strong> <?php echo $asset['vendor_name'] ?: 'Not specified'; ?></p>
                                    <p><strong>Invoice Number:</strong> <?php echo $asset['invoice_number'] ?: 'Not specified'; ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Warranty Period:</strong> <?php echo $asset['warranty_period']; ?> years</p>
                                    <p><strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($asset['created_at'])); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Last Updated:</strong> <?php echo date('d/m/Y H:i', strtotime($asset['updated_at'])); ?></p>
                                    <p><strong>Asset Age:</strong> 
                                        <?php 
                                        $purchase_date = new DateTime($asset['purchase_date']);
                                        $current_date = new DateTime();
                                        $age = $current_date->diff($purchase_date);
                                        echo $age->y . ' years, ' . $age->m . ' months';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dispose Asset Modal -->
<div class="modal fade" id="disposeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dispose Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="dispose_asset.php">
                <div class="modal-body">
                    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This action will permanently dispose the asset and create accounting entries.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asset:</label>
                        <input type="text" class="form-control" value="<?php echo $asset['asset_name'] ?? ''; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="disposal_date" class="form-label">Disposal Date:</label>
                        <input type="date" class="form-control" name="disposal_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="disposal_amount" class="form-label">Disposal Amount:</label>
                        <input type="number" step="0.01" class="form-control" name="disposal_amount" value="0" required>
                        <small class="form-text text-muted">Enter 0 if scrapped with no sale value</small>
                    </div>
                    <div class="mb-3">
                        <label for="disposal_type" class="form-label">Disposal Type:</label>
                        <select name="disposal_type" class="form-control" required>
                            <option value="Disposed">Disposed (Sold)</option>
                            <option value="Scrapped">Scrapped (No value)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="disposal_remarks" class="form-label">Disposal Remarks:</label>
                        <textarea name="disposal_remarks" class="form-control" rows="3" 
                                  placeholder="Reason for disposal, buyer details, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Dispose Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDisposeModal() {
    new bootstrap.Modal(document.getElementById('disposeModal')).show();
}
</script>

<?php include_once "inc/footer.php"; ?>