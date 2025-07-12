<?php
// profit_loss_account.php - Updated with Financial Period Support
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/PLAccountManager.php";
include_once "components/accounting_filters.php";
require('fpdf186/fpdf.php');

$accountingReports = new PLAccountManager();

// Get filter parameters using the common component
$filterParams = getAccountingFilterParams();
$pl_data = null;

// Generate P&L data if we have valid parameters
if ($filterParams['has_data']) {
    if ($filterParams['filter_type'] == 'financial_period') {
        $pl_data = $accountingReports->getProfitLossAccountByPeriod($filterParams['financial_period_id']);
    } else {
        $pl_data = $accountingReports->getProfitLossAccount($filterParams['start_date'], $filterParams['end_date']);
    }
}

// Check for export requests
$export_format = isset($_GET['export']) ? $_GET['export'] : '';

// Handle PDF Export
if ($export_format == 'pdf' && $pl_data) {
    ob_end_clean();
    
    class PLAccountPDF extends FPDF {
        private $startDate, $endDate, $periodInfo;
        
        function __construct($start_date, $end_date, $period_info = null) {
            parent::__construct();
            $this->startDate = $start_date;
            $this->endDate = $end_date;
            $this->periodInfo = $period_info;
        }
        
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'PROFIT & LOSS ACCOUNT', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            
            if ($this->periodInfo) {
                $this->Cell(0, 6, 'For ' . $this->periodInfo['period_name'], 0, 1, 'C');
                $this->Cell(0, 6, '(' . date('d/m/Y', strtotime($this->startDate)) . ' to ' . date('d/m/Y', strtotime($this->endDate)) . ')', 0, 1, 'C');
            } else {
                $this->Cell(0, 6, 'For the period from ' . date('d/m/Y', strtotime($this->startDate)) . ' to ' . date('d/m/Y', strtotime($this->endDate)), 0, 1, 'C');
            }
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
            $this->SetFont('Arial', 'B', 10);
            
            // Table headers
            $this->Cell(70, 8, 'PARTICULARS', 1, 0, 'C');
            $this->Cell(25, 8, 'AMOUNT', 1, 0, 'C');
            $this->Cell(5, 8, '', 0, 0);
            $this->Cell(70, 8, 'PARTICULARS', 1, 0, 'C');
            $this->Cell(25, 8, 'AMOUNT', 1, 1, 'C');
            
            $this->SetFont('Arial', '', 9);
            
            $expenses = $pl_data['expenses'];
            $income = $pl_data['income'];
            
            // Add Net Profit/Loss to appropriate side
            if ($pl_data['net_profit'] > 0) {
                $expenses[] = ['account_name' => 'Net Profit', 'amount' => $pl_data['net_profit']];
            } elseif ($pl_data['net_profit'] < 0) {
                $income[] = ['account_name' => 'Net Loss', 'amount' => abs($pl_data['net_profit'])];
            }
            
            $max_rows = max(count($expenses), count($income));
            
            for ($i = 0; $i < $max_rows; $i++) {
                // Expenses (Left side)
                if ($i < count($expenses)) {
                    $this->Cell(70, 6, $expenses[$i]['account_name'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($expenses[$i]['amount'], 2), 1, 0, 'R');
                } else {
                    $this->Cell(70, 6, '', 1, 0);
                    $this->Cell(25, 6, '', 1, 0);
                }
                
                $this->Cell(5, 6, '', 0, 0);
                
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
    
    $pdf = new PLAccountPDF($filterParams['start_date'], $filterParams['end_date'], $filterParams['period_info']);
    $pdf->AddPage();
    $pdf->PLTable($pl_data);
    
    $filename = 'Profit_Loss_Account_' . $filterParams['start_date'] . '_to_' . $filterParams['end_date'] . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}

// Handle Excel Export
if ($export_format == 'excel' && $pl_data) {
    ob_end_clean();
    
    $filename = 'Profit_Loss_Account_' . $filterParams['start_date'] . '_to_' . $filterParams['end_date'] . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table width="100%" style="border-collapse: collapse;">';
    echo '<tr><td colspan="4" style="text-align:center; font-weight:bold; font-size:14px;">JAYALAKSHMI ENTERPRISES</td></tr>';
    echo '<tr><td colspan="4" style="text-align:center; font-weight:bold; font-size:12px;">PROFIT & LOSS ACCOUNT</td></tr>';
    
    if ($filterParams['period_info']) {
        echo '<tr><td colspan="4" style="text-align:center;">For ' . htmlspecialchars($filterParams['period_info']['period_name']) . '</td></tr>';
        echo '<tr><td colspan="4" style="text-align:center;">(' . date('d/m/Y', strtotime($filterParams['start_date'])) . ' to ' . date('d/m/Y', strtotime($filterParams['end_date'])) . ')</td></tr>';
    } else {
        echo '<tr><td colspan="4" style="text-align:center;">For the period from ' . date('d/m/Y', strtotime($filterParams['start_date'])) . ' to ' . date('d/m/Y', strtotime($filterParams['end_date'])) . '</td></tr>';
    }
    
    echo '<tr><td colspan="4">&nbsp;</td></tr>';
    
    // Column headers
    echo '<tr>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">AMOUNT</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center; background-color:#f0f0f0; width:25%;">AMOUNT</td>';
    echo '</tr>';
    
    // Data rows
    $expenses = $pl_data['expenses'];
    $income = $pl_data['income'];
    
    if ($pl_data['net_profit'] > 0) {
        $expenses[] = ['account_name' => 'Net Profit', 'amount' => $pl_data['net_profit']];
    } elseif ($pl_data['net_profit'] < 0) {
        $income[] = ['account_name' => 'Net Loss', 'amount' => abs($pl_data['net_profit'])];
    }
    
    $max_rows = max(count($expenses), count($income));
    
    for ($i = 0; $i < $max_rows; $i++) {
        echo '<tr>';
        
        // Expenses (Left side)
        if ($i < count($expenses)) {
            echo '<td style="border:1px solid black;">' . htmlspecialchars($expenses[$i]['account_name']) . '</td>';
            echo '<td style="border:1px solid black; text-align:right;">' . number_format($expenses[$i]['amount'], 2) . '</td>';
        } else {
            echo '<td style="border:1px solid black;">&nbsp;</td>';
            echo '<td style="border:1px solid black;">&nbsp;</td>';
        }
        
        // Income (Right side)
        if ($i < count($income)) {
            echo '<td style="border:1px solid black;">' . htmlspecialchars($income[$i]['account_name']) . '</td>';
            echo '<td style="border:1px solid black; text-align:right;">' . number_format($income[$i]['amount'], 2) . '</td>';
        } else {
            echo '<td style="border:1px solid black;">&nbsp;</td>';
            echo '<td style="border:1px solid black;">&nbsp;</td>';
        }
        
        echo '</tr>';
    }
    
    // Total row
    echo '<tr>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">Total</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:right;">' . number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2) . '</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">Total</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:right;">' . number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2) . '</td>';
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

        <!-- Filter Component -->
        <?php
        $filterConfig = [
            'show_financial_periods' => true,
            'show_quick_dates' => true,
            'show_export_buttons' => true,
            'submit_button_text' => 'Generate P&L Report',
            'submit_button_icon' => 'fa-chart-line',
            'export_formats' => ['pdf', 'excel']
        ];
        echo renderAccountingFilters($filterConfig);
        ?>

        <!-- Error Message -->
        <?php if ($filterParams['error_message']): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($filterParams['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- P&L Report -->
        <?php if ($pl_data && !$filterParams['error_message']): ?>
        <div class="pl-card">
            <div class="pl-header">
                <h4 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Profit & Loss Account
                </h4>
                <?php if ($filterParams['period_info']): ?>
                <p class="mb-0">For <?php echo htmlspecialchars($filterParams['period_info']['period_name']); ?></p>
                <p class="mb-0 small">(<?php echo date('d/m/Y', strtotime($filterParams['start_date'])); ?> to <?php echo date('d/m/Y', strtotime($filterParams['end_date'])); ?>)</p>
                <?php if ($filterParams['period_info']['is_closed']): ?>
                <p class="mb-0 small"><span class="badge bg-secondary">Period Closed</span></p>
                <?php endif; ?>
                <?php else: ?>
                <p class="mb-0">For the period from <?php echo date('d/m/Y', strtotime($filterParams['start_date'])); ?> to <?php echo date('d/m/Y', strtotime($filterParams['end_date'])); ?></p>
                <?php endif; ?>
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

                <!-- Period Information -->
                <?php if ($filterParams['period_info']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6><i class="fa fa-info-circle"></i> Financial Period Information</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Period:</strong> <?php echo htmlspecialchars($filterParams['period_info']['period_name']); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Duration:</strong> <?php echo date('M d, Y', strtotime($filterParams['start_date'])); ?> - <?php echo date('M d, Y', strtotime($filterParams['end_date'])); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $filterParams['period_info']['is_closed'] ? 'secondary' : 'success'; ?>">
                                        <?php echo $filterParams['period_info']['is_closed'] ? 'Closed' : 'Open'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Days:</strong> <?php echo round((strtotime($filterParams['end_date']) - strtotime($filterParams['start_date'])) / (60*60*24)) + 1; ?> days
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include_once "inc/footer.php"; ?>