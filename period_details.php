<?php
include 'libs/Session.php';
Session::checkSession();

include 'helpers/Format.php';
include 'classes/CapitalManager.php';

$fm = new Format();
$capital = new CapitalManager();

// Get period ID from URL
$period_id = isset($_GET['id']) ? $fm->validation($_GET['id']) : null;

if (!$period_id) {
    header('Location: capital_management.php?error=invalid_period');
    exit();
}

// Get period details
$period = $capital->getPeriodDetails($period_id);

if (!$period) {
    header('Location: capital_management.php?error=period_not_found');
    exit();
}

// Get period transactions
$transactions_query = "SELECT 
                        t.id,
                        t.transaction_date,
                        t.description,
                        t.reference_no,
                        t.transaction_type,
                        t.status,
                        t.created_at,
                        SUM(td.debit) as total_debit,
                        SUM(td.credit) as total_credit
                      FROM tbl_transactions t
                      LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
                      WHERE t.financial_period_id = '$period_id'
                      GROUP BY t.id
                      ORDER BY t.transaction_date DESC, t.created_at DESC";

$transactions_result = $capital->dbcon()->query($transactions_query);

// Get account-wise summary
$account_summary_query = "SELECT 
                           a.account_name,
                           a.account_type,
                           a.account_code,
                           COALESCE(SUM(td.debit), 0) as total_debit,
                           COALESCE(SUM(td.credit), 0) as total_credit,
                           CASE 
                             WHEN a.account_type IN ('Asset', 'Expense') THEN COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0)
                             ELSE COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0)
                           END as balance
                         FROM tbl_accounts a
                         LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                         LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                           AND t.financial_period_id = '$period_id'
                           AND t.status = 'Posted'
                         WHERE a.is_active = TRUE
                         GROUP BY a.id
                         HAVING total_debit > 0 OR total_credit > 0
                         ORDER BY a.account_type, a.account_name";

$account_summary_result = $capital->dbcon()->query($account_summary_query);

// Calculate totals
$total_debits = 0;
$total_credits = 0;
$total_income = 0;
$total_expenses = 0;

if ($account_summary_result) {
    $account_summary_result->data_seek(0);
    while ($account = $account_summary_result->fetch_assoc()) {
        $total_debits += $account['total_debit'];
        $total_credits += $account['total_credit'];
        
        if ($account['account_type'] == 'Income') {
            $total_income += $account['balance'];
        } elseif ($account['account_type'] == 'Expense') {
            $total_expenses += $account['balance'];
        }
    }
}

$net_profit = $total_income - $total_expenses;

// Calculate period duration
$start_date = new DateTime($period['start_date']);
$end_date = new DateTime($period['end_date']);
$duration = $start_date->diff($end_date)->days + 1;

// Check if period is current
$today = date('Y-m-d');
$is_current = ($today >= $period['start_date'] && $today <= $period['end_date'] && !$period['is_closed']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Period Details - <?php echo htmlspecialchars($period['period_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.income {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .stat-card.expense {
            background: linear-gradient(135deg, #f44336 0%, #da190b 100%);
        }
        
        .stat-card.profit {
            background: linear-gradient(135deg, #2196F3 0%, #0b7dda 100%);
        }
        
        .stat-card.loss {
            background: linear-gradient(135deg, #FF9800 0%, #e68900 100%);
        }
        
        .period-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-current {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-past {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-future {
            background-color: #cce7ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .account-type-header {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .print-hidden {
            display: none;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-hidden {
                display: block !important;
            }
            
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }
        }
        
        .transaction-table th {
            background-color: #343a40;
            color: white;
            font-weight: 500;
        }
        
        .balance-positive {
            color: #28a745;
            font-weight: 500;
        }
        
        .balance-negative {
            color: #dc3545;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            <?php echo htmlspecialchars($period['period_name']); ?>
                        </h2>
                        <p class="text-muted mb-0">Financial Period Details & Analysis</p>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-outline-primary me-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-1"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Period Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Period Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><strong>Period Name:</strong></td>
                                <td><?php echo htmlspecialchars($period['period_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Start Date:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($period['start_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>End Date:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($period['end_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Duration:</strong></td>
                                <td><?php echo $duration; ?> days</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php if ($period['is_closed']): ?>
                                        <span class="period-status status-closed">
                                            <i class="fas fa-lock me-1"></i>Closed
                                        </span>
                                    <?php elseif ($is_current): ?>
                                        <span class="period-status status-current">
                                            <i class="fas fa-play-circle me-1"></i>Current
                                        </span>
                                    <?php elseif ($today > $period['end_date']): ?>
                                        <span class="period-status status-past">
                                            <i class="fas fa-history me-1"></i>Past
                                        </span>
                                    <?php else: ?>
                                        <span class="period-status status-future">
                                            <i class="fas fa-clock me-1"></i>Future
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($period['is_closed'] && isset($period['closed_at'])): ?>
                            <tr>
                                <td><strong>Closed On:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($period['closed_at'])); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Quick Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><strong>Total Transactions:</strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $transactions_result ? $transactions_result->num_rows : 0; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total Debits:</strong></td>
                                <td>₹<?php echo number_format($total_debits, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Credits:</strong></td>
                                <td>₹<?php echo number_format($total_credits, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Income:</strong></td>
                                <td class="text-success">₹<?php echo number_format($total_income, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Expenses:</strong></td>
                                <td class="text-danger">₹<?php echo number_format($total_expenses, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Net P&L:</strong></td>
                                <td class="<?php echo $net_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <strong>₹<?php echo number_format($net_profit, 2); ?></strong>
                                    <?php if ($net_profit >= 0): ?>
                                        <i class="fas fa-arrow-up ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-down ms-1"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card income">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Total Income</h6>
                            <h4 class="mb-0">₹<?php echo number_format($total_income, 0); ?></h4>
                        </div>
                        <i class="fas fa-plus-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card expense">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Total Expenses</h6>
                            <h4 class="mb-0">₹<?php echo number_format($total_expenses, 0); ?></h4>
                        </div>
                        <i class="fas fa-minus-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card <?php echo $net_profit >= 0 ? 'profit' : 'loss'; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Net <?php echo $net_profit >= 0 ? 'Profit' : 'Loss'; ?></h6>
                            <h4 class="mb-0">₹<?php echo number_format(abs($net_profit), 0); ?></h4>
                        </div>
                        <i class="fas fa-<?php echo $net_profit >= 0 ? 'chart-line' : 'chart-line-down'; ?> fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Transactions</h6>
                            <h4 class="mb-0"><?php echo $transactions_result ? $transactions_result->num_rows : 0; ?></h4>
                        </div>
                        <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account-wise Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list-alt me-2"></i>Account-wise Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($account_summary_result && $account_summary_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Account</th>
                                            <th>Type</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $current_type = '';
                                        $account_summary_result->data_seek(0);
                                        while ($account = $account_summary_result->fetch_assoc()):
                                            if ($current_type != $account['account_type']):
                                                $current_type = $account['account_type'];
                                        ?>
                                            <tr class="account-type-header">
                                                <td colspan="5">
                                                    <strong><?php echo $current_type; ?> Accounts</strong>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($account['account_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo $account['account_code']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $account['account_type']; ?></span>
                                            </td>
                                            <td class="text-end">₹<?php echo number_format($account['total_debit'], 2); ?></td>
                                            <td class="text-end">₹<?php echo number_format($account['total_credit'], 2); ?></td>
                                            <td class="text-end">
                                                <span class="<?php echo $account['balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                                    ₹<?php echo number_format($account['balance'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="2">TOTALS</th>
                                            <th class="text-end">₹<?php echo number_format($total_debits, 2); ?></th>
                                            <th class="text-end">₹<?php echo number_format($total_credits, 2); ?></th>
                                            <th class="text-end">
                                                <span class="<?php echo ($total_debits - $total_credits) >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                                    ₹<?php echo number_format($total_debits - $total_credits, 2); ?>
                                                </span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No account activity in this period</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Transaction History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover transaction-table" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $transactions_result->data_seek(0);
                                        while ($transaction = $transactions_result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($transaction['reference_no']); ?></code>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                                <br><small class="text-muted">
                                                    Created: <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($transaction['status'] == 'Posted'): ?>
                                                    <span class="badge bg-success">Posted</span>
                                                <?php elseif ($transaction['status'] == 'Draft'): ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo $transaction['status']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">₹<?php echo number_format($transaction['total_debit'], 2); ?></td>
                                            <td class="text-end">₹<?php echo number_format($transaction['total_credit'], 2); ?></td>
                                            <td class="no-print">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewTransactionDetails('<?php echo $transaction['reference_no']; ?>')"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No transactions found in this period</h5>
                                <p class="text-muted">All transactions will appear here once created.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print-only footer -->
        <div class="print-hidden mt-5">
            <hr>
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">
                        Generated on: <?php echo date('d/m/Y H:i:s'); ?>
                    </small>
                </div>
                <div class="col-6 text-end">
                    <small class="text-muted">
                        Jayalakshmi Enterprises - Financial Management System
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable for transactions
            $('#transactionsTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true,
                "language": {
                    "search": "Search transactions:",
                    "lengthMenu": "Show _MENU_ transactions per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
                    "infoEmpty": "No transactions available",
                    "infoFiltered": "(filtered from _MAX_ total transactions)"
                },
                "columnDefs": [
                    { "orderable": false, "targets": -1 } // Disable sorting on Actions column
                ]
            });
        });

        // View transaction details
        function viewTransactionDetails(referenceNumber) {
            window.open('transaction_details.php?ref=' + referenceNumber, '_blank', 'width=900,height=700');
        }

        // Print function
        window.addEventListener('beforeprint', function() {
            // Hide DataTable controls for printing
            $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').hide();
        });

        window.addEventListener('afterprint', function() {
            // Show DataTable controls after printing
            $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').show();
        });
    </script>
</body>
</html>