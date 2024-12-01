<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
  require_once 'classes/ExpenseTracker.php';
  $tracker = new ExpenseTracker();
?>
<?php 
    $error = '';
    $success = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date = $_POST['date'];

        if (empty($category) || empty($amount) || empty($description) || empty($date)) {
            $error = "Please fill in all fields.";
        } else {
            if ($tracker->addExpense($category, $amount, $description, $date)) {
                $success = "Expense added successfully!";
                // Clear form
                $_POST = [];
            } else {
                $error = "Failed to add expense.";
            }
        }
    }
?>

<h3 class="page-heading mb-4 mb-5 font-weight-bold text-center">Add Expense</h3>
<h5 class="card-title p-3 bg-none text-dark rounded border-bottom">Expense Details</h5>
<div class="container pt-4">
    <?php
    if (!empty($success)) {
    ?>
    <div id="successMessage" class="alert alert-success alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?php echo $success; ?>
    </div>
    <?php
    }
    if (!empty($error)) {
    ?>
    <div id="errorMessage" class="alert alert-danger alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?php echo $error; ?>
    </div>
    <?php
    }
    ?>

    <form action="" method="POST" id="add_expense_form">
        <div class="form-group row">
            <label for="inputExpenseCategory" class="text-right col-2 font-weight-bold col-form-label">Category</label>                      
            <div class="col-sm-9">
                <select name="category" class="form-control" id="inputExpenseCategory" required>
                    <option value="">Select Category</option>
                    <option value="Food" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Food') ? 'selected' : ''; ?>>Food</option>
                    <option value="Transportation" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
                    <option value="Utilities" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Utilities') ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Entertainment" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                    <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="inputExpenseAmount" class="text-right col-2 font-weight-bold col-form-label">Amount</label>  
            <div class="col-sm-9">
                <input type="number" name="amount" class="form-control" id="inputExpenseAmount" placeholder="Enter Amount" step="0.01" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-group row">
            <label for="inputExpenseDescription" class="text-right font-weight-bold col-2 col-form-label">Description</label>                      
            <div class="col-sm-9">
                <textarea name="description" class="form-control" id="inputExpenseDescription" placeholder="Enter Description" required><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label for="inputExpenseDate" class="text-right font-weight-bold col-2 col-form-label">Date</label>                      
            <div class="col-sm-9">
                <input type="date" name="date" class="form-control" id="inputExpenseDate" value="<?php echo isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'); ?>" required>
            </div>
        </div>

        <hr>
        <div class="box-footer col-11">
            <button type="submit" name="submit" class="btn btn-info pull-right" data-loading-text="<i class='fa fa-spinner fa-spin'></i> Please Wait">Submit</button>
        </div>
    </form>
</div>
</div>

<?php
include_once "inc/footer.php";
?>