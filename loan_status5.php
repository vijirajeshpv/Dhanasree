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
    $mob = $_POST['key'];
    $br = $emp->findBorrowerByMobile($mob);
    if ($br) {
        $row = $br->fetch_assoc();
        $name = $row['name'];
        $b_id = $row['id'];

        $status = ($_POST['loan_status'] == 'non-closed') ? 0 : 1;
	$status1=($status==1)?'Closed':'Non-Closed';
        $loanDetails = $ml->getGoldLoanDetailsByBid($b_id, $status);

        if (!empty($loanDetails)) {
            ob_end_clean(); // Discard the buffered output

            // Create a new PDF document
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);

            // Add a title
            $pdf->Cell(0, 10, "$status1 Gold Loan Details for $name ", 0, 1, 'C');

            // Add table headers
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(20, 10, 'GL No', 1);
            $pdf->Cell(30, 10, 'Item Description', 1);
            $pdf->Cell(25, 10, 'Gross Weight', 1);
            $pdf->Cell(25, 10, 'Stone Weight', 1);
            $pdf->Cell(25, 10, 'Net Weight', 1);
            
            $pdf->Cell(25, 10, 'Loan Amount', 1);
            $pdf->Cell(25, 10, 'Date', 1);
            $pdf->Ln();

            // Add table rows
            $pdf->SetFont('Arial', '', 10);
            foreach ($loanDetails as $row) {
                $pdf->Cell(20, 10, $row['gl_no'], 1);
                $pdf->Cell(30, 10, $row['item_description'], 1);
                $pdf->Cell(25, 10, $row['gross_weight'], 1);
                $pdf->Cell(25, 10, $row['stone_weight'], 1);
                $pdf->Cell(25, 10, $row['net_weight'], 1);
                
                $pdf->Cell(25, 10, $row['loan_amnt'], 1);
                $pdf->Cell(25, 10, $row['date'], 1);
                $pdf->Ln();
            }

            // Output the PDF
            $pdf->Output('D', 'LoanDetails.pdf');
            exit;
        }
    } else {
        echo "<span class='text-center' style='color:red'>Borrower NID not matched or not applicable for loan</span>";
    }
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter mobile number of borrower" required>
        </div>
        <div class="col-sm-3">
            <input type="radio" name="loan_status" value="closed"> Closed
            <input type="radio" name="loan_status" value="non-closed"> Non-Closed
        </div>
        <div class="col-sm-1">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
</form>

<?php if(!empty($loanDetails)): ?>
<div class="card">
    <div class="card-header bg-secondary text-white">
        Loan Status
    </div>
    <div class="card-body">
        <h5 class="card-title">Gold Loan Details for <?php echo $name; ?></h5>
        <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>GL No</th>
                    <th>Item Description</th>
                    <th>Gross Weight</th>
                    <th>Stone Weight</th>
                    <th>Net Weight</th>
                    <th>Market Value</th>
                    <th>Loan Amount</th>
                    <th>Date</th>
                    <th>Item Image</th>
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
                        <td><?php echo $row['market_value']; ?></td>
                        <td><?php echo $row['loan_amnt']; ?></td>
                        <td><?php echo $row['date']; ?></td>
                        <td><img style="width:60px;height:100%;" src="<?php echo $row['file']; ?>" alt=""></td>
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
