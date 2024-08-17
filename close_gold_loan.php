<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
?>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Close'])) {

  $selected = $ml->getGoldLoanDetails($_POST['gl_no']);
}

// Close gold loan if the "Close" button is clicked
if (isset($_POST['Close'])) {
  $updated = $ml->closeGoldLoan($_POST['gl_no']);

}

?>
<h3 class="page-heading mt-4 mb-5 font-weight-bold text-center border-bottom pb-3">Close Gold Loan </h3>

<?php
$loan = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
  $gl_no = $_POST['key'];
  $br = $ml->getGoldLoanDetails($gl_no);

  if ($br) {
    $row = $br->fetch_assoc();
    $name = $row['name'];
    $gl_no = $row['gl_no'];
$openingDate = $row['date'];
    //var_dump($b_id);
    // $total_loan = $row['total_loan'];
    // $amount_paid = $row['amount_paid'];
    $gploan = $ml->getGoldLoanDetails($gl_no);
    if ($gploan) {
      $loan = $gploan->fetch_assoc();

      $loan_no_r = $loan['gl_no'];
    } else {
      echo "<span class='text-center' style='color:red'>Loan not approved or already Paid!</span>";
    }
  } else {
    echo "<span class='text-center' style='color:red'>GL No not matched </span>";
  }
}
?>
<form action="" method="POST" autocomplete="off">
  <div class="form-group row">
    <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Enter Gl Number: </label>
    <div class="col-sm-6">
      <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Gl Number" required>
    </div>
    <div class="col-sm-3 ">
      <input type="submit" class="btn btn-info w-100" name="search" value="Search">
    </div>
  </div>

</form>

<form action="" method="post" name="myform" id="myform" autocomplete="off">

  <div class="form-group row">
    <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
    <div class="col-sm-9">
      <input type="text" name="borrower_name" class="form-control" id="inputBorrowerFirstName" value="<?php if (isset($name)) echo $name; ?>" required readonly>
      <input type="hidden" name="b_id" value="<?php if (isset($b_id)) echo $b_id; ?>">

 </div>
  </div>
<div class="form-group row">
      <label class="text-right col-2 font-weight-bold col-form-label">GL_NO</label>
<div class="col-sm-9">
      <input type="text" class="form-control" read only name="gl_no" value="<?php if (isset($loan['gl_no']))  echo $loan['gl_no']; ?>">
    </div>
  </div>


  <?php
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $gl_no = $_POST['key'];

    //$br = $ml->getOpeningDate($gl_no);

        
        
//echo  $gl_no;
//echo nl2br("\n");
 // echo $openingDate;
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
else {
       // echo "<span class='text-center' style='color:red'>GL Number not matched </span>";
    }
  ?>
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

<?php 
include_once "inc/footer.php";
?>