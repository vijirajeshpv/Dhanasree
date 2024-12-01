<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
  require_once 'classes/ExpenseTracker.php';
  $tracker = new ExpenseTracker();

  $error = '';
  $success = '';
  $expense = null;

  // Check if an ID is provided
  if (!isset($_GET['id']) || empty($_GET['id'])) {
      header("Location: index.php");
      exit();
  }
  $expense_id = $_GET['id'];

  // Fetch the expense details
  $expense = $tracker->getExpenseById($expense_id);

  // If no expense found, redirect
  if (!$expense) {
      header("Location: index.php");
      exit();
  }

  // Process form submission
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $category = $_POST['category'];
      $amount = $_POST['amount'];
      $description = $_POST['description'];
      $date = $_POST['date'];

      if (empty($category) || empty($amount) || empty($description) || empty($date)) {
          $error = "Please fill in all fields.";
      } else {
          if ($tracker->updateExpense($expense_id, $category, $amount, $description, $date)) {
              $success = "Expense updated successfully!";
              // Refresh expense data after update
              $expense = $tracker->getExpenseById($expense_id);
          } else {
              $error = "Failed to update expense.";
          }
      }
  }
?>

<h3 class="page-heading mb-4 mb-5 font-weight-bold text-center">Edit Expense</h3>
<h5 class="card-title p-3 bg-none text-dark rounded border-bottom">Expense Details</h5>
<div class="container pt-4">
    <?php
    if (!empty($success)) {
    ?>
    <div id="successMessage" class="alert alert-success alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php
    }
    if (!empty($error)) {
    ?>
    <div id="errorMessage" class="alert alert-danger alert-dismissible">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php
    }
    ?>

    <form action="" method="POST" id="edit_expense_form">
        <div class="form-group row">
            <label for="inputExpenseCategory" class="text-right col-2 font-weight-bold col-form-label">Category</label>                      
            <div class="col-sm-9">
                <select name="category" class="form-control" id="inputExpenseCategory" required>
                    <option value="">Select Category</option>
                    <option value="Food" <?php echo ($expense['category'] == 'Food') ? 'selected' : ''; ?>>Food</option>
                    <option value="Transportation" <?php echo ($expense['category'] == 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
                    <option value="Utilities" <?php echo ($expense['category'] == 'Utilities') ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Entertainment" <?php echo ($expense['category'] == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                    <option value="Other" <?php echo ($expense['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="inputExpenseAmount" class="text-right col-2 font-weight-bold col-form-label">Amount</label>  
            <div class="col-sm-9">
                <input type="number" name="amount" class="form-control" id="inputExpenseAmount" placeholder="Enter Amount" step="0.01" 
                       value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
            </div>
        </div>
        
        <div class="form-group row">
            <label for="inputExpenseDescription" class="text-right font-weight-bold col-2 col-form-label">Description</label>                      
            <div class="col-sm-9">
                <textarea name="description" class="form-control" id="inputExpenseDescription" placeholder="Enter Description" required><?php echo htmlspecialchars($expense['description']); ?></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label for="inputExpenseDate" class="text-right font-weight-bold col-2 col-form-label">Date</label>                      
            <div class="col-sm-9">
                <input type="date" name="date" class="form-control" id="inputExpenseDate" 
                       value="<?php echo htmlspecialchars($expense['date']); ?>" required>
            </div>
        </div>

        <hr>
        <div class="box-footer col-11">
            <button type="submit" name="submit" class="btn btn-info pull-right" data-loading-text="<i class='fa fa-spinner fa-spin'></i> Please Wait">Update Expense</button>
        </div>
    </form>
</div>

<?php
include_once "inc/footer.php";
?>