<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";

$name = "";
$b_id = "";
$loanDetails = array();
$status = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $gl_no = $_POST['key'];
    $br = $ml->getGoldLoanDetails($gl_no);
    if ($br) {
        $row = $br->fetch_assoc();

        // Get the selected loan status (closed or non-closed)
        $status = ($_POST['loan_status'] == 'non-closed') ? 0 : 1;

        // Fetch the loan details based on the selected status
        $loanDetails = $ml->getRepledgeDetails1($gl_no, $status);
    } else {
        echo "<span class='text-center' style='color:red'>Borrower ID not matched or not applicable for loan</span>";
    }
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Enter Gl Number: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Gl Number" required>
        </div>
        <div class="col-sm-3">
            <input type="radio" name="loan_status" value="closed"> Closed
            <input type="radio" name="loan_status" value="non-closed"> Non-Closed
        </div>
        <div class="col-sm-1">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>

</form>

<?php if (!empty($loanDetails)): ?>
    <div class="card">
        <div class="card-header bg-secondary text-white">
            Loan Status
        </div>
        <div class="card-body">
            <h5 class="card-title">Gold Loan Details for <?php echo $name; ?></h5>
            <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>gl_no</th>
                        <th>bank gl no</th>
                        <th>date</th>
                        <th>due date</th>
                        <th>status</th>
                        <th>Bank loan_amnt</th>
                        <th>customer name</th>
                        <th>interest</th>
                        <th>others</th>
                        <th>total_amount</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loanDetails as $row): ?>
                        <tr>
                            <td><?php echo $row['finance_gl_no']; ?></td>
                            <td><?php echo $row['bank_gl_no']; ?></td>
                            
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['due_date']; ?> </td>
                            <td><?php echo $row['Status']; ?></td>
                            <td><?php echo $row['amount_bank']; ?></td>
                            <td><?php echo $row['customer_name']; ?> </td>
                            <td><?php echo $row['interest']; ?></td>
                            <td><?php echo $row['others']; ?></td>
                            <td><?php echo $row['total_amount']; ?></td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
include_once "inc/footer.php";
?>
