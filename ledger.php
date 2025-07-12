<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/AccountingReports.php";
include_once "classes/accounting/AccountingHelper.php";
include_once "classes/accounting/AccountManager.php";
require('fpdf186/fpdf.php'); // Include FPDF library

// Initialize classes
$accounting = new AccountingReports();
$helper = new AccountingHelper();
$accountManager = new AccountManager();

// Initialize variables
$ledgerData = null;
$selectedAccount = null;
$filterType = 'date_range'; // 'date_range' or 'financial_period'
$startDate = '';
$endDate = '';
$financialPeriodId = null;
$errorMessage = '';
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountId = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
    $filterType = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'date_range';
    
    if ($accountId > 0) {
        if ($filterType == 'financial_period') {
            $financialPeriodId = isset($_POST['financial_period_id']) ? intval($_POST['financial_period_id']) : null;
            if ($financialPeriodId) {
                $ledgerData = $accounting->getLedger($accountId, null, null, $financialPeriodId);
            } else {
                $errorMessage = "Please select a financial period.";
            }
        } else {
            $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
            $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
            
            if ($startDate && $endDate) {
                $ledgerData = $accounting->getLedger($accountId, $startDate, $endDate);
            } else {
                $errorMessage = "Please select both start and end dates.";
            }
        }
        
        if ($ledgerData && isset($ledgerData['error'])) {
            $errorMessage = $ledgerData['error'];
            $ledgerData = null;
        }
    } else {
        $errorMessage = "Please select an account.";
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv' && isset($_GET['account_id'])) {
    $accountId = intval($_GET['account_id']);
    $exportStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $exportEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $exportPeriodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;
    
    // Clear any previous output
    ob_clean();
    
    // Initialize accounting class for export
    $exportAccounting = new AccountingReports();
    
    // Get ledger data
    if ($exportPeriodId) {
        $exportLedger = $exportAccounting->getLedger($accountId, null, null, $exportPeriodId);
    } else {
        $exportLedger = $exportAccounting->getLedger($accountId, $exportStartDate, $exportEndDate);
    }
    
    if ($exportLedger && !isset($exportLedger['error'])) {
        $account_name = str_replace(' ', '_', $exportLedger['account']['account_name']);
        $filename = "Ledger_{$account_name}_" . date('Ymd') . '.xls';
        
        // Use HTML-based Excel format like balance sheet
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        echo '<body>';
        
        // Company Header
        echo '<table border="0" width="100%" style="text-align:center;">';
        echo '<tr><td colspan="7" style="font-size:12px; font-weight:bold;">JAYALAKSHMI ENTERPRISES, THANISSERY, THRISSUR</td></tr>';
        echo '<tr><td colspan="7" style="font-size:11px; font-weight:bold; text-decoration:underline;">ACCOUNT LEDGER REPORT</td></tr>';
        echo '<tr><td colspan="7">&nbsp;</td></tr>';
        echo '</table>';
        
        // Account Information
        echo '<table border="0" width="100%">';
        echo '<tr><td width="20%" style="font-weight:bold;">Account:</td><td width="80%">' . htmlspecialchars($exportLedger['account']['account_code']) . ' - ' . htmlspecialchars($exportLedger['account']['account_name']) . '</td></tr>';
        echo '<tr><td style="font-weight:bold;">Type:</td><td>' . htmlspecialchars($exportLedger['account']['account_type']) . '</td></tr>';
        
        if (isset($exportLedger['filter_info']['name'])) {
            echo '<tr><td style="font-weight:bold;">Period:</td><td>' . htmlspecialchars($exportLedger['filter_info']['name']) . '</td></tr>';
        } else {
            echo '<tr><td style="font-weight:bold;">Period:</td><td>' . date('d-M-Y', strtotime($exportLedger['filter_info']['start_date'])) . ' to ' . date('d-M-Y', strtotime($exportLedger['filter_info']['end_date'])) . '</td></tr>';
        }
        
        echo '<tr><td style="font-weight:bold;">Generated:</td><td>' . date('d-M-Y H:i') . '</td></tr>';
        echo '<tr><td colspan="2">&nbsp;</td></tr>';
        echo '</table>';
        
        // Summary
        echo '<table border="0" width="100%">';
        echo '<tr><td colspan="2" style="font-weight:bold; background-color:#f0f0f0;">PERIOD SUMMARY</td></tr>';
        echo '<tr><td width="30%" style="font-weight:bold;">Opening Balance:</td><td width="70%" style="text-align:right;">' . number_format($exportLedger['opening_balance'], 2) . '</td></tr>';
        echo '<tr><td style="font-weight:bold;">Total Debits:</td><td style="text-align:right;">' . number_format($exportLedger['period_totals']['total_debit'], 2) . '</td></tr>';
        echo '<tr><td style="font-weight:bold;">Total Credits:</td><td style="text-align:right;">' . number_format($exportLedger['period_totals']['total_credit'], 2) . '</td></tr>';
        echo '<tr><td style="font-weight:bold;">Closing Balance:</td><td style="text-align:right;">' . number_format($exportLedger['closing_balance'], 2) . '</td></tr>';
        echo '<tr><td style="font-weight:bold;">Total Transactions:</td><td style="text-align:right;">' . $exportLedger['period_totals']['transaction_count'] . '</td></tr>';
        echo '<tr><td colspan="2">&nbsp;</td></tr>';
        echo '</table>';
        
        // Main ledger table
        echo '<table border="1" style="border-collapse:collapse; width:100%;">';
        echo '<tr style="background-color:#f0f0f0; font-weight:bold;">';
        echo '<td width="12%" style="text-align:center;">Date</td>';
        echo '<td width="35%" style="text-align:center;">Description</td>';
        echo '<td width="15%" style="text-align:center;">Reference</td>';
        echo '<td width="10%" style="text-align:center;">Type</td>';
        echo '<td width="14%" style="text-align:center;">Debit</td>';
        echo '<td width="14%" style="text-align:center;">Credit</td>';
        echo '</tr>';
        
        // Opening balance row
        echo '<tr style="background-color:#e6f3ff; font-weight:bold;">';
        echo '<td style="text-align:center;">' . date('d-M-Y', strtotime($exportLedger['filter_info']['start_date'])) . '</td>';
        echo '<td>Opening Balance</td>';
        echo '<td style="text-align:center;">-</td>';
        echo '<td style="text-align:center;">-</td>';
        echo '<td style="text-align:right;">' . ($exportLedger['opening_balance'] > 0 ? number_format($exportLedger['opening_balance'], 2) : '-') . '</td>';
        echo '<td style="text-align:right;">' . ($exportLedger['opening_balance'] < 0 ? number_format(abs($exportLedger['opening_balance']), 2) : '-') . '</td>';
        echo '</tr>';
        
        // Transaction rows
        foreach ($exportLedger['entries'] as $entry) {
            // Clean description
            $description = $entry['description'];
            
            // Simplify long descriptions
            if (strpos($description, 'Gold loan disbursement') !== false) {
                $description = 'Gold Loan Disbursement';
            } elseif (strpos($description, 'Gold loan closure') !== false) {
                $description = 'Gold Loan Closure';
            } elseif (strpos($description, 'Bank Repledge') !== false) {
                $description = 'Bank Repledge';
            } elseif (strpos($description, 'Expense:') !== false) {
                $parts = explode(' - ', $description);
                $description = isset($parts[0]) ? $parts[0] : $description;
                $description = str_replace('Expense: ', '', $description);
            } elseif (strpos($description, 'Asset Purchase') !== false) {
                $description = 'Asset Purchase';
            } elseif (strpos($description, 'Capital Withdrawal') !== false) {
                $description = 'Capital Withdrawal';
            }
            
            // Limit description length
            if (strlen($description) > 40) {
                $description = substr($description, 0, 37) . '...';
            }
            
            // Clean reference
            $reference = $entry['reference_no'];
            if (strlen($reference) > 15) {
                $reference = substr($reference, 0, 12) . '...';
            }
            
            echo '<tr>';
            echo '<td style="text-align:center;">' . date('d-M-Y', strtotime($entry['transaction_date'])) . '</td>';
            echo '<td>' . htmlspecialchars($description) . '</td>';
            echo '<td style="text-align:center;">' . htmlspecialchars($reference) . '</td>';
            echo '<td style="text-align:center;">' . htmlspecialchars($entry['transaction_type']) . '</td>';
            echo '<td style="text-align:right;">' . ($entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-') . '</td>';
            echo '<td style="text-align:right;">' . ($entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-') . '</td>';
            echo '</tr>';
        }
        
        // Closing balance row
        echo '<tr style="background-color:#ffe6f3; font-weight:bold;">';
        echo '<td style="text-align:center;">' . date('d-M-Y', strtotime($exportLedger['filter_info']['end_date'])) . '</td>';
        echo '<td>Closing Balance</td>';
        echo '<td style="text-align:center;">-</td>';
        echo '<td style="text-align:center;">-</td>';
        echo '<td style="text-align:right;">' . ($exportLedger['closing_balance'] > 0 ? number_format($exportLedger['closing_balance'], 2) : '-') . '</td>';
        echo '<td style="text-align:right;">' . ($exportLedger['closing_balance'] < 0 ? number_format(abs($exportLedger['closing_balance']), 2) : '-') . '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        // Footer
        echo '<br><table border="0" width="100%">';
        echo '<tr>';
        echo '<td width="50%">Place: Thanissery, Thrissur</td>';
        echo '<td width="50%" style="text-align:right;">Generated by Accounting System</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Date: ' . date('d/m/Y') . '</td>';
        echo '<td></td>';
        echo '</tr>';
        echo '</table>';
        
        echo '</body></html>';
    }
    exit;
}

// Handle PDF Export
if (isset($_GET['export']) && $_GET['export'] == 'pdf' && isset($_GET['account_id'])) {
    $accountId = intval($_GET['account_id']);
    $exportStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $exportEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $exportPeriodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;
    
    // Clear any previous output
    ob_clean();
    
    // Initialize accounting class for export
    $exportAccounting = new AccountingReports();
    
    // Get ledger data
    if ($exportPeriodId) {
        $exportLedger = $exportAccounting->getLedger($accountId, null, null, $exportPeriodId);
    } else {
        $exportLedger = $exportAccounting->getLedger($accountId, $exportStartDate, $exportEndDate);
    }
    
    if ($exportLedger && !isset($exportLedger['error'])) {
        
        class LedgerPDF extends FPDF {
            private $account;
            private $filterInfo;
            
            function setLedgerData($account, $filterInfo) {
                $this->account = $account;
                $this->filterInfo = $filterInfo;
            }
            
            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
                $this->SetFont('Arial', '', 12);
                $this->Cell(0, 8, 'Account Ledger Report', 0, 1, 'C');
                $this->Ln(5);
                
                // Account details
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 8, 'Account: ' . $this->account['account_code'] . ' - ' . $this->account['account_name'], 0, 1);
                $this->SetFont('Arial', '', 10);
                $this->Cell(0, 6, 'Account Type: ' . $this->account['account_type'], 0, 1);
                
                if (isset($this->filterInfo['name'])) {
                    $this->Cell(0, 6, 'Period: ' . $this->filterInfo['name'], 0, 1);
                } else {
                    $this->Cell(0, 6, 'Period: ' . date('M d, Y', strtotime($this->filterInfo['start_date'])) . ' to ' . date('M d, Y', strtotime($this->filterInfo['end_date'])), 0, 1);
                }
                
                $this->Cell(0, 6, 'Generated: ' . date('M d, Y H:i'), 0, 1);
                $this->Ln(5);
                
                // Table header - Adjusted column widths to fit within page bounds
                $this->SetFont('Arial', 'B', 8);
                $this->SetFillColor(52, 58, 64);
                $this->SetTextColor(255, 255, 255);
                
                $this->Cell(22, 8, 'Date', 1, 0, 'C', true);
                $this->Cell(55, 8, 'Description', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Reference', 1, 0, 'C', true);
                $this->Cell(18, 8, 'Type', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Debit', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Credit', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Balance', 1, 1, 'C', true);
                
                $this->SetTextColor(0, 0, 0);
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - Generated by Jayalakshmi Enterprises Accounting System', 0, 0, 'C');
            }
            
            function addLedgerRow($date, $description, $reference, $type, $debit, $credit, $balance, $isSpecial = false) {
                if ($isSpecial) {
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetFillColor(240, 240, 240);
                } else {
                    $this->SetFont('Arial', '', 7);
                    $this->SetFillColor(255, 255, 255);
                }
                
                // Check if we need a new page
                if ($this->GetY() > 250) {
                    $this->AddPage();
                }
                
                $this->Cell(22, 6, date('d/m/y', strtotime($date)), 1, 0, 'C', $isSpecial);
                
                // Handle long descriptions - truncate to fit
                $desc = strlen($description) > 28 ? substr($description, 0, 25) . '...' : $description;
                $this->Cell(55, 6, $desc, 1, 0, 'L', $isSpecial);
                
                // Truncate reference if too long
                $ref = strlen($reference) > 12 ? substr($reference, 0, 9) . '...' : $reference;
                $this->Cell(20, 6, $ref, 1, 0, 'C', $isSpecial);
                
                // Truncate type if too long
                $typeShort = strlen($type) > 10 ? substr($type, 0, 7) . '...' : $type;
                $this->Cell(18, 6, $typeShort, 1, 0, 'C', $isSpecial);
                
                $this->Cell(25, 6, $debit > 0 ? number_format($debit, 0) : '-', 1, 0, 'R', $isSpecial);
                $this->Cell(25, 6, $credit > 0 ? number_format($credit, 0) : '-', 1, 0, 'R', $isSpecial);
                $this->Cell(25, 6, number_format($balance, 0), 1, 1, 'R', $isSpecial);
            }
            
            function addSummarySection($openingBalance, $totalDebits, $totalCredits, $closingBalance, $transactionCount) {
                $this->Ln(10);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 8, 'Period Summary', 0, 1);
                
                $this->SetFont('Arial', '', 10);
                $this->Cell(60, 6, 'Opening Balance:', 0, 0);
                $this->Cell(50, 6, 'Rs. ' . number_format($openingBalance, 2), 0, 1, 'R');
                
                $this->Cell(60, 6, 'Total Debits:', 0, 0);
                $this->Cell(50, 6, 'Rs. ' . number_format($totalDebits, 2), 0, 1, 'R');
                
                $this->Cell(60, 6, 'Total Credits:', 0, 0);
                $this->Cell(50, 6, 'Rs. ' . number_format($totalCredits, 2), 0, 1, 'R');
                
                $this->Cell(60, 6, 'Net Movement:', 0, 0);
                $this->Cell(50, 6, 'Rs. ' . number_format($totalDebits - $totalCredits, 2), 0, 1, 'R');
                
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(60, 6, 'Closing Balance:', 0, 0);
                $this->Cell(50, 6, 'Rs. ' . number_format($closingBalance, 2), 0, 1, 'R');
                
                $this->Ln(5);
                $this->SetFont('Arial', '', 9);
                $this->Cell(0, 6, 'Total Transactions: ' . $transactionCount, 0, 1);
            }
        }
        
        // Create PDF
        $pdf = new LedgerPDF();
        $pdf->setLedgerData($exportLedger['account'], $exportLedger['filter_info']);
        $pdf->AddPage();
        
        // Add opening balance
        $pdf->addLedgerRow(
            $exportLedger['filter_info']['start_date'],
            'Opening Balance',
            '',
            '',
            $exportLedger['opening_balance'] > 0 ? $exportLedger['opening_balance'] : 0,
            $exportLedger['opening_balance'] < 0 ? abs($exportLedger['opening_balance']) : 0,
            $exportLedger['opening_balance'],
            true
        );
        
        // Add transactions
        foreach ($exportLedger['entries'] as $entry) {
            $pdf->addLedgerRow(
                $entry['transaction_date'],
                $entry['description'],
                $entry['reference_no'],
                $entry['transaction_type'],
                $entry['debit'],
                $entry['credit'],
                $entry['running_balance']
            );
        }
        
        // Add closing balance
        $pdf->addLedgerRow(
            $exportLedger['filter_info']['end_date'],
            'Closing Balance',
            '',
            '',
            $exportLedger['closing_balance'] > 0 ? $exportLedger['closing_balance'] : 0,
            $exportLedger['closing_balance'] < 0 ? abs($exportLedger['closing_balance']) : 0,
            $exportLedger['closing_balance'],
            true
        );
        
        // Add summary
        $pdf->addSummarySection(
            $exportLedger['opening_balance'],
            $exportLedger['period_totals']['total_debit'],
            $exportLedger['period_totals']['total_credit'],
            $exportLedger['closing_balance'],
            $exportLedger['period_totals']['transaction_count']
        );
        
        // Output PDF
        $account_name = str_replace(' ', '_', $exportLedger['account']['account_name']);
        $filename = "ledger_{$account_name}_" . date('Ymd_His') . '.pdf';
        
        $pdf->Output('D', $filename);
    }
    exit;
}

// Get all accounts for dropdown
$accountsResult = $accountManager->getAccountsForDropdown();

// Get financial periods for dropdown  
$periodsResult = $helper->getFinancialPeriods();

ob_end_flush(); // End output buffering and send content
?>

<style>
.ledger-container {
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

.ledger-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.account-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
}

.balance-summary {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin: 15px 0;
}

.filter-toggle {
    background: #e9ecef;
    border-radius: 25px;
    padding: 5px;
    display: inline-flex;
    margin-bottom: 15px;
}

.filter-toggle label {
    padding: 8px 20px;
    margin: 0;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-toggle input[type="radio"]:checked + label {
    background: #007bff;
    color: white;
}

.filter-toggle input[type="radio"] {
    display: none;
}

.ledger-table {
    font-size: 0.9rem;
}

.ledger-table th {
    background: #343a40;
    color: white;
    font-weight: 600;
    border: none;
}

.ledger-table th.debit-col,
.ledger-table th.credit-col {
    background: #343a40;
    color: white;
}

.ledger-table td.debit-col {
    background: #fff5f5;
}

.ledger-table td.credit-col {
    background: #f0fff4;
}

.opening-balance {
    background: #e3f2fd !important;
    font-weight: bold;
}

.closing-balance {
    background: #f3e5f5 !important;
    font-weight: bold;
}

.export-buttons {
    margin: 15px 0;
}

.related-accounts {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .ledger-container {
        background: white;
        padding: 0;
    }
    
    .filter-card, .export-buttons {
        display: none;
    }
}
</style>

<div class="ledger-container">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fa fa-book"></i> Account Ledger</h2>
                <p class="text-muted">View detailed account transactions with opening and closing balances</p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="row no-print">
            <div class="col-12">
                <div class="card filter-card">
                    <div class="card-body">
                        <form method="POST" id="ledgerForm">
                            <div class="row">
                                
                                <!-- Account Selection -->
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Select Account</strong></label>
                                    <select name="account_id" class="form-select" required>
                                        <option value="">Choose Account...</option>
                                        <?php 
                                        if ($accountsResult) {
                                            $currentType = '';
                                            while ($account = $accountsResult->fetch_assoc()) {
                                                if ($currentType != $account['account_type']) {
                                                    if ($currentType != '') echo '</optgroup>';
                                                    echo '<optgroup label="' . $account['account_type'] . ' Accounts">';
                                                    $currentType = $account['account_type'];
                                                }
                                                $selected = (isset($_POST['account_id']) && $_POST['account_id'] == $account['id']) ? 'selected' : '';
                                                echo '<option value="' . $account['id'] . '" ' . $selected . '>';
                                                echo $account['account_code'] . ' - ' . $account['account_name'];
                                                echo '</option>';
                                            }
                                            if ($currentType != '') echo '</optgroup>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Filter Type Toggle -->
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Filter By</strong></label>
                                    <div class="filter-toggle">
                                        <input type="radio" name="filter_type" value="date_range" id="date_filter" 
                                               <?php echo ($filterType == 'date_range') ? 'checked' : ''; ?>>
                                        <label for="date_filter">Date Range</label>
                                        
                                        <input type="radio" name="filter_type" value="financial_period" id="period_filter"
                                               <?php echo ($filterType == 'financial_period') ? 'checked' : ''; ?>>
                                        <label for="period_filter">Financial Period</label>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fa fa-search"></i> Generate Ledger
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearForm()">
                                        <i class="fa fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>

                            <!-- Date Range Filters -->
                            <div class="row mt-3" id="dateFilters" style="<?php echo ($filterType == 'financial_period') ? 'display:none' : ''; ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($startDate); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($endDate); ?>">
                                </div>
                            </div>

                            <!-- Financial Period Filter -->
                            <div class="row mt-3" id="periodFilters" style="<?php echo ($filterType == 'date_range') ? 'display:none' : ''; ?>">
                                <div class="col-md-4">
                                    <label class="form-label">Financial Period</label>
                                    <select name="financial_period_id" class="form-select">
                                        <option value="">Select Period...</option>
                                        <?php 
                                        if ($periodsResult) {
                                            while ($period = $periodsResult->fetch_assoc()) {
                                                $selected = (isset($_POST['financial_period_id']) && $_POST['financial_period_id'] == $period['id']) ? 'selected' : '';
                                                $status = $period['is_closed'] ? ' (Closed)' : ' (Open)';
                                                echo '<option value="' . $period['id'] . '" ' . $selected . '>';
                                                echo $period['period_name'] . ' (' . date('M d, Y', strtotime($period['start_date'])) . ' - ' . date('M d, Y', strtotime($period['end_date'])) . ')' . $status;
                                                echo '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($errorMessage): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ledger Results -->
        <?php if ($ledgerData && !isset($ledgerData['error'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="card ledger-card">
                    
                    <!-- Account Header -->
                    <div class="account-header">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="mb-1">
                                    <?php echo htmlspecialchars($ledgerData['account']['account_code']); ?> - 
                                    <?php echo htmlspecialchars($ledgerData['account']['account_name']); ?>
                                </h4>
                                <p class="mb-0">
                                    <span class="badge bg-light text-dark me-2"><?php echo $ledgerData['account']['account_type']; ?></span>
                                    <span class="text-light"><?php echo $ledgerData['filter_info']['type']; ?>: 
                                    <?php 
                                    if (isset($ledgerData['filter_info']['name'])) {
                                        echo htmlspecialchars($ledgerData['filter_info']['name']);
                                    } else {
                                        echo date('M d, Y', strtotime($ledgerData['filter_info']['start_date'])) . ' to ' . 
                                             date('M d, Y', strtotime($ledgerData['filter_info']['end_date']));
                                    }
                                    ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="export-buttons no-print">
                                    <?php 
                                    // CSV Export URL
                                    $csvParams = [
                                        'export' => 'csv',
                                        'account_id' => isset($_POST['account_id']) ? $_POST['account_id'] : ''
                                    ];
                                    
                                    // PDF Export URL
                                    $pdfParams = [
                                        'export' => 'pdf',
                                        'account_id' => isset($_POST['account_id']) ? $_POST['account_id'] : ''
                                    ];
                                    
                                    if ($filterType == 'financial_period' && $financialPeriodId) {
                                        $csvParams['period_id'] = $financialPeriodId;
                                        $pdfParams['period_id'] = $financialPeriodId;
                                    } else {
                                        $csvParams['start_date'] = $ledgerData['filter_info']['start_date'];
                                        $csvParams['end_date'] = $ledgerData['filter_info']['end_date'];
                                        $pdfParams['start_date'] = $ledgerData['filter_info']['start_date'];
                                        $pdfParams['end_date'] = $ledgerData['filter_info']['end_date'];
                                    }
                                    
                                    $csvUrl = '?' . http_build_query($csvParams);
                                    $pdfUrl = '?' . http_build_query($pdfParams);
                                    ?>
                                    <a href="<?php echo $csvUrl; ?>" class="btn btn-success btn-sm me-2">
                                        <i class="fa fa-file-excel-o"></i> Export Excel
                                    </a>
                                    <a href="<?php echo $pdfUrl; ?>" class="btn btn-danger btn-sm">
                                        <i class="fa fa-file-pdf-o"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <!-- Balance Summary -->
                        <div class="balance-summary">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h6 class="text-muted mb-1">Opening Balance</h6>
                                    <h5 class="mb-0">₹<?php echo number_format($ledgerData['opening_balance'], 2); ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted mb-1">Total Debits</h6>
                                    <h5 class="mb-0 text-danger">₹<?php echo number_format($ledgerData['period_totals']['total_debit'], 2); ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted mb-1">Total Credits</h6>
                                    <h5 class="mb-0 text-success">₹<?php echo number_format($ledgerData['period_totals']['total_credit'], 2); ?></h5>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted mb-1">Closing Balance</h6>
                                    <h5 class="mb-0">₹<?php echo number_format($ledgerData['closing_balance'], 2); ?></h5>
                                </div>
                            </div>
                        </div>

                        <!-- Ledger Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered ledger-table">
                                <thead>
                                    <tr>
                                        <th width="10%">Date</th>
                                        <th width="35%">Description</th>
                                        <th width="12%">Reference</th>
                                        <th width="8%">Type</th>
                                        <th width="12%" class="text-end debit-col">Debit (₹)</th>
                                        <th width="12%" class="text-end credit-col">Credit (₹)</th>
                                        <th width="11%" class="text-end">Balance (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    
                                    <!-- Opening Balance Row -->
                                    <tr class="opening-balance">
                                        <td><?php echo date('M d, Y', strtotime($ledgerData['filter_info']['start_date'])); ?></td>
                                        <td><strong>Opening Balance</strong></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td class="text-end"><?php echo $ledgerData['opening_balance'] > 0 ? number_format($ledgerData['opening_balance'], 2) : '-'; ?></td>
                                        <td class="text-end"><?php echo $ledgerData['opening_balance'] < 0 ? number_format(abs($ledgerData['opening_balance']), 2) : '-'; ?></td>
                                        <td class="text-end"><strong><?php echo number_format($ledgerData['opening_balance'], 2); ?></strong></td>
                                    </tr>

                                    <!-- Transaction Rows -->
                                    <?php if (!empty($ledgerData['entries'])): ?>
                                        <?php foreach ($ledgerData['entries'] as $entry): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($entry['transaction_date'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($entry['description']); ?>
                                                <?php if ($entry['detail_description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($entry['detail_description']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($entry['status'] == 'Draft'): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($entry['reference_no']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $entry['transaction_type']; ?></span></td>
                                            <td class="text-end debit-col"><?php echo $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-'; ?></td>
                                            <td class="text-end credit-col"><?php echo $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-'; ?></td>
                                            <td class="text-end"><?php echo number_format($entry['running_balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle"></i> No transactions found for the selected period.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Closing Balance Row -->
                                    <tr class="closing-balance">
                                        <td><?php echo date('M d, Y', strtotime($ledgerData['filter_info']['end_date'])); ?></td>
                                        <td><strong>Closing Balance</strong></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td class="text-end"><?php echo $ledgerData['closing_balance'] > 0 ? number_format($ledgerData['closing_balance'], 2) : '-'; ?></td>
                                        <td class="text-end"><?php echo $ledgerData['closing_balance'] < 0 ? number_format(abs($ledgerData['closing_balance']), 2) : '-'; ?></td>
                                        <td class="text-end"><strong><?php echo number_format($ledgerData['closing_balance'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Period Summary -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Period Summary</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><strong>Total Transactions:</strong> <?php echo $ledgerData['period_totals']['transaction_count']; ?></li>
                                            <li><strong>Net Movement:</strong> ₹<?php echo number_format($ledgerData['period_totals']['net_movement'], 2); ?></li>
                                            <li><strong>Generated:</strong> <?php echo date('M d, Y H:i', strtotime($ledgerData['generated_at'])); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Related Accounts -->
                            <?php if (!empty($ledgerData['related_accounts'])): ?>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Frequently Transacted Accounts</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-center">Transactions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($ledgerData['related_accounts'], 0, 5) as $related): ?>
                                                    <tr>
                                                        <td>
                                                            <small><?php echo htmlspecialchars($related['account_code']); ?></small><br>
                                                            <?php echo htmlspecialchars($related['account_name']); ?>
                                                        </td>
                                                        <td class="text-center"><?php echo $related['transaction_count']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
        <?php endif; ?>

    </div>
</div>

<script>
// Toggle between date range and financial period filters
document.querySelectorAll('input[name="filter_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const dateFilters = document.getElementById('dateFilters');
        const periodFilters = document.getElementById('periodFilters');
        
        if (this.value === 'date_range') {
            dateFilters.style.display = 'block';
            periodFilters.style.display = 'none';
        } else {
            dateFilters.style.display = 'none';
            periodFilters.style.display = 'block';
        }
    });
});

// Clear form function
function clearForm() {
    document.getElementById('ledgerForm').reset();
    document.getElementById('dateFilters').style.display = 'block';
    document.getElementById('periodFilters').style.display = 'none';
}

// Auto-submit form when account changes (optional)
document.querySelector('select[name="account_id"]').addEventListener('change', function() {
    // Uncomment the line below if you want auto-submit
    // document.getElementById('ledgerForm').submit();
});
</script>

<?php include_once "inc/footer.php"; ?>