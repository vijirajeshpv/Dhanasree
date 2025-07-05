<?php
ob_start(); // Start output buffering
include_once "inc/header.php";
include_once "inc/sidebar.php";

// Initialize variables to avoid undefined variable warnings
$name = $b_id = $mob = $addr = $documents = $outputDate = $pl_no = $loan_amount = $other_documents = "";
$interest='';
$part_payment ='';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Loan Application Submission
    if (isset($_POST['submit_loan_application'])) {
        // Safely retrieve form data
 $inserted = $ml->applyForPLoan($_POST, $_FILES);
        if ($inserted) {
        $pl_no = $_POST['pl_no'] ?? '';
        $loan_amount = $_POST['loan_amount'] ?? '';
        $documents = isset($_POST['documents']) ? implode(", ", $_POST['documents']) : '';
        $other_documents = $_POST['other_documents'] ?? '';
        $interest = $_POST['interest'] ?? '';
        $part_payment = $_POST['part_payment'] ?? '';

        // Validate required fields
        if (empty($pl_no) || empty($loan_amount) || empty($interest)) {
            echo "<span class='error'>Please fill in all required fields.</span>";
            return;
        }

        // Insert loan application
       
            // Fetch current date
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

            $date = new DateTime();
            $outputDate = $date->format('d-m-Y');

            // Prepare data for the PDF
            $report_data = [
                'PL Number' => $pl_no,
                'Name' => $name,
                'Borrower ID' => $b_id,
                'Address' => $addr,
                'Mobile' => $mob,
                'Loan Amount' => $loan_amount,
                'Interest Rate' => $interest,
                'Documents' => $documents,
                'Part Payment' => $part_payment,
                'Date' => $outputDate,
            ];

            // Generate PDF
            require('fpdf186/fpdf.php'); // Ensure FPDF is included in your project

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 22);
            $pdf->Cell(0, 10, 'Jayalakshmi Enterprises', 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->Cell(0, 10, 'Manapaady, Thanisserry P.O., 680701', 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'KML REGISTRATION NUMBER: 32080302832', 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'PROMISSORY NOTE', 0, 1, 'C');


            // Loop through report data and add to PDF
            foreach ($report_data as $label => $value) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(50, 10, $label . ':', 0, 0);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 10, $value, 0, 1);
            }

            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 10, "\nI, ..................................., promise to pay Dhanasree Finance or order the sum of ............. value received, with interest at the annual rate of ............ %, payable on or after .....................\n\nName: _____________________________\n\nSignature: _________________________");

            // Output PDF to browser
            ob_end_clean(); // Clear the output buffer to prevent corruption
            $pdf->Output('D', 'Loan_Application_Receipt.pdf'); // 'D' forces download
            exit; // Stop further processing to prevent page content from being appended to PDF
        } else {
            echo "<span class='error'>Error inserting data into the database.</span>";
        }
    }

    // Handle Borrower Search
    if (isset($_POST['search'])) {
        $mobile = $_POST['key'] ?? '';
        if (!empty($mobile)) {
            $br = $emp->findBorrowerByMobile($mobile);
            if ($br) {
                $row = $br->fetch_assoc();
                $name = htmlspecialchars($row['name'] ?? '');
                $b_id = htmlspecialchars($row['id'] ?? '');
                $addr = htmlspecialchars($row['address'] ?? '');
                $mob = htmlspecialchars($row['mobile'] ?? '');
            } else {
                echo "<span class='error'>Borrower not found.</span>";
            }
        }
    }
}
?>

<!-- Search Borrower Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" autocomplete="off">
    <div class="form-group row">
        <label for="key" class="text-right col-2 font-weight-bold col-form-label">Search Borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="key" placeholder="Enter Mobile Number of Borrower" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
</form>

<!-- Loan Application Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" name="myform" id="myform" autocomplete="off">
    <div class="form-group row">
        <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Borrower Name</label>
        <div class="col-sm-9">
            <input type="text" name="borrower_name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label for="b_id" class="text-right col-2 font-weight-bold col-form-label">Borrower ID</label>
        <div class="col-sm-9">
            <input type="text" name="b_id" class="form-control" value="<?php echo htmlspecialchars($b_id); ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label for="pl_no" class="text-right col-2 font-weight-bold col-form-label">PL Number</label>
        <div class="col-sm-9">
            <input type="text" name="pl_no" class="form-control" value="<?php echo htmlspecialchars($pl_no); ?>" required>
        </div>
    </div>

    <div class="form-group row">
        <label for="documents" class="text-right col-2 font-weight-bold col-form-label">Documents</label>
        <div class="col-sm-9">
            <select id="documents" name="documents[]" multiple onchange="getItem()">
                <option value="Pronote">Pronote</option>
                <option value="Cheque">Cheque</option>
                <option value="Others">Others</option>
            </select>
            <input type="text" placeholder="Selected Items" id="other_documents" name="other_documents" value="<?php echo htmlspecialchars($documents); ?>" readonly>
        </div>
    </div>

    <div class="form-group row">
        <label for="interest" class="text-right col-2 font-weight-bold col-form-label">Interest Rate</label>
        <div class="col-sm-9">
            <input type="text" name="interest" class="form-control" id="interest" value="<?php echo htmlspecialchars($interest); ?>" required>
        </div>
    </div>

    <div class="form-group row">
        <label for="loan_amount" class="text-right col-2 font-weight-bold col-form-label">Loan Amount</label>
        <div class="col-sm-9">
            <input type="text" name="loan_amount" class="form-control" id="loan_amount" value="<?php echo htmlspecialchars($loan_amount); ?>" required>
        </div>
    </div>

    <div class="form-group row">
        <label for="part_payment" class="text-right col-2 font-weight-bold col-form-label">Part Payment</label>
        <div class="col-sm-9">
            <input type="text" name="part_payment" class="form-control" id="part_payment" value="<?php echo htmlspecialchars($part_payment); ?>">
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-6">
            <input type="submit" name="submit_loan_application" class="btn btn-info pull-right" value="Submit Application">
        </div>
    </div>
</form>

<script>
// Handle selection of documents
function getItem() {
    var selectedItems = Array.from(document.getElementById("documents").options)
        .filter(option => option.selected)
        .map(option => option.value);
    document.getElementById("other_documents").value = selectedItems.join(", ");
}
</script>

<?php
include_once "inc/footer.php";
?>
