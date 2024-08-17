<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
?>


       <script>
        function calculateEMI() {
            
            var bank_gl_no=document.myform.bank_gl_no.value;
              if (!bank_gl_no)
                bank_gl_no = '0';
            
            var date = document.myform.date.value;
            if (!date)
                date = '0';

             var due_date = document.myform.due_date.value;
            if (!due_date)
                due_date = '0';
            


            var finance_gl_no =document.myform.finance_gl_no.value;
            if (!finance_gl_no)
                finance_gl_no = '0';

		var amount_bank=document.myform.amount_bank.value;
		if(!amount_bank)
               amount_bank ='0';
            }
         
      </script>


  <?php 

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_repledge_application'])) {

        $inserted = $ml->applyForRepledge($_POST,$_FILES);

        }
   ?>
        <h2 class="page-heading mt-4 mb-5 font-weight-bold text-center">Gold Repledge Form</h2>
        <h5 class="card-title p-3 bg-none text-dark rounded border-bottom ">Fill up loan details</h5>
        <div class="container pt-4">
          <?php
          if (isset($inserted)){
          ?>
          <div id="successMessage" class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
              <?php  echo $inserted; ?>
         </div>

          <?php
            }          
          ?> 


          

          

          <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST"  enctype="multipart/form-data" name="myform" id="myform" >
            


           

            

            <div class="form-group row">
              <label for="bank_gl_no" class="text-right col-2 font-weight-bold col-form-label">Bank GL Number</label>                      
              <div class="col-sm-9">
                  <input type="text"  name="bank_gl_no" class="form-control" id="bank_gl_no" onkeyup="calculateEMI()" placeholder="Enter Bank GL Number" required>
              </div>
            </div>

            <div class="form-group row">
                <label  class="text-right col-2 font-weight-bold col-form-label">Date</label>                      
                 <div class="col-sm-9">
                  <input type="date"  name="date" class="form-control" id="date" onkeyup="calculateEMI()" placeholder="Enter Date" required>
              </div>
            </div> 
            
             <div class="form-group row">
                <label  class="text-right col-2 font-weight-bold col-form-label">Due Date</label>                      
                 <div class="col-sm-9">
                  <input type="date"  id="due_date" name="due_date" onkeyup="calculateEMI()" class="form-control"  required>
              </div>
            </div> 

            <div class="form-group row">
                <label for="finance_gl_no" class="text-right col-2 font-weight-bold col-form-label">Finance GL No</label>  
                <div class="col-sm-9">
                    <input type="text" name="finance_gl_no" class="form-control" id="finance_gl_no"  required>
                </div>
            </div>
<div class="form-group row">
                <label for="amount_bank" class="text-right col-2 font-weight-bold col-form-label">Bank Loan Amount</label>  
                <div class="col-sm-9">
                    <input type="text" name="amount_bank" class="form-control positive-integer" id="amount_bank" onkeyup="calculateEMI()"  required>
                </div>
            </div>
<div class="form-group row">
                <label for="Customer_name" class="text-right col-2 font-weight-bold col-form-label">Customer Name</label>  
                <div class="col-sm-9">
                    <input type="text" name="customer_name" class="form-control positive-integer" id="customer_name"  onkeyup="calculateEMI()"required>
                </div>
            </div>

          
             <hr>
          <div class="form-group row">
              <div class="col-md-11 d-flex justify-content-end">
              <div class="pr-3">
                 <input type="reset" name="reset_repledge_application" class="btn btn-outline-secondary pull-right" value="Reset">
              </div>
                 <input type="submit" name="submit_repledge_application" class="btn btn-info pull-right" value="Submit Application">
              </div>
          </div><!-- /.box-footer -->    
        </form>
       </div>       
       </div>       

     
<?php
include_once "inc/footer.php";
?>