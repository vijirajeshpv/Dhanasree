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

// Get trial balance data
$trialBalanceData = $accounting->getTrialBalance($asOfDate);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    ob_end_clean();

    class TrialBalancePDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'Jayalakshmi Enterprises', 0, 1, 'C');
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 8, 'Trial Balance', 0, 1, 'C');
            global $asOfDate;
            $this->Cell(0, 8, 'As of ' . date('d-M-Y', strtotime($asOfDate)), 0, 1, 'C');
            $this->Ln(5);
        }
    }

    $pdf = new TrialBalancePDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 10);
    
    // Headers
    $pdf->Cell(30, 8, 'Code', 1, 0, 'C');
    $pdf->Cell(80, 8, 'Account Name', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Debit', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Credit', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 9);
    
    foreach ($trialBalanceData['accounts'] as $type => $accounts) {
        if (!empty($accounts)) {
            // Type header
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(170, 6, $type, 1, 1, 'L');
            $pdf->SetFont('Arial', '', 9);
            
            foreach ($accounts as $account) {
                $pdf->Cell(30, 6, $account['account_code'], 1, 0, 'L');
                $pdf->Cell(80, 6, substr($account['account_name'], 0, 40), 1, 0, 'L');
                $pdf->Cell(30, 6, number_format($account['total_debit'], 2), 1, 0, 'R');
                $pdf->Cell(30, 6, number_format($account['total_credit'], 2), 1, 1, 'R');
            }
        }
    }
    
    // Totals
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(110, 8, 'TOTAL', 1, 0, 'C');
    $pdf->Cell(30, 8, number_format($trialBalanceData['totals']['debit'], 2), 1, 0, 'R');
    $pdf->Cell(30, 8, number_format($trialBalanceData['totals']['credit'], 2), 1, 1, 'R');
    
    $pdf->Output('D', 'Trial_Balance_' . $asOfDate . '.pdf');
    exit;
}
?>

<div class="container-fluid">
    <h3 class="page-heading mb-4">Trial Balance</h3>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">Filter Options</h6>
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
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                        <a href="?as_of_date=<?php echo $asOfDate; ?>&export=pdf" class="btn btn-success">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Trial Balance Data -->
    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Trial Balance as of <?php echo date('d-M-Y', strtotime($asOfDate)); ?></h6>
            <div>
                <?php if ($trialBalanceData['is_balanced']): ?>
                    <span class="badge bg-success">Balanced</span>
                <?php else: ?>
                    <span class="badge bg-danger">Not Balanced</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 120px;">Account Code</th>
                            <th>Account Name</th>
                            <th style="width: 120px; text-align: right;">Debit</th>
                            <th style="width: 120px; text-align: right;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trialBalanceData['accounts'] as $type => $accounts): ?>
                            <?php if (!empty($accounts)): ?>
                                <!-- Account Type Header -->
                                <tr class="table-secondary">
                                    <td colspan="4">
                                        <strong><?php echo $type; ?></strong>
                                    </td>
                                </tr>
                                
                                <!-- Account Rows -->
                                <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?php echo $account['account_code']; ?></td>
                                    <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                    <td style="text-align: right;">
                                        <?php echo $account['total_debit'] > 0 ? number_format($account['total_debit'], 2) : '-'; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php echo $account['total_credit'] > 0 ? number_format($account['total_credit'], 2) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <!-- Totals Row -->
                        <tr class="table-warning">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($trialBalanceData['totals']['debit'], 2); ?></strong>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($trialBalanceData['totals']['credit'], 2); ?></strong>
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
                        Balance Status: 
                        <?php if ($trialBalanceData['is_balanced']): ?>
                            <span class="text-success">✓ Trial Balance is balanced</span>
                        <?php else: ?>
                            <span class="text-danger">✗ Trial Balance is not balanced</span>
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Difference: ₹<?php echo number_format(abs($trialBalanceData['totals']['debit'] - $trialBalanceData['totals']['credit']), 2); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th, .table td {
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

.table thead th {
    background-color: #343a40;
    color: white;
    font-weight: bold;
    text-align: center;
}

.me-2 {
    margin-right: 0.5rem;
}

.text-end {
    text-align: right;
}

.badge {
    font-size: 0.8em;
}
</style>

<?php
include_once "inc/footer.php";
ob_end_flush();
?>