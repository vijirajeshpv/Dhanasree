<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $gl_no = $_POST['key'];
    $br = $ml->getGoldLoanDetails($gl_no);
$openingDate="";
$loan="";
$totalDays="";
    if ($br) {
        $row = $br->fetch_assoc();
	$name=$row['name'];
        $openingDate = $row['date'];
        // Fetch additional loan details if necessary
	if ($openingDate !== null) {
      $closingDate = new DateTime(); // Current date/time
      $openDate = DateTime::createFromFormat('Y-m-d', $openingDate);
if ($openDate === false) {
    echo "Invalid input date format.";
} else {
    // Step 2: Format the date into ddmmyyyy
    $formattedDate = $openDate->format('d-m-Y');
    //echo $formattedDate; // Output: 16092023
}
      // Check if the conversion was successful
      if ($openDate && $closingDate) {
        // Calculate the difference between $endDate and $startDate
        $interval = $openDate->diff($closingDate);

        // Get the total number of days from the interval
        $totalDays = $interval->days;
//echo $totalDays;
      } else {
        echo "Invalid date format or date string.";
      }



      // Now you can use $daysDifference to get the difference between dates.
    } else {
      // Handle the case when $openingDate is null.
      // For example, you can set a default value or show an error message.
    }

    $loan_amnt = $loan?$loan['loan_amnt']:0;
    $interest = ceil((($loan_amnt * 0.18) / 365) * $totalDays);

    // Calculate the final amount
    $total = ceil($loan_amnt + $interest);
}
        $loan = $ml->getGoldLoanDetails($gl_no);
        if ($loan) {
            $loanDetails = $loan->fetch_assoc();
        } else {
            echo "<span class='text-center' style='color:red'>Loan not found!</span>";
        }
    } else {
        echo "<span class='text-center' style='color:red'>GL No not matched or not applicable for loan</span>";
    }


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Close'])) {
    // Process form submission to close the loan
    $gl_no = $_POST['gl_no'];
    $updated = $ml->closeGoldLoan($gl_no);
    if ($updated) {
        echo "<span class='text-center' style='color:green'>Loan closed successfully!</span>";
    } else {
        echo "<span class='text-center' style='color:red'>Failed to close loan. Please try again.</span>";
    }
}
?>

<h3 class="page-heading mt-4 mb-5 font-weight-bold text-center border-bottom pb-3">Close Gold Loan </h3>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Gl_No" required>
        </div>
        <div class="col-sm-3 ">
            <input type="submit" class="btn btn-info w-100" name="search" value="Search">
        </div>
    </div>
</form>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" autocomplete="off">
    <!-- Display borrower and loan details here -->
    <?php if (!empty($name) && !empty($loanDetails)): ?>
        <div class="form-group row">
            <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
            <div class="col-sm-9">
                <input type="text" name="borrower_name" class="form-control" id="inputBorrowerFirstName" value="<?php echo $name; ?>" required readonly>
                <input type="hidden" name="b_id" value="<?php echo $b_id; ?>">
            </div>
        </div>
        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">GL_NO</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" read only name="gl_no" value="<?php echo $loanDetails['gl_no']; ?>">
            </div>
        </div>
        <!-- Display other loan details here -->
        <!-- Add a hidden field for gl_no -->
        <input type="hidden" name="gl_no" value="<?php echo $loanDetails['gl_no']; ?>">
<div class="form-group row">
      <label class="text-right col-2 font-weight-bold col-form-label">Opening Date</label>
<div class="col-sm-9">
      <input type="text" class="form-control" read only name="opening_date" value="<?php if (isset($formattedDate))  echo $formattedDate; ?>">
    </div>
  </div>


    <div class="form-group row">
      <label class="text-right col-2 font-weight-bold col-form-label">Net_weight</label>
      <div class="col-sm-9">
        <input type="number" name="net_weight" class="form-control" value="<?php echo  $loan?$loan['net_weight']:''; ?>" readonly>
      </div>
    </div>


    <div class="form-group row">
      <label for="interest" class="text-right col-2 font-weight-bold col-form-label">Interest</label>
      <div class="col-sm-9">
        <input type="number" name="interest" class="form-control" id="interest" value="<?php echo $interest?$interest:''; ?>" readonly>
      </div>
    </div>
  
  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">loan amount</label>
    <div class="col-sm-9">
      <input type="number" name="current_inst" class="form-control" value="<?php if (isset($loan['loan_amnt'])) echo $loan['loan_amnt']; ?>" readonly>
    </div>
  </div>
  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">Item Description</label>
    <div class="col-sm-9">
      <input type="text" name="item_description" class="form-control" value="<?php if (isset($loan['item_description'])) {
                                                                                echo $loan['item_description'];
                                                                              } ?>" readonly>
    </div>
  </div>


  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">Total Amount</label>
    <div class="col-sm-9">


      <input type="number" name="total" class="form-control" value="<?php if (isset($loan['loan_amnt'])) echo $total; ?>" readonly>
    </div>
  </div>
  <hr>
  <div class="form-group row">
    <div class="col-md-11">
      <input type="submit" name="Close" class="btn btn-success pull-right w-25" value="Close Loan">
    </div>
  </div><!-- /.box-footer -->
</form>
</div>
        
    <?php endif; ?>


<?php include_once "inc/footer.php"; ?>
