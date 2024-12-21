<?php
// classes/ExportBalanceSheet.php
require_once 'BalanceSheetGenerator.php';
require_once 'vendor/autoload.php'; // For TCPDF and PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportBalanceSheet {
    private $balanceSheet;
    private $rupeeSymbol;

    public function __construct() {
        $this->balanceSheet = new BalanceSheetGenerator();
        // Using HTML entity for rupee since some fonts might not support the Unicode symbol
        $this->rupeeSymbol = 'Rs.';  // Alternative: '&#8377;' for HTML output
    }

    public function exportToPDF($start_date, $end_date) {
        $data = $this->getBalanceSheetData($start_date, $end_date);
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Your Business Name');
        $pdf->SetTitle('Balance Sheet Report');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage();
        
        // Add the logo
        $logoPath = 'images/dhanasreeLogo.png';
        $logoWidth = 50; // Width of the logo
        $pageWidth = $pdf->getPageWidth(); // Get the page width
        $xPosition = ($pageWidth - $logoWidth) / 2; // Calculate the centered x position
        $pdf->Image($logoPath, $xPosition, 10, $logoWidth); // Center the logo
        
        $pdf->SetFont('dejavusans', '', 11);
        
        // Move below the logo
        $pdf->SetY(40); 
        
        // Title
        $pdf->Cell(0, 10, 'Business Balance Sheet', 0, 1, 'C');
        $pdf->Cell(0, 10, 'Period: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)), 0, 1, 'C');
        $pdf->Ln(10);
    
        // Income Sources Section
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Income Sources', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        // Add table headers with borders
        $pdf->Cell(100, 8, 'Description', 1, 0, 'L');
        $pdf->Cell(90, 8, 'Amount', 1, 1, 'R');
        
        foreach($data['income_sources'] as $label => $value) {
            $pdf->Cell(100, 8, $label, 1, 0, 'L');
            $pdf->Cell(90, 8, $this->rupeeSymbol . ' ' . number_format($value, 2), 1, 1, 'R');
        }
        
        $pdf->Ln(5);
    
        // Expenses Section
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Expenses Breakdown', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        // Add table headers with borders
        $pdf->Cell(100, 8, 'Category', 1, 0, 'L');
        $pdf->Cell(90, 8, 'Amount', 1, 1, 'R');
        
        foreach($data['expenses'] as $category => $amount) {
            $pdf->Cell(100, 8, $category . ' Expenses', 1, 0, 'L');
            $pdf->Cell(90, 8, $this->rupeeSymbol . ' ' . number_format($amount, 2), 1, 1, 'R');
        }
        
        $pdf->Ln(5);
    
        // Summary Section
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        // Add table headers with borders
        $pdf->Cell(100, 8, 'Item', 1, 0, 'L');
        $pdf->Cell(90, 8, 'Value', 1, 1, 'R');
        
        foreach($data['summary'] as $label => $value) {
            $pdf->Cell(100, 8, $label, 1, 0, 'L');
            $pdf->Cell(90, 8, is_numeric($value) ? $this->rupeeSymbol . ' ' . number_format($value, 2) : $value, 1, 1, 'R');
        }
    
        return $pdf;
    }
    public function exportToExcel($start_date, $end_date) {
        $data = $this->getBalanceSheetData($start_date, $end_date);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $sheet->setCellValue('A1', 'Business Balance Sheet');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A2', 'Period: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)));
        
        // Income Sources
        $row = 4;
        $sheet->setCellValue('A' . $row, 'Income Sources');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Headers
        $sheet->setCellValue('A' . $row, 'Description');
        $sheet->setCellValue('B' . $row, 'Amount');
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row++;
        
        foreach($data['income_sources'] as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }
        
        // Expenses
        $row += 1;
        $sheet->setCellValue('A' . $row, 'Expenses Breakdown');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Headers
        $sheet->setCellValue('A' . $row, 'Category');
        $sheet->setCellValue('B' . $row, 'Amount');
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row++;
        
        foreach($data['expenses'] as $category => $amount) {
            $sheet->setCellValue('A' . $row, $category . ' Expenses');
            $sheet->setCellValue('B' . $row, $amount);
            $row++;
        }
        
        // Summary
        $row += 1;
        $sheet->setCellValue('A' . $row, 'Summary');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Headers
        $sheet->setCellValue('A' . $row, 'Item');
        $sheet->setCellValue('B' . $row, 'Value');
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row++;
        
        foreach($data['summary'] as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }
    
        // Format numbers and currency
        $sheet->getStyle('B6:B' . $row)
              ->getNumberFormat()
              ->setFormatCode('"Rs."#,##0.00');
    
        // Auto-size columns
        foreach(range('A','B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    
        // Save and output as an Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Ensure the file is saved properly
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Balance_Sheet_Report.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
    
    private function getBalanceSheetData($start_date, $end_date) {
        $goldLoanSummary = $this->balanceSheet->getGoldLoanSummary($start_date, $end_date);
        $repledgeSummary = $this->balanceSheet->getRepledgeSummary($start_date, $end_date);
        $expenseCategories = $this->balanceSheet->getExpenseCategories($start_date, $end_date);
        $financialSummary = $this->balanceSheet->calculatePotentialNetIncome($start_date, $end_date);

        return [
            'income_sources' => [
                'Total Gold Loans' => $goldLoanSummary['total_loan_amount'] ?? 0,
                'Gold Loan Market Value' => $goldLoanSummary['total_market_value'] ?? 0,
                'Total Closed Loan Amount' => $goldLoanSummary['closed_loan_amount'] ?? 0,
                'Total Interest from Gold Loans' => $goldLoanSummary['total_interest'] ?? 0,
                'Repledge Bank Amount' => $repledgeSummary['total_bank_amount'] ?? 0
            ],
            'expenses' => $expenseCategories,
            'summary' => [
                'Total Gold Loans Count' => $goldLoanSummary['total_loans'] ?? 0,
                'Total Repledges Count' => $repledgeSummary['total_repledges'] ?? 0,
                'Total Expenses' => $financialSummary['total_expenses'],
                'Potential Net Income' => $financialSummary['potential_net_income']
            ]
        ];
    }
}