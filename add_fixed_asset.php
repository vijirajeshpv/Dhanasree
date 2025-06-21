<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = $assetManager->addFixedAsset($_POST);
    
    if ($result) {
        $message = "<div class='alert alert-success'>Fixed asset added successfully! Asset ID: $result</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to add fixed asset. Please try again.</div>";
    }
}

// Get existing assets for display
$assets = $assetManager->getFixedAssets();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa fa-plus"></i> Add Fixed Asset</h6>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_name" class="form-label">Asset Name *</label>
                                    <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Furniture">Furniture</option>
                                        <option value="Fixtures">Fixtures</option>
                                        <option value="Equipment">Equipment</option>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date *</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_amount" class="form-label">Purchase Amount *</label>
                                    <input type="number" step="0.01" class="form-control" id="purchase_amount" name="purchase_amount" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="depreciation_rate" class="form-label">Depreciation Rate (%) *</label>
                                    <input type="number" step="0.01" class="form-control" id="depreciation_rate" name="depreciation_rate" value="10.00" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_name" class="form-label">Vendor Name</label>
                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_number" class="form-label">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_number" name="invoice_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="warranty_period" class="form-label">Warranty Period (Years)</label>
                                    <input type="number" class="form-control" id="warranty_period" name="warranty_period" value="0">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Add Asset
                            </button>
                            <a href="asset_list.php" class="btn btn-secondary">
                                <i class="fa fa-list"></i> View Assets
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Current Assets Summary</h6>
                </div>
                <div class="card-body">
                    <?php if ($assets && $assets->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Purchase Date</th>
                                    <th>Cost</th>
                                    <th>Book Value</th>
                                    <th>Status</th>
                                    <th>Accounting</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($asset = $assets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asset['asset_code']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['category']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($asset['purchase_date'])); ?></td>
                                    <td>₹<?php echo number_format($asset['purchase_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($asset['current_book_value'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $asset['status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                            <?php echo $asset['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $asset['accounting_status'] == 'Posted' ? 'success' : 'warning'; ?>">
                                            <?php echo $asset['accounting_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No fixed assets found. Add your first asset using the form above.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set today's date as default for purchase date
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('purchase_date').value = new Date().toISOString().split('T')[0];
});
</script>

<?php include_once "inc/footer.php"; ?>