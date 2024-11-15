<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
require('fpdf186/fpdf.php'); // Include FPDF library


$stockDetails = array();



    
       $stockDetails = $ml->netStockRegister();

if (!empty($stockDetails)) {
    // Optional: Assign stock details to loan details for PDF creation
    $loanDetails = $stockDetails;

    ob_end_clean(); // Discard the buffered output

    // Create a new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);

    // Use a meaningful title
    $pdf->Cell(0, 10, "Stock details Details ", 0, 1, 'C');

    // Table headers
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 10, 'GL No', 1);
    $pdf->Cell(30, 10, 'Item Description', 1);
    $pdf->Cell(25, 10, 'Gross Weight', 1);
    $pdf->Cell(25, 10, 'Stone Weight', 1);
    $pdf->Cell(25, 10, 'Net Weight', 1);
    $pdf->Ln();

    // Table rows
    $pdf->SetFont('Arial', '', 10);
    foreach ($loanDetails as $row) {
        $pdf->Cell(20, 10, $row['gl_no'], 1);
        $pdf->Cell(30, 10, $row['item_description'], 1);
        $pdf->Cell(25, 10, $row['gross_weight'], 1);
        $pdf->Cell(25, 10, $row['stone_weight'], 1);
        $pdf->Cell(25, 10, $row['net_weight'], 1);
        $pdf->Ln();
    }

    $pdf->Output('D', 'Stock Register.pdf');
    exit;
}



?>



<?php if(!empty($stockDetails)): ?>
<div class="card">
    <div class="card-header bg-secondary text-white">
        Loan Status
    </div>
    
            <thead>
                <tr>
                    <th>GL No</th>
                    <th>Item Description</th>
                    <th>Gross Weight</th>
                    <th>Stone Weight</th>
                    <th>Net Weight</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockDetails as $row): ?>
                    <tr>
                        <td><?php echo $row['gl_no']; ?></td>
                        <td><?php echo $row['item_description']; ?></td>
                        <td><?php echo $row['gross_weight']; ?></td>
                        <td><?php echo $row['stone_weight']; ?></td>
                        <td><?php echo $row['net_weight']; ?></td>
                        
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
