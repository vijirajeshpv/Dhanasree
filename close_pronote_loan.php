<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
?>

<h3 class="page-heading mt-4 mb-5 font-weight-bold text-center border-bottom pb-3">Close Pronote Loan</h3>

<?php
$loan = [];
$name = "";
$pl_no = "";
$openingDate = "";
$amount_paid = "";
$interest = "";
$total = "";
$loan_amnt = "";
$formattedDate="";
$part_payment="";

// Handle Search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    if (!empty($_POST['key'])) {
        $pl_no = $_POST['key'];
        $br = $ml->getPronoteDetails($pl_no);

        if ($br) {
            $row = $br->fetch_assoc();
            $name = $row['name'];
            $openingDate = $row['date'];
            $part_payment = $row['part_payment'];
            $rate=$row['interest'];
            $loan_amnt = $row['loan_amount'];
            $openDate = DateTime::createFromFormat('Y-m-d', $openingDate);

            if ($openDate) {
                $formattedDate = $openDate->format('d-m-Y');
                $closingDate = new DateTime(); // Current date
                $interval = $openDate->diff($closingDate);
                $totalDays = $interval->days;

               
                $interest =$loan_amnt*$rate/100* ($totalDays / 365);
               $interest_updated= $interest-$part_payment;
$total= $loan_amnt+$interest_updated;
            }
        } else {
            echo "<span class='error'>GL Number not matched.</span>";
        }
    }
}

// Handle Close Loan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Close'])) {
    if (!empty($_POST['pl_no'])) {
        $pl_no = $_POST['pl_no'];
        $interest = $_POST['interest'];
	$name=$_POST['borrower_name'];
	$formattedDate=$_POST['opening_date'];
	$loan_amnt=$_POST['loan_amnt'];
       // $part_payment = $_POST['part_payment'];

	$total=$_POST['total'];

        // Call the function to close the loan
        $updated = $ml->closePronoteLoan($pl_no, $interest);

        if ($updated) {
            echo "<span class='success'>Pronote loan closed successfully!</span>";
	  // PDF generation code
    require('fpdf186/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->Cell(0, 10, 'Jayalakshmi Enterprises', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 15, 'Manapaady, Thanisserry P.O., 680701', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 15, 'KML REGISTRATION NUMBER: 32080302832', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 15, 'Pledge Form M (C Rule 14 of KML Act)', 0, 1, 'C');

    $report_data = [
        'PL Number' => $pl_no,
        'Name' => $name,
        'Date' => $formattedDate,
        
        'Interest' => $interest,
        'Loan Amount' => $loan_amnt,
        'Total Amount' => $total
    ];

    $pdf->Ln(10); // Line break

    foreach ($report_data as $label => $value) {
        $pdf->SetX(44);
        $pdf->Cell(80, 10, $label, 0, 0); // Set width for label
        $pdf->Cell(60, 10, $value, 0, 1); // Align value to the right
    }

    try {
        ob_end_clean(); // Clear the output buffer
        $pdf->Output('D', 'ClosePronoteLoan.pdf'); // Download the PDF
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
        } else {
            echo "<span class='error'>Loan not approved or already paid!</span>";
        }
    } else {
        echo "<span class='error'>PL Number not matched.</span>";
    }
}
?>

<form action="" method="POST" autocomplete="off">
    <!-- Search Form -->
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search GL No: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Pl_No" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info w-100" name="search" value="Search">
        </div>
    </div>
</form>

<form action="" method="post" name="myform" id="myform" autocomplete="off">
    <!-- Loan Details Form -->
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
        <div class="col-sm-9">
            <input type="text" name="borrower_name" class="form-control" value="<?php echo $name; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">PL_NO</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" name="pl_no" value="<?php echo $pl_no; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Opening Date</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" name="opening_date" value="<?php echo $formattedDate; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Loan Amount</label>
        <div class="col-sm-9">
            <input type="number" name="loan_amnt" class="form-control" value="<?php echo $loan_amnt; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Interest</label>
        <div class="col-sm-9">
            <input type="number" name="interest" class="form-control" value="<?php echo $interest_updated; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Total Amount</label>
        <div class="col-sm-9">
            <input type="number" name="total" class="form-control" value="<?php echo $total; ?>" readonly>
        </div>
    </div>

    <hr>
    <div class="form-group row">
        <div class="col-md-11">
            <input type="submit" name="Close" class="btn btn-success pull-right w-25" value="Close Loan">
        </div>
    </div>
</form>

<?php
include_once "inc/footer.php";
?>
