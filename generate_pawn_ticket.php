<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
require('fpdf186/fpdf.php'); // Include FPDF library

$name = "";
$b_id = "";
$gl_no = "";
$loanDetails = array();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    // Get the entered GL No
    $gl_no = $_POST['key'];

    // Assuming $ml is your model or data access object to get loan details
    $loanDetails = $ml->getGoldLoanDetails($gl_no);

    if (!empty($loanDetails)) {
        ob_end_clean();  // Discard the buffered output

        // Create a new PDF document
        $pdf = new FPDF();
        $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 22);
        $pdf->Cell(0, 0, 'Jayalakshmi Enterprises', 0, 1, 'C');
 	$pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 15, 'Manapaady,Thanisserry P.O.,680701', 0, 1, 'C');
	$pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 15, 'KML REISTRATION NUMBER:32080302832 ', 0, 1, 'C');
	$pdf->SetFont('Arial', '', 14);
        $pdf->Cell(0, 15, 'Pledge Form H (See Rule 15(1) of KML Act)', 0, 1, 'C');
        // Add watermark (optional)
        $pdf->SetFont('Arial', 'B', 50);
        $pdf->SetTextColor(230, 230, 230);

        $pdf->SetTextColor(0, 0, 0);  // Reset color to black

        // Add title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, "PAWN TICKET", 0, 1, 'C');

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

        // Footer with page number
    
        $pdf->SetFont('Arial', 'I', 8);
        
$pdf->SetFont('Arial', 'B', 12);
$rules =" Terms and Conditions.\n";
 $pdf->SetFont('Arial', '', 8);
	$rules .= "1. Interest is payable every three months.\n";
$rules .= "2. This card should be brought while settling the interest and settling the transaction.\n";
$rules .= "3. TThe pledged party must come to collect it.\n";
$rules .= "4.It should be replaced within 6 months.\n";

$pdf->MultiCell(0, 10, $rules);
        // Output the PDF
        $pdf->Output('D', 'Pawn_token.pdf');
        exit;
    } else {
        echo "<span class='text-center' style='color:red'>Borrower NID not matched or not applicable for loan</span>";
    }
}
?>

<!-- Form to Collect GL No -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <div class="form-group row">
        <label for="Gl_No" class="text-right col-2 font-weight-bold col-form-label">Search Gold Loan: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="inputGl_No" placeholder="Enter GL No" required>
        </div>

        <div class="col-sm-1">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
</form>

<?php if (!empty($loanDetails)): ?>
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Gold Loan Details for <?php echo $gl_no; ?></h5>
        <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>GL No</th>
                    <th>Item Description</th>
                    <th>Gross Weight</th>
                    <th>Stone Weight</th>
                    <th>Net Weight</th>
                    <th>Loan Amount</th>
                    <th>Date</th>
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
                        <td><?php echo $row['date']; ?></td>
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
