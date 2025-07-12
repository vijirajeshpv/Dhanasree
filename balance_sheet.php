<?php
// balance_sheet.php - Complete Balance Sheet with Financial Period Support
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/BalanceSheetManager.php";
include_once "components/accounting_filters.php";
require('fpdf186/fpdf.php');

$balanceSheetManager = new BalanceSheetManager();

// Get filter parameters using the common component
$filterParams = getAccountingFilterParams();
$balance_sheet_data = null;

// For Balance Sheet, we use the end date (as of date)
// If date range is selected, use the end date
// If financial period is selected, use the period end date
$as_of_date = '';

if ($filterParams['has_data']) {
    if ($filterParams['filter_type'] == 'financial_period') {
        $balance_sheet_data = $balanceSheetManager->getBalanceSheetByPeriod($filterParams['financial_period_id']);
        $as_of_date = $filterParams['end_date'];
    } else {
        $as_of_date = $filterParams['end_date'];
        $balance_sheet_data = $balanceSheetManager->getCategorizedBalanceSheet($as_of_date);
    }
}

// Check for export requests
$export_format = isset($_GET['export']) ? $_GET['export'] : '';

// Handle Excel Export
if ($export_format == 'excel' && $balance_sheet_data) {
    ob_end_clean();
    $balanceSheetManager->exportBalanceSheetToExcel($as_of_date);
    exit;
}

// Handle PDF Export
if ($export_format == 'pdf' && $balance_sheet_data) {
    ob_end_clean();
    
    class BalanceSheetPDF extends FPDF {
        private $asOfDate, $periodInfo;
        
        function __construct($as_of_date, $period_info = null) {
            parent::__construct();
            $this->asOfDate = $as_of_date;
            $this->periodInfo = $period_info;
        }
        
        function Header() {
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 8, 'BALANCE SHEET', 0, 1, 'C');
            $this->SetFont('Arial', '', 12);
            
            if ($this->periodInfo) {
                $this->Cell(0, 6, 'As of ' . date('d/m/Y', strtotime($this->asOfDate)), 0, 1, 'C');
                $this->Cell(0, 6, '(' . $this->periodInfo['period_name'] . ')', 0, 1, 'C');
            } else {
                $this->Cell(0, 6, 'As of ' . date('d/m/Y', strtotime($this->asOfDate)), 0, 1, 'C');
            }
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-20);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, 'Generated on ' . date('d/m/Y H:i'), 0, 1, 'C');
        }
        
        function BalanceSheetTable($data) {
            $this->SetFont('Arial', 'B', 12);
            
            // Assets section
            $this->Cell(120, 8, 'ASSETS', 0, 0, 'L');
            $this->Cell(50, 8, 'AMOUNT (Rs)', 0, 1, 'R');
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(2);
            
            $this->SetFont('Arial', '', 10);
            
            // Current Assets
            if (!empty($data['current_assets'])) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(120, 6, 'Current Assets:', 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                foreach ($data['current_assets'] as $asset) {
                    $this->Cell(10, 5, '', 0, 0);
                    $this->Cell(110, 5, $asset['account_name'], 0, 0, 'L');
                    $this->Cell(50, 5, number_format($asset['balance'], 2), 0, 1, 'R');
                }
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(10, 6, '', 0, 0);
                $this->Cell(110, 6, 'Total Current Assets', 0, 0, 'L');
                $this->Cell(50, 6, number_format($data['total_current_assets'], 2), 0, 1, 'R');
                $this->Ln(3);
            }
            
            // Fixed Assets
            if (!empty($data['fixed_assets'])) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(120, 6, 'Fixed Assets:', 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                foreach ($data['fixed_assets'] as $asset) {
                    $this->Cell(10, 5, '', 0, 0);
                    $this->Cell(110, 5, $asset['account_name'], 0, 0, 'L');
                    $this->Cell(50, 5, number_format($asset['balance'], 2), 0, 1, 'R');
                }
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(10, 6, '', 0, 0);
                $this->Cell(110, 6, 'Total Fixed Assets', 0, 0, 'L');
                $this->Cell(50, 6, number_format($data['total_fixed_assets'], 2), 0, 1, 'R');
                $this->Ln(3);
            }
            
            // Other Assets
            if (!empty($data['other_assets'])) {
                foreach ($data['other_assets'] as $asset) {
                    $this->Cell(10, 5, '', 0, 0);
                    $this->Cell(110, 5, $asset['account_name'], 0, 0, 'L');
                    $this->Cell(50, 5, number_format($asset['balance'], 2), 0, 1, 'R');
                }
                $this->Ln(3);
            }
            
            // Total Assets
            $this->SetFont('Arial', 'B', 12);
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Cell(120, 8, 'TOTAL ASSETS', 0, 0, 'L');
            $this->Cell(50, 8, number_format($data['total_assets'], 2), 0, 1, 'R');
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(5);
            
            // Liabilities section
            $this->Cell(120, 8, 'LIABILITIES', 0, 0, 'L');
            $this->Cell(50, 8, '', 0, 1, 'R');
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(2);
            
            $this->SetFont('Arial', '', 10);
            
            // Current Liabilities
            if (!empty($data['current_liabilities'])) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(120, 6, 'Current Liabilities:', 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                foreach ($data['current_liabilities'] as $liability) {
                    $this->Cell(10, 5, '', 0, 0);
                    $this->Cell(110, 5, $liability['account_name'], 0, 0, 'L');
                    $this->Cell(50, 5, number_format($liability['balance'], 2), 0, 1, 'R');
                }
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(10, 6, '', 0, 0);
                $this->Cell(110, 6, 'Total Current Liabilities', 0, 0, 'L');
                $this->Cell(50, 6, number_format($data['total_current_liabilities'], 2), 0, 1, 'R');
                $this->Ln(3);
            }
            
            // Long-term Liabilities
            if (!empty($data['long_term_liabilities'])) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(120, 6, 'Long-term Liabilities:', 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                foreach ($data['long_term_liabilities'] as $liability) {
                    $this->Cell(10, 5, '', 0, 0);
                    $this->Cell(110, 5, $liability['account_name'], 0, 0, 'L');
                    $this->Cell(50, 5, number_format($liability['balance'], 2), 0, 1, 'R');
                }
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(10, 6, '', 0, 0);
                $this->Cell(110, 6, 'Total Long-term Liabilities', 0, 0, 'L');
                $this->Cell(50, 6, number_format($data['total_long_term_liabilities'], 2), 0, 1, 'R');
                $this->Ln(3);
            }
            
            // Total Liabilities
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(120, 6, 'Total Liabilities', 0, 0, 'L');
            $this->Cell(50, 6, number_format($data['total_liabilities'], 2), 0, 1, 'R');
            $this->Ln(3);
            
            // Equity section
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(120, 8, 'EQUITY', 0, 0, 'L');
            $this->Cell(50, 8, '', 0, 1, 'R');
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(2);
            
            $this->SetFont('Arial', '', 10);
            foreach ($data['equity'] as $equity) {
                $this->Cell(10, 5, '', 0, 0);
                $this->Cell(110, 5, $equity['account_name'], 0, 0, 'L');
                $this->Cell(50, 5, number_format($equity['balance'], 2), 0, 1, 'R');
            }
            
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(120, 6, 'Total Equity', 0, 0, 'L');
            $this->Cell(50, 6, number_format($data['total_equity'], 2), 0, 1, 'R');
            $this->Ln(5);
            
            // Total Liabilities & Equity
            $this->SetFont('Arial', 'B', 12);
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Cell(120, 8, 'TOTAL LIABILITIES & EQUITY', 0, 0, 'L');
            $this->Cell(50, 8, number_format($data['total_liabilities_equity'], 2), 0, 1, 'R');
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            
            // Balance check
            if (!$data['is_balanced']) {
                $this->Ln(5);
                $this->SetFont('Arial', 'B', 10);
                $this->SetTextColor(255, 0, 0);
                $difference = $data['total_assets'] - $data['total_liabilities_equity'];
                $this->Cell(0, 6, 'WARNING: Balance Sheet does not balance! Difference: Rs ' . number_format($difference, 2), 0, 1, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        }
    }
    
    $pdf = new BalanceSheetPDF($as_of_date, $filterParams['period_info']);
    $pdf->AddPage();
    $pdf->BalanceSheetTable($balance_sheet_data);
    
    $filename = 'Balance_Sheet_' . $as_of_date . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}

// Clear output buffer for normal page display
ob_end_flush();
?>

<style>
.bs-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.bs-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.bs-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
}

.summary-cards {
    margin: 20px 0;
}

.bs-table {
    font-size: 0.9rem;
}

.bs-table th {
    background: #343a40;
    color: white;
    font-weight: 600;
    border: none;
}

.assets-section {
    background: #e8f5e8;
}

.liabilities-section {
    background: #fff5f5;
}

.equity-section {
    background: #f0f8ff;
}

.category-header {
    background: #6c757d !important;
    color: white !important;
    font-weight: bold;
}

.total-row {
    background: #f8f9fa !important;
    font-weight: bold;
    border-top: 2px solid #dee2e6;
}

.balance-indicator {
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.balanced {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.unbalanced {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .bs-container {
        background: white;
        padding: 0;
    }
}
</style>

<div class="bs-container">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fa fa-balance-scale"></i> Balance Sheet</h2>
                <p class="text-muted">View financial position and account balances as of a specific date</p>
            </div>
        </div>

        <!-- Filter Component -->
        <?php
        $filterConfig = [
            'show_financial_periods' => true,
            'show_quick_dates' => false, // Balance sheet typically uses specific end dates
            'show_export_buttons' => true,
            'submit_button_text' => 'Generate Balance Sheet',
            'submit_button_icon' => 'fa-balance-scale',
            'export_formats' => ['pdf', 'excel']
        ];
        echo renderAccountingFilters($filterConfig);
        ?>

        <!-- Information Alert for Balance Sheet -->
        <div class="alert alert-info no-print">
            <h6><i class="fa fa-info-circle"></i> About Balance Sheet</h6>
            <p class="mb-0">
                The Balance Sheet shows your financial position <strong>as of a specific date</strong>. 
                When using date ranges, the <strong>end date</strong> is used as the "as of" date. 
                For financial periods, the <strong>period end date</strong> is used.
            </p>
        </div>

        <!-- Error Message -->
        <?php if ($filterParams['error_message']): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($filterParams['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Balance Sheet Report -->
        <?php if ($balance_sheet_data && !$filterParams['error_message']): ?>
        <div class="bs-card">
            <div class="bs-header">
                <h4 class="mb-0">
                    <i class="fas fa-balance-scale me-2"></i>Balance Sheet
                </h4>
                <?php if ($filterParams['period_info']): ?>
                <p class="mb-0">As of <?php echo date('d/m/Y', strtotime($as_of_date)); ?></p>
                <p class="mb-0 small">(<?php echo htmlspecialchars($filterParams['period_info']['period_name']); ?>)</p>
                <?php if ($filterParams['period_info']['is_closed']): ?>
                <p class="mb-0 small"><span class="badge bg-secondary">Period Closed</span></p>
                <?php endif; ?>
                <?php else: ?>
                <p class="mb-0">As of <?php echo date('d/m/Y', strtotime($as_of_date)); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <!-- Balance Indicator -->
                <div class="balance-indicator <?php echo $balance_sheet_data['is_balanced'] ? 'balanced' : 'unbalanced'; ?>">
                    <h6>
                        <i class="fa fa-<?php echo $balance_sheet_data['is_balanced'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        Balance Status: <?php echo $balance_sheet_data['is_balanced'] ? 'BALANCED' : 'UNBALANCED'; ?>
                    </h6>
                    <?php if (!$balance_sheet_data['is_balanced']): ?>
                    <p class="mb-0">
                        Difference: ₹<?php echo number_format(abs($balance_sheet_data['total_assets'] - $balance_sheet_data['total_liabilities_equity']), 2); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="text-success">Total Assets</h6>
                                    <h4 class="text-success">₹<?php echo number_format($balance_sheet_data['total_assets'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h6 class="text-danger">Total Liabilities</h6>
                                    <h4 class="text-danger">₹<?php echo number_format($balance_sheet_data['total_liabilities'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h6 class="text-primary">Total Equity</h6>
                                    <h4 class="text-primary">₹<?php echo number_format($balance_sheet_data['total_equity'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balance Sheet Table -->
                <div class="table-responsive">
                    <table class="table table-bordered bs-table">
                        <thead>
                            <tr>
                                <th width="60%">ACCOUNT</th>
                                <th width="40%">AMOUNT (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ASSETS SECTION -->
                            <tr class="category-header">
                                <td colspan="2"><strong>ASSETS</strong></td>
                            </tr>
                            
                            <!-- Current Assets -->
                            <?php if (!empty($balance_sheet_data['current_assets'])): ?>
                            <tr class="assets-section">
                                <td><strong>Current Assets:</strong></td>
                                <td></td>
                            </tr>
                            <?php foreach ($balance_sheet_data['current_assets'] as $asset): ?>
                            <tr class="assets-section">
                                <td style="padding-left: 30px;"><?php echo htmlspecialchars($asset['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($asset['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="assets-section">
                                <td style="padding-left: 20px;"><strong>Total Current Assets</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_current_assets'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Fixed Assets -->
                            <?php if (!empty($balance_sheet_data['fixed_assets'])): ?>
                            <tr class="assets-section">
                                <td><strong>Fixed Assets:</strong></td>
                                <td></td>
                            </tr>
                            <?php foreach ($balance_sheet_data['fixed_assets'] as $asset): ?>
                            <tr class="assets-section">
                                <td style="padding-left: 30px;"><?php echo htmlspecialchars($asset['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($asset['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="assets-section">
                                <td style="padding-left: 20px;"><strong>Total Fixed Assets</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_fixed_assets'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Other Assets -->
                            <?php if (!empty($balance_sheet_data['other_assets'])): ?>
                            <?php foreach ($balance_sheet_data['other_assets'] as $asset): ?>
                            <tr class="assets-section">
                                <td style="padding-left: 20px;"><?php echo htmlspecialchars($asset['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($asset['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Total Assets -->
                            <tr class="total-row">
                                <td><strong>TOTAL ASSETS</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_assets'], 2); ?></strong></td>
                            </tr>
                            
                            <!-- LIABILITIES SECTION -->
                            <tr class="category-header">
                                <td colspan="2"><strong>LIABILITIES</strong></td>
                            </tr>
                            
                            <!-- Current Liabilities -->
                            <?php if (!empty($balance_sheet_data['current_liabilities'])): ?>
                            <tr class="liabilities-section">
                                <td><strong>Current Liabilities:</strong></td>
                                <td></td>
                            </tr>
                            <?php foreach ($balance_sheet_data['current_liabilities'] as $liability): ?>
                            <tr class="liabilities-section">
                                <td style="padding-left: 30px;"><?php echo htmlspecialchars($liability['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($liability['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="liabilities-section">
                                <td style="padding-left: 20px;"><strong>Total Current Liabilities</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_current_liabilities'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Long-term Liabilities -->
                            <?php if (!empty($balance_sheet_data['long_term_liabilities'])): ?>
                            <tr class="liabilities-section">
                                <td><strong>Long-term Liabilities:</strong></td>
                                <td></td>
                            </tr>
                            <?php foreach ($balance_sheet_data['long_term_liabilities'] as $liability): ?>
                            <tr class="liabilities-section">
                                <td style="padding-left: 30px;"><?php echo htmlspecialchars($liability['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($liability['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="liabilities-section">
                                <td style="padding-left: 20px;"><strong>Total Long-term Liabilities</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_long_term_liabilities'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Total Liabilities -->
                            <tr class="liabilities-section">
                                <td><strong>Total Liabilities</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_liabilities'], 2); ?></strong></td>
                            </tr>
                            
                            <!-- EQUITY SECTION -->
                            <tr class="category-header">
                                <td colspan="2"><strong>EQUITY</strong></td>
                            </tr>
                            
                            <?php foreach ($balance_sheet_data['equity'] as $equity): ?>
                            <tr class="equity-section">
                                <td style="padding-left: 20px;"><?php echo htmlspecialchars($equity['account_name']); ?></td>
                                <td class="text-end"><?php echo number_format($equity['balance'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Total Equity -->
                            <tr class="equity-section">
                                <td><strong>Total Equity</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_equity'], 2); ?></strong></td>
                            </tr>
                            
                            <!-- Total Liabilities & Equity -->
                            <tr class="total-row">
                                <td><strong>TOTAL LIABILITIES & EQUITY</strong></td>
                                <td class="text-end"><strong><?php echo number_format($balance_sheet_data['total_liabilities_equity'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Financial Ratios (if data exists) -->
                <?php if ($balance_sheet_data['total_assets'] > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fa fa-calculator"></i> Key Financial Ratios</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $current_assets_total = $balance_sheet_data['total_current_assets'] ?? 0;
                                    $current_liabilities_total = $balance_sheet_data['total_current_liabilities'] ?? 0;
                                    $total_liabilities = $balance_sheet_data['total_liabilities'] ?? 0;
                                    $total_equity = $balance_sheet_data['total_equity'] ?? 0;
                                    $total_assets = $balance_sheet_data['total_assets'] ?? 0;
                                    
                                    $current_ratio = $current_liabilities_total > 0 ? 
                                        $current_assets_total / $current_liabilities_total : 0;
                                    $debt_to_equity = $total_equity > 0 ? 
                                        $total_liabilities / $total_equity : 0;
                                    $equity_ratio = $total_assets > 0 ? 
                                        $total_equity / $total_assets : 0;
                                    ?>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6>Current Ratio</h6>
                                            <h5 class="text-<?php echo $current_ratio >= 2 ? 'success' : ($current_ratio >= 1 ? 'warning' : 'danger'); ?>">
                                                <?php echo number_format($current_ratio, 2); ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo $current_ratio >= 2 ? 'Good' : ($current_ratio >= 1 ? 'Fair' : 'Poor'); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6>Debt-to-Equity</h6>
                                            <h5 class="text-<?php echo $debt_to_equity <= 1 ? 'success' : ($debt_to_equity <= 2 ? 'warning' : 'danger'); ?>">
                                                <?php echo number_format($debt_to_equity, 2); ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo $debt_to_equity <= 1 ? 'Conservative' : ($debt_to_equity <= 2 ? 'Moderate' : 'High'); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6>Equity Ratio</h6>
                                            <h5 class="text-<?php echo $equity_ratio >= 0.5 ? 'success' : ($equity_ratio >= 0.3 ? 'warning' : 'danger'); ?>">
                                                <?php echo number_format($equity_ratio * 100, 1); ?>%
                                            </h5>
                                            <small class="text-muted">Owner's Share</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6>Total Assets</h6>
                                            <h5 class="text-info">₹<?php echo number_format($total_assets, 0); ?></h5>
                                            <small class="text-muted">Business Size</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
                                    <strong>As of Date:</strong> <?php echo date('M d, Y', strtotime($as_of_date)); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $filterParams['period_info']['is_closed'] ? 'secondary' : 'success'; ?>">
                                        <?php echo $filterParams['period_info']['is_closed'] ? 'Closed' : 'Open'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Balance Status:</strong> 
                                    <span class="badge bg-<?php echo $balance_sheet_data['is_balanced'] ? 'success' : 'danger'; ?>">
                                        <?php echo $balance_sheet_data['is_balanced'] ? 'Balanced' : 'Unbalanced'; ?>
                                    </span>
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