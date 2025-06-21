<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/FixedAssetManager.php";
require('fpdf186/fpdf.php');

$assetManager = new FixedAssetManager();

// Handle report generation
$report_type = isset($_GET['report']) ? $_GET['report'] : 'summary';
$export_format = isset($_GET['export']) ? $_GET['export'] : '';

// Date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get data based on report type
switch ($report_type) {
    case 'summary':
        $report_data = $assetManager->getAssetSummaryByCategory();
        $report_title = "Asset Summary by Category";
        break;
    case 'depreciation':
        $report_data = $assetManager->getDepreciationSchedule(null, $start_date, $end_date);
        $report_title = "Depreciation Schedule Report";
        break;
    case 'detailed':
        $report_data = $assetManager->getFixedAssets();
        $report_title = "Detailed Asset Register";
        break;
    case 'maintenance':
        $report_data = $assetManager->getAssetsRequiringMaintenance();
        $report_title = "Assets Requiring Maintenance";
        break;
    default:
        $report_data = $assetManager->getAssetSummaryByCategory();
        $report_title = "Asset Summary by Category";
}

// Handle PDF export
if ($export_format == 'pdf') {
    ob_end_clean();

    class AssetReportPDF extends FPDF {
        private $reportTitle;
        
        function __construct($title) {
            parent::__construct();
            $this->reportTitle = $title;
        }
        
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, $this->reportTitle, 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'Generated on: ' . date('d/m/Y H:i'), 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new AssetReportPDF($report_title);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // Generate different PDF layouts based on report type
    if ($report_type == 'summary' && $report_data) {
        // Summary report
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 8, 'Category', 1);
        $pdf->Cell(25, 8, 'Count', 1);
        $pdf->Cell(35, 8, 'Total Cost', 1);
        $pdf->Cell(35, 8, 'Depreciation', 1);
        $pdf->Cell(35, 8, 'Book Value', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);
        while ($row = $report_data->fetch_assoc()) {
            $pdf->Cell(50, 6, $row['category'], 1);
            $pdf->Cell(25, 6, $row['asset_count'], 1, 0, 'C');
            $pdf->Cell(35, 6, number_format($row['total_cost'], 2), 1, 0, 'R');
            $pdf->Cell(35, 6, number_format($row['total_depreciation'], 2), 1, 0, 'R');
            $pdf->Cell(35, 6, number_format($row['total_book_value'], 2), 1, 0, 'R');
            $pdf->Ln();
        }
    } elseif ($report_type == 'detailed' && $report_data) {
        // Detailed asset register
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 6, 'Asset Code', 1);
        $pdf->Cell(40, 6, 'Asset Name', 1);
        $pdf->Cell(20, 6, 'Category', 1);
        $pdf->Cell(25, 6, 'Purchase Date', 1);
        $pdf->Cell(30, 6, 'Cost', 1);
        $pdf->Cell(30, 6, 'Book Value', 1);
        $pdf->Cell(20, 6, 'Status', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 7);
        while ($row = $report_data->fetch_assoc()) {
            $pdf->Cell(25, 5, $row['asset_code'], 1);
            $pdf->Cell(40, 5, substr($row['asset_name'], 0, 20), 1);
            $pdf->Cell(20, 5, $row['category'], 1);
            $pdf->Cell(25, 5, date('d/m/Y', strtotime($row['purchase_date'])), 1);
            $pdf->Cell(30, 5, number_format($row['purchase_amount'], 2), 1, 0, 'R');
            $pdf->Cell(30, 5, number_format($row['current_book_value'], 2), 1, 0, 'R');
            $pdf->Cell(20, 5, $row['status'], 1);
            $pdf->Ln();
        }
    }

    $pdf->Output('D', 'Asset_Report_' . date('Y-m-d') . '.pdf');
    exit;
}

// Handle Excel export
if ($export_format == 'excel') {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Asset_Report_' . date('Y-m-d') . '.xls"');
    
    echo '<html><body>';
    echo '<h2>JAYALAKSHMI ENTERPRISES</h2>';
    echo '<h3>' . $report_title . '</h3>';
    echo '<p>Generated on: ' . date('d/m/Y H:i') . '</p>';
    
    if ($report_type == 'summary' && $report_data) {
        echo '<table border="1">';
        echo '<tr><th>Category</th><th>Asset Count</th><th>Total Cost</th><th>Total Depreciation</th><th>Book Value</th></tr>';
        while ($row = $report_data->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['category'] . '</td>';
            echo '<td>' . $row['asset_count'] . '</td>';
            echo '<td>' . number_format($row['total_cost'], 2) . '</td>';
            echo '<td>' . number_format($row['total_depreciation'], 2) . '</td>';
            echo '<td>' . number_format($row['total_book_value'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } elseif ($report_type == 'detailed' && $report_data) {
        echo '<table border="1">';
        echo '<tr><th>Asset Code</th><th>Asset Name</th><th>Category</th><th>Purchase Date</th><th>Cost</th><th>Book Value</th><th>Status</th></tr>';
        while ($row = $report_data->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['asset_code'] . '</td>';
            echo '<td>' . $row['asset_name'] . '</td>';
            echo '<td>' . $row['category'] . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($row['purchase_date'])) . '</td>';
            echo '<td>' . number_format($row['purchase_amount'], 2) . '</td>';
            echo '<td>' . number_format($row['current_book_value'], 2) . '</td>';
            echo '<td>' . $row['status'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}

// Get total values for dashboard
$total_values = $assetManager->getTotalAssetValue();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Asset Reports Dashboard</h6>
                </div>
                <div class="card-body">
                    <!-- Report Selection -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Report Type:</label>
                                <select name="report" class="form-control" onchange="toggleDateRange()">
                                    <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Asset Summary</option>
                                    <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Register</option>
                                    <option value="depreciation" <?php echo $report_type == 'depreciation' ? 'selected' : ''; ?>>Depreciation Schedule</option>
                                    <option value="maintenance" <?php echo $report_type == 'maintenance' ? 'selected' : ''; ?>>Maintenance Required</option>
                                </select>
                            </div>
                            <div class="col-md-2" id="date-range" style="display: <?php echo $report_type == 'depreciation' ? 'block' : 'none'; ?>;">
                                <label class="form-label">Start Date:</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-2" id="date-range-end" style="display: <?php echo $report_type == 'depreciation' ? 'block' : 'none'; ?>;">
                                <label class="form-label">End Date:</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-info me-2">
                                    <i class="fa fa-search"></i> Generate Report
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fa fa-download"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?report=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=pdf">
                                            <i class="fa fa-file-pdf"></i> PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="?report=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=excel">
                                            <i class="fa fa-file-excel"></i> Excel
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Asset Overview Cards -->
                    <?php if ($total_values): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>₹<?php echo number_format($total_values['total_cost'], 0); ?></h4>
                                    <p class="mb-0">Total Asset Cost</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>₹<?php echo number_format($total_values['total_depreciation'], 0); ?></h4>
                                    <p class="mb-0">Total Depreciation</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>₹<?php echo number_format($total_values['total_book_value'], 0); ?></h4>
                                    <p class="mb-0">Current Book Value</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4><?php echo $total_values['total_cost'] > 0 ? round(($total_values['total_depreciation'] / $total_values['total_cost']) * 100, 1) : 0; ?>%</h4>
                                    <p class="mb-0">Depreciation Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><?php echo $report_title; ?></h6>
                </div>
                <div class="card-body">
                    <?php if ($report_data && $report_data->num_rows > 0): ?>
                    
                    <?php if ($report_type == 'summary'): ?>
                    <!-- Asset Summary Report -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Category</th>
                                    <th>Asset Count</th>
                                    <th>Total Cost</th>
                                    <th>Total Depreciation</th>
                                    <th>Current Book Value</th>
                                    <th>Avg. Depreciation Rate</th>
                                    <th>Posted Assets</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $row['category']; ?></strong></td>
                                    <td><?php echo $row['asset_count']; ?></td>
                                    <td>₹<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['total_depreciation'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['total_book_value'], 2); ?></td>
                                    <td><?php echo isset($row['avg_depreciation_rate']) ? $row['avg_depreciation_rate'] : '0'; ?>%</td>
                                    <td><?php echo isset($row['posted_assets']) ? $row['posted_assets'] : '0'; ?>/<?php echo $row['asset_count']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php elseif ($report_type == 'detailed'): ?>
                    <!-- Detailed Asset Register -->
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Purchase Date</th>
                                    <th>Purchase Cost</th>
                                    <th>Depreciation Rate</th>
                                    <th>Accumulated Depreciation</th>
                                    <th>Book Value</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['asset_code']; ?></td>
                                    <td><?php echo $row['asset_name']; ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['purchase_date'])); ?></td>
                                    <td>₹<?php echo number_format($row['purchase_amount'], 2); ?></td>
                                    <td><?php echo $row['depreciation_rate']; ?>%</td>
                                    <td>₹<?php echo number_format($row['accumulated_depreciation'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['current_book_value'], 2); ?></td>
                                    <td><?php echo $row['location'] ?: '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : 'warning'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php elseif ($report_type == 'depreciation'): ?>
                    <!-- Depreciation Schedule Report -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Depreciation Amount</th>
                                    <th>Accumulated Depreciation</th>
                                    <th>Book Value After</th>
                                    <th>Status</th>
                                    <th>Accounting Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['depreciation_date'])); ?></td>
                                    <td><?php echo $row['asset_code']; ?></td>
                                    <td><?php echo $row['asset_name']; ?></td>
                                    <td>₹<?php echo number_format($row['depreciation_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['accumulated_depreciation'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['book_value_after'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'Posted' ? 'success' : 'warning'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['accounting_ref'] ?: '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php elseif ($report_type == 'maintenance'): ?>
                    <!-- Maintenance Required Report -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Purchase Date</th>
                                    <th>Age (Days)</th>
                                    <th>Warranty Period</th>
                                    <th>Days Over Warranty</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['asset_code']; ?></td>
                                    <td><?php echo $row['asset_name']; ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['purchase_date'])); ?></td>
                                    <td><?php echo $row['days_since_purchase']; ?></td>
                                    <td><?php echo $row['warranty_period']; ?> years</td>
                                    <td><?php echo $row['days_since_purchase'] - $row['warranty_days']; ?></td>
                                    <td><?php echo $row['location'] ?: '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="alert alert-info">
                        <h5>No Data Found</h5>
                        <p>No data available for the selected report type and date range.</p>
                    </div>
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
                    <h6 class="mb-0">Quick Actions</h6>
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
                                <i class="fa fa-list"></i> Asset List
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="depreciation_management.php" class="btn btn-warning btn-block">
                                <i class="fa fa-calendar"></i> Manage Depreciation
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

<script>
function toggleDateRange() {
    const reportType = document.querySelector('select[name="report"]').value;
    const dateRange = document.getElementById('date-range');
    const dateRangeEnd = document.getElementById('date-range-end');
    
    if (reportType === 'depreciation') {
        dateRange.style.display = 'block';
        dateRangeEnd.style.display = 'block';
    } else {
        dateRange.style.display = 'none';
        dateRangeEnd.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDateRange();
});
</script>

<style>
.btn-block {
    width: 100%;
    margin-bottom: 10px;
}
</style>

<?php include_once "inc/footer.php"; ?>