<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
?>

     <!-- <h3 style="" >Jayalakshmi Enterprises<br>Kml No<br>Manapady<br>Katoor Road</h3>            
    <div class="form-group row">
              <div class="col-md-6">
              
              </div>
          </div><   
        </form>
       </div>             -->
<div class="dash-head  pb-5 px-0 container">
  <div class="d-flex align-items-center  border-bottom  dash-head-wrap py-3 bg-white">
     <div class="col-8"> 
      <div class="font-weight-bold head "> Welcome, <?php echo Session::get("name");?></div>
      <span class="font-weight-bold"></span>
    </div>
      <div class="col-4 text-right">
      <div class="div">
      <div class="font-weight-bold ">Dhanasree Financiers</div>
      </div>
        <div class="sub">Kml Reg.No 32080357361, <br>Neelankavil Complex, <br> 6\26C,Arippalam-680688</div> 
      </div>
  </div>
</div>
  <div class="container dash-cont d-flex justify-content-center align-items-stretch px-0"> 
      <div class="d-flex flex-wrap justify-content-center col-8 p-0 align-items-center">
          <div class="col-5 pl-0 ">
                <a href="apply_for_gold_loan2.php">
                  <div class="card justify-content-center text-center d-flex  mb-4 ">
                  <span> <svg width="32" height="32" viewBox="0 0 22 21" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0 21.0001L1.5 16.0001H8.5L10 21.0001H0ZM12 21.0001L13.5 16.0001H20.5L22 21.0001H12ZM5 14.0001L6.5 9.0001H13.5L15 14.0001H5ZM22 5.0501L18.14 6.1401L17.05 10.0001L15.96 6.1401L12.1 5.0501L15.96 3.9601L17.05 0.100098L18.14 3.9601L22 5.0501Z" fill="#0a1487"/>
              </svg></span>Add Gold Loan 
                  </div>
                </a>
          </div>
          <div class="col-5 pl-0">
                <a href="close_gold_loan.php">
                  <div class="card justify-content-center text-center d-flex  mb-4 ">
                <span> <i class="fa fa-window-close-o" aria-hidden="true"></i></span> Close Gold Loan
                  </div>
                </a>
          </div>
          <div class="col-5 pl-0">
                <a href="apply_for_repledge.php">
                  <div class="card justify-content-center text-center d-flex mr-4">
                  <span> <i class="fa fa-refresh" aria-hidden="true"></i></span>Repledge Loan
                  </div>
                </a>
          </div>
          <div class="col-5 pl-0">
                <a href="close_repledge.php">
                  <div class="card justify-content-center text-center d-flex  ">
                <span> <i class="fa fa-window-close-o" aria-hidden="true"></i></span> Close Repledge
                  </div>
                </a>
          </div>
      </div>
      <!-- <div class="d-flex flex-wrap col-4 align-items-center bg-white rounded px-0">
          <div class="col-12 px-0">
                  <div class="info-card justify-content-center text-center d-flex align-items-center text-success">
                  <div class="h5 mb-0 mr-4 font-weight-bold col-8">
               Active Loans
                  </div>
                  <div class="h1 font-weight-bold mb-0 "> 156</div>
                  </div>
          </div>
          <div class="col-12"> <hr></div>
      
          <div class="col-12 px-0">
                  <div class="info-card justify-content-center text-center d-flex align-items-center text-primary">
                  <div class="h5 mb-0 mr-4 font-weight-bold col-8">
                  Number of clients
                  </div>
                  <div class="h1 font-weight-bold mb-0 "> 106</div>
                  </div>
                 
          </div>
          <div class="col-12"> <hr></div>
          <div class="col-12 px-0">
                  <div class="info-card justify-content-center text-center d-flex align-items-center text-danger">
                  <div class="h5 mb-0 mr-4 font-weight-bold col-8">
                 Loans Due Next Week
                  </div>
                  <div class="h1 font-weight-bold mb-0"> 16</div>
                  </div>
          </div>
          
          
      </div> -->

  </div>
</div>
<?php
include_once "inc/footer.php";
?>