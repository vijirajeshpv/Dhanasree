<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
?>
<script>
        function calculateTotal() {
 var interest = document.myform.interest.value;
            if (!interest)
                interest = '0';

             var others = document.myform.others.value;
            if (!others)
                others = '0';
var amount_bank=document.myform.amount_bank.value;
            if (!amount_bank)
                amount_bank = '0';
var interest = parseFloat(interest);
            var others = parseFloat(others);
var amount_bank=parseFloat(amount_bank);

            var total   =(interest + others)+amount_bank;
document.myform.total.value=parseFloat(total);

}
</script>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Close'])) {
if (isset($_POST['bank_gl_no'])) {
  $selected = $ml->getRepledgeDetails($_POST['bank_gl_no']);


// Close repledge if the "Close" button is clicked
if (isset($_POST['Close'])) {
  $updated = $ml->closeRepledge($_POST['bank_gl_no']);

}
}
}
?>
<h2 class="page-heading mt-4 mb-5 font-weight-bold text-center border-bottom pb-3 ">Close Repledge</h2>

<?php
$loan = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
  $bank_gl_no = $_POST['key'];
  $br = $ml->getRepledgeDetails($bank_gl_no);

  if ($br) {
    $row = $br->fetch_assoc();
    $name = $row['customer_name'];
    $bank_gl_no = $row['bank_gl_no'];
    //var_dump($b_id);
    // $total_loan = $row['total_loan'];
    // $amount_paid = $row['amount_paid'];
    $gploan = $ml->getRepledgeDetails($bank_gl_no);
    if ($gploan) {
      $loan = $gploan->fetch_assoc();

      $loan_no_r = $loan['bank_gl_no'];
    } else {
      echo "<span class='text-center' style='color:red'>Loan not existing or already Paid!</span>";
    }
  } else {
    echo "<span class='text-center' style='color:red'>GL No not matched or not applicable for loan</span>";
  }
}
?>
<form action="" method="POST">
  <div class="form-group row">
    <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Enter Bank Gl Number: </label>
    <div class="col-sm-6">
      <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Bank Gl_No" required>
    </div>
    <div class="col-sm-3">
      <input type="submit" class="btn w-100 btn-info" name="search" value="Search">
    </div>
  </div>

</form>

<form action="" method="post" name="myform" id="myform">

  <div class="form-group row">
    <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
    <div class="col-sm-9">
      <input type="text" name="borrower_name" class="form-control" id="inputBorrowerFirstName" value="<?php if (isset($name)) echo $name; ?>" required readonly>
      <input type="hidden" name="customer_id" value="<?php if (isset($customer_id)) echo $customer_id; ?>">
      <input type="hidden" name="bank_gl_no" value="<?php if (isset($loan['bank_gl_no']))  echo $loan['bank_gl_no']; ?>">
    </div>
  </div>


     
  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">loan amount</label>
    <div class="col-sm-9">
      <input type="number" name="amount_bank" class="form-control" value="<?php if (isset($loan['amount_bank'])) echo $loan['amount_bank']; ?>" onkeyup="calculateTotal()" readonly>
    </div>
  </div>
  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">Finance Gl No </label>
    <div class="col-sm-9">
      <input type="text" name="finance_gl_no" class="form-control" value="<?php if (isset($loan['finance_gl_no'])) {
                                                                                echo $loan['finance_gl_no'];
                                                                              } ?>" readonly>
    </div>
  </div>
<div class="form-group row">
<label class="text-right col-2 font-weight-bold col-form-label">Interest</label>
<div class="col-sm-9">
      <input type="number" name="interest" class="form-control" value=""  onkeyup="calculateTotal()">
    </div>
  </div>
<!-- code1-->
<div class="form-group row">
<label class="text-right col-2 font-weight-bold col-form-label">Other Charges</label>
<div class="col-sm-9">
      <input type="number" name="others" class="form-control" value="" onkeyup="calculateTotal()" >
    </div>
  </div>
<?php
// check
 

?>
  <div class="form-group row">
    <label class="text-right col-2 font-weight-bold col-form-label">Total Amount</label>
    <div class="col-sm-9">


      <input type="number" name="total" class="form-control" value=""  >
    </div>
  </div>
  <hr>
  <div class="form-group row">
    <div class="col-md-11 text-right">
      <input type="submit" name="Close" class="btn btn-danger w-25 " value="Close">
    </div>
  </div><!-- /.box-footer -->
</form>
</div>

<?php 
include_once "inc/footer.php";
?>