<?php
ob_start(); // Start output buffering

include_once "inc/header.php";
include_once "inc/sidebar.php";
require('fpdf186/fpdf.php'); // Include FPDF library

$loanDetails = array();
$current_date = new DateTime(); // Create a DateTime object with the current date
$current_date_str = $current_date->format('Y-m-d'); // Format the date as 'YYYY-MM-DD'

// Fetch the loan details for due loans
$loanDetails = $ml->getDueDetails($current_date_str); // Pass the formatted date string

if ($loanDetails && $loanDetails->num_rows > 0) {
    ob_end_clean(); // Discard the buffered output

    // Create a new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Due Gold Loan Details on $current_date_str", 0, 1, 'C');

    // Add table headers
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 10, 'GL No', 1);
    $pdf->Cell(20, 10, 'Customer', 1);
    $pdf->Cell(20, 10, 'Mobile', 1);
    $pdf->Cell(30, 10, 'Item Description', 1);
    $pdf->Cell(25, 10, 'Loan Amount', 1);
    $pdf->Cell(25, 10, 'Interest', 1);
    $pdf->Cell(25, 10, 'Total', 1);
    $pdf->Cell(25, 10, 'Date', 1);
    $pdf->Ln();

    // Add table rows
    $pdf->SetFont('Arial', '', 10);
    while ($row = $loanDetails->fetch_assoc()) {
        $gl_no = isset($row['gl_no']) ? $row['gl_no'] : 'N/A';
        $loan_amount = isset($row['loan_amnt']) ? $row['loan_amnt'] : 0;

        // Handle date conversion and difference calculation
$date=isset($row['date']) ? $row['date']: 0 ;
$odate=DateTime::createFromFormat('Y-m-d', $date);
        $due_date_str = isset($row['due_date']) ? $row['due_date']: 0 ;
        $due_date = DateTime::createFromFormat('Y-m-d', $due_date_str);

        $totalDays = 0;
        if ($date && $current_date) {
            $interval = $odate->diff($current_date);
            $totalDays = $interval->days;
            
        }

        // Calculate interest and total amount
        $interest = ceil((($loan_amount * 0.20) / 365) * abs($totalDays));
        $total = ceil($loan_amount + $interest);

        $pdf->Cell(20, 10, $gl_no, 1);
        $pdf->Cell(20, 10, $row['name'], 1);
        $pdf->Cell(20, 10, $row['mobile'], 1);
        $pdf->Cell(30, 10, $row['item_description'], 1);
        $pdf->Cell(25, 10, $loan_amount, 1);
        $pdf->Cell(25, 10, $interest, 1);
        $pdf->Cell(25, 10, $total, 1);
        $pdf->Cell(25, 10, $row['date'], 1);
        $pdf->Ln();
    }

    // Output the PDF
    $pdf->Output('D', 'DueLoanDetails.pdf');
    exit;
} else {
    echo "No loans due on this date.";
}

include_once "inc/footer.php";
ob_end_flush(); // Flush the output buffer and send output
?>
