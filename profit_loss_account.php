<?php
// profit_loss_account.php - FIXED VERSION
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/PLAccountManager.php";
require('fpdf186/fpdf.php');

$accountingReports = new PLAccountManager();

// Initialize variables with proper defaults
$start_date = '';
$end_date = '';
$export_format = '';
$pl_data = null;
$errorMessage = '';

// Handle form submission and parameters
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Check for export format first
    $export_format = isset($_GET['export']) ? $_GET['export'] : '';
    
    // Get date parameters - with proper validation
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        
        // Validate dates
        if (empty($start_date) || empty($end_date)) {
            $errorMessage = "Please select both start and end dates.";
        } elseif (strtotime($start_date) > strtotime($end_date)) {
            $errorMessage = "Start date cannot be later than end date.";
        } else {
            // Generate P&L data
            $pl_data = $accountingReports->getProfitLossAccount($start_date, $end_date);
        }
    } else {
        // Set default dates (current month)
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        
        // Generate P&L data with defaults
        $pl_data = $accountingReports->getProfitLossAccount($start_date, $end_date);
    }
}

// Handle PDF Export
if ($export_format == 'pdf' && $pl_data) {
    ob_end_clean();
    
    class PLAccountPDF extends FPDF {
        private $startDate, $endDate;
        
        function __construct($start_date, $end_date) {
            parent::__construct();
            $this->startDate = $start_date;
            $this->endDate = $end_date;
        }
        
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'PROFIT & LOSS ACCOUNT', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'For the period from ' . date('d/m/Y', strtotime($this->startDate)) . ' to ' . date('d/m/Y', strtotime($this->endDate)), 0, 1, 'C');
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-25);
            $this->SetFont('Arial', '', 8);
            $this->Cell(95, 5, 'Place: Irinjalakuda', 0, 0, 'L');
            $this->Cell(0, 5, 'As per our report even date attached', 0, 1, 'R');
            $this->Cell(95, 5, 'Date: ' . date('d/m/Y'), 0, 1, 'L');
        }
        
        function PLTable($pl_data) {
            // Set up the table structure with proper widths that fit in page
            $this->SetFont('Arial', 'B', 10);
            
            // Table headers - adjusted widths to fit page (210mm page, 20mm margins = 170mm available)
            $this->Cell(70, 8, 'PARTICULARS', 1, 0, 'C');
            $this->Cell(25, 8, 'AMOUNT', 1, 0, 'C');
            $this->Cell(5, 8, '', 0, 0); // Gap between tables
            $this->Cell(70, 8, 'PARTICULARS', 1, 0, 'C');
            $this->Cell(25, 8, 'AMOUNT', 1, 1, 'C');
            
            $this->SetFont('Arial', '', 9);
            
            // Prepare expense and income arrays
            $expenses = $pl_data['expenses'];
            $income = $pl_data['income'];
            
            // Add Net Profit to expenses side if profit
            if ($pl_data['net_profit'] > 0) {
                $expenses[] = [
                    'account_name' => 'Net Profit',
                    'amount' => $pl_data['net_profit']
                ];
            }
            
            // Add Net Loss to income side if loss
            if ($pl_data['net_profit'] < 0) {
                $income[] = [
                    'account_name' => 'Net Loss',
                    'amount' => abs($pl_data['net_profit'])
                ];
            }
            
            $max_rows = max(count($expenses), count($income));
            
            // Display items
            for ($i = 0; $i < $max_rows; $i++) {
                // Expenses (Left side)
                if ($i < count($expenses)) {
                    $this->Cell(70, 6, $expenses[$i]['account_name'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($expenses[$i]['amount'], 2), 1, 0, 'R');
                } else {
                    $this->Cell(70, 6, '', 1, 0);
                    $this->Cell(25, 6, '', 1, 0);
                }
                
                $this->Cell(5, 6, '', 0, 0); // Gap
                
                // Income (Right side)
                if ($i < count($income)) {
                    $this->Cell(70, 6, $income[$i]['account_name'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($income[$i]['amount'], 2), 1, 1, 'R');
                } else {
                    $this->Cell(70, 6, '', 1, 0);
                    $this->Cell(25, 6, '', 1, 1);
                }
            }
            
            // Total row
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(70, 8, 'Total', 1, 0, 'C');
            $this->Cell(25, 8, number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2), 1, 0, 'R');
            $this->Cell(5, 8, '', 0, 0);
            $this->Cell(70, 8, 'Total', 1, 0, 'C');
            $this->Cell(25, 8, number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2), 1, 1, 'R');
        }
    }
    
    $pdf = new PLAccountPDF($start_date, $end_date);
    $pdf->AddPage();
    $pdf->PLTable($pl_data);
    $pdf->Output('D', 'Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.pdf');
    exit;
}

// Handle Excel Export
if ($export_format == 'excel' && $pl_data) {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.xls"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table width="100%" style="border-collapse: collapse;">';
    echo '<tr><td colspan="4" style="text-align:center; font-weight:bold; font-size:14px;">JAYALAKSHMI ENTERPRISES</td></tr>';
    echo '<tr><td colspan="4" style="text-align:center; font-weight:bold; font-size:12px;">PROFIT & LOSS ACCOUNT</td></tr>';
    echo '<tr><td colspan="4" style="text-align:center;">For the period from ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)) . '</td></tr>';
    echo '<tr><td colspan="4">&nbsp;</td></tr>';
    
    // Column headers
    echo '<tr>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">AMOUNT</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">AMOUNT</td>';
    echo '</tr>';
    
    // Prepare data
    $expenses = $pl_data['expenses'];
    $income = $pl_data['income'];
    
    // Add Net Profit to expenses side if profit
    if ($pl_data['net_profit'] > 0) {
        $expenses[] = [
            'account_name' => 'Net Profit',
            'amount' => $pl_data['net_profit']
        ];
    }
    
    // Add Net Loss to income side if loss
    if ($pl_data['net_profit'] < 0) {
        $income[] = [
            'account_name' => 'Net Loss',
            'amount' => abs($pl_data['net_profit'])
        ];
    }
    
    $max_rows = max(count($expenses), count($income));
    
    // Data rows
    for ($i = 0; $i < $max_rows; $i++) {
        echo '<tr>';
        
        // Expenses (Left side)
        if ($i < count($expenses)) {
            echo '<td style="border:1px solid black; width:25%;">' . htmlspecialchars($expenses[$i]['account_name']) . '</td>';
            echo '<td style="border:1px solid black; text-align:right; width:25%;">' . number_format($expenses[$i]['amount'], 2) . '</td>';
        } else {
            echo '<td style="border:1px solid black; width:25%;">&nbsp;</td>';
            echo '<td style="border:1px solid black; width:25%;">&nbsp;</td>';
        }
        
        // Income (Right side)
        if ($i < count($income)) {
            echo '<td style="border:1px solid black; width:25%;">' . htmlspecialchars($income[$i]['account_name']) . '</td>';
            echo '<td style="border:1px solid black; text-align:right; width:25%;">' . number_format($income[$i]['amount'], 2) . '</td>';
        } else {
            echo '<td style="border:1px solid black; width:25%;">&nbsp;</td>';
            echo '<td style="border:1px solid black; width:25%;">&nbsp;</td>';
        }
        
        echo '</tr>';
    }
    
    // Total row
    echo '<tr>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; width:25%;">Total</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:right; width:25%;">' . number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2) . '</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; width:25%;">Total</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:right; width:25%;">' . number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2) . '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Footer
    echo '<br><br>';
    echo '<table width="100%">';
    echo '<tr>';
    echo '<td style="width:50%;">Place: Irinjalakuda</td>';
    echo '<td style="width:50%; text-align:right;">As per our report even date attached</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Date: ' . date('d/m/Y') . '</td>';
    echo '<td>&nbsp;</td>';
    echo '</tr>';
    echo '</table>';
    
    echo '</body>';
    echo '</html>';
    exit;
}

// Clear output buffer for normal page display
ob_end_flush();
?>

<style>
.pl-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.filter-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.pl-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pl-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
}

.summary-cards {
    margin: 20px 0;
}

.pl-table {
    font-size: 0.9rem;
}

.pl-table th {
    background: #343a40;
    color: white;
    font-weight: 600;
    border: none;
    text-align: center;
}

.pl-table .expenses-col {
    background: #fff5f5;
}

.pl-table .income-col {
    background: #f0fff4;
}

.total-row {
    background: #f8f9fa !important;
    font-weight: bold;
}

.net-profit {
    background: #d4edda !important;
    color: #155724;
    font-weight: bold;
}

.net-loss {
    background: #f8d7da !important;
    color: #721c24;
    font-weight: bold;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .pl-container {
        background: white;
        padding: 0;
    }
    
    .filter-card {
        display: none;
    }
}
</style>

<div class="pl-container">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fa fa-chart-line"></i> Profit & Loss Account</h2>
                <p class="text-muted">View comprehensive profit and loss statement with income and expense analysis</p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card no-print">
            <div class="card-body">
                <h5 class="card-title mb-3">Filter Options</h5>
                
                <!-- Date Selection Form -->
                <form method="GET" id="plForm">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="btn-group d-grid" role="group">
                                <?php if ($pl_data && !$errorMessage): ?>
                                <a href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&export=pdf" 
                                   class="btn btn-danger">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </a>
                                <a href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&export=excel" 
                                   class="btn btn-success">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fa fa-file-pdf"></i> PDF
                                </button>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fa fa-file-excel"></i> Excel
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Date Filters -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('thisMonth')">This Month</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('lastMonth')">Last Month</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('thisYear')">This Year</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('lastYear')">Last Year</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Error Message -->
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php endif; ?>

        <!-- P&L Report -->
        <?php if ($pl_data && !$errorMessage): ?>
        <div class="pl-card">
            <div class="pl-header">
                <h4 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Profit & Loss Account
                </h4>
                <p class="mb-0">For the period from <?php echo date('d/m/Y', strtotime($start_date)); ?> to <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
            </div>
            
            <div class="card-body">
                <!-- P&L Summary Cards -->
                <div class="summary-cards">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="text-success">Total Income</h6>
                                    <h4 class="text-success">₹<?php echo number_format($pl_data['total_income'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h6 class="text-danger">Total Expenses</h6>
                                    <h4 class="text-danger">₹<?php echo number_format($pl_data['total_expenses'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-<?php echo $pl_data['net_profit'] >= 0 ? 'success' : 'warning'; ?>">
                                <div class="card-body text-center">
                                    <h6 class="text-<?php echo $pl_data['net_profit'] >= 0 ? 'success' : 'warning'; ?>">
                                        Net <?php echo $pl_data['net_profit'] >= 0 ? 'Profit' : 'Loss'; ?>
                                    </h6>
                                    <h4 class="text-<?php echo $pl_data['net_profit'] >= 0 ? 'success' : 'warning'; ?>">
                                        ₹<?php echo number_format(abs($pl_data['net_profit']), 2); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h6 class="text-info">Profit Margin</h6>
                                    <h4 class="text-info">
                                        <?php 
                                        $margin = $pl_data['total_income'] > 0 ? ($pl_data['net_profit'] / $pl_data['total_income']) * 100 : 0;
                                        echo number_format($margin, 2); 
                                        ?>%
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- P&L Table -->
                <div class="table-responsive">
                    <table class="table table-bordered pl-table">
                        <thead>
                            <tr>
                                <th width="25%">EXPENSES</th>
                                <th width="25%">AMOUNT (₹)</th>
                                <th width="25%">INCOME</th>
                                <th width="25%">AMOUNT (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $expenses = $pl_data['expenses'];
                            $income = $pl_data['income'];
                            
                            // Add Net Profit to expenses side if profit
                            if ($pl_data['net_profit'] > 0) {
                                $expenses[] = [
                                    'account_name' => 'Net Profit',
                                    'amount' => $pl_data['net_profit']
                                ];
                            }
                            
                            // Add Net Loss to income side if loss
                            if ($pl_data['net_profit'] < 0) {
                                $income[] = [
                                    'account_name' => 'Net Loss',
                                    'amount' => abs($pl_data['net_profit'])
                                ];
                            }
                            
                            $max_rows = max(count($expenses), count($income));
                            
                            for ($i = 0; $i < $max_rows; $i++):
                                $isNetRow = false;
                                if ($i < count($expenses) && in_array($expenses[$i]['account_name'], ['Net Profit', 'Net Loss'])) {
                                    $isNetRow = true;
                                }
                                if ($i < count($income) && in_array($income[$i]['account_name'], ['Net Profit', 'Net Loss'])) {
                                    $isNetRow = true;
                                }
                                
                                $rowClass = $isNetRow ? ($pl_data['net_profit'] >= 0 ? 'net-profit' : 'net-loss') : '';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <!-- Expenses (Left side) -->
                                <td class="expenses-col">
                                    <?php echo $i < count($expenses) ? htmlspecialchars($expenses[$i]['account_name']) : '&nbsp;'; ?>
                                </td>
                                <td class="expenses-col text-end">
                                    <?php echo $i < count($expenses) ? number_format($expenses[$i]['amount'], 2) : '&nbsp;'; ?>
                                </td>
                                
                                <!-- Income (Right side) -->
                                <td class="income-col">
                                    <?php echo $i < count($income) ? htmlspecialchars($income[$i]['account_name']) : '&nbsp;'; ?>
                                </td>
                                <td class="income-col text-end">
                                    <?php echo $i < count($income) ? number_format($income[$i]['amount'], 2) : '&nbsp;'; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                            
                            <!-- Total row -->
                            <tr class="total-row">
                                <td class="text-center"><strong>TOTAL</strong></td>
                                <td class="text-end"><strong><?php echo number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2); ?></strong></td>
                                <td class="text-center"><strong>TOTAL</strong></td>
                                <td class="text-end"><strong><?php echo number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Quick date range functions
function setDateRange(range) {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    const today = new Date();
    
    switch(range) {
        case 'today':
            const todayStr = today.toISOString().split('T')[0];
            startDate.value = todayStr;
            endDate.value = todayStr;
            break;
            
        case 'thisMonth':
            const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            const thisMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            startDate.value = thisMonthStart.toISOString().split('T')[0];
            endDate.value = thisMonthEnd.toISOString().split('T')[0];
            break;
            
        case 'lastMonth':
            const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            startDate.value = lastMonthStart.toISOString().split('T')[0];
            endDate.value = lastMonthEnd.toISOString().split('T')[0];
            break;
            
        case 'thisYear':
            const thisYearStart = new Date(today.getFullYear(), 0, 1);
            const thisYearEnd = new Date(today.getFullYear(), 11, 31);
            startDate.value = thisYearStart.toISOString().split('T')[0];
            endDate.value = thisYearEnd.toISOString().split('T')[0];
            break;
            
        case 'lastYear':
            const lastYearStart = new Date(today.getFullYear() - 1, 0, 1);
            const lastYearEnd = new Date(today.getFullYear() - 1, 11, 31);
            startDate.value = lastYearStart.toISOString().split('T')[0];
            endDate.value = lastYearEnd.toISOString().split('T')[0];
            break;
    }
}

// Form validation
document.getElementById('plForm').addEventListener('submit', function(e) {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    if (!startDate || !endDate) {
        e.preventDefault();
        alert('Please select both start and end dates.');
        return false;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        e.preventDefault();
        alert('Start date cannot be later than end date.');
        return false;
    }
});
</script>

<?php include_once "inc/footer.php"; ?>