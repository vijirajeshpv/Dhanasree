<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
?>
  <?php 
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $inserted = $emp->addBorrower($_POST,$_FILES);
        }
   ?>
        <h3 class="page-heading mb-4  mb-5 font-weight-bold text-center">Add Borrower</h3>
        <h5 class="card-title p-3 bg-none text-dark rounded border-bottom ">Borrower Personal Details</h5>
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
          <form action="" method="POST" enctype="multipart/form-data" id="add_borrower_form">

            <div class="form-group row">
              <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>                      
              <div class="col-sm-9">
                  <input type="text" name="borrower_name" class="form-control" id="inputBorrowerFirstName" placeholder="Enter First Name Only" value="">
              </div>
            </div>           
            <div class="form-group row">
                <label for="inputBorrowerMobile" class="text-right col-2 font-weight-bold col-form-label">Mobile</label>  
                <div class="col-sm-9">
                    <input type="text" name="borrower_mobile" class="form-control positive-integer" id="inputBorrowerMobile" placeholder="Numbers Only" value="">
                </div>
            </div>
            
            
            <hr>
            <div class="form-group row">
                <label for="inputBorrowerAddress" class="text-right font-weight-bold col-2 col-form-label">Address</label>                      
                <div class="col-sm-9">
                    <input type="text" name="borrower_address" class="form-control" id="inputBorrowerAddress" placeholder="Address" value="">
                </div>
            </div>

          
          <div class="form-group row">
              
              <label for="user_picture" class="text-right font-weight-bold col-2 col-form-label">Borrower Photo</label>
              <div class="col-sm-9">    
                <input type="file" id="photo_file" name="image">
              </div>
          </div>
             <hr>
          <div class="box-footer col-11">
              <button type="submit" name="submit" class="btn btn-info pull-right" data-loading-text="<i class='fa fa-spinner fa-spin '></i> Please Wait">Submit</button>
          </div><!-- /.box-footer -->    
        </form>
       </div>           
       </div>           
<?php
include_once "inc/footer.php";
?>