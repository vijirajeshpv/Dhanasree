<?php
// capital_management.php
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/CapitalManager.php";

$capitalManager = new CapitalManager();

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['capital_investment'])) {
        $message = $capitalManager->recordCapitalInvestment(
            $_POST['amount'], 
            $_POST['description'], 
            $_POST['transaction_date'],
            $_POST['source_account']
        );
    } elseif (isset($_POST['capital_withdrawal'])) {
        $message = $capitalManager->recordCapitalWithdrawal(
            $_POST['amount'], 
            $_POST['description'], 
            $_POST['transaction_date'],
            $_POST['destination_account']
        );
    } elseif (isset($_POST['profit_transfer'])) {
        $message = $capitalManager->transferProfitToRetainedEarnings(
            $_POST['net_profit'], 
            $_POST['transfer_date']
        );
    }
}

// Get capital summary
$capitalSummary = $capitalManager->getCapitalSummary();

// Get recent transactions
$recentTransactions = $capitalManager->getCapitalTransactions(null, null, 10);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fa fa-coins"></i> Capital Management - JAYALAKSHMI ENTERPRISES</h6>
                </div>
                <div class="card-body">
                    
                    <!-- Message Display -->
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Capital Summary Dashboard -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Owner Capital</h6>
                                            <h4>₹<?php echo number_format($capitalSummary['capital_balance'], 2); ?></h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-user-tie fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Retained Earnings</h6>
                                            <h4>₹<?php echo number_format($capitalSummary['retained_earnings'], 2); ?></h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-piggy-bank fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Equity</h6>
                                            <h4>₹<?php echo number_format($capitalSummary['total_equity'], 2); ?></h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">This Month</h6>
                                            <small>Investments: ₹<?php echo number_format($capitalSummary['month_investments'], 0); ?></small><br>
                                            <small>Withdrawals: ₹<?php echo number_format($capitalSummary['month_withdrawals'], 0); ?></small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-calendar fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs" id="capitalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab">Capital Transactions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profit-tab" data-bs-toggle="tab" data-bs-target="#profit" type="button" role="tab">Profit/Loss Transfer</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">Reports</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Transaction History</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="capitalTabContent">
                        
                        <!-- Capital Transactions Tab -->
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                            <div class="mt-3">
                                <div class="row">
                                    <!-- Capital Investment -->
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0"><i class="fa fa-plus-circle"></i> Capital Investment</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label class="form-label">Investment Amount *</label>
                                                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                                                        <small class="text-muted">Enter the amount being invested in the business</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Source Account</label>
                                                        <select class="form-control" name="source_account">
                                                            <option value="1001">Cash in Hand</option>
                                                            <option value="1002">Bank Account</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <input type="text" class="form-control" name="description" placeholder="Purpose of investment">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Investment Date</label>
                                                        <input type="date" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    <button type="submit" name="capital_investment" class="btn btn-primary">
                                                        <i class="fa fa-plus"></i> Record Investment
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Capital Withdrawal -->
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0"><i class="fa fa-minus-circle"></i> Capital Withdrawal</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label class="form-label">Withdrawal Amount *</label>
                                                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                                                        <small class="text-muted">Available: ₹<?php echo number_format($capitalSummary['capital_balance'], 2); ?></small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Destination Account</label>
                                                        <select class="form-control" name="destination_account">
                                                            <option value="1001">Cash in Hand</option>
                                                            <option value="1002">Bank Account</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <input type="text" class="form-control" name="description" placeholder="Purpose of withdrawal">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Withdrawal Date</label>
                                                        <input type="date" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    <button type="submit" name="capital_withdrawal" class="btn btn-warning">
                                                        <i class="fa fa-minus"></i> Record Withdrawal
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profit/Loss Transfer Tab -->
                        <div class="tab-pane fade" id="profit" role="tabpanel">
                            <div class="mt-3">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fa fa-exchange-alt"></i> Transfer Profit/Loss to Retained Earnings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <form method="POST">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Net Profit/Loss Amount *</label>
                                                                <input type="number" class="form-control" name="net_profit" step="0.01" required>
                                                                <small class="text-muted">Enter positive for profit, negative for loss</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Transfer Date</label>
                                                                <input type="date" class="form-control" name="transfer_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit" name="profit_transfer" class="btn btn-success">
                                                        <i class="fa fa-exchange-alt"></i> Transfer to Retained Earnings
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-info">
                                                    <h6><i class="fa fa-info-circle"></i> How it works:</h6>
                                                    <ul class="mb-0 small">
                                                        <li><strong>Profit:</strong> Increases retained earnings</li>
                                                        <li><strong>Loss:</strong> Decreases retained earnings</li>
                                                        <li>Usually done at year-end</li>
                                                        <li>Automatically creates journal entries</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reports Tab -->
                        <div class="tab-pane fade" id="reports" role="tabpanel">
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Capital Reports</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">From Date</label>
                                                            <input type="date" class="form-control" id="fromDate" value="<?php echo date('Y-04-01'); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">To Date</label>
                                                            <input type="date" class="form-control" id="toDate" value="<?php echo date('Y-m-d'); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Report Format</label>
                                                            <select class="form-control" id="reportFormat">
                                                                <option value="pdf">PDF</option>
                                                                <option value="excel">Excel</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-outline-primary" onclick="generateCapitalStatement()">
                                                                <i class="fa fa-file-pdf"></i> Capital Statement
                                                            </button>
                                                            <button type="button" class="btn btn-outline-success" onclick="generateMovementReport()">
                                                                <i class="fa fa-chart-line"></i> Movement Report
                                                            </button>
                                                            <button type="button" class="btn btn-outline-info" onclick="generateSummaryReport()">
                                                                <i class="fa fa-chart-pie"></i> Summary Report
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning" onclick="generateMonthlyAnalysis()">
                                                                <i class="fa fa-calendar"></i> Monthly Analysis
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction History Tab -->
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <div class="mt-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fa fa-history"></i> Recent Capital Transactions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-sm">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Transaction Type</th>
                                                        <th>Amount</th>
                                                        <th>Description</th>
                                                        <th>Reference</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($recentTransactions && $recentTransactions->num_rows > 0):
                                                        while ($rt = $recentTransactions->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($rt['transaction_date'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $rt['transaction_type'] == 'Capital Investment' ? 'success' : 
                                                                     ($rt['transaction_type'] == 'Capital Withdrawal' ? 'warning' : 'info'); 
                                                            ?>">
                                                                <?php echo $rt['transaction_type']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="<?php echo $rt['transaction_type'] == 'Capital Investment' ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $rt['transaction_type'] == 'Capital Investment' ? '+' : '-'; ?>₹<?php echo number_format($rt['amount'], 2); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($rt['description']); ?></td>
                                                        <td><?php echo htmlspecialchars($rt['reference_no']); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails('<?php echo $rt['reference_no']; ?>')">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No transactions found</td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button class="btn btn-primary" onclick="loadMoreTransactions()">
                                                <i class="fa fa-plus"></i> Load More Transactions
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="exportTransactions()">
                                                <i class="fa fa-download"></i> Export All
                                            </button>
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
</div>

<script>
// Generate Capital Statement
function generateCapitalStatement() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const format = document.getElementById('reportFormat').value;
    
    const params = new URLSearchParams({
        'report_type': 'statement',
        'from_date': fromDate,
        'to_date': toDate,
        'format': format
    });
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Movement Report
function generateMovementReport() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const format = document.getElementById('reportFormat').value;
    
    const params = new URLSearchParams({
        'report_type': 'movement',
        'from_date': fromDate,
        'to_date': toDate,
        'format': format
    });
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Summary Report
function generateSummaryReport() {
    const format = document.getElementById('reportFormat').value;
    
    const params = new URLSearchParams({
        'report_type': 'summary',
        'format': format
    });
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Monthly Analysis
function generateMonthlyAnalysis() {
    const format = document.getElementById('reportFormat').value;
    
    const params = new URLSearchParams({
        'report_type': 'monthly',
        'format': format
    });
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// View transaction details
function viewTransactionDetails(referenceNumber) {
    // Open modal or new page with transaction details
    window.open('transaction_details.php?ref=' + referenceNumber, '_blank', 'width=800,height=600');
}

// Load more transactions
function loadMoreTransactions() {
    // AJAX call to load more transactions
    alert('Loading more transactions... (implement AJAX call)');
}

// Export transactions
function exportTransactions() {
    window.open('capital_reports.php?report_type=transactions&format=excel', '_blank');
}

// Auto-refresh capital summary every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

<?php include_once "inc/footer.php"; ?>