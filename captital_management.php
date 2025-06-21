<?php
// capital_management.php
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "classes/CapitalManager.php";

$capitalManager = new CapitalManager();

// Get current financial period
$currentPeriod = $capitalManager->getCurrentFinancialPeriod();

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
    } elseif (isset($_POST['close_period'])) {
        $message = $capitalManager->closeFinancialPeriod($_POST['period_id']);
    }
    // ADD THIS NEW SECTION FOR PERIOD CREATION
    elseif (isset($_POST['create_period'])) {
        $message = $capitalManager->createFinancialPeriod(
            $_POST['period_name'],
            $_POST['start_date'],
            $_POST['end_date']
        );
    } 
    
    // ADD THIS NEW SECTION FOR PERIOD DELETION
    elseif (isset($_POST['delete_period_id'])) {
        $message = $capitalManager->deleteFinancialPeriod($_POST['delete_period_id']);
    }
}

// Get capital summary
$capitalSummary = $capitalManager->getCapitalSummary();

// Get period-specific transactions
$recentTransactions = $currentPeriod ? 
    $capitalManager->getCapitalTransactionsByPeriod($currentPeriod['id'], 10) : 
    $capitalManager->getCapitalTransactions(null, null, 10);

// Get all financial periods for admin
$allPeriods = $capitalManager->getFinancialPeriods();

// Get current period P&L
$currentPeriodPL = $currentPeriod ? $capitalManager->getPeriodProfitLoss($currentPeriod['id']) : null;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fa fa-coins"></i> Capital Management - JAYALAKSHMI ENTERPRISES</h6>
                        <div class="text-right">
                            <?php if ($currentPeriod): ?>
                                <small>Current Period: <strong><?php echo $currentPeriod['period_name']; ?></strong></small><br>
                                <small><?php echo date('d/m/Y', strtotime($currentPeriod['start_date'])) . ' to ' . date('d/m/Y', strtotime($currentPeriod['end_date'])); ?></small>
                            <?php else: ?>
                                <span class="badge badge-warning">No Active Period</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Period Warning -->
                    <?php if (!$currentPeriod): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> 
                        <strong>No Active Financial Period!</strong> 
                        Please contact administrator to create or activate a financial period before creating transactions.
                    </div>
                    <?php endif; ?>

                    <!-- Message Display -->
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Enhanced Capital Summary Dashboard -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center p-2">
                                    <small>Owner Capital</small>
                                    <h5 class="mb-0">₹<?php echo number_format($capitalSummary['capital_balance'], 0); ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center p-2">
                                    <small>Retained Earnings</small>
                                    <h5 class="mb-0">₹<?php echo number_format($capitalSummary['retained_earnings'], 0); ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center p-2">
                                    <small>Total Equity</small>
                                    <h5 class="mb-0">₹<?php echo number_format($capitalSummary['total_equity'], 0); ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center p-2">
                                    <small>Month Activity</small>
                                    <div style="font-size: 0.8em;">
                                        <div>In: ₹<?php echo number_format($capitalSummary['month_investments'], 0); ?></div>
                                        <div>Out: ₹<?php echo number_format($capitalSummary['month_withdrawals'], 0); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-<?php echo $currentPeriodPL && $currentPeriodPL['net_profit'] >= 0 ? 'success' : 'danger'; ?> text-white">
                                <div class="card-body text-center p-2">
                                    <small>Period P&L</small>
                                    <h6 class="mb-0">
                                        <?php if ($currentPeriodPL): ?>
                                            ₹<?php echo number_format($currentPeriodPL['net_profit'], 0); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center p-2">
                                    <small>Period Status</small>
                                    <h6 class="mb-0">
                                        <?php if ($currentPeriod): ?>
                                            <?php echo $currentPeriod['is_closed'] ? 'Closed' : 'Active'; ?>
                                        <?php else: ?>
                                            Inactive
                                        <?php endif; ?>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs" id="capitalTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="transactions-tab" data-toggle="tab" href="#transactions" role="tab">
                                <i class="fa fa-exchange-alt"></i> Capital Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="profit-tab" data-toggle="tab" href="#profit" role="tab">
                                <i class="fa fa-chart-pie"></i> Profit/Loss Transfer
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="reports-tab" data-toggle="tab" href="#reports" role="tab">
                                <i class="fa fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">
                                <i class="fa fa-history"></i> Transaction History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="periods-tab" data-toggle="tab" href="#periods" role="tab">
                                <i class="fa fa-calendar"></i> Financial Periods
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="capitalTabContent">
                        
                        <!-- Capital Transactions Tab -->
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                            <div class="mt-3">
                                <?php if (!$currentPeriod): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> Transaction forms are disabled because no financial period is active.
                                </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <!-- Capital Investment -->
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0"><i class="fa fa-plus-circle"></i> Capital Investment</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST">
                                                    <div class="form-group">
                                                        <label class="form-label">Investment Amount *</label>
                                                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" 
                                                               <?php echo !$currentPeriod ? 'disabled' : ''; ?> required>
                                                        <small class="text-muted">Enter the amount being invested in the business</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Source Account</label>
                                                        <select class="form-control" name="source_account" <?php echo !$currentPeriod ? 'disabled' : ''; ?>>
                                                            <option value="1001">Cash in Hand</option>
                                                            <option value="1002">Bank Account</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" rows="2" 
                                                                  placeholder="Brief description of the capital investment"
                                                                  <?php echo !$currentPeriod ? 'disabled' : ''; ?>></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Transaction Date</label>
                                                        <input type="date" class="form-control" name="transaction_date" 
                                                               value="<?php echo date('Y-m-d'); ?>"
                                                               <?php if ($currentPeriod): ?>
                                                               min="<?php echo $currentPeriod['start_date']; ?>"
                                                               max="<?php echo $currentPeriod['end_date']; ?>"
                                                               <?php else: ?>
                                                               disabled
                                                               <?php endif; ?>
                                                               required>
                                                        <?php if ($currentPeriod): ?>
                                                        <small class="text-muted">
                                                            Must be between <?php echo date('d/m/Y', strtotime($currentPeriod['start_date'])); ?> 
                                                            and <?php echo date('d/m/Y', strtotime($currentPeriod['end_date'])); ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="submit" name="capital_investment" class="btn btn-primary btn-block"
                                                            <?php echo !$currentPeriod || ($currentPeriod && $currentPeriod['is_closed']) ? 'disabled' : ''; ?>>
                                                        <i class="fa fa-plus"></i> Record Investment
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Capital Withdrawal -->
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-danger text-white">
                                                <h6 class="mb-0"><i class="fa fa-minus-circle"></i> Capital Withdrawal</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST">
                                                    <div class="form-group">
                                                        <label class="form-label">Withdrawal Amount *</label>
                                                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" 
                                                               <?php echo !$currentPeriod ? 'disabled' : ''; ?> required>
                                                        <small class="text-muted">Maximum available: ₹<?php echo number_format($capitalSummary['capital_balance'], 2); ?></small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Destination Account</label>
                                                        <select class="form-control" name="destination_account" <?php echo !$currentPeriod ? 'disabled' : ''; ?>>
                                                            <option value="1001">Cash in Hand</option>
                                                            <option value="1002">Bank Account</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" rows="2" 
                                                                  placeholder="Purpose of withdrawal"
                                                                  <?php echo !$currentPeriod ? 'disabled' : ''; ?>></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Transaction Date</label>
                                                        <input type="date" class="form-control" name="transaction_date" 
                                                               value="<?php echo date('Y-m-d'); ?>"
                                                               <?php if ($currentPeriod): ?>
                                                               min="<?php echo $currentPeriod['start_date']; ?>"
                                                               max="<?php echo $currentPeriod['end_date']; ?>"
                                                               <?php else: ?>
                                                               disabled
                                                               <?php endif; ?>
                                                               required>
                                                    </div>
                                                    <button type="submit" name="capital_withdrawal" class="btn btn-danger btn-block"
                                                            <?php echo !$currentPeriod || ($currentPeriod && $currentPeriod['is_closed']) ? 'disabled' : ''; ?>>
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
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0"><i class="fa fa-exchange-alt"></i> Profit/Loss Transfer to Retained Earnings</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if ($currentPeriodPL): ?>
                                                <div class="alert alert-info">
                                                    <h6><i class="fa fa-info-circle"></i> Current Period P&L Summary:</h6>
                                                    <div class="row">
                                                        <div class="col-md-4">Total Income: <strong>₹<?php echo number_format($currentPeriodPL['total_income'], 2); ?></strong></div>
                                                        <div class="col-md-4">Total Expenses: <strong>₹<?php echo number_format($currentPeriodPL['total_expenses'], 2); ?></strong></div>
                                                        <div class="col-md-4">Net P&L: <strong class="text-<?php echo $currentPeriodPL['net_profit'] >= 0 ? 'success' : 'danger'; ?>">₹<?php echo number_format($currentPeriodPL['net_profit'], 2); ?></strong></div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <form method="POST">
                                                    <div class="form-group">
                                                        <label class="form-label">Net Profit/Loss Amount *</label>
                                                        <input type="number" class="form-control" name="net_profit" step="0.01" 
                                                               value="<?php echo $currentPeriodPL ? $currentPeriodPL['net_profit'] : ''; ?>"
                                                               <?php echo !$currentPeriod ? 'disabled' : ''; ?> required>
                                                        <small class="text-muted">Enter positive amount for profit, negative for loss</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Transfer Date</label>
                                                        <input type="date" class="form-control" name="transfer_date" 
                                                               value="<?php echo date('Y-m-d'); ?>"
                                                               <?php if ($currentPeriod): ?>
                                                               min="<?php echo $currentPeriod['start_date']; ?>"
                                                               max="<?php echo $currentPeriod['end_date']; ?>"
                                                               <?php else: ?>
                                                               disabled
                                                               <?php endif; ?>
                                                               required>
                                                    </div>
                                                    <button type="submit" name="profit_transfer" class="btn btn-warning btn-block"
                                                            <?php echo !$currentPeriod || ($currentPeriod && $currentPeriod['is_closed']) ? 'disabled' : ''; ?>>
                                                        <i class="fa fa-arrow-right"></i> Transfer to Retained Earnings
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0"><i class="fa fa-info-circle"></i> Transfer Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-unstyled mb-0">
                                                    <li><i class="fa fa-check text-success"></i> <strong>Profit:</strong> Increases retained earnings</li>
                                                    <li><i class="fa fa-times text-danger"></i> <strong>Loss:</strong> Decreases retained earnings</li>
                                                    <li><i class="fa fa-calendar"></i> Usually done at period-end</li>
                                                    <li><i class="fa fa-cog"></i> Automatically creates journal entries</li>
                                                    <li><i class="fa fa-lock"></i> Links to current financial period</li>
                                                </ul>
                                                
                                                <?php if ($currentPeriod): ?>
                                                <hr>
                                                <small class="text-muted">
                                                    <strong>Current Period:</strong><br>
                                                    <?php echo $currentPeriod['period_name']; ?><br>
                                                    <?php echo date('d/m/Y', strtotime($currentPeriod['start_date'])) . ' to ' . date('d/m/Y', strtotime($currentPeriod['end_date'])); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reports Tab -->
                        <div class="tab-pane fade" id="reports" role="tabpanel">
                            <div class="mt-3">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Capital Reports</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">From Date</label>
                                                    <input type="date" class="form-control" id="fromDate" 
                                                           value="<?php echo $currentPeriod ? $currentPeriod['start_date'] : date('Y-m-01'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">To Date</label>
                                                    <input type="date" class="form-control" id="toDate" 
                                                           value="<?php echo $currentPeriod ? $currentPeriod['end_date'] : date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Financial Period</label>
                                                    <select class="form-control" id="periodSelect" onchange="updateDateRange()">
                                                        <option value="">Custom Date Range</option>
                                                        <?php if ($allPeriods && $allPeriods->num_rows > 0): ?>
                                                            <?php while ($period = $allPeriods->fetch_assoc()): ?>
                                                                <option value="<?php echo $period['id']; ?>" 
                                                                        data-start="<?php echo $period['start_date']; ?>"
                                                                        data-end="<?php echo $period['end_date']; ?>"
                                                                        <?php echo $currentPeriod && $period['id'] == $currentPeriod['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo $period['period_name']; ?>
                                                                    <?php if ($period['is_closed']): ?> (Closed)<?php endif; ?>
                                                                </option>
                                                            <?php endwhile; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
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
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" onclick="generateCapitalStatement()">
                                                        <i class="fa fa-file-pdf"></i> Capital Statement
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" onclick="generateMovementReport()">
                                                        <i class="fa fa-chart-line"></i> Movement Report
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info" onclick="generateSummaryReport()">
                                                        <i class="fa fa-chart-pie"></i> Summary Report
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning" onclick="generatePeriodAnalysis()">
                                                        <i class="fa fa-calendar"></i> Period Analysis
                                                    </button>
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
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fa fa-history"></i> Transaction History 
                                                <?php if ($currentPeriod): ?>
                                                    - <?php echo $currentPeriod['period_name']; ?>
                                                <?php endif; ?>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportTransactions()">
                                                <i class="fa fa-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-sm">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Type</th>
                                                        <th>Amount</th>
                                                        <th>Description</th>
                                                        <th>Period</th>
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
                                                            <span class="badge badge-<?php 
                                                                echo $rt['transaction_type'] == 'Capital Investment' ? 'success' : 
                                                                     ($rt['transaction_type'] == 'Capital Withdrawal' ? 'danger' : 'warning'); 
                                                            ?>">
                                                                <?php echo $rt['transaction_type']; ?>
                                                            </span>
                                                        </td>
                                                        <td>₹<?php echo number_format($rt['amount'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($rt['description']); ?></td>
                                                        <td>
                                                            <?php if (isset($rt['period_name'])): ?>
                                                                <small class="text-muted"><?php echo $rt['period_name']; ?></small>
                                                            <?php else: ?>
                                                                <small class="text-muted">N/A</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <code><?php echo $rt['reference_no'] ?? 'N/A'; ?></code>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails('<?php echo $rt['reference_no']; ?>')">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">
                                                            <i class="fa fa-info-circle"></i> No transactions found
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                       <!-- Financial Periods Tab -->
                       <!-- Financial Periods Tab -->
                       <div class="tab-pane fade" id="periods" role="tabpanel">
                            <div class="mt-3">
                                <!-- Create New Period Section -->
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fa fa-plus"></i> Create New Financial Period</h6>
                                            <button class="btn btn-sm btn-light" type="button" onclick="toggleCreatePeriodForm()">
                                                <i class="fa fa-plus" id="toggleIcon"></i> <span id="toggleText">Show Form</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body" id="createPeriodForm" style="display: none;">
                                        <form method="POST" onsubmit="return validatePeriodForm()">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Period Name *</label>
                                                        <input type="text" class="form-control" name="period_name" 
                                                               placeholder="e.g., FY 2025-2026" required>
                                                        <small class="text-muted">Enter a descriptive name for the period</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="form-label">Start Date *</label>
                                                        <input type="date" class="form-control" name="start_date" 
                                                               id="periodStartDate" required>
                                                        <small class="text-muted">Period start date</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="form-label">End Date *</label>
                                                        <input type="date" class="form-control" name="end_date" 
                                                               id="periodEndDate" required>
                                                        <small class="text-muted">Period end date</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label class="form-label">&nbsp;</label>
                                                        <button type="submit" name="create_period" class="btn btn-primary btn-block">
                                                            <i class="fa fa-plus"></i> Create Period
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="alert alert-info">
                                                        <h6><i class="fa fa-info-circle"></i> Period Creation Guidelines:</h6>
                                                        <ul class="mb-0">
                                                            <li>End date must be after start date</li>
                                                            <li>Periods cannot overlap with existing periods</li>
                                                            <li>Typically financial years run from April 1st to March 31st</li>
                                                            <li>Only one period can be active at a time</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Quick Period Templates -->
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h6><i class="fa fa-magic"></i> Quick Templates:</h6>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setFinancialYear(2025)">
                                                            FY 2025-26
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setFinancialYear(2026)">
                                                            FY 2026-27
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setCalendarYear(2025)">
                                                            Calendar 2025
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setQuarter()">
                                                            Next Quarter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Existing Periods List -->
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fa fa-calendar"></i> Financial Periods Management</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-sm">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Period Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Duration</th>
                                                        <th>Status</th>
                                                        <th>Transactions</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $allPeriods->data_seek(0); // Reset result pointer
                                                    if ($allPeriods && $allPeriods->num_rows > 0):
                                                        while ($period = $allPeriods->fetch_assoc()):
                                                            $today = date('Y-m-d');
                                                            
                                                            // Calculate duration
                                                            $start = new DateTime($period['start_date']);
                                                            $end = new DateTime($period['end_date']);
                                                            $duration = $start->diff($end)->days + 1;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($period['period_name']); ?></strong>
                                                            <?php if ($today >= $period['start_date'] && $today <= $period['end_date'] && !$period['is_closed']): ?>
                                                                <span class="badge badge-success badge-sm ml-1">Current</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($period['start_date'])); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($period['end_date'])); ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo $duration; ?> days
                                                                <?php if ($duration >= 360): ?>
                                                                    (<?php echo round($duration/365, 1); ?> years)
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($period['is_closed']): ?>
                                                                <span class="badge badge-secondary">
                                                                    <i class="fa fa-lock"></i> Closed
                                                                </span>
                                                            <?php else: ?>
                                                                <?php if ($today >= $period['start_date'] && $today <= $period['end_date']): ?>
                                                                    <span class="badge badge-success">
                                                                        <i class="fa fa-play"></i> Active
                                                                    </span>
                                                                <?php elseif ($today < $period['start_date']): ?>
                                                                    <span class="badge badge-info">
                                                                        <i class="fa fa-clock"></i> Future
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-warning">
                                                                        <i class="fa fa-history"></i> Past
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light">
                                                                <?php echo isset($period['transaction_count']) ? $period['transaction_count'] : 0; ?> txns
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button class="btn btn-outline-info" onclick="viewPeriodDetails(<?php echo $period['id']; ?>)" title="View Details">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                
                                                                <?php if (!$period['is_closed'] && $today > $period['end_date']): ?>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                                                        <button type="submit" name="close_period" class="btn btn-warning btn-sm"
                                                                                onclick="return confirmPeriodClose('<?php echo $period['id']; ?>', '<?php echo htmlspecialchars($period['period_name']); ?>')" title="Close Period">
                                                                            <i class="fa fa-lock"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!$period['is_closed'] && ($period['transaction_count'] ?? 0) == 0): ?>
                                                                    <button class="btn btn-outline-danger btn-sm" onclick="confirmDeletePeriod(<?php echo $period['id']; ?>, '<?php echo htmlspecialchars($period['period_name']); ?>')" title="Delete Period">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">
                                                            <i class="fa fa-info-circle"></i> No financial periods found. Create your first period above.
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="alert alert-info mt-3">
                                            <h6><i class="fa fa-info-circle"></i> Period Management Notes:</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ul class="mb-0">
                                                        <li>Only one period can be active at a time</li>
                                                        <li>Transactions can only be created in active periods</li>
                                                        <li>Closed periods cannot be modified</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <ul class="mb-0">
                                                        <li>Period closing automatically transfers P&L to retained earnings</li>
                                                        <li>Empty periods can be deleted if not closed</li>
                                                        <li>Create periods in advance for better planning</li>
                                                    </ul>
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
</div>

<script>
    function toggleCreatePeriodForm() {
    const form = document.getElementById('createPeriodForm');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        icon.className = 'fa fa-minus';
        text.textContent = 'Hide Form';
    } else {
        form.style.display = 'none';
        icon.className = 'fa fa-plus';
        text.textContent = 'Show Form';
    }
}

// Validate period creation form
function validatePeriodForm() {
    const startDate = document.getElementById('periodStartDate').value;
    const endDate = document.getElementById('periodEndDate').value;
    const periodName = document.querySelector('input[name="period_name"]').value;
    
    if (!startDate || !endDate || !periodName) {
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (new Date(endDate) <= new Date(startDate)) {
        alert('End date must be after start date.');
        return false;
    }
    
    // Check if the period name follows a standard format
    if (!periodName.trim()) {
        alert('Please enter a valid period name.');
        return false;
    }
    
    return confirm('Are you sure you want to create this financial period?\n\nPeriod: ' + periodName + '\nFrom: ' + formatDate(startDate) + '\nTo: ' + formatDate(endDate));
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

// Set financial year template
function setFinancialYear(year) {
    const startDate = year + '-04-01';
    const endDate = (year + 1) + '-03-31';
    const periodName = 'FY ' + year + '-' + (year + 1).toString().slice(-2);
    
    document.getElementById('periodStartDate').value = startDate;
    document.getElementById('periodEndDate').value = endDate;
    document.querySelector('input[name="period_name"]').value = periodName;
}

// Set calendar year template
function setCalendarYear(year) {
    const startDate = year + '-01-01';
    const endDate = year + '-12-31';
    const periodName = 'Calendar Year ' + year;
    
    document.getElementById('periodStartDate').value = startDate;
    document.getElementById('periodEndDate').value = endDate;
    document.querySelector('input[name="period_name"]').value = periodName;
}

// Set next quarter template
function setQuarter() {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    let quarterStart, quarterEnd, quarterName;
    
    if (currentMonth < 3) { // Q2
        quarterStart = new Date(currentYear, 3, 1); // April 1
        quarterEnd = new Date(currentYear, 5, 30); // June 30
        quarterName = 'Q2 ' + currentYear;
    } else if (currentMonth < 6) { // Q3
        quarterStart = new Date(currentYear, 6, 1); // July 1
        quarterEnd = new Date(currentYear, 8, 30); // September 30
        quarterName = 'Q3 ' + currentYear;
    } else if (currentMonth < 9) { // Q4
        quarterStart = new Date(currentYear, 9, 1); // October 1
        quarterEnd = new Date(currentYear, 11, 31); // December 31
        quarterName = 'Q4 ' + currentYear;
    } else { // Q1 next year
        quarterStart = new Date(currentYear + 1, 0, 1); // January 1
        quarterEnd = new Date(currentYear + 1, 2, 31); // March 31
        quarterName = 'Q1 ' + (currentYear + 1);
    }
    
    document.getElementById('periodStartDate').value = quarterStart.toISOString().split('T')[0];
    document.getElementById('periodEndDate').value = quarterEnd.toISOString().split('T')[0];
    document.querySelector('input[name="period_name"]').value = quarterName;
}

// Confirm period close with enhanced details
function confirmPeriodClose(periodId, periodName) {
    const message = `Are you sure you want to close the period "${periodName}"?

This will:
• Calculate current period P&L
• Transfer profit/loss to retained earnings
• Prevent future transactions in this period
• Mark the period as permanently closed

This action cannot be undone!

Continue?`;
    
    return confirm(message);
}

// Confirm period deletion
function confirmDeletePeriod(periodId, periodName) {
    if (confirm(`Are you sure you want to delete the period "${periodName}"?\n\nThis action cannot be undone!`)) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_period_id';
        input.value = periodId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-suggest period name based on dates
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('periodStartDate');
    const endDateInput = document.getElementById('periodEndDate');
    const periodNameInput = document.querySelector('input[name="period_name"]');
    
    function suggestPeriodName() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && endDate > startDate) {
            // Check if it's a financial year (April to March)
            if (startDate.getMonth() === 3 && startDate.getDate() === 1 && 
                endDate.getMonth() === 2 && endDate.getDate() === 31) {
                const fyStart = startDate.getFullYear();
                const fyEnd = endDate.getFullYear();
                periodNameInput.value = `FY ${fyStart}-${fyEnd.toString().slice(-2)}`;
            }
            // Check if it's a calendar year
            else if (startDate.getMonth() === 0 && startDate.getDate() === 1 && 
                     endDate.getMonth() === 11 && endDate.getDate() === 31) {
                periodNameInput.value = `Calendar Year ${startDate.getFullYear()}`;
            }
            // Check if it's a quarter
            else {
                const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                if (duration >= 85 && duration <= 95) { // Roughly 3 months
                    const month = startDate.getMonth();
                    const year = startDate.getFullYear();
                    if (month === 0) periodNameInput.value = `Q1 ${year}`;
                    else if (month === 3) periodNameInput.value = `Q2 ${year}`;
                    else if (month === 6) periodNameInput.value = `Q3 ${year}`;
                    else if (month === 9) periodNameInput.value = `Q4 ${year}`;
                }
            }
        }
    }
    
    startDateInput.addEventListener('change', suggestPeriodName);
    endDateInput.addEventListener('change', suggestPeriodName);
});
// Update date range based on selected period
function updateDateRange() {
    const periodSelect = document.getElementById('periodSelect');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    
    if (periodSelect.value) {
        const selectedOption = periodSelect.options[periodSelect.selectedIndex];
        fromDate.value = selectedOption.getAttribute('data-start');
        toDate.value = selectedOption.getAttribute('data-end');
    }
}

// Generate Capital Statement
function generateCapitalStatement() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const format = document.getElementById('reportFormat').value;
    const periodId = document.getElementById('periodSelect').value;
    
    const params = new URLSearchParams({
        'report_type': 'capital_statement',
        'from_date': fromDate,
        'to_date': toDate,
        'format': format
    });
    
    if (periodId) {
        params.append('period_id', periodId);
    }
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Movement Report
function generateMovementReport() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const format = document.getElementById('reportFormat').value;
    const periodId = document.getElementById('periodSelect').value;
    
    const params = new URLSearchParams({
        'report_type': 'movement',
        'from_date': fromDate,
        'to_date': toDate,
        'format': format
    });
    
    if (periodId) {
        params.append('period_id', periodId);
    }
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Summary Report
function generateSummaryReport() {
    const format = document.getElementById('reportFormat').value;
    const periodId = document.getElementById('periodSelect').value;
    
    const params = new URLSearchParams({
        'report_type': 'summary',
        'format': format
    });
    
    if (periodId) {
        params.append('period_id', periodId);
    }
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Generate Period Analysis
function generatePeriodAnalysis() {
    const format = document.getElementById('reportFormat').value;
    const periodId = document.getElementById('periodSelect').value;
    
    const params = new URLSearchParams({
        'report_type': 'period_analysis',
        'format': format
    });
    
    if (periodId) {
        params.append('period_id', periodId);
    }
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// View transaction details
function viewTransactionDetails(referenceNumber) {
    window.open('transaction_details.php?ref=' + referenceNumber, '_blank', 'width=900,height=700');
}

// View period details
function viewPeriodDetails(periodId) {
    window.open('period_details.php?id=' + periodId, '_blank', 'width=800,height=600');
}

// Export transactions
function exportTransactions() {
    const periodId = document.getElementById('periodSelect') ? document.getElementById('periodSelect').value : '';
    
    const params = new URLSearchParams({
        'report_type': 'transactions',
        'format': 'excel'
    });
    
    if (periodId) {
        params.append('period_id', periodId);
    }
    
    window.open('capital_reports.php?' + params.toString(), '_blank');
}

// Validate transaction dates on form submission
document.addEventListener('DOMContentLoaded', function() {
    // Add validation to transaction date inputs
    const dateInputs = document.querySelectorAll('input[name="transaction_date"], input[name="transfer_date"]');
    
    dateInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const minDate = new Date(this.min);
            const maxDate = new Date(this.max);
            
            if (selectedDate < minDate || selectedDate > maxDate) {
                alert('Selected date must be within the current financial period.');
                this.value = '<?php echo date('Y-m-d'); ?>';
            }
        });
    });
    
    // Initialize period select on page load
    updateDateRange();
    
    // Auto-refresh current period info every 5 minutes
    setInterval(function() {
        // You could add AJAX call here to refresh period status
        console.log('Period status check...');
    }, 300000);
});

// Form validation before submission
function validateCapitalForm(formType) {
    const currentPeriod = <?php echo $currentPeriod ? 'true' : 'false'; ?>;
    const periodClosed = <?php echo $currentPeriod && $currentPeriod['is_closed'] ? 'true' : 'false'; ?>;
    
    if (!currentPeriod) {
        alert('No active financial period. Please contact administrator.');
        return false;
    }
    
    if (periodClosed) {
        alert('Current financial period is closed. Cannot create transactions.');
        return false;
    }
    
    return true;
}

// Add form validation to all transaction forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        if (form.querySelector('button[name="capital_investment"], button[name="capital_withdrawal"], button[name="profit_transfer"]')) {
            form.addEventListener('submit', function(e) {
                if (!validateCapitalForm()) {
                    e.preventDefault();
                }
            });
        }
    });
});

// Period closing confirmation with additional details
function confirmPeriodClose(periodId, periodName) {
    if (confirm('Are you sure you want to close the period "' + periodName + '"?\n\nThis will:\n- Calculate and transfer P&L to retained earnings\n- Prevent future transactions in this period\n- This action cannot be undone\n\nContinue?')) {
        return true;
    }
    return false;
}

// Enhanced period validation
function validatePeriodOperation(operation, periodId) {
    // Add additional validation logic here if needed
    return true;
}

// Auto-update period status indicators
function updatePeriodStatusIndicators() {
    // This could be enhanced with AJAX to update period status without page refresh
    const statusElements = document.querySelectorAll('.period-status');
    statusElements.forEach(function(element) {
        // Update logic here
    });
}

// Initialize tooltips for period information
document.addEventListener('DOMContentLoaded', function() {
    // Add Bootstrap tooltips if available
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
});

// Keyboard shortcuts for common actions
document.addEventListener('keydown', function(e) {
    // Ctrl+1 for Capital Investment tab
    if (e.ctrlKey && e.key === '1') {
        e.preventDefault();
        document.getElementById('transactions-tab').click();
    }
    
    // Ctrl+2 for Profit/Loss Transfer tab
    if (e.ctrlKey && e.key === '2') {
        e.preventDefault();
        document.getElementById('profit-tab').click();
    }
    
    // Ctrl+3 for Reports tab
    if (e.ctrlKey && e.key === '3') {
        e.preventDefault();
        document.getElementById('reports-tab').click();
    }
    
    // Ctrl+4 for History tab
    if (e.ctrlKey && e.key === '4') {
        e.preventDefault();
        document.getElementById('history-tab').click();
    }
    
    // Ctrl+5 for Periods tab
    if (e.ctrlKey && e.key === '5') {
        e.preventDefault();
        document.getElementById('periods-tab').click();
    }
});

// Enhanced error handling for form submissions
function handleFormError(error) {
    console.error('Form submission error:', error);
    alert('An error occurred while processing your request. Please try again.');
}

// Success notification enhancement
function showSuccessNotification(message) {
    // Enhanced success notification
    console.log('Success:', message);
}

// Auto-save draft functionality (future enhancement)
function autoSaveDraft() {
    // Could implement auto-save for form data
}

// Print functionality for reports
function printCurrentTab() {
    window.print();
}

// Export current tab data
function exportCurrentTabData() {
    const activeTab = document.querySelector('.tab-pane.active');
    if (activeTab) {
        const tabId = activeTab.id;
        switch(tabId) {
            case 'transactions':
                // Export transactions data
                break;
            case 'history':
                exportTransactions();
                break;
            default:
                alert('Export not available for this tab');
        }
    }
}
</script>

<style>
/* Enhanced styling for period management */
.period-status-indicator {
    position: relative;
    display: inline-block;
}

.period-tooltip {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}

.period-status-indicator:hover .period-tooltip {
    opacity: 1;
}

/* Improved form styling */
.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    opacity: 0.65;
}

/* Enhanced button states */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Period cards animation */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
    }
    
    .card-body.text-center.p-2 {
        padding: 1rem !important;
    }
    
    .btn-group-sm .btn {
        margin-bottom: 5px;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#createPeriodForm {
    border-top: 1px solid #dee2e6;
    margin-top: 15px;
    padding-top: 15px;
}

.btn-group-sm .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}

/* Period status enhancements */
.badge {
    font-size: 0.8em;
}

.badge i {
    margin-right: 3px;
}

/* Table enhancements */
.table td {
    vertical-align: middle;
}

/* Form animation */
#createPeriodForm {
    transition: all 0.3s ease-in-out;
}

/* Period templates styling */
.btn-outline-secondary {
    margin-right: 5px;
    margin-bottom: 5px;
}

/* Responsive improvements for period creation */
@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-wrap: wrap;
    }
    
    .btn-group .btn {
        flex: 1 1 auto;
        margin: 2px;
    }
}
</style>

<?php include_once "inc/footer.php"; ?>