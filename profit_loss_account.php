<?php
// profit_loss_account.php
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/PLAccountManager.php";
require('fpdf186/fpdf.php');

$accountingReports = new PLAccountManager();

// Get date parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$export_format = isset($_GET['export']) ? $_GET['export'] : '';

// Get P&L data
$pl_data = $accountingReports->getProfitLossAccount($start_date, $end_date);

// Handle PDF Export
if ($export_format == 'pdf') {
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
            // Set up the table structure similar to your format
            $this->SetFont('Arial', 'B', 10);
            
            // Table headers
            $this->Cell(3, 8, '', 0, 0); // To
            $this->Cell(87, 8, 'PARTICULARS', 1, 0, 'C');
            $this->Cell(25, 8, 'AMOUNT', 1, 0, 'C');
            $this->Cell(3, 8, '', 0, 0); // By
            $this->Cell(87, 8, 'PARTICULARS', 1, 0, 'C');
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
                // Expenses (To side)
                if ($i < count($expenses)) {
                    $this->Cell(3, 6, 'To', 0, 0, 'L');
                    $this->Cell(87, 6, $expenses[$i]['account_name'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($expenses[$i]['amount'], 2), 1, 0, 'R');
                } else {
                    $this->Cell(3, 6, '', 0, 0);
                    $this->Cell(87, 6, '', 1, 0);
                    $this->Cell(25, 6, '', 1, 0);
                }
                
                $this->Cell(3, 6, '', 0, 0); // Gap
                
                // Income (By side)
                if ($i < count($income)) {
                    $this->Cell(3, 6, 'By', 0, 0, 'L');
                    $this->Cell(84, 6, $income[$i]['account_name'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($income[$i]['amount'], 2), 1, 1, 'R');
                } else {
                    $this->Cell(3, 6, '', 0, 0);
                    $this->Cell(84, 6, '', 1, 0);
                    $this->Cell(25, 6, '', 1, 1);
                }
            }
            
            // Total row
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(3, 8, '', 0, 0);
            $this->Cell(87, 8, 'Total', 1, 0, 'C');
            $this->Cell(25, 8, number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2), 1, 0, 'R');
            $this->Cell(3, 8, '', 0, 0);
            $this->Cell(87, 8, 'Total', 1, 0, 'C');
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
if ($export_format == 'excel') {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.xls"');
    
    echo '<html><body>';
    echo '<table>';
    echo '<tr><td colspan="5" style="text-align:center; font-weight:bold; font-size:14px;">JAYALAKSHMI ENTERPRISES</td></tr>';
    echo '<tr><td colspan="5" style="text-align:center; font-weight:bold; font-size:12px;">PROFIT & LOSS ACCOUNT</td></tr>';
    echo '<tr><td colspan="5" style="text-align:center;">For the period from ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)) . '</td></tr>';
    echo '<tr><td colspan="5">&nbsp;</td></tr>';
    
    // Headers
    echo '<tr>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">AMOUNT</td>';
    echo '<td>&nbsp;</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">PARTICULARS</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">AMOUNT</td>';
    echo '</tr>';
    
    // Prepare data
    $expenses = $pl_data['expenses'];
    $income = $pl_data['income'];
    
    // Add Net Profit/Loss
    if ($pl_data['net_profit'] > 0) {
        $expenses[] = ['account_name' => 'Net Profit', 'amount' => $pl_data['net_profit']];
    } else if ($pl_data['net_profit'] < 0) {
        $income[] = ['account_name' => 'Net Loss', 'amount' => abs($pl_data['net_profit'])];
    }
    
    $max_rows = max(count($expenses), count($income));
    
    // Data rows
    for ($i = 0; $i < $max_rows; $i++) {
        echo '<tr>';
        
        // Expenses side
        if ($i < count($expenses)) {
            echo '<td style="border:1px solid black;">' . $expenses[$i]['account_name'] . '</td>';
            echo '<td style="border:1px solid black; text-align:right;">' . number_format($expenses[$i]['amount'], 2) . '</td>';
        } else {
            echo '<td style="border:1px solid black;">&nbsp;</td>';
            echo '<td style="border:1px solid black;">&nbsp;</td>';
        }
        
        echo '<td>&nbsp;</td>';
        
        // Income side
        if ($i < count($income)) {
            echo '<td style="border:1px solid black;">' . $income[$i]['account_name'] . '</td>';
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
    echo '<td>&nbsp;</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:center;">Total</td>';
    echo '<td style="border:1px solid black; font-weight:bold; text-align:right;">' . number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2) . '</td>';
    echo '</tr>';
    
    echo '<tr><td colspan="5">&nbsp;</td></tr>';
    echo '<tr><td colspan="2">Place: Irinjalakuda</td><td colspan="3" style="text-align:right;">As per our report even date attached</td></tr>';
    echo '<tr><td colspan="2">Date: ' . date('d/m/Y') . '</td><td colspan="3">&nbsp;</td></tr>';
    
    echo '</table>';
    echo '</body></html>';
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa fa-chart-line"></i> Profit & Loss Account - JAYALAKSHMI ENTERPRISES</h6>
                </div>
                <div class="card-body">
                    
                    <!-- Date Range Selection -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">From Date:</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date:</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="btn-group d-grid">
                                    <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=pdf" class="btn btn-danger">PDF</a>
                                    <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=excel" class="btn btn-success">Excel</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- P&L Summary Cards -->
                    <div class="row mb-4">
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
                                    <h6 class="text-<?php echo $pl_data['net_profit'] >= 0 ? 'success' : 'warning'; ?>">Net <?php echo $pl_data['net_profit'] >= 0 ? 'Profit' : 'Loss'; ?></h6>
                                    <h4 class="text-<?php echo $pl_data['net_profit'] >= 0 ? 'success' : 'warning'; ?>">₹<?php echo number_format(abs($pl_data['net_profit']), 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h6 class="text-info">Profit Margin</h6>
                                    <h4 class="text-info"><?php echo $pl_data['total_income'] > 0 ? number_format(($pl_data['net_profit'] / $pl_data['total_income']) * 100, 2) : '0.00'; ?>%</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- P&L Statement Table -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-center mb-3">PROFIT & LOSS ACCOUNT</h5>
                            <h6 class="text-center mb-4">For the period from <?php echo date('d/m/Y', strtotime($start_date)); ?> to <?php echo date('d/m/Y', strtotime($end_date)); ?></h6>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="1%"></th>
                                            <th width="42%" class="text-center">PARTICULARS</th>
                                            <th width="12%" class="text-center">AMOUNT</th>
                                            <th width="1%"></th>
                                            <th width="42%" class="text-center">PARTICULARS</th>
                                            <th width="12%" class="text-center">AMOUNT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Prepare data for display
                                        $expenses = $pl_data['expenses'];
                                        $income = $pl_data['income'];
                                        
                                        // Add Net Profit/Loss to appropriate side
                                        if ($pl_data['net_profit'] > 0) {
                                            $expenses[] = ['account_name' => 'Net Profit', 'amount' => $pl_data['net_profit']];
                                        } else if ($pl_data['net_profit'] < 0) {
                                            $income[] = ['account_name' => 'Net Loss', 'amount' => abs($pl_data['net_profit'])];
                                        }
                                        
                                        $max_rows = max(count($expenses), count($income));
                                        
                                        for ($i = 0; $i < $max_rows; $i++):
                                        ?>
                                        <tr>
                                            <td>To</td>
                                            <td><?php echo isset($expenses[$i]) ? $expenses[$i]['account_name'] : ''; ?></td>
                                            <td class="text-end"><?php echo isset($expenses[$i]) ? number_format($expenses[$i]['amount'], 2) : ''; ?></td>
                                            <td>By</td>
                                            <td><?php echo isset($income[$i]) ? $income[$i]['account_name'] : ''; ?></td>
                                            <td class="text-end"><?php echo isset($income[$i]) ? number_format($income[$i]['amount'], 2) : ''; ?></td>
                                        </tr>
                                        <?php endfor; ?>
                                        
                                        <!-- Total Row -->
                                        <tr class="table-dark">
                                            <td></td>
                                            <td class="text-center"><strong>Total</strong></td>
                                            <td class="text-end"><strong>₹<?php echo number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2); ?></strong></td>
                                            <td></td>
                                            <td class="text-center"><strong>Total</strong></td>
                                            <td class="text-end"><strong>₹<?php echo number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <p><strong>Place:</strong> Irinjalakuda</p>
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y'); ?></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p><em>As per our report even date attached</em></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Breakdown -->
                    <div class="row mt-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">Expense Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th class="text-end">Amount</th>
                                                    <th class="text-end">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pl_data['expenses'] as $expense): ?>
                                                <tr>
                                                    <td><?php echo $expense['account_name']; ?></td>
                                                    <td class="text-end">₹<?php echo number_format($expense['amount'], 2); ?></td>
                                                    <td class="text-end"><?php echo $pl_data['total_expenses'] > 0 ? number_format(($expense['amount'] / $pl_data['total_expenses']) * 100, 1) : '0.0'; ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Income Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th class="text-end">Amount</th>
                                                    <th class="text-end">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pl_data['income'] as $income_item): ?>
                                                <tr>
                                                    <td><?php echo $income_item['account_name']; ?></td>
                                                    <td class="text-end">₹<?php echo number_format($income_item['amount'], 2); ?></td>
                                                    <td class="text-end"><?php echo $pl_data['total_income'] > 0 ? number_format(($income_item['amount'] / $pl_data['total_income']) * 100, 1) : '0.0'; ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "inc/footer.php"; ?>