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
if ($export_format == 'excel') {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.xls"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table width="100%" style="border-collapse: collapse;">';
    echo '<tr><td colspan="5" style="text-align:center; font-weight:bold; font-size:14px;">JAYALAKSHMI ENTERPRISES</td></tr>';
    echo '<tr><td colspan="5" style="text-align:center; font-weight:bold; font-size:12px;">PROFIT & LOSS ACCOUNT</td></tr>';
    echo '<tr><td colspan="5" style="text-align:center;">For the period from ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)) . '</td></tr>';
    echo '<tr><td colspan="5">&nbsp;</td></tr>';
    
    // Column headers - Equal width columns for Excel
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

// Rest of the file for web display
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Profit & Loss Account
                    </h4>
                </div>
                
                <div class="card-body">
                    <!-- Date Selection Form -->
                    <form method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Start Date:</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date:</label>
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
                                    <h4 class="text-info">
                                        <?php 
                                        $margin = $pl_data['total_income'] > 0 ? ($pl_data['net_profit'] / $pl_data['total_income']) * 100 : 0;
                                        echo number_format($margin, 1); 
                                        ?>%
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- P&L Table Display -->
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th colspan="2" class="text-center">EXPENSES</th>
                                            <th colspan="2" class="text-center">INCOME</th>
                                        </tr>
                                        <tr>
                                            <th>Particulars</th>
                                            <th class="text-end" width="150">Amount</th>
                                            <th>Particulars</th>
                                            <th class="text-end" width="150">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
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
                                        
                                        for ($i = 0; $i < $max_rows; $i++):
                                        ?>
                                        <tr>
                                            <!-- Expenses -->
                                            <?php if ($i < count($expenses)): ?>
                                                <td><?php echo htmlspecialchars($expenses[$i]['account_name']); ?></td>
                                                <td class="text-end">₹<?php echo number_format($expenses[$i]['amount'], 2); ?></td>
                                            <?php else: ?>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                            <?php endif; ?>
                                            
                                            <!-- Income -->
                                            <?php if ($i < count($income)): ?>
                                                <td><?php echo htmlspecialchars($income[$i]['account_name']); ?></td>
                                                <td class="text-end">₹<?php echo number_format($income[$i]['amount'], 2); ?></td>
                                            <?php else: ?>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th class="text-center">Total</th>
                                            <th class="text-end">₹<?php echo number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2); ?></th>
                                            <th class="text-center">Total</th>
                                            <th class="text-end">₹<?php echo number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "inc/footer.php"; ?>