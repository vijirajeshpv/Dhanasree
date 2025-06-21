<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();

// Handle session messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = "";
if (isset($_SESSION['asset_message'])) {
    $msg_parts = explode('|', $_SESSION['asset_message']);
    if (count($msg_parts) >= 2) {
        $msg_type = $msg_parts[0];
        $msg_text = $msg_parts[1];
        $alert_class = ($msg_type == 'success') ? 'alert-success' : 'alert-danger';
        $message = "<div class='alert $alert_class alert-dismissible fade show'>
                      $msg_text
                      <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
    unset($_SESSION['asset_message']);
}

// Handle filters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Active';

// Get assets based on filters
$assets = $assetManager->getFixedAssets($category_filter ?: null, $status_filter);

// Get summary data
$total_value = $assetManager->getTotalAssetValue();
$asset_summary = $assetManager->getAssetSummaryByCategory();

// Count assets by status for tabs
$active_count = 0;
$disposed_count = 0;
$scrapped_count = 0;

$count_query = "SELECT status, COUNT(*) as count FROM tbl_fixed_assets GROUP BY status";
$count_result = $assetManager->dbcon()->query($count_query);
if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        switch ($row['status']) {
            case 'Active': $active_count = $row['count']; break;
            case 'Disposed': $disposed_count = $row['count']; break;
            case 'Scrapped': $scrapped_count = $row['count']; break;
        }
    }
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Fixed Assets Management</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Asset List</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="add_fixed_asset.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add New Asset
                    </a>
                    <a href="depreciation_management.php" class="btn btn-warning">
                        <i class="fa fa-calendar"></i> Depreciation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
    <div class="row mb-3">
        <div class="col-12">
            <?php echo $message; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <?php if ($total_value): ?>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Cost</h6>
                            <h4 class="mb-0">₹<?php echo number_format($total_value['total_cost'], 0); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-rupee fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Depreciation</h6>
                            <h4 class="mb-0">₹<?php echo number_format($total_value['total_depreciation'], 0); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-chart-line-down fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Book Value</h6>
                            <h4 class="mb-0">₹<?php echo number_format($total_value['total_book_value'], 0); ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-chart-bar fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Assets</h6>
                            <h4 class="mb-0"><?php echo $active_count + $disposed_count + $scrapped_count; ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-boxes fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Asset List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="card-title mb-0">
                                <i class="fa fa-list"></i> Asset List
                                <?php if ($category_filter): ?>
                                    - <?php echo htmlspecialchars($category_filter); ?>
                                <?php endif; ?>
                            </h6>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="toggleFilters()" id="filterToggleBtn">
                                <i class="fa fa-filter"></i> Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Tabs (Alternative to dropdown) -->
                <div class="card-body border-bottom">
                    <div class="row align-items-center">
                        <div class="col">
                            <ul class="nav nav-pills nav-sm">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $status_filter == 'Active' ? 'active' : ''; ?>" 
                                       href="?status=Active<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">
                                        Active
                                        <?php if ($active_count > 0): ?>
                                            <span class="badge bg-light text-dark ms-1"><?php echo $active_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $status_filter == 'Disposed' ? 'active' : ''; ?>" 
                                       href="?status=Disposed<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">
                                        Disposed
                                        <?php if ($disposed_count > 0): ?>
                                            <span class="badge bg-light text-dark ms-1"><?php echo $disposed_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $status_filter == 'Scrapped' ? 'active' : ''; ?>" 
                                       href="?status=Scrapped<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">
                                        Scrapped
                                        <?php if ($scrapped_count > 0): ?>
                                            <span class="badge bg-light text-dark ms-1"><?php echo $scrapped_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="toggleFilters()" id="filterToggleBtn">
                                <i class="fa fa-filter"></i> More Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (Show/Hide with JavaScript) -->
                <div id="filterSection" style="display: none;">
                    <div class="card-body border-bottom bg-light">
                        <form method="GET" action="" class="row g-3">
                            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    <option value="Furniture" <?php echo $category_filter == 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                                    <option value="Fixtures" <?php echo $category_filter == 'Fixtures' ? 'selected' : ''; ?>>Fixtures</option>
                                    <option value="Equipment" <?php echo $category_filter == 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                                    <option value="Electronics" <?php echo $category_filter == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Others" <?php echo $category_filter == 'Others' ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa fa-search"></i> Apply Category Filter
                                    </button>
                                    <a href="?status=<?php echo $status_filter; ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="fa fa-times"></i> Clear Category
                                    </a>
                                    <a href="asset_reports.php" class="btn btn-outline-success btn-sm">
                                        <i class="fa fa-chart-bar"></i> Reports
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Asset Table -->
                <div class="card-body p-0">
                    <?php if ($assets && $assets->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Asset</th>
                                    <th class="border-0">Category</th>
                                    <th class="border-0">Purchase Info</th>
                                    <th class="border-0">Financial</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($asset = $assets->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                            <small class="text-muted">
                                                <i class="fa fa-barcode"></i> <?php echo $asset['asset_code']; ?>
                                                <?php if ($asset['location']): ?>
                                                    | <i class="fa fa-map-marker-alt"></i> <?php echo htmlspecialchars($asset['location']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $asset['category']; ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($asset['purchase_date'])); ?></div>
                                            <div><strong>Cost:</strong> ₹<?php echo number_format($asset['purchase_amount'], 0); ?></div>
                                            <div><strong>Rate:</strong> <?php echo $asset['depreciation_rate']; ?>%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Depreciation:</strong> ₹<?php echo number_format($asset['accumulated_depreciation'], 0); ?></div>
                                            <div><strong>Book Value:</strong> 
                                                <span class="fw-bold text-success">₹<?php echo number_format($asset['current_book_value'], 0); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php echo $asset['status'] == 'Active' ? 'success' : ($asset['status'] == 'Disposed' ? 'warning' : 'danger'); ?>">
                                                <?php echo $asset['status']; ?>
                                            </span>
                                            <small class="badge bg-<?php echo $asset['accounting_status'] == 'Posted' ? 'success' : 'secondary'; ?>">
                                                <?php echo $asset['accounting_status']; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewAsset(<?php echo $asset['id']; ?>)" 
                                                    title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php if ($asset['status'] == 'Active'): ?>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="editAsset(<?php echo $asset['id']; ?>)" 
                                                    title="Edit Asset">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="disposeAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_name'], ENT_QUOTES); ?>')" 
                                                    title="Dispose Asset">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fa fa-inbox fa-3x text-muted"></i>
                        </div>
                        <h5 class="text-muted">No Assets Found</h5>
                        <p class="text-muted mb-4">
                            <?php if ($category_filter || $status_filter != 'Active'): ?>
                                No assets match your current filters.
                            <?php else: ?>
                                You haven't added any assets yet.
                            <?php endif; ?>
                        </p>
                        <div>
                            <?php if ($category_filter || $status_filter != 'Active'): ?>
                                <a href="asset_list.php" class="btn btn-outline-primary me-2">Clear Filters</a>
                            <?php endif; ?>
                            <a href="add_fixed_asset.php" class="btn btn-primary">Add Your First Asset</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asset Details Modal -->
<div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetModalLabel">
                    <i class="fa fa-info-circle"></i> Asset Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="assetModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading asset details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Dispose Asset Modal -->
<div class="modal fade" id="disposeModal" tabindex="-1" aria-labelledby="disposeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="disposeModalLabel">
                    <i class="fa fa-exclamation-triangle"></i> Dispose Asset
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="dispose_asset.php" id="disposeAssetForm">
                <div class="modal-body">
                    <input type="hidden" id="dispose_asset_id" name="asset_id">
                    <input type="hidden" name="redirect" value="asset_list.php">
                    
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action will permanently dispose the asset and create accounting entries. This cannot be undone.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Asset Name</label>
                        <input type="text" class="form-control" id="dispose_asset_name" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="disposal_date" class="form-label">Disposal Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="disposal_date" name="disposal_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="disposal_amount" class="form-label">Disposal Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="disposal_amount" name="disposal_amount" required>
                                </div>
                                <small class="form-text text-muted">Enter 0 if scrapped with no sale value</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="disposal_type" class="form-label">Disposal Type <span class="text-danger">*</span></label>
                        <select id="disposal_type" name="disposal_type" class="form-select" required>
                            <option value="">Select disposal type</option>
                            <option value="Disposed">Disposed (Sold/Transferred)</option>
                            <option value="Scrapped">Scrapped (No salvage value)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="disposal_remarks" class="form-label">Disposal Remarks</label>
                        <textarea id="disposal_remarks" name="disposal_remarks" class="form-control" rows="3" 
                                  placeholder="Enter reason for disposal, buyer details, condition, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash"></i> Dispose Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle filters section
function toggleFilters() {
    const filterSection = document.getElementById('filterSection');
    const toggleBtn = document.getElementById('filterToggleBtn');
    
    if (filterSection.style.display === 'none' || filterSection.style.display === '') {
        filterSection.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fa fa-times"></i> Hide Filters';
        toggleBtn.classList.remove('btn-outline-secondary');
        toggleBtn.classList.add('btn-secondary');
    } else {
        filterSection.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fa fa-filter"></i> More Filters';
        toggleBtn.classList.remove('btn-secondary');
        toggleBtn.classList.add('btn-outline-secondary');
    }
}

// Show filters if any filter is applied
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilters = urlParams.get('category') || urlParams.get('status') !== 'Active';
    
    if (hasFilters) {
        toggleFilters();
    }
});

function viewAsset(assetId) {
    // Reset modal content
    document.getElementById('assetModalBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading asset details...</p>
        </div>
    `;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('assetModal'));
    modal.show();
    
    // Load asset details via AJAX
    fetch('view_asset_ajax.php?id=' + assetId)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(data => {
            document.getElementById('assetModalBody').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('assetModalBody').innerHTML = 
                `<div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    Error loading asset details. Please try again.
                </div>`;
        });
}

function editAsset(assetId) {
    window.location.href = 'edit_asset.php?id=' + assetId;
}

function disposeAsset(assetId, assetName) {
    // Reset and populate form
    document.getElementById('dispose_asset_id').value = assetId;
    document.getElementById('dispose_asset_name').value = assetName;
    document.getElementById('disposal_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('disposal_amount').value = '0';
    document.getElementById('disposal_type').value = '';
    document.getElementById('disposal_remarks').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('disposeModal'));
    modal.show();
}

// Form validation
document.getElementById('disposeAssetForm').addEventListener('submit', function(e) {
    const amount = document.getElementById('disposal_amount').value;
    const type = document.getElementById('disposal_type').value;
    
    if (!type) {
        e.preventDefault();
        alert('Please select a disposal type.');
        return false;
    }
    
    if (amount < 0) {
        e.preventDefault();
        alert('Disposal amount cannot be negative.');
        return false;
    }
    
    // Confirm disposal
    if (!confirm('Are you sure you want to dispose this asset? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>

<style>
.opacity-75 { opacity: 0.75; }
.card-title { color: #495057; }
.btn-group-sm > .btn { padding: 0.25rem 0.5rem; }
.badge { font-size: 0.75em; }
</style>

<?php include_once "inc/footer.php"; ?>