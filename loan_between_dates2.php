<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
require('fpdf186/fpdf.php'); // Include FPDF library

$name = "";
$b_id = "";
$loanDetails = array();
$status = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = ($_POST['loan_status'] == 'non-closed') ? 0 : 1;

    // Fetch the loan details based on the selected status and date range
    $loanDetails = $ml->getGoldLoanDetailsBetweenDates($status, $start_date, $end_date);

    if (!empty($loanDetails)) {
        ob_end_clean(); // Discard the buffered output

        // Create a new PDF document
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        // Add a title
        $pdf->Cell(0, 10, "Gold Loan Details", 0, 1, 'C');

        // Add table headers
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 10, 'GL No', 1);
        $pdf->Cell(25, 10, 'Item ', 1);
        $pdf->Cell(20, 10, 'Gross Wgpt', 1);
        $pdf->Cell(20, 10, 'Stone Wght', 1);
        $pdf->Cell(20, 10, 'Net Wght', 1);
        $pdf->Cell(25, 10, 'Loan Amount', 1);
        $pdf->Cell(25, 10, 'Name', 1);
        $pdf->Cell(20, 10, 'Date', 1);
        $pdf->Cell(25, 10, 'Closing Date', 1);
        $pdf->Ln();

        // Add table rows
        $pdf->SetFont('Arial', '', 10);
        foreach ($loanDetails as $row) {
            $pdf->Cell(15, 10, $row['gl_no'], 1);
            $pdf->Cell(25, 10, $row['item_description'], 1);
            $pdf->Cell(20, 10, $row['gross_weight'], 1);
            $pdf->Cell(20, 10, $row['stone_weight'], 1);
            $pdf->Cell(20, 10, $row['net_weight'], 1);
            $pdf->Cell(25, 10, $row['loan_amnt'], 1);
            $pdf->Cell(25, 10, $row['name'], 1);
            $pdf->Cell(20, 10, $row['date'], 1);
            $pdf->Cell(25, 10, $row['closing_date'], 1);
            $pdf->Ln();
        }

        // Output the PDF
        $pdf->Output('D', 'LoanDetails.pdf');
        exit;
    } else {
        echo "<span class='text-center' style='color:red'>No loan details found for the selected date range and status</span>";
    }
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Loan status: </label>
        <div class="col-sm-3">
            <input type="radio" name="loan_status" value="closed"> Closed
            <input type="radio" name="loan_status" value="non-closed"> Non-Closed
        </div>
        <div class="col-sm-1">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
    <div class="form-group row">
        <label for="start_date" class="text-right col-2 font-weight-bold col-form-label">Start Date:</label>
        <div class="col-sm-4">
            <input type="date" name="start_date" class="form-control" id="start_date" required>
        </div>
        <label for="end_date" class="text-right col-2 font-weight-bold col-form-label">End Date:</label>
        <div class="col-sm-4">
            <input type="date" name="end_date" class="form-control" id="end_date" required>
        </div>
    </div>
</form>

<?php if (!empty($loanDetails)): ?>
    <div class="card">
        <div class="card-header bg-secondary text-white">
            Loan Status
        </div>
        <div class="card-body">
            <h5 class="card-title">Gold Loan Details</h5>
            <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="70%">
                <thead>
                    <tr>
                        <th>GL No</th>
                        <th>Item Description</th>
                        <th>Gross Weight</th>
                        <th>Stone Weight</th>
                        <th>Net Weight</th>
                        <th>Loan Amount</th>
                        <th>Customer Name</th>
                        <th>Date</th>
                        <th>Closing Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loanDetails as $row): ?>
                        <tr>
                            <td><?php echo $row['gl_no']; ?></td>
                            <td><?php echo $row['item_description']; ?></td>
                            <td><?php echo $row['gross_weight']; ?></td>
                            <td><?php echo $row['stone_weight']; ?></td>
                            <td><?php echo $row['net_weight']; ?></td>
                            <td><?php echo $row['loan_amnt']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['closing_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
include_once "inc/footer.php";
ob_end_flush(); // Flush the output buffer and send output
?>
