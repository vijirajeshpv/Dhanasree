<?php
ob_start(); // Start output buffering
include_once "inc/header.php";
include_once "inc/sidebar.php";

$name = "";
$b_id = "";
$mob = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_loan_application'])) {
    $inserted = $ml->applyForLoan($_POST, $_FILES);
    if (isset($inserted)) {
        // Prepare report data
        $gl_no = isset($_POST['gl_no']) ? $_POST['gl_no'] : '';
        $name = isset($_POST['borrower_name']) ? $_POST['borrower_name'] : '';
        $b_id = isset($_POST['b_id']) ? $_POST['b_id'] : '';
        $mobile = isset($_POST['key']) ? $_POST['key'] : '';

        $br = $emp->findBorrowerByMobile($mobile);

        if ($br) {
            $row = $br->fetch_assoc();
            $name = isset($row['name']) ? $row['name'] : null;
            $b_id = isset($_POST['b_id']) ? $_POST['b_id'] : null;
            $cmr = $emp->getCustomerDetails($b_id);
            if ($cmr) {
                $row = $cmr->fetch_assoc();
		$name=$row['name'];
                $addr = $row['address'];
                $mob = $row['mobile'];
                $addr = isset($row['address']) ? $row['address'] : '';
                $mob = isset($row['mobile']) ? $row['mobile'] : '';
            }
        }
      //  $date = date("Y-m-d");
   // $date1 = new DateTime($date);
     $outputDate = isset($_POST['date']) ? $_POST['date'] : '';

        $item_description = isset($_POST['item_description']) ? $_POST['item_description'] : '';
        $other_item_description = isset($_POST['other_item_description']) ? $_POST['other_item_description'] : '';
        $net_weight = isset($_POST['net_weight']) ? $_POST['net_weight'] : '';
        $loan_amount = isset($_POST['loan_amount']) ? $_POST['loan_amount'] : '';
        $image = isset($_POST['customer']) ? $_POST['customer'] : '';
        $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
	$offset = 44;
        $report_data = [
            'GL Number' => $gl_no,
            'Name' => $name,
            'Borrower ID' => $b_id,
            'Address' => $addr,
            'Mobile' => $mob,
            'Date' => $outputDate,
            'Item Description' => $other_item_description,
            'Net Weight' => $net_weight,
            'Loan Amount' => $loan_amount,
            'Customer' => $image,
            'Due Date' => $due_date
        ];

        require('fpdf186/fpdf.php');
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 22);
        $pdf->Cell(0, 0, 'Dhanasree Financiers', 0, 1, 'C');
 	$pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 15, 'Neelankavil Complex,6\26C,Arippalam-680688', 0, 1, 'C');
	$pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 15, 'KML REISTRATION NUMBER:32080357361 ', 0, 1, 'C');
	$pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 15, 'Pledge Form M (C Rule 14 of KML Act)', 0, 1, 'C');

       

        // Loop through report data
        foreach ($report_data as $label => $value) {
	$pdf->SetX($offset);
            $pdf->Cell(80, 10, $label, 0, 0); // Set width for label
            $pdf->Cell(60, 10, $value, 0, 1); // Align value to the right
        }
     $pdf->SetFont('Arial', '', 12);
	$rules = "1. Interest must be paid within three months.\n";
$rules .= "2. A gold loan must be closed within the course of one year.\n";
$rules .= "3. The interest charge is 18% p.a. of the gold loan amount.\n";
$rules .= "4. Failure to repay the loan may result in the auction of the pledged gold.\n";
$rules .= "5. The borrower must keep the receipt safe and produce it when reclaiming the pledged gold.\n";
$rules .= "6. The customer is liable to inform them of the change of contact address.\n";
$rules .= "7. The customer must assure that the gold ornaments pledged to the company are owned by himself. \n";
$rules .=" Declaration By the Customer\n";
$rules .=" I received the gold ornaments that I pledged.\n";

// MultiCell to wrap the text
$pdf->MultiCell(0, 10, $rules);
        $pdfPath = "my_generated_pdf.pdf";

        try {
            ob_end_clean(); // Clear the output buffer
            $pdf->Output(); // Save the PDF to a file
            echo "PDF generated successfully at: $pdfPath";
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    } else {
        echo "Error inserting data into the database.";
    }
}
// Other logic
$name = " ";
$b_id = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    //$date =isset($_POST['date']) ? $_POST['date'] : '';
    // $date1 = new DateTime($date);
    $outputDate = isset($_POST['date']) ? $_POST['date'] : '';
    $mobile = $_POST['key'];
    $customer_name = isset($_POST['borrower_name']) ? $_POST['borrower_name'] : '';
    $b_id = isset($_POST['b_id']) ? $_POST['b_id'] : '';
    $gl_no = isset($_POST['gl_no']) ? $_POST['gl_no'] : '';
    $other_item_description = isset($_POST['other_item_description']) ? $_POST['other_item_description'] : '';
    $item_description = isset($_POST['item_description']) ? $_POST['item_description'] : '';
    $net_weight = isset($_POST['net_weight']) ? $_POST['net_weight'] : '';
    $loan_amount = isset($_POST['loan_amount']) ? $_POST['loan_amount'] : '';
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';

    $br = $emp->findBorrowerByMobile($mobile);

    if ($br) {
        $row = $br->fetch_assoc();
        $name = isset($row['name']) ? $row['name'] : null;
        $b_id = isset($row['id']) ? $row['id'] : null;
        $cmr = $emp->getCustomerDetails($b_id);
        if ($cmr) {
            $row = $cmr->fetch_assoc();
            $addr = $row['address'];
            $mob = $row['mobile'];
            $image = $row['image'];
        }
    }
}
?>

<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST" autocomplete="off">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search Borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Mobile Number of borrower" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info w-100" name="search" value="Search">
        </div>
    </div>
</form>

<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" name="myform" id="myform" autocomplete="off">
    <!-- Added Borrower Name -->
    <div class="form-group row">
        <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Borrower Name</label>
        <div class="col-sm-9">
            <input type="text" name="borrower_name" class="form-control" value="<?php echo $name; ?>" readonly>
        </div>
    </div>

    <!-- Added Borrower ID -->
    <div class="form-group row">
        <label for="borrower_id" class="text-right col-2 font-weight-bold col-form-label">Borrower ID</label>
        <div class="col-sm-9">
            <input type="text" name="b_id" class="form-control" value="<?php echo $b_id; ?>" readonly>
        </div>
    </div>

    <!--Gl_No -->
    <div class="form-group row">
        <label for="gl_no" class="text-right col-2 font-weight-bold col-form-label">GL Number</label>
        <div class="col-sm-9">
            <input type="text" name="gl_no" class="form-control">
        </div>
    </div>
 <!-- -->
    <div class="form-group row">
        <label for="date" class="text-right col-2 font-weight-bold col-form-label">Date</label>
        <div class="col-sm-9">
            <input type="text" name="date" class="form-control">
        </div>
    </div>
    <div class="form-group row">
        <label for="item_description" class="text-right col-2 font-weight-bold col-form-label ">Item description</label>
        <div class="col-sm-9">
            <!--Item Description-->
            <label for="item_description">Select an item:</label>
            <div class="d-flex justify-content-between align-items-center">
                <select id="item_description" name="item_description[]" onchange="getItem()" class="form-control col-6">
                    <option value="Chain">Chain</option>
                    <option value="Bangle">Bangle</option>
                    <option value="Ring">Ring</option>
                    <option value="ear ring">ear ring</option>
                    <option value="others">others</option>
                </select>
                <input type="text" placeholder="selected_item" id="other_item_description" name="other_item_description" class="border-0  col-4">
            </div>
        </div>
    </div>

   <div class="form-group row">
              <label for="gross_weight" class="text-right col-2 font-weight-bold col-form-label">gross_weight</label>                      
              <div class="col-sm-9">
                  <input type="number" onkeyup="calculateEMI()" name="gross_weight" class="form-control" id="gross_weight" min="0"  step="0.01" placeholder="Enter gross_weight" required>
              </div>
            </div>

            <div class="form-group row">
                <label  class="text-right col-2 font-weight-bold col-form-label">Stone weight</label>                      
                 <div class="col-sm-9">
                  <input type="number" onkeyup="calculateEMI()" name="stone_weight" class="form-control" id="stone_weight" min="0"  step="0.01" placeholder="Enter Stone weight" required>
              </div>
            </div> 
            
             <div class="form-group row">
                <label  class="text-right col-2 font-weight-bold col-form-label">Net Weight</label>                      
                 <div class="col-sm-9">
                  <input type="text"  id="net_weight" name="net_weight" class="form-control" readonly required>
              </div>
            </div> 

            <div class="form-group row">
                <label for="market_value" class="text-right col-2 font-weight-bold col-form-label">Market Value</label>  
                <div class="col-sm-9">
                    <input type="number" onkeyup="calculateEMI()" name="market_value" class="form-control" id="market_value"  required>
                </div>
            </div>
<div class="form-group row">
                <label for="loan_amount" class="text-right col-2 font-weight-bold col-form-label">Loan Amount</label>  
                <div class="col-sm-9">
                    <input type="text" name="loan_amount" class="form-control positive-integer" id="loan_amount"  required>
                </div>
            </div>
<div class="form-group row">
                <label for="due_date" class="text-right col-2 font-weight-bold col-form-label">Due Date</label>  
                <div class="col-sm-9">
                    <input type="text" name="due_date" class="form-control positive-integer" id="due_date"    >
                </div>
            </div>
          <hr>
          <div class="form-group row">
              <label for="gold_images" class="text-right font-weight-bold col-2 col-form-label">Gold Images<br></label>
              <div class="col-sm-9">    
                  <input type="file"  id="gold_photo_file" name="image" required>
              </div>
          </div>
             <hr>
    <div class="form-group row">
        <div class="col-md-6">
            <input type="submit" name="submit_loan_application" class="btn btn-info pull-right" value="Submit Application">
        </div>
    </div>
</form>

<script>
        function calculateEMI() {
            
            var item_description=document.myform.item_description.value;
              if (!item_description)
                item_description = null;
            
            var gross_weight = document.myform.gross_weight.value;
            if (!gross_weight)
                gross_weight = '0';

             var stone_weight = document.myform.stone_weight.value;
            if (!stone_weight)
                stone_weight = '0';
            


            var market_value =document.myform.market_value.value;
            if (!market_value)
                market_value = '0';

            
            var gross_weight = parseFloat(gross_weight);
            var stone_weight = parseFloat(stone_weight);
            var net_weight   =gross_weight-stone_weight;

            var loan_amount = Math.round(net_weight*(market_value*(80/100)));
            document.myform.net_weight.value = parseFloat(net_weight).toFixed(2);
            document.myform.loan_amount.value=parseFloat(loan_amount);
		
		// Get the current date in the format "YYYY-MM-DD"
/* var currentDate = new Date();
var year = currentDate.getFullYear();
var month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
var day = currentDate.getDate().toString().padStart(2, '0');
var dateString = year + '-' + month + '-' + day;

// Create a JavaScript Date object
var dateObject = new Date(dateString);

// Format the date to "DD-MM-YYYY"
var outputDate = dateObject.getDate().toString().padStart(2, '0') + '-' +
                  (dateObject.getMonth() + 1).toString().padStart(2, '0') + '-' +
                  dateObject.getFullYear();

// Add 365 days to the date
dateObject.setDate(dateObject.getDate() + 365);

// Format the due date to "DD-MM-YYYY"
var due_date = dateObject.getDate().toString().padStart(2, '0') + '-' +
              (dateObject.getMonth() + 1).toString().padStart(2, '0') + '-' +
              dateObject.getFullYear();
document.myform.due_date.value=due_date; */

}

   
//         function getItem() {
//        //  var e = document.getElementById("item_description");
//  // document.myform.other_item_description.value = e.options[e.selectedIndex].value;

// var other_item_description = document.getElementById("other_item_description");

// other_item_description.value=Array.prototype.filter.call( document.getElementById("item_description").options,el => el.selected).map(el => el.value).join(",");
//   }

function getItem() {
            var other_item_description = document.getElementById("other_item_description");
            other_item_description.value = Array.prototype.filter.call(
                document.getElementById("item_description").options,
                el => el.selected
            ).map(el => el.value).join(",");
        }
  
       
      </script> 
<?php

include_once "inc/footer.php";
?>


