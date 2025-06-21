<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/AccountingReports.php";
require('fpdf186/fpdf.php');

// Initialize AccountingReports class
$accounting = new AccountingReports();

// Handle date filtering
$asOfDate = isset($_GET['as_of_date']) ? $_GET['as_of_date'] : date('Y-m-d');

// Get balance sheet data
$balanceSheetData = $accounting->getBalanceSheet($asOfDate);

// Custom function to categorize and organize balance sheet data
function organizeBalanceSheetData($balanceSheetData) {
    $organized = [
        'fixed_assets' => [],
        'current_assets' => [],
        'capital_accounts' => [],
        'current_liabilities' => [],
        'totals' => [
            'fixed_assets' => 0,
            'current_assets' => 0,
            'total_assets' => 0,
            'capital' => 0,
            'liabilities' => 0,
            'total_liab_equity' => 0
        ]
    ];
    
    // Categorize assets
    foreach ($balanceSheetData['assets'] as $asset) {
        if (in_array($asset['account_code'], ['FA001', 'FA002', 'FA003', 'FA004', 'FURNITURE001', 'OFFICEEQUIP001']) || 
            strpos($asset['account_name'], 'Furniture') !== false || 
            strpos($asset['account_name'], 'Fixtures') !== false ||
            strpos($asset['account_name'], 'Electrical') !== false ||
            strpos($asset['account_name'], 'Weighing') !== false ||
            strpos($asset['account_name'], 'Depreciation') !== false) {
            $organized['fixed_assets'][] = $asset;
            $organized['totals']['fixed_assets'] += $asset['balance'];
        } else {
            $organized['current_assets'][] = $asset;
            $organized['totals']['current_assets'] += $asset['balance'];
        }
    }
    
    // Categorize equity (capital accounts)
    foreach ($balanceSheetData['equity'] as $equity) {
        if (in_array($equity['account_code'], ['EQ001', 'EQ002', 'EQ003']) ||
            strpos($equity['account_name'], 'Capital') !== false ||
            in_array($equity['account_name'], ['Karthiyani', 'Rajesh', 'Vasudeval'])) {
            $organized['capital_accounts'][] = $equity;
            $organized['totals']['capital'] += $equity['balance'];
        }
    }
    
    // All liabilities go to current liabilities
    foreach ($balanceSheetData['liabilities'] as $liability) {
        $organized['current_liabilities'][] = $liability;
        $organized['totals']['liabilities'] += $liability['balance'];
    }
    
    $organized['totals']['total_assets'] = $organized['totals']['fixed_assets'] + $organized['totals']['current_assets'];
    $organized['totals']['total_liab_equity'] = $organized['totals']['capital'] + $organized['totals']['liabilities'];
    
    return $organized;
}

$organizedData = organizeBalanceSheetData($balanceSheetData);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    ob_end_clean();

    class BalanceSheetPDF extends FPDF {
        private $asOfDate;
        
        function __construct($asOfDate) {
            parent::__construct();
            $this->asOfDate = $asOfDate;
        }
        
        function Header() {
            // Company header
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, 'JAYALAKSHMI ENTERPRISES, THANISSERY, THRISSUR - KML REG NO.32080302832 (2508-0-413)', 0, 1, 'C');
            
            // Balance sheet title with date
            $this->SetFont('Arial', 'BU', 11);
            $this->Cell(0, 8, 'BALANCE SHEET AS AT ' . strtoupper(date('jS F Y', strtotime($this->asOfDate))), 0, 1, 'C');
            $this->Ln(3);
        }
        
        function BalanceSheetTable($organizedData) {
            // Table headers
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(95, 6, 'LIABILITIES', 1, 0, 'C');
            $this->Cell(20, 6, 'AMOUNT', 1, 0, 'C');
            $this->Cell(5, 6, '', 0, 0); // Spacing
            $this->Cell(45, 6, 'ASSETS', 1, 0, 'C');
            $this->Cell(25, 6, 'AMOUNT', 1, 1, 'C');
            
            // Capital Account section
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(95, 5, 'CAPITAL ACCOUNT', 1, 0, 'L');
            $this->Cell(20, 5, '', 1, 0, 'C');
            $this->Cell(5, 5, '', 0, 0);
            $this->Cell(45, 5, 'FIXED ASSETS', 1, 0, 'L');
            $this->Cell(25, 5, '', 1, 1, 'C');
            
            $this->SetFont('Arial', '', 8);
            
            // Display capital accounts with fixed assets
            $maxCapital = count($organizedData['capital_accounts']);
            $maxFixed = count($organizedData['fixed_assets']);
            $maxRows = max($maxCapital, $maxFixed);
            
            for ($i = 0; $i < $maxRows; $i++) {
                // Capital account
                if ($i < $maxCapital) {
                    $capital = $organizedData['capital_accounts'][$i];
                    $this->Cell(95, 5, $capital['account_name'], 1, 0, 'L');
                    $this->Cell(20, 5, number_format($capital['balance'], 2), 1, 0, 'R');
                } else {
                    $this->Cell(95, 5, '', 1, 0, 'L');
                    $this->Cell(20, 5, '', 1, 0, 'R');
                }
                
                $this->Cell(5, 5, '', 0, 0);
                
                // Fixed asset
                if ($i < $maxFixed) {
                    $asset = $organizedData['fixed_assets'][$i];
                    $this->Cell(45, 5, $asset['account_name'], 1, 0, 'L');
                    $this->Cell(25, 5, number_format($asset['balance'], 2), 1, 1, 'R');
                } else {
                    $this->Cell(45, 5, '', 1, 0, 'L');
                    $this->Cell(25, 5, '', 1, 1, 'R');
                }
            }
            
            // Capital total
            $this->Cell(95, 5, '', 1, 0, 'L');
            $this->Cell(20, 5, number_format($organizedData['totals']['capital'], 2), 1, 0, 'R');
            $this->Cell(5, 5, '', 0, 0);
            $this->Cell(45, 5, '', 1, 0, 'L');
            $this->Cell(25, 5, '', 1, 1, 'R');
            
            // Current Liabilities section
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(95, 5, 'PARTNERS CURRENT A/C.', 1, 0, 'L');
            $this->Cell(20, 5, '', 1, 0, 'C');
            $this->Cell(5, 5, '', 0, 0);
            $this->Cell(45, 5, 'CURRENT ASSETS', 1, 0, 'L');
            $this->Cell(25, 5, '', 1, 1, 'C');
            
            $this->SetFont('Arial', '', 8);
            
            // Display current liabilities with current assets
            $maxLiabilities = count($organizedData['current_liabilities']);
            $maxCurrent = count($organizedData['current_assets']);
            $maxRows = max($maxLiabilities, $maxCurrent);
            
            for ($i = 0; $i < $maxRows; $i++) {
                // Current liability
                if ($i < $maxLiabilities) {
                    $liability = $organizedData['current_liabilities'][$i];
                    $this->Cell(95, 5, $liability['account_name'], 1, 0, 'L');
                    $this->Cell(20, 5, number_format($liability['balance'], 2), 1, 0, 'R');
                } else {
                    $this->Cell(95, 5, '', 1, 0, 'L');
                    $this->Cell(20, 5, '', 1, 0, 'R');
                }
                
                $this->Cell(5, 5, '', 0, 0);
                
                // Current asset
                if ($i < $maxCurrent) {
                    $asset = $organizedData['current_assets'][$i];
                    $this->Cell(45, 5, $asset['account_name'], 1, 0, 'L');
                    $this->Cell(25, 5, number_format($asset['balance'], 2), 1, 1, 'R');
                } else {
                    $this->Cell(45, 5, '', 1, 0, 'L');
                    $this->Cell(25, 5, '', 1, 1, 'R');
                }
            }
            
            // Totals
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(95, 6, 'Total', 1, 0, 'C');
            $this->Cell(20, 6, number_format($organizedData['totals']['total_liab_equity'], 2), 1, 0, 'R');
            $this->Cell(5, 6, '', 0, 0);
            $this->Cell(45, 6, 'Total', 1, 0, 'C');
            $this->Cell(25, 6, number_format($organizedData['totals']['total_assets'], 2), 1, 1, 'R');
            
            // Footer
            $this->Ln(5);
            $this->SetFont('Arial', '', 8);
            $this->Cell(95, 5, 'Place:Irinjalakuda', 0, 0, 'L');
            $this->Cell(0, 5, 'As per our report even date attached', 0, 1, 'R');
            $this->Cell(95, 5, 'Date:' . date('d/m/Y'), 0, 1, 'L');
        }
    }

    $pdf = new BalanceSheetPDF($asOfDate);
    $pdf->AddPage();
    $pdf->BalanceSheetTable($organizedData);
    $pdf->Output('D', 'Balance_Sheet_' . $asOfDate . '.pdf');
    exit;
}

// Handle Excel export (similar structure)
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Balance_Sheet_' . $asOfDate . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table border="0" width="100%" style="text-align:center;">';
    echo '<tr><td colspan="4" style="font-size:12px; font-weight:bold;">JAYALAKSHMI ENTERPRISES, THANISSERY, THRISSUR - KML REG NO.32080302832 (2508-0-413)</td></tr>';
    echo '<tr><td colspan="4" style="font-size:11px; font-weight:bold; text-decoration:underline;">BALANCE SHEET AS AT ' . strtoupper(date('jS F Y', strtotime($asOfDate))) . '</td></tr>';
    echo '<tr><td colspan="4">&nbsp;</td></tr>';
    echo '</table>';
    
    // Balance sheet table
    echo '<table border="1" style="border-collapse:collapse; width:100%;">';
    echo '<tr style="background-color:#f0f0f0; font-weight:bold;">';
    echo '<td width="45%">LIABILITIES</td>';
    echo '<td width="15%">AMOUNT</td>';
    echo '<td width="25%">ASSETS</td>';
    echo '<td width="15%">AMOUNT</td>';
    echo '</tr>';
    
    // Capital section
    echo '<tr style="background-color:#e6f3ff; font-weight:bold;">';
    echo '<td>CAPITAL ACCOUNT</td>';
    echo '<td></td>';
    echo '<td>FIXED ASSETS</td>';
    echo '<td></td>';
    echo '</tr>';
    
    // Capital accounts with fixed assets
    $maxRows = max(count($organizedData['capital_accounts']), count($organizedData['fixed_assets']));
    
    for ($i = 0; $i < $maxRows; $i++) {
        echo '<tr>';
        
        // Capital account
        if ($i < count($organizedData['capital_accounts'])) {
            $capital = $organizedData['capital_accounts'][$i];
            echo '<td>' . htmlspecialchars($capital['account_name']) . '</td>';
            echo '<td align="right">' . number_format($capital['balance'], 2) . '</td>';
        } else {
            echo '<td></td><td></td>';
        }
        
        // Fixed asset
        if ($i < count($organizedData['fixed_assets'])) {
            $asset = $organizedData['fixed_assets'][$i];
            echo '<td>' . htmlspecialchars($asset['account_name']) . '</td>';
            echo '<td align="right">' . number_format($asset['balance'], 2) . '</td>';
        } else {
            echo '<td></td><td></td>';
        }
        
        echo '</tr>';
    }
    
    // Capital total
    echo '<tr>';
    echo '<td></td>';
    echo '<td align="right" style="font-weight:bold;">' . number_format($organizedData['totals']['capital'], 2) . '</td>';
    echo '<td></td><td></td>';
    echo '</tr>';
    
    // Current liabilities section
    echo '<tr style="background-color:#e6f3ff; font-weight:bold;">';
    echo '<td>PARTNERS CURRENT A/C.</td>';
    echo '<td></td>';
    echo '<td>CURRENT ASSETS</td>';
    echo '<td></td>';
    echo '</tr>';
    
    // Current liabilities with current assets
    $maxRows = max(count($organizedData['current_liabilities']), count($organizedData['current_assets']));
    
    for ($i = 0; $i < $maxRows; $i++) {
        echo '<tr>';
        
        // Current liability
        if ($i < count($organizedData['current_liabilities'])) {
            $liability = $organizedData['current_liabilities'][$i];
            echo '<td>' . htmlspecialchars($liability['account_name']) . '</td>';
            echo '<td align="right">' . number_format($liability['balance'], 2) . '</td>';
        } else {
            echo '<td></td><td></td>';
        }
        
        // Current asset
        if ($i < count($organizedData['current_assets'])) {
            $asset = $organizedData['current_assets'][$i];
            echo '<td>' . htmlspecialchars($asset['account_name']) . '</td>';
            echo '<td align="right">' . number_format($asset['balance'], 2) . '</td>';
        } else {
            echo '<td></td><td></td>';
        }
        
        echo '</tr>';
    }
    
    // Totals
    echo '<tr style="background-color:#fff2cc; font-weight:bold;">';
    echo '<td>Total</td>';
    echo '<td align="right">' . number_format($organizedData['totals']['total_liab_equity'], 2) . '</td>';
    echo '<td>Total</td>';
    echo '<td align="right">' . number_format($organizedData['totals']['total_assets'], 2) . '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Footer
    echo '<br><table border="0" width="100%">';
    echo '<tr>';
    echo '<td width="50%">Place:Irinjalakuda</td>';
    echo '<td width="50%" align="right">As per our report even date attached</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Date:' . date('d/m/Y') . '</td>';
    echo '<td></td>';
    echo '</tr>';
    echo '</table>';
    
    echo '</body></html>';
    exit;
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header matching the image -->
            <div class="text-center mb-4">
                <h4 style="margin-bottom: 5px; font-weight: bold;">JAYALAKSHMI ENTERPRISES, THANISSERY, THRISSUR - KML REG NO.32080302832 (2508-0-413)</h4>
                <h4 style="margin-bottom: 15px; text-decoration: underline; font-weight: bold;">
                    BALANCE SHEET AS AT <?php echo strtoupper(date('jS F Y', strtotime($asOfDate))); ?>
                </h4>
            </div>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fa fa-calendar"></i> As of Date</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label for="as_of_date" class="form-label">As of Date:</label>
                        <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="<?php echo $asOfDate; ?>" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-info me-2">
                            <i class="fa fa-refresh"></i> Generate
                        </button>
                        <a href="?as_of_date=<?php echo $asOfDate; ?>&export=pdf" class="btn btn-danger me-2">
                            <i class="fa fa-file-pdf-o"></i> PDF
                        </a>
                        <a href="?as_of_date=<?php echo $asOfDate; ?>&export=excel" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Sheet -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 11px;">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="width: 45%; text-align: center; border: 1px solid #000;">LIABILITIES</th>
                            <th style="width: 15%; text-align: center; border: 1px solid #000;">AMOUNT</th>
                            <th style="width: 25%; text-align: center; border: 1px solid #000;">ASSETS</th>
                            <th style="width: 15%; text-align: center; border: 1px solid #000;">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Capital Account Section -->
                        <tr style="background-color: #e6f3ff;">
                            <td style="border: 1px solid #000; font-weight: bold; padding: 5px;">CAPITAL ACCOUNT</td>
                            <td style="border: 1px solid #000;"></td>
                            <td style="border: 1px solid #000; font-weight: bold; padding: 5px;">FIXED ASSETS</td>
                            <td style="border: 1px solid #000;"></td>
                        </tr>
                        
                        <!-- Capital accounts with fixed assets -->
                        <?php 
                        $maxRows = max(count($organizedData['capital_accounts']), count($organizedData['fixed_assets']));
                        
                        for ($i = 0; $i < $maxRows; $i++): ?>
                        <tr>
                            <!-- Capital account -->
                            <?php if ($i < count($organizedData['capital_accounts'])): 
                                $capital = $organizedData['capital_accounts'][$i]; ?>
                                <td style="border: 1px solid #000; padding: 3px;"><?php echo htmlspecialchars($capital['account_name']); ?></td>
                                <td style="border: 1px solid #000; text-align: right; padding: 3px;"><?php echo number_format($capital['balance'], 2); ?></td>
                            <?php else: ?>
                                <td style="border: 1px solid #000;"></td>
                                <td style="border: 1px solid #000;"></td>
                            <?php endif; ?>
                            
                            <!-- Fixed asset -->
                            <?php if ($i < count($organizedData['fixed_assets'])): 
                                $asset = $organizedData['fixed_assets'][$i]; ?>
                                <td style="border: 1px solid #000; padding: 3px;"><?php echo htmlspecialchars($asset['account_name']); ?></td>
                                <td style="border: 1px solid #000; text-align: right; padding: 3px;"><?php echo number_format($asset['balance'], 2); ?></td>
                            <?php else: ?>
                                <td style="border: 1px solid #000;"></td>
                                <td style="border: 1px solid #000;"></td>
                            <?php endif; ?>
                        </tr>
                        <?php endfor; ?>
                        
                        <!-- Capital Total -->
                        <tr>
                            <td style="border: 1px solid #000;"></td>
                            <td style="border: 1px solid #000; text-align: right; padding: 3px; font-weight: bold;">
                                <?php echo number_format($organizedData['totals']['capital'], 2); ?>
                            </td>
                            <td style="border: 1px solid #000;"></td>
                            <td style="border: 1px solid #000;"></td>
                        </tr>
                        
                        <!-- Partners Current A/C Section -->
                        <tr style="background-color: #e6f3ff;">
                            <td style="border: 1px solid #000; font-weight: bold; padding: 5px;">PARTNERS CURRENT A/C.</td>
                            <td style="border: 1px solid #000;"></td>
                            <td style="border: 1px solid #000; font-weight: bold; padding: 5px;">CURRENT ASSETS</td>
                            <td style="border: 1px solid #000;"></td>
                        </tr>
                        
                        <!-- Current liabilities with current assets -->
                        <?php 
                        $maxRows = max(count($organizedData['current_liabilities']), count($organizedData['current_assets']));
                        
                        for ($i = 0; $i < $maxRows; $i++): ?>
                        <tr>
                            <!-- Current liability -->
                            <?php if ($i < count($organizedData['current_liabilities'])): 
                                $liability = $organizedData['current_liabilities'][$i]; ?>
                                <td style="border: 1px solid #000; padding: 3px;"><?php echo htmlspecialchars($liability['account_name']); ?></td>
                                <td style="border: 1px solid #000; text-align: right; padding: 3px;"><?php echo number_format($liability['balance'], 2); ?></td>
                            <?php else: ?>
                                <td style="border: 1px solid #000;"></td>
                                <td style="border: 1px solid #000;"></td>
                            <?php endif; ?>
                            
                            <!-- Current asset -->
                            <?php if ($i < count($organizedData['current_assets'])): 
                                $asset = $organizedData['current_assets'][$i]; ?>
                                <td style="border: 1px solid #000; padding: 3px;"><?php echo htmlspecialchars($asset['account_name']); ?></td>
                                <td style="border: 1px solid #000; text-align: right; padding: 3px;"><?php echo number_format($asset['balance'], 2); ?></td>
                            <?php else: ?>
                                <td style="border: 1px solid #000;"></td>
                                <td style="border: 1px solid #000;"></td>
                            <?php endif; ?>
                        </tr>
                        <?php endfor; ?>
                        
                        <!-- Totals Row -->
                        <tr style="background-color: #fff2cc; font-weight: bold;">
                            <td style="border: 1px solid #000; text-align: center; padding: 5px;">Total</td>
                            <td style="border: 1px solid #000; text-align: right; padding: 5px;">
                                <?php echo number_format($organizedData['totals']['total_liab_equity'], 2); ?>
                            </td>
                            <td style="border: 1px solid #000; text-align: center; padding: 5px;">Total</td>
                            <td style="border: 1px solid #000; text-align: right; padding: 5px;">
                                <?php echo number_format($organizedData['totals']['total_assets'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        Place: Irinjalakuda<br>
                        Date: <?php echo date('d/m/Y'); ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Balance Status: 
                        <?php if (abs($organizedData['totals']['total_assets'] - $organizedData['totals']['total_liab_equity']) < 0.01): ?>
                            <span class="text-success">✓ Balanced</span>
                        <?php else: ?>
                            <span class="text-danger">✗ Not Balanced</span>
                            <br>Difference: ₹<?php echo number_format(abs($organizedData['totals']['total_assets'] - $organizedData['totals']['total_liab_equity']), 2); ?>
                        <?php endif; ?>
                        <br>
                        As per our report even date attached
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th, .table td {
    border: 1px solid #000 !important;
    vertical-align: middle;
}

.table thead th {
    background-color: #f8f9fa;
    color: #000;
    font-weight: bold;
    text-align: center;
}

.me-2 {
    margin-right: 0.5rem;
}

.text-end {
    text-align: right;
}

/* Print styles */
@media print {
    .card-header, 
    .card-footer, 
    .btn, 
    .form-control,
    .card:first-child {
        display: none !important;
    }
    
    .table {
        font-size: 9px;
    }
}
</style>

<?php
include_once "inc/footer.php";
ob_end_flush(); // Flush the output buffer and send output
?>