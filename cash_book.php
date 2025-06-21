<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/accounting/AccountingReports.php";
require('fpdf186/fpdf.php'); // Include FPDF library

// Initialize AccountingReports class
$accounting = new AccountingReports();

// Initialize variables
$cashBookData = array();
$startDate = '';
$endDate = '';
$openingBalance = 0;
$closingBalance = 0;

// Handle date filtering
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
} else {
    // Default to current financial year (Apr-Mar)
    $currentYear = date('Y');
    $currentMonth = date('m');
    
    if ($currentMonth >= 4) {
        $startDate = $currentYear . '-04-01';
        $endDate = ($currentYear + 1) . '-03-31';
    } else {
        $startDate = ($currentYear - 1) . '-04-01';
        $endDate = $currentYear . '-03-31';
    }
}

// Get cash book data using the existing AccountingReports class
$cashBookResult = $accounting->getCashBook($startDate, $endDate, 1001); // Cash in Hand account
$openingBalance = $cashBookResult['opening_balance'];
$cashBookData = $cashBookResult['entries'];
$closingBalance = $cashBookResult['closing_balance'];

// Process data to match the format in your image
function formatCashBookEntries($openingBalance, $cashBookData, $startDate, $endDate) {
    $formattedEntries = array();
    $runningBalance = $openingBalance;
    $voucherCounter = 1;

    $formattedEntries[] = array(
        'date' => $startDate,
        'particulars' => 'To Opening Balance',
        'vch_type' => '',
        'vch_no' => '',
        'debit' => $openingBalance > 0 ? $openingBalance : 0,
        'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
        'balance' => $runningBalance,
        'type' => 'opening'
    );

    foreach ($cashBookData as $transaction) {
        $runningBalance = $transaction['balance'];
        $particulars = ($transaction['debit'] > 0 ? 'To ' : 'By ') . $transaction['description'];

        $formattedEntries[] = array(
            'date' => $transaction['transaction_date'],
            'particulars' => $particulars,
            'vch_type' => $transaction['transaction_type'],
            'vch_no' => $voucherCounter,
            'debit' => $transaction['debit'],
            'credit' => $transaction['credit'],
            'balance' => $runningBalance,
            'type' => 'transaction'
        );
        $voucherCounter++;
    }

    $formattedEntries[] = array(
        'date' => $endDate,
        'particulars' => 'By Closing Balance',
        'vch_type' => '',
        'vch_no' => '',
        'debit' => $runningBalance < 0 ? abs($runningBalance) : 0,
        'credit' => $runningBalance > 0 ? $runningBalance : 0,
        'balance' => $runningBalance,
        'type' => 'closing'
    );

    return $formattedEntries;
}

$formattedEntries = formatCashBookEntries($openingBalance, $cashBookData, $startDate, $endDate);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    ob_end_clean();

    class CashBookPDF extends FPDF {
        private $startDate;
        private $endDate;

        function __construct($startDate, $endDate) {
            parent::__construct();
            $this->startDate = $startDate;
            $this->endDate = $endDate;
        }

        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 8, 'Jayalakshmi Enterprises', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Thanissery', 0, 1, 'C');
            $this->Cell(0, 5, 'Thrissur', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'Cash Book', 0, 1, 'C');
            $this->Ln(2);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'continued...', 0, 0, 'R');
        }

        function CashBookTable($entries) {
            $this->SetFont('Arial', '', 9);
            $dateRange = date('d-M-Y', strtotime($this->startDate)) . ' to ' . date('d-M-Y', strtotime($this->endDate));
            $this->Cell(140, 6, $dateRange, 0, 0, 'L');
            $this->Cell(50, 6, 'Page 1', 0, 1, 'R');
            $this->Ln(2);

            $this->SetFont('Arial', 'B', 8);
            $this->Cell(25, 6, 'Date', 1, 0, 'C');
            $this->Cell(75, 6, 'Particulars', 1, 0, 'C');
            $this->Cell(20, 6, 'Vch Type', 1, 0, 'C');
            $this->Cell(15, 6, 'Vch No', 1, 0, 'C');
            $this->Cell(25, 6, 'Debit', 1, 0, 'C');
            $this->Cell(25, 6, 'Credit', 1, 1, 'C');
            $this->SetFont('Arial', '', 7);

            $pageNum = 1;
            foreach ($entries as $entry) {
                $this->Cell(25, 5, date('d-m-Y', strtotime($entry['date'])), 1, 0, 'C');
                $this->Cell(75, 5, substr($entry['particulars'], 0, 35), 1, 0, 'L');
                $this->Cell(20, 5, $entry['vch_type'], 1, 0, 'C');
                $this->Cell(15, 5, $entry['vch_no'], 1, 0, 'C');
                $this->Cell(25, 5, $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '', 1, 0, 'R');
                $this->Cell(25, 5, $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '', 1, 1, 'R');

                if ($entry['type'] !== 'transaction') {
                    $this->Cell(135, 4, '', 0, 0);
                    $this->Cell(50, 4, number_format($entry['balance'], 2), 0, 1, 'R');
                }

                if ($this->GetY() > 250) {
                    $this->AddPage();
                    $pageNum++;

                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell(25, 6, 'Date', 1, 0, 'C');
                    $this->Cell(75, 6, 'Particulars', 1, 0, 'C');
                    $this->Cell(20, 6, 'Vch Type', 1, 0, 'C');
                    $this->Cell(15, 6, 'Vch No', 1, 0, 'C');
                    $this->Cell(25, 6, 'Debit', 1, 0, 'C');
                    $this->Cell(25, 6, 'Credit', 1, 1, 'C');
                    $this->SetFont('Arial', '', 7);
                }
            }
        }
    }

    $pdf = new CashBookPDF($startDate, $endDate);
    $pdf->AddPage();
    $pdf->CashBookTable($formattedEntries, $startDate, $endDate);
    $pdf->Output('D', 'Cash_Book_' . date('d-M-Y', strtotime($startDate)) . '_to_' . date('d-M-Y', strtotime($endDate)) . '.pdf');
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    ob_end_clean();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Cash_Book_' . date('d-M-Y', strtotime($startDate)) . '_to_' . date('d-M-Y', strtotime($endDate)) . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table border="0" width="100%">';
    echo '<tr><td colspan="7" align="center" style="font-size:16px; font-weight:bold;">Jayalakshmi Enterprises</td></tr>';
    echo '<tr><td colspan="7" align="center" style="font-size:10px;">Treasury</td></tr>';
    echo '<tr><td colspan="7" align="center" style="font-size:10px;">Thrissur</td></tr>';
    echo '<tr><td colspan="7" align="center" style="font-size:14px; font-weight:bold;">Cash Book</td></tr>';
    echo '<tr><td colspan="7" align="right" style="font-size:9px;">' . date('d-M-Y', strtotime($startDate)) . ' to ' . date('d-M-Y', strtotime($endDate)) . '</td></tr>';
    echo '<tr><td colspan="7">&nbsp;</td></tr>';
    echo '</table>';
    
    // Data table
    echo '<table border="1" style="border-collapse:collapse;">';
    echo '<tr style="background-color:#f0f0f0; font-weight:bold;">';
    echo '<td width="80">Date</td>';
    echo '<td width="300">Particulars</td>';
    echo '<td width="80">Vch Type</td>';
    echo '<td width="60">Vch No</td>';
    echo '<td width="100">Debit</td>';
    echo '<td width="100">Credit</td>';
    echo '<td width="60">Page</td>';
    echo '</tr>';
    
    $pageNum = 1;
    foreach ($formattedEntries as $entry) {
        $bgColor = '';
        if ($entry['type'] == 'opening') $bgColor = 'background-color:#e6f3ff;';
        if ($entry['type'] == 'closing') $bgColor = 'background-color:#fff2cc;';
        
        echo '<tr style="' . $bgColor . '">';
        echo '<td>' . date('d-m-Y', strtotime($entry['date'])) . '</td>';
        echo '<td>' . htmlspecialchars($entry['particulars']) . '</td>';
        echo '<td>' . $entry['vch_type'] . '</td>';
        echo '<td>' . $entry['vch_no'] . '</td>';
        echo '<td align="right">' . ($entry['debit'] > 0 ? number_format($entry['debit'], 2) : '') . '</td>';
        echo '<td align="right">' . ($entry['credit'] > 0 ? number_format($entry['credit'], 2) : '') . '</td>';
        echo '<td align="center">' . $pageNum . '</td>';
        echo '</tr>';
        
        // Add balance rows for key entries
        if ($entry['type'] != 'transaction') {
            echo '<tr style="' . $bgColor . '">';
            echo '<td colspan="4"></td>';
            echo '<td colspan="2" align="right" style="font-style:italic;">' . number_format($entry['balance'], 2) . '</td>';
            echo '<td></td>';
            echo '</tr>';
        }
    }
    
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
                <h2 style="margin-bottom: 5px;">Jayalakshmi Enterprises</h2>
                <p style="margin-bottom: 2px; color: #666; font-size: 12px;">Treasury</p>
                <p style="margin-bottom: 8px; color: #666; font-size: 12px;">Thrissur</p>
                <h3 style="margin-bottom: 10px;">Cash Book</h3>
                <div class="d-flex justify-content-between align-items-center">
                    <span></span>
                    <p class="text-muted mb-0" style="font-size: 11px;"><?php echo date('d-M-Y', strtotime($startDate)) . ' to ' . date('d-M-Y', strtotime($endDate)); ?></p>
                    <p class="text-muted mb-0" style="font-size: 11px; font-weight: bold;">Page 1</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fa fa-filter"></i> Filter Options</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">From Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">To Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-info me-2">
                            <i class="fa fa-search"></i> View
                        </button>
                        <?php if (!empty($formattedEntries)): ?>
                        <a href="?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&export=pdf" class="btn btn-danger me-2">
                            <i class="fa fa-file-pdf-o"></i> PDF
                        </a>
                        <a href="?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&export=excel" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Excel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cash Book Data - Exact format from your image -->
    <?php if (!empty($formattedEntries)): ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 10px;">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="width: 80px; text-align: center; border: 1px solid #000;">Date</th>
                            <th style="width: 300px; text-align: center; border: 1px solid #000;">Particulars</th>
                            <th style="width: 80px; text-align: center; border: 1px solid #000;">Vch Type</th>
                            <th style="width: 60px; text-align: center; border: 1px solid #000;">Vch No</th>
                            <th style="width: 100px; text-align: center; border: 1px solid #000;">Debit</th>
                            <th style="width: 100px; text-align: center; border: 1px solid #000;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pageNum = 1;
                        foreach ($formattedEntries as $index => $entry): 
                            $bgColor = '';
                            if ($entry['type'] == 'opening') $bgColor = 'background-color: #e6f3ff;';
                            if ($entry['type'] == 'closing') $bgColor = 'background-color: #fff2cc;';
                        ?>
                        <tr style="<?php echo $bgColor; ?>">
                            <td style="border: 1px solid #000; text-align: center; padding: 3px;">
                                <?php echo date('d-m-Y', strtotime($entry['date'])); ?>
                            </td>
                            <td style="border: 1px solid #000; padding-left: 5px; padding: 3px;">
                                <?php echo htmlspecialchars($entry['particulars']); ?>
                            </td>
                            <td style="border: 1px solid #000; text-align: center; padding: 3px;">
                                <?php echo $entry['vch_type']; ?>
                            </td>
                            <td style="border: 1px solid #000; text-align: center; padding: 3px;">
                                <?php echo $entry['vch_no']; ?>
                            </td>
                            <td style="border: 1px solid #000; text-align: right; padding-right: 5px; padding: 3px;">
                                <?php echo $entry['debit'] > 0 ? number_format($entry['debit'], 2) : ''; ?>
                            </td>
                            <td style="border: 1px solid #000; text-align: right; padding-right: 5px; padding: 3px;">
                                <?php echo $entry['credit'] > 0 ? number_format($entry['credit'], 2) : ''; ?>
                            </td>
                        </tr>
                        
                        <?php 
                        // Add balance information rows like in your image
                        if ($entry['type'] != 'transaction'): ?>
                        <tr style="<?php echo $bgColor; ?>">
                            <td colspan="4" style="border: 1px solid #000; padding: 3px;"></td>
                            <td colspan="2" style="border: 1px solid #000; text-align: right; padding-right: 5px; font-style: italic; padding: 3px;">
                                <?php echo number_format($entry['balance'], 2); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-md-8">
                    <small class="text-muted">
                        <i class="fa fa-info-circle"></i> 
                        Period: <?php echo date('d-M-Y', strtotime($startDate)); ?> to <?php echo date('d-M-Y', strtotime($endDate)); ?> | 
                        Entries: <?php echo count($formattedEntries); ?> | 
                        Net Movement: ₹<?php echo number_format($closingBalance - $openingBalance, 2); ?>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        <strong>Closing Balance: ₹<?php echo number_format($closingBalance, 2); ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="alert alert-info">
                <i class="fa fa-info-circle fa-2x mb-3"></i>
                <h5>No cash book entries found</h5>
                <p class="mb-0">No transactions found for the selected period. Try adjusting the date range or check if there are any posted transactions.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Exact styling to match the image */
.table {
    margin-bottom: 0;
    border-collapse: collapse;
}

.table th, 
.table td {
    border: 1px solid #000 !important;
    vertical-align: middle;
    font-size: 10px;
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
        font-size: 8px;
    }
    
    .container-fluid {
        margin: 0;
        padding: 0;
    }
}
</style>

<?php
include_once "inc/footer.php";
ob_end_flush(); // Flush the output buffer and send output
?>