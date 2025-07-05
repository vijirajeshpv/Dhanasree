<?php
ob_start(); // Start output buffering
include_once "inc/header.php";
include_once "inc/sidebar.php";
$name = "";
$b_id = "";
$mob = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recieve_loan_from_relatives'])) {
    $inserted = $ml->ReceiveLoan($_POST);
    if (isset($inserted)) {
        // Prepare report data
        $deposit_date = isset($_POST['deposit_date']) ? $_POST['deposit_date'] : '';
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
        $date = date("Y-m-d");
    $date1 = new DateTime($date);
    $outputDate = $date1->format('d-m-Y');



//---
 $date = date("Y-m-d");
    $date1 = new DateTime($date);
    $outputDate = $date1->format('d-m-Y');

        $deposit_date = isset($_POST['deposit_date']) ? $_POST['deposit_date'] : '';
        $deposit_amount = isset($_POST['deposit_amount']) ? $_POST['deposit_amount'] : '';
$deposit_amount_in_words = isset($_POST['deposit_amount_in_words']) ? $_POST['deposit_amount_in_words'] : '';
$rate = isset($_POST['rate_of_interest']) ? $_POST['rate_of_interest'] : '';
	$offset = 44;
        $report_data = [
            
            'Name' => $name,
            'Borrower ID' => $b_id,
            'Amount' => $deposit_amount,
            'Amount In Words' => $deposit_amount_in_words,
            'Date' => $outputDate,
            'Rate' => $rate_of_interest,
	    
            'Due Date' => $deposit_date
        ];

        require('fpdf186/fpdf.php');
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 22);
        $pdf->Cell(0, 0, 'Jayalakshmi Enterprises', 0, 1, 'C');
 	$pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, 'Manapaady,Thanisserry P.O.,680701', 0, 1, 'C');
	$pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8,'KML REGISTRATION NUMBER:32080302832 ', 0, 1, 'C');
	$pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 6, 'Deposit  Form  Relatives (C Rule 14 of KML Act)', 0, 1, 'C');

       $pdf->SetFont('Arial', '', 11);

        // Loop through report data
        foreach ($report_data as $label => $value) {
	$pdf->SetX($offset);
            $pdf->Cell(80, 10, $label, 0, 0); // Set width for label

            $pdf->Cell(60, 10, $value, 0, 1); // Align value to the right
        }
$pdf->SetFont('Arial', 'B', 10);
$pdf->MultiCell(0,10," Terms and Conditions.\n");
    $pdf->SetFont('Arial', '', 10);  // Set font to Arial, Regular, size 10

// Display normal rules
$rules = "1. Interest must be paid within three months.\n";
$rules .= "2. A gold loan must be closed within the course of one year.\n";
$rules .= "3. The interest charge is 18% p.a. of the gold loan amount.\n";
$rules .= "4. Failure to repay the loan may result in the auction of the pledged gold.\n";
$rules .= "5. The borrower must keep the receipt safe and produce it when reclaiming the pledged gold.\n";
$rules .= "6. The customer is liable to inform them of the change of contact address.\n";
$rules .= "7. The customer must assure that the gold ornaments pledged to the company are owned by himself.\n";
$pdf->MultiCell(0, 10, $rules);  // Print normal text

// Set font to bold for "Declaration By the Customer"
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, "Declaration By the Customer", 0, 1, 'L');  // Print bold text

// Set font back to normal for the rest of the text
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 10, "I received the gold ornaments that I pledged.");  // Print normal text

        $pdfPath = "Deposit.pdf";

        try {
            ob_end_clean(); // Clear the output buffer
            // $pdf->Output(); // Save the PDF to a file
$pdf->Output('D', 'Loan From Relative.pdf');
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
    $date = date("Y-m-d");
    $date1 = new DateTime($date);
    $outputDate = $date1->format('d-m-Y');
    $mobile = $_POST['key'];
    $customer_name = isset($_POST['borrower_name']) ? $_POST['borrower_name'] : '';
    $b_id = isset($_POST['b_id']) ? $_POST['b_id'] : '';
    $deposit_amount = isset($_POST['deposit_amount']) ? $_POST['deposit_amount'] : '';
    $deposit_amount_in_words = isset($_POST['deposit_amount_in_words']) ? $_POST['deposit_amount_in_words'] : '';
    $rate = isset($_POST['rate_of_interest']) ? $_POST['rate_of_interest'] : '';
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
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search Customer: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Mobile Number of Customer" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
</form>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" name="myform" id="myform" autocomplete="off">
   
 <!-- Added Borrower Name -->
    <div class="form-group row">
        <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Customer Name</label>
        <div class="col-sm-9">
            <input type="text" name="borrower_name" class="form-control" value="<?php echo $name; ?>" readonly>
        </div> 
   </div>
 <!-- Added Borrower ID -->
    <div class="form-group row">
        <label for="borrower_id" class="text-right col-2 font-weight-bold col-form-label">Customer ID</label>
        <div class="col-sm-9">
            <input type="text" name="b_id" class="form-control" value="<?php echo $b_id; ?>" readonly>
        </div>
    </div>
  <!--Deposit Date: -->
    <div class="form-group row">
        <label for="gl_no" class="text-right col-2 font-weight-bold col-form-label">Deposit Due Date</label>
        <div class="col-sm-9">
            <input type="date" name="deposit_date" class="form-control">
        </div>
    </div>
    <!--Deposit Amount: -->
    <div class="form-group row">
        <label for="gl_no" class="text-right col-2 font-weight-bold col-form-label">Deposit Amount</label>
        <div class="col-sm-9">
            <input type="number"  step="0.01"name="deposit_amount" class="form-control">
        </div>
    </div>
  <!--Deposit Amount in words: -->
    <div class="form-group row">
        <label for="gl_no" class="text-right col-2 font-weight-bold col-form-label">Deposit Amount In Words</label>
        <div class="col-sm-9">
            <input type="text"  name="deposit_amount_in_words" class="form-control">
        </div>
    </div>
  <!--Rate: -->
    <div class="form-group row">
        <label for="gl_no" class="text-right col-2 font-weight-bold col-form-label">Rate</label>
        <div class="col-sm-9">
            <input type="number"  step="0.01"name="rate_of_interest" class="form-control">
        </div>
    </div>

     <hr>
    <div class="form-group row">
        <div class="col-md-6">
            <input type="submit" name="recieve_loan_from_relatives" class="btn btn-info pull-right" value="Recieve Loan">
        </div>
    </div>
</form>
