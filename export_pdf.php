<?php
// export_pdf.php
require_once 'classes/ExportBalanceSheet.php';

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    
    $exporter = new ExportBalanceSheet();
    $pdf = $exporter->exportToPDF($start_date, $end_date);
    
    $filename = 'balance_sheet_' . date('Y-m-d') . '.pdf';
    
    // Output PDF
    $pdf->Output($filename, 'D');
    exit;
}
