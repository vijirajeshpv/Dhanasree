<?php
// transaction_details.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . "/classes/CapitalManager.php");

$capitalManager = new CapitalManager();

// Get reference number from URL
$reference_no = isset($_GET['ref']) ? $_GET['ref'] : '';

if (empty($reference_no)) {
    echo '<div class="alert alert-danger">No reference number provided</div>';
    exit;
}

// Get transaction details
$transaction_query = "SELECT t.*, 
                            GROUP_CONCAT(
                                CONCAT(a.account_name, ' (', a.account_code, ')') 
                                ORDER BY td.id SEPARATOR '<br>'
                            ) as accounts,
                            GROUP_CONCAT(
                                CASE WHEN td.debit > 0 
                                THEN CONCAT('Dr: ₹', FORMAT(td.debit, 2)) 
                                ELSE CONCAT('Cr: ₹', FORMAT(td.credit, 2)) 
                                END 
                                ORDER BY td.id SEPARATOR '<br>'
                            ) as amounts
                     FROM tbl_transactions t
                     LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
                     LEFT JOIN tbl_accounts a ON td.account_id = a.id
                     WHERE t.reference_no = '$reference_no'
                     GROUP BY t.id";

// Get transaction details
$transaction_query = "SELECT t.*, 
                            GROUP_CONCAT(
                                CONCAT(a.account_name, ' (', a.account_code, ')') 
                                ORDER BY td.id SEPARATOR '<br>'
                            ) as accounts,
                            GROUP_CONCAT(
                                CASE WHEN td.debit > 0 
                                THEN CONCAT('Dr: ₹', FORMAT(td.debit, 2)) 
                                ELSE CONCAT('Cr: ₹', FORMAT(td.credit, 2)) 
                                END 
                                ORDER BY td.id SEPARATOR '<br>'
                            ) as amounts
                     FROM tbl_transactions t
                     LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
                     LEFT JOIN tbl_accounts a ON td.account_id = a.id
                     WHERE t.reference_no = '$reference_no'
                     GROUP BY t.id";

$transaction = $capitalManager->getDb()->select($transaction_query);

if (!$transaction || $transaction->num_rows == 0) {
    echo '<div class="alert alert-warning">Transaction not found</div>';
    exit;
}

$tx = $transaction->fetch_assoc();

// Get detailed transaction entries
$details_query = "SELECT td.*, a.account_name, a.account_code, a.account_type
                 FROM tbl_transaction_details td
                 JOIN tbl_accounts a ON td.account_id = a.id
                 JOIN tbl_transactions t ON td.transaction_id = t.id
                 WHERE t.reference_no = '$reference_no'
                 ORDER BY td.id";

$details = $capitalManager->getDb()->select($details_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - <?php echo htmlspecialchars($reference_no); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .transaction-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .account-dr { background-color: #f8f9ff; border-left: 4px solid #007bff; }
        .account-cr { background-color: #fff8f8; border-left: 4px solid #dc3545; }
        .balance-check { background-color: #e8f5e8; border: 1px solid #28a745; border-radius: 5px; }
        .status-badge { font-size: 0.9em; }
        .print-btn { position: absolute; top: 10px; right: 10px; }
        @media print {
            .print-btn, .no-print { display: none !important; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-5">
        <button class="btn btn-secondary btn-sm print-btn no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
        
        <div class="card">
            <!-- Transaction Header -->
            <div class="card-header transaction-header text-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-1">
                            <i class="fa fa-receipt"></i> Transaction Details
                        </h5>
                        <small>Reference: <?php echo htmlspecialchars($tx['reference_no']); ?></small>
                    </div>
                    <div class="col-auto">
                        <span class="badge badge-light status-badge">
                            <?php echo $tx['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Transaction Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0"><i class="fa fa-info-circle"></i> Transaction Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($tx['transaction_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $tx['transaction_type'] == 'Receipt' ? 'success' : 
                                                     ($tx['transaction_type'] == 'Payment' ? 'danger' : 'info'); 
                                            ?>">
                                                <?php echo $tx['transaction_type']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference:</strong></td>
                                        <td><code><?php echo htmlspecialchars($tx['reference_no']); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="mb-0"><i class="fa fa-align-left"></i> Description</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($tx['description']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Journal Entries -->
                <div class="card border-dark">
                    <div class="card-header bg-dark text-white py-2">
                        <h6 class="mb-0"><i class="fa fa-book"></i> Journal Entries (Double Entry)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Account Code</th>
                                        <th width="35%">Account Name</th>
                                        <th width="10%">Type</th>
                                        <th width="15%">Debit (₹)</th>
                                        <th width="15%">Credit (₹)</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_debit = 0;
                                    $total_credit = 0;
                                    $entry_num = 1;
                                    
                                    if ($details && $details->num_rows > 0):
                                        while ($detail = $details->fetch_assoc()):
                                            $total_debit += $detail['debit'];
                                            $total_credit += $detail['credit'];
                                            $is_debit = $detail['debit'] > 0;
                                    ?>
                                    <tr class="<?php echo $is_debit ? 'account-dr' : 'account-cr'; ?>">
                                        <td class="text-center"><?php echo $entry_num++; ?></td>
                                        <td><code><?php echo htmlspecialchars($detail['account_code']); ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($detail['account_name']); ?></strong>
                                            <?php if ($detail['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($detail['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline-secondary badge-sm">
                                                <?php echo $detail['account_type']; ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($detail['debit'] > 0): ?>
                                                <strong>₹<?php echo number_format($detail['debit'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($detail['credit'] > 0): ?>
                                                <strong>₹<?php echo number_format($detail['credit'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <i class="fa fa-<?php echo $is_debit ? 'plus-circle text-primary' : 'minus-circle text-danger'; ?>"></i>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            <i class="fa fa-exclamation-circle"></i> No journal entries found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="thead-dark">
                                    <tr>
                                        <th colspan="4" class="text-right">TOTALS:</th>
                                        <th class="text-right">₹<?php echo number_format($total_debit, 2); ?></th>
                                        <th class="text-right">₹<?php echo number_format($total_credit, 2); ?></th>
                                        <th class="text-center">
                                            <?php if (abs($total_debit - $total_credit) < 0.01): ?>
                                                <i class="fa fa-check-circle text-success" title="Balanced"></i>
                                            <?php else: ?>
                                                <i class="fa fa-exclamation-triangle text-warning" title="Not Balanced"></i>
                                            <?php endif; ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Balance Verification -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="balance-check p-3">
                            <h6 class="mb-2">
                                <i class="fa fa-balance-scale"></i> Balance Verification
                            </h6>
                            <?php if (abs($total_debit - $total_credit) < 0.01): ?>
                                <div class="text-success">
                                    <i class="fa fa-check-circle"></i> 
                                    <strong>Transaction is Balanced</strong>
                                    <br><small>Debits = Credits (₹<?php echo number_format($total_debit, 2); ?>)</small>
                                </div>
                            <?php else: ?>
                                <div class="text-danger">
                                    <i class="fa fa-exclamation-triangle"></i> 
                                    <strong>Transaction is NOT Balanced</strong>
                                    <br><small>Difference: ₹<?php echo number_format(abs($total_debit - $total_credit), 2); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-secondary">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="fa fa-chart-pie"></i> Transaction Summary</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h6 class="text-primary">Total Debits</h6>
                                        <h5>₹<?php echo number_format($total_debit, 2); ?></h5>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-danger">Total Credits</h6>
                                        <h5>₹<?php echo number_format($total_credit, 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-4 no-print">
                    <div class="col-12 text-center">
                        <button class="btn btn-secondary" onclick="window.close()">
                            <i class="fa fa-times"></i> Close
                        </button>
                        <button class="btn btn-primary ml-2" onclick="window.print()">
                            <i class="fa fa-print"></i> Print
                        </button>
                        <a href="captital_management.php" class="btn btn-success ml-2">
                            <i class="fa fa-arrow-left"></i> Back to Capital Management
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-4 pt-3 border-top">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>JAYALAKSHMI ENTERPRISES</strong><br>
                                Thanissery, Thrissur<br>
                                KML Reg No: 32080302832 (2508-0-413)
                            </small>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                Generated on: <?php echo date('d/m/Y H:i:s'); ?><br>
                                System Reference: <?php echo $tx['id']; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus and highlight
        document.addEventListener('DOMContentLoaded', function() {
            // Add some nice animations
            $('.card').addClass('animate__animated animate__fadeIn');
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
                if (e.key === 'Escape') {
                    window.close();
                }
            });
        });
    </script>
</body>
</html>