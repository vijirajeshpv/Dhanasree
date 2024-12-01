<?php
ob_start(); // Start output buffering
include_once "inc/header.php";
include_once "inc/sidebar.php";
require_once 'classes/ExpenseTracker.php';
$tracker = new ExpenseTracker();

// Default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Handle date filter
if (isset($_POST['filter'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

$expenses = $tracker->getExpenses($start_date, $end_date);
$total_expenses = $tracker->getTotalExpenses($start_date, $end_date);
?>

<div class="card">
    <div class="card-header bg-secondary text-white">
        Expense Tracker
    </div>
    <div class="card-body">
        <h5 class="card-title">Expense Details</h5>

        <?php 
        // Display session messages
        if (isset($_SESSION['message'])): ?>
            <div class="alert <?php 
                echo isset($_SESSION['message_type']) && $_SESSION['message_type'] == 'error' 
                    ? 'alert-danger alert-dismissible' 
                    : 'alert-success alert-dismissible'; 
            ?>">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <?php 
                echo htmlspecialchars($_SESSION['message']); 
                // Clear the message after displaying
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="filter-section mb-3">
            <form method="POST" class="form-inline">
                <div class="form-group mr-2">
                    <label for="start_date" class="mr-2">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group mr-2">
                    <label for="end_date" class="mr-2">End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" name="filter" class="btn btn-primary">Filter</button>
            </form>
        </div>

        <div class="summary mb-3">
            <h4>Total Expenses: ₹<?php echo number_format($total_expenses, 2); ?></h4>
        </div>

        <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            
            <tbody>
                <?php 
                if ($expenses && $expenses->num_rows > 0) {
                    foreach ($expenses as $expense) {
                ?>
                <tr>
                    <td><?php echo $expense['date']; ?></td>
                    <td><?php echo $expense['category']; ?></td>
                    <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                    <td><?php echo $expense['description']; ?></td>
                    <td>
                        <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-warning mr-1">Edit</a>
                        <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="5" class="text-center">No Expenses Found</td>
                </tr>
                <?php 
                }
                ?>
            </tbody>
        </table>

        <div class="card-footer">
            <a href="add_expense.php" class="btn btn-success">Add New Expense</a>
        </div>
    </div>
</div>
</div>

<?php
include_once "inc/footer.php";
?>