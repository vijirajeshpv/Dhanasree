<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";

$name = ""; $b_id = ""; $loanDetails = array();

// Fetch all outstanding loans when the page first loads
$allLoanDetails = $ml->getGoldLoanStatus();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $mob = $_POST['key'];
    $br = $emp->findBorrowerByMobile($mob);
    if ($br) {
        $row = $br->fetch_assoc();
        $name = $row['name'];
        $b_id = $row['id'];
        // Fetch the loan details for the searched customer
        $loanDetails = $ml->getGoldLoanDetailsByBid($b_id);
    } else {
        echo "<span class='text-center' style='color:red'>Borrower NID not matched or not applicable for loan</span>";
    }
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter mobile number of borrower" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>

</form>

<div class="card">
    <div class="card-header bg-secondary text-white">
        Loan Status
    </div>
    <div class="card-body">
        <?php if(empty($loanDetails)): ?>
            <h5 class="card-title">Outstanding Loan Details</h5>
            <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>gl_no </th>
                        <th>item_description</th>
                        <th>gross_weight</th>
                        <th>stone_weight</th>
                        <th>net_weight</th>
                        <th>market_value</th>
                        <th>loan_amnt</th>
                        <th>customer name</th>
                        <th>date</th>
                        <th>closing_date</th>
                        <th>Status</th>
                        <th>file</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allLoanDetails as $row): ?>
                        <tr>
                            <td><?php echo $row['gl_no']; ?></td>
                            <td><?php echo $row['item_description']; ?></td>
                            <td><?php echo $row['gross_weight']; ?> </td>
                            <td><?php echo $row['stone_weight']; ?></td>
                            <td><?php echo $row['net_weight']; ?> </td>
                            <td><?php echo $row['market_value']; ?></td>
                            <td><?php echo $row['loan_amnt']; ?></td>
                            <td><?php echo $row['name']; ?> </td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['closing_date']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><img src="<?php echo $row['file']; ?>" width="100" height="100" ></td>
                            <td>
                                <input type="radio" name="loan_status_<?php echo $row['gl_no']; ?>" value="closed"> Closed
                                <input type="radio" name="loan_status_<?php echo $row['gl_no']; ?>" value="open" checked> Open
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h5 class="card-title">Gold Loan Details for <?php echo $name; ?></h5>
            <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>gl_no </th>
                        <th>item_description</th>
                        <th>gross_weight</th>
                        <th>stone_weight</th>
                        <th>net_weight</th>
                        <th>market_value</th>
                        <th>loan_amnt</th>
                        <th>customer name</th>
                        <th>date</th>
                        <th>closing_date</th>
                        <th>Status</th>
                        <th>file</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loanDetails as $row): ?>
                        <tr>
                            <td><?php echo $row['gl_no']; ?></td>
                            <td><?php echo $row['item_description']; ?></td>
                            <td><?php echo $row['gross_weight']; ?> </td>
                            <td><?php echo $row['stone_weight']; ?></td>
                            <td><?php echo $row['net_weight']; ?> </td>
                            <td><?php echo $row['market_value']; ?></td>
                            <td><?php echo $row['loan_amnt']; ?></td>
                            <td><?php echo $row['name']; ?> </td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['closing_date']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php //echo $row['file']; ?></td>
                            <td>
                                <input type="radio" name="loan_status_<?php echo $row['gl_no']; ?>" value="closed"> Closed
                                <input type="radio" name="loan_status_<?php echo $row['gl_no']; ?>" value="open" checked> Open
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
include_once "inc/footer.php";
?>
