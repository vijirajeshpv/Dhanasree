<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
require_once 'classes/BalanceSheetGenerator.php';

$balanceSheet = new BalanceSheetGenerator();

// Get summaries
$goldLoanSummary = $balanceSheet->getGoldLoanSummary();
$repledgeSummary = $balanceSheet->getRepledgeSummary();
$expenseCategories = $balanceSheet->getExpenseCategories();
$financialSummary = $balanceSheet->calculatePotentialNetIncome();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">Business Balance Sheet</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mb-4">Financial Overview</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th colspan="2" class="text-center">Income Sources</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Gold Loans</td>
                                    <td>₹<?php echo number_format($goldLoanSummary['total_loan_amount'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Gold Loan Market Value</td>
                                    <td>₹<?php echo number_format($goldLoanSummary['total_market_value'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Closed Loan Amount</td>
                                    <td>₹<?php echo number_format($goldLoanSummary['closed_loan_amount'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Interest from Gold Loans</td>
                                    <td>₹<?php echo number_format($goldLoanSummary['total_interest'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Repledge Bank Amount</td>
                                    <td>₹<?php echo number_format($repledgeSummary['total_bank_amount'] ?? 0, 2); ?></td>
                                </tr>
                            </tbody>

                            <thead class="thead-dark">
                                <tr>
                                    <th colspan="2" class="text-center">Expenses Breakdown</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenseCategories as $category => $amount): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?> Expenses</td>
                                    <td>₹<?php echo number_format($amount, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <td><strong>Total Expenses</strong></td>
                                    <td><strong>₹<?php echo number_format($financialSummary['total_expenses'], 2); ?></strong></td>
                                </tr>
                            </tbody>

                            <thead class="thead-dark">
                                <tr>
                                    <th colspan="2" class="text-center">Summary</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Gold Loans Count</td>
                                    <td><?php echo $goldLoanSummary['total_loans'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Repledges Count</td>
                                    <td><?php echo $repledgeSummary['total_repledges'] ?? 0; ?></td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>Potential Net Income</strong></td>
                                    <td>
                                        <strong>
                                            ₹<?php echo number_format($financialSummary['potential_net_income'], 2); ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">Last updated: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
    </div>
</div>
</div>

<?php
include_once "inc/footer.php";
?>