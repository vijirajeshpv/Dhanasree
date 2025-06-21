<!-- partial -->
    <div class="container-fluid">
      <div class="row row-offcanvas row-offcanvas-right">
        <!-- partial:partials/_sidebar.html -->
        <nav class="bg-white sidebar sidebar-offcanvas" id="sidebar">
          <div class="user-info">
           <div class="user-profile-wrap text-center  ">
           <img class="img-fluid rounded-circle" src="images/user.jpg" alt="">
           </div>
            <p class="name"><?php echo Session::get("name");?></p>
            <p class="designation"><?php echo Session::get("designation");?></p>
            <span class="online"></span>
          </div>
          <ul class="nav">
            <li class="nav-item active ">
              <a class="nav-link" href="index.php">
              <i class="fa fa-th-large" aria-hidden="true"></i>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>

          <?php if(Session::get("role") == 2){?>
          <!-- borrower option -->
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#borrower-pages" aria-expanded="false" aria-controls="borrower-pages">
              <i class="fa fa-user" aria-hidden="true"></i>
                <span class="menu-title">Borrower<i class="fa fa-sort-down"></i></span>
              </a>
              <div class="collapse" id="borrower-pages">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="addborrower.php">Add Borrower</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="viewborrower.php">View Borrower</a>
                  </li>
                  
<li class="nav-item">
                    <a class="nav-link" href="search.php">Search  Borrower</a>
                  </li>
                </ul>
              </div>
            </li>
            <!-- end borrower option -->
         <!-- Gold loan option -->
            <li class="nav-item">
           <!--   <a class="nav-link" data-toggle="collapse" href="" aria-expanded="false" aria-controls="goldloan-pages"> -->
              <a class="nav-link" href="apply_for_gold_loan2.php">
              <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                <span class="menu-title">Gold Loan<i class="fa fa-sort-                                down"></i></span>
              </a>
<div class="collapse" id="goldloan-pages">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="apply_for_gold_loan2.php">Add Gold Loan</a>
                  </li>
                  
 <li class="nav-item">
                    <a class="nav-link" href="updategoldloan.php">Update Gold Loan</a>
                  </li>
<li class="nav-item">
                    <a class="nav-link" href="deletegoldloan.php">Delete Gold Loan</a>
                  </li>
                </ul>
              </div>
            </li>
            <!-- end goldloan option -->

            </li>
            
            <?php } ?>

<li class="nav-item">
              <a class="nav-link" href="generate_pawn_ticket.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Pawn Ticket</span>
              </a>
            </li>
 <li class="nav-item">
              <a class="nav-link" href="loan_between_dates2.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Loan Between Dates</span>
              </a>
            </li>
             <li class="nav-item">
              <a class="nav-link" href="loan_status3.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Loan Status</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="close_gold_loan.php">
              <i class="fa fa-window-close-o" aria-hidden="true"></i>
                <span class="menu-title">Close Gold Loan</span>
              </a>
            </li>
 	<li class="nav-item">
              <a class="nav-link" href="stock_register_report.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Stock Register</span>
              </a>
            </li>
<li class="nav-item">
              <a class="nav-link" href="stock_register.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Net Stock </span>
              </a>
            </li>
           <li class="nav-item">
              <a class="nav-link" href="apply_for_repledge.php">
              <i class="fa fa-refresh" aria-hidden="true"></i>
                <span class="menu-title">Repledge Loan</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="repledge_loan_status_btw_dates.php">
              <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span class="menu-title">Repledge Status</span>
              </a>
            </li>
             <li class="nav-item">
              <a class="nav-link" href="close_repledge.php">
              <i class="fa fa-window-close-o" aria-hidden="true"></i>
                <span class="menu-title">Closing Repledge</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#expenses-pages" aria-expanded="false" aria-controls="expenses-pages">
              <i class="fa fa-user" aria-hidden="true"></i>
                <span class="menu-title">Expenses<i class="fa fa-sort-down"></i></span>
              </a>
              <div class="collapse" id="expenses-pages">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add_expense.php">Add Expenses</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="expenses_dashboard.php">View Expenses</a>
                  </li>
                  
                </ul>
              </div>
            </li>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="due_list1.php">
              <i class="fa fa-window-close-o" aria-hidden="true"></i>
                <span class="menu-title">Over Due</span>
              </a>
            </li>
            
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#accounts-pages" aria-expanded="false" aria-controls="accounts-pages">
              <i class="fa fa-money" aria-hidden="true"></i>
                <span class="menu-title">Accounts<i class="fa fa-sort-down"></i></span>
              </a>
              <div class="collapse" id="accounts-pages">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="balance_sheet.php">Balance Sheet</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="cash_book.php">Cash Book</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="trial_balance.php">Trial Balance</a>
                  </li>
                </ul>
              </div>
            </li>
            

            <li class="nav-item">
              <a class="nav-link" href="add_fixed_asset.php">
              <i class="fa fa-asset-icon" aria-hidden="true"></i>
                <span class="menu-title">Fixed Assets</span>
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="depreciation_management.php">
              <i class="fa fa-window-close-o" aria-hidden="true"></i>
                <span class="menu-title">Depreciation Management</span>
              </a>
            </li>

            
            <li class="nav-item">
              <a class="nav-link" href="captital_management.php">
              <i class="fa fa-window-close-o" aria-hidden="true"></i>
                <span class="menu-title">Capital Management</span>
              </a>
            </li>

          </ul>
        </nav>

        <!-- partial -->
        <div class="content-wrapper">