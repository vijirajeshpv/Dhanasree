<?php
ob_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
require('fpdf186/fpdf.php');

$stockDetails = $ml->netStockRegister(); // Fetch stock details
$totalGrossWeight = 0;
$totalNetWeight = 0;

if (!empty($stockDetails)) {
    foreach ($stockDetails as $row) {
        $totalGrossWeight += $row['gross_weight'];
        $totalNetWeight += $row['net_weight'];
    }

    // Generate the PDF after calculating totals
    generatePDF($stockDetails, $totalGrossWeight, $totalNetWeight);
}

function generatePDF($stockDetails, $totalGrossWeight, $totalNetWeight) {
    ob_end_clean(); // Clear output buffer

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Stock Details", 0, 1, 'C');

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
    foreach ($stockDetails as $row) {
        $pdf->Cell(20, 10, $row['gl_no'], 1);
        $pdf->Cell(30, 10, $row['item_description'], 1);
        $pdf->Cell(25, 10, $row['gross_weight'], 1);
        $pdf->Cell(25, 10, $row['stone_weight'], 1);
        $pdf->Cell(25, 10, $row['net_weight'], 1);
        $pdf->Ln();
    }

    // Add totals to the PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(75, 10, 'Total Gross Weight:', 1);
    $pdf->Cell(25, 10, $totalGrossWeight, 1);
    $pdf->Ln();
    $pdf->Cell(75, 10, 'Total Net Weight:', 1);
    $pdf->Cell(25, 10, $totalNetWeight, 1);

    $pdf->Output('D', 'Stock Register.pdf');
    exit;
}
?>
