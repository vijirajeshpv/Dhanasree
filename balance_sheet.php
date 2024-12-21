<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
require_once 'classes/BalanceSheetGenerator.php';

$balanceSheet = new BalanceSheetGenerator();

// Default date range (current month)
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

// Get summaries with date range
$goldLoanSummary = $balanceSheet->getGoldLoanSummary($start_date, $end_date);
$repledgeSummary = $balanceSheet->getRepledgeSummary($start_date, $end_date);
$expenseCategories = $balanceSheet->getExpenseCategories($start_date, $end_date);
$financialSummary = $balanceSheet->calculatePotentialNetIncome($start_date, $end_date);
?>

<div class="mb-4">
    <div class="btn-group">
        <a href="export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="btn btn-danger">
            <i class="fas fa-file-pdf mr-2"></i>Export PDF
        </a>
        <a href="export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="btn btn-success">
            <i class="fas fa-file-excel mr-2"></i>Export Excel
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">Business Balance Sheet</h3>
        </div>
        <div class="card-body">
            <!-- Date Range Filter Form -->
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Apply Filter</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Quick Date Range Buttons -->
            <div class="mb-4">
                <button class="btn btn-outline-secondary mr-2" onclick="setDateRange('today')">Today</button>
                <button class="btn btn-outline-secondary mr-2" onclick="setDateRange('this_week')">This Week</button>
                <button class="btn btn-outline-secondary mr-2" onclick="setDateRange('this_month')">This Month</button>
                <button class="btn btn-outline-secondary mr-2" onclick="setDateRange('this_year')">This Year</button>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4 class="mb-4">Financial Overview (<?php echo date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)); ?>)</h4>
                    
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
                                    <td><strong>₹<?php echo number_format($financialSummary['potential_net_income'], 2); ?></strong></td>
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

<!-- Add this JavaScript at the bottom of the page -->
<script>
function setDateRange(range) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    switch(range) {
        case 'today':
            // Start and end are already today
            break;
        case 'this_week':
            start.setDate(today.getDate() - today.getDay()); // Start of week (Sunday)
            end.setDate(today.getDate() + (6 - today.getDay())); // End of week (Saturday)
            break;
        case 'this_month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'this_year':
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
            break;
    }

    document.getElementById('start_date').value = formatDate(start);
    document.getElementById('end_date').value = formatDate(end);
    document.forms[0].submit();
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}
</script>

<?php
include_once "inc/footer.php";
?>