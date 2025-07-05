<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
?>

<h3 class="page-heading mt-4 mb-5 font-weight-bold text-center border-bottom pb-3">Close Loan From Relative</h3>

<?php
$loan = [];
$name = "";
$d_id = "";
$DepositDate = "";
$deposit = "";
$interest = "";
$total = "";
$formattedDate="";
$deposit_amount="";
$due_date="";
// Handle Search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    if (!empty($_POST['key'])) {
        $d_id = $_POST['key'];
        $br = $ml->getDepositDetails($d_id);

        if ($br) {
            $row = $br->fetch_assoc();
            $name = $row['name'];
            $DepositDate = $row['date'];
            $due_date = $row['due_date'];
            $deposit_amount = $row['deposit_amount'];
            $openDate = DateTime::createFromFormat('Y-m-d', $DepositDate);

            if ($openDate) {
                $formattedDate = $openDate->format('d-m-Y');
                $closingDate = new DateTime(); 
                $interval = $openDate->diff($closingDate);
                $totalDays = $interval->days;
                $interest= ceil($deposit_amount*.12*($totalDays / 365));
		$total=$interest+$deposit_amount;

                }
        } else {
            echo "<span class='error'>GL Number not matched.</span>";
        }
    }
}

// Handle Close Loan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Close'])) {
    if (!empty($_POST['d_id'])) {
        $d_id = $_POST['d_id'];
        $interest = $_POST['interest'];
	$name=$_POST['borrower_name'];
	$formattedDate=$_POST['opening_date'];
	$deposit_amount=$_POST['deposit_amount'];
	$total=$_POST['total'];

        // Call the function to close the loan
        $updated = $ml->closeDeposit($d_id, $interest);

        if ($updated) {
            echo "<span class='success'>Gold loan closed successfully!</span>";
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
        'Deposit ID' => $d_id,
        'Name' => $name,
        'Date' => $formattedDate,
         'Due Date'=>$due_date,
        'Interest' => $interest,
        'Deposit Amount' => $deposit_amount,
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
        $pdf->Output('D', 'CloseDeposit.pdf'); // Download the PDF
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
        } else {
            echo "<span class='error'>Loan not approved or already paid!</span>";
        }
    } else {
        echo "<span class='error'>GL Number not matched.</span>";
    }
}
?>

<form action="" method="POST" autocomplete="off">
    <!-- Search Form -->
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search D ID: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter D Id" required>
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
        <label class="text-right col-2 font-weight-bold col-form-label">D_ID</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" name="d_id" value="<?php echo $d_id; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Deposit Date</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" name="opening_date" value="<?php echo $formattedDate; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label"> Amount</label>
        <div class="col-sm-9">
            <input type="number" name="deposit_amount" class="form-control" value="<?php echo $deposit_amount; ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label class="text-right col-2 font-weight-bold col-form-label">Interest</label>
        <div class="col-sm-9">
            <input type="number" name="interest" class="form-control" value="<?php echo $interest; ?>" readonly>
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
