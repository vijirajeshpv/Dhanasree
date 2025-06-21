<?php
// capital_reports.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . "/classes/CapitalManager.php");
require('fpdf186/fpdf.php');

$capitalManager = new CapitalManager();

// Get parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Generate report data based on type
switch ($report_type) {
    case 'capital_statement':
    case 'statement':
        $report_data = $capitalManager->generateCapitalStatement($from_date, $to_date);
        $report_title = "Capital Statement";
        break;
        
    case 'movement':
        $report_data = $capitalManager->getCapitalTransactions($from_date, $to_date, 100);
        $report_title = "Capital Movement Report";
        break;
        
    case 'summary':
        $report_data = $capitalManager->getCapitalSummary();
        $report_title = "Capital Summary Report";
        break;
        
    case 'monthly':
        $report_data = $capitalManager->getMonthlyPLComparison(date('Y'));
        $report_title = "Monthly Capital Analysis";
        break;
        
    case 'transactions':
        $report_data = $capitalManager->getCapitalTransactions($from_date, $to_date, 1000);
        $report_title = "Capital Transactions Report";
        break;
        
    default:
        $report_data = $capitalManager->getCapitalSummary();
        $report_title = "Capital Summary Report";
}

// Handle PDF Export
if ($format == 'pdf') {
    ob_end_clean();
    
    class CapitalReportPDF extends FPDF {
        private $reportTitle;
        private $fromDate;
        private $toDate;
        
        function __construct($title, $from = null, $to = null) {
            parent::__construct();
            $this->reportTitle = $title;
            $this->fromDate = $from;
            $this->toDate = $to;
        }
        
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'JAYALAKSHMI ENTERPRISES', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'THANISSERY, THRISSUR - KML REG NO.32080302832 (2508-0-413)', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, strtoupper($this->reportTitle), 0, 1, 'C');
            
            if ($this->fromDate && $this->toDate) {
                $this->SetFont('Arial', '', 10);
                $this->Cell(0, 6, 'Period: ' . date('d/m/Y', strtotime($this->fromDate)) . ' to ' . date('d/m/Y', strtotime($this->toDate)), 0, 1, 'C');
            }
            
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, 'Generated on: ' . date('d/m/Y H:i'), 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-20);
            $this->SetFont('Arial', '', 8);
            $this->Cell(95, 5, 'Place: Irinjalakuda', 0, 0, 'L');
            $this->Cell(0, 5, 'As per our report even date attached', 0, 1, 'R');
            $this->Cell(95, 5, 'Date: ' . date('d/m/Y'), 0, 0, 'L');
            $this->SetY(-10);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
        
        function CapitalSummaryTable($data) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'CAPITAL SUMMARY', 0, 1, 'C');
            $this->Ln(3);
            
            // Summary boxes
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(240, 240, 240);
            
            $this->Cell(90, 8, 'Particulars', 1, 0, 'C', true);
            $this->Cell(35, 8, 'Amount (₹)', 1, 1, 'C', true);
            
            $this->SetFont('Arial', '', 10);
            $this->Cell(90, 6, 'Owner Capital Balance', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['capital_balance'], 2), 1, 1, 'R');
            
            $this->Cell(90, 6, 'Retained Earnings', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['retained_earnings'], 2), 1, 1, 'R');
            
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(90, 6, 'Total Equity', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['total_equity'], 2), 1, 1, 'R');
            
            $this->Ln(5);
            
            // Monthly summary
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 8, 'CURRENT MONTH ACTIVITY', 0, 1, 'C');
            $this->Ln(2);
            
            $this->SetFillColor(240, 240, 240);
            $this->Cell(90, 8, 'Activity', 1, 0, 'C', true);
            $this->Cell(35, 8, 'Amount (₹)', 1, 1, 'C', true);
            
            $this->SetFont('Arial', '', 10);
            $this->Cell(90, 6, 'Monthly Investments', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['month_investments'], 2), 1, 1, 'R');
            
            $this->Cell(90, 6, 'Monthly Withdrawals', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['month_withdrawals'], 2), 1, 1, 'R');
            
            $this->Cell(90, 6, 'Number of Transactions', 1, 0, 'L');
            $this->Cell(35, 6, $data['month_transactions'], 1, 1, 'C');
        }
        
        function TransactionTable($transactions) {
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 8, 'CAPITAL TRANSACTIONS', 0, 1, 'C');
            $this->Ln(3);
            
            // Table headers
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(20, 8, 'Date', 1, 0, 'C', true);
            $this->Cell(45, 8, 'Transaction Type', 1, 0, 'C', true);
            $this->Cell(25, 8, 'Amount', 1, 0, 'C', true);
            $this->Cell(70, 8, 'Description', 1, 0, 'C', true);
            $this->Cell(25, 8, 'Reference', 1, 1, 'C', true);
            
            $this->SetFont('Arial', '', 8);
            $total_investment = 0;
            $total_withdrawal = 0;
            
            if ($transactions && $transactions->num_rows > 0) {
                while ($row = $transactions->fetch_assoc()) {
                    $this->Cell(20, 6, date('d/m/Y', strtotime($row['transaction_date'])), 1, 0, 'C');
                    $this->Cell(45, 6, $row['transaction_type'], 1, 0, 'L');
                    $this->Cell(25, 6, number_format($row['amount'], 2), 1, 0, 'R');
                    $this->Cell(70, 6, substr($row['description'], 0, 40), 1, 0, 'L');
                    $this->Cell(25, 6, $row['reference_no'] ?? 'N/A', 1, 1, 'C');
                    
                    if ($row['transaction_type'] == 'Capital Investment') {
                        $total_investment += $row['amount'];
                    } else {
                        $total_withdrawal += $row['amount'];
                    }
                    
                    // Check if we need a new page
                    if ($this->GetY() > 250) {
                        $this->AddPage();
                        // Repeat headers
                        $this->SetFont('Arial', 'B', 9);
                        $this->SetFillColor(240, 240, 240);
                        $this->Cell(20, 8, 'Date', 1, 0, 'C', true);
                        $this->Cell(45, 8, 'Transaction Type', 1, 0, 'C', true);
                        $this->Cell(25, 8, 'Amount', 1, 0, 'C', true);
                        $this->Cell(70, 8, 'Description', 1, 0, 'C', true);
                        $this->Cell(25, 8, 'Reference', 1, 1, 'C', true);
                        $this->SetFont('Arial', '', 8);
                    }
                }
            } else {
                $this->Cell(185, 10, 'No transactions found for the selected period', 1, 1, 'C');
            }
            
            // Totals
            $this->Ln(3);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(90, 6, 'Total Investments:', 1, 0, 'L');
            $this->Cell(35, 6, number_format($total_investment, 2), 1, 1, 'R');
            $this->Cell(90, 6, 'Total Withdrawals:', 1, 0, 'L');
            $this->Cell(35, 6, number_format($total_withdrawal, 2), 1, 1, 'R');
            $this->Cell(90, 6, 'Net Capital Change:', 1, 0, 'L');
            $this->Cell(35, 6, number_format($total_investment - $total_withdrawal, 2), 1, 1, 'R');
        }
        
        function CapitalStatementTable($data) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'STATEMENT OF CHANGES IN CAPITAL', 0, 1, 'C');
            $this->Ln(3);
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(120, 8, 'Particulars', 1, 0, 'C', true);
            $this->Cell(35, 8, 'Amount (₹)', 1, 1, 'C', true);
            
            $this->SetFont('Arial', '', 10);
            
            // Opening balances
            $this->Cell(120, 6, 'Opening Capital Balance', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['opening_capital'], 2), 1, 1, 'R');
            
            $this->Cell(120, 6, 'Opening Retained Earnings', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['opening_retained'], 2), 1, 1, 'R');
            
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(120, 6, 'Total Opening Equity', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['opening_capital'] + $data['opening_retained'], 2), 1, 1, 'R');
            
            $this->Ln(2);
            
            // Changes during period
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(155, 6, 'CHANGES DURING THE PERIOD:', 0, 1, 'L');
            
            $this->SetFont('Arial', '', 10);
            // We'll need to calculate these from transactions
            $total_investments = 0;
            $total_withdrawals = 0;
            
            if ($data['transactions'] && $data['transactions']->num_rows > 0) {
                $transactions = $data['transactions'];
                while ($row = $transactions->fetch_assoc()) {
                    if ($row['transaction_type'] == 'Capital Investment') {
                        $total_investments += $row['amount'];
                    } else {
                        $total_withdrawals += $row['amount'];
                    }
                }
            }
            
            $this->Cell(120, 6, 'Add: Capital Investments', 1, 0, 'L');
            $this->Cell(35, 6, number_format($total_investments, 2), 1, 1, 'R');
            
            $this->Cell(120, 6, 'Less: Capital Withdrawals', 1, 0, 'L');
            $this->Cell(35, 6, number_format($total_withdrawals, 2), 1, 1, 'R');
            
            $this->Ln(2);
            
            // Closing balances
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(120, 6, 'Closing Capital Balance', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['closing_capital'], 2), 1, 1, 'R');
            
            $this->Cell(120, 6, 'Closing Retained Earnings', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['closing_retained'], 2), 1, 1, 'R');
            
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(120, 6, 'TOTAL CLOSING EQUITY', 1, 0, 'L');
            $this->Cell(35, 6, number_format($data['closing_capital'] + $data['closing_retained'], 2), 1, 1, 'R');
        }
    }
    
    $pdf = new CapitalReportPDF($report_title, $from_date, $to_date);
    $pdf->AddPage();
    
    // Generate appropriate report
    switch ($report_type) {
        case 'capital_statement':
        case 'statement':
            $pdf->CapitalStatementTable($report_data);
            break;
            
        case 'movement':
        case 'transactions':
            $pdf->TransactionTable($report_data);
            break;
            
        case 'summary':
        default:
            $pdf->CapitalSummaryTable($report_data);
            if ($report_type == 'summary') {
                $pdf->Ln(10);
                $recent_transactions = $capitalManager->getCapitalTransactions($from_date, $to_date, 10);
                $pdf->TransactionTable($recent_transactions);
            }
            break;
    }
    
    $filename = str_replace(' ', '_', $report_title) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}

// Handle Excel Export
if ($format == 'excel') {
    ob_end_clean();
    
    $filename = str_replace(' ', '_', $report_title) . '_' . date('Y-m-d') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    
    // Header
    echo '<table border="0" width="100%">';
    echo '<tr><td colspan="6" align="center" style="font-size:16px; font-weight:bold;">JAYALAKSHMI ENTERPRISES</td></tr>';
    echo '<tr><td colspan="6" align="center" style="font-size:10px;">THANISSERY, THRISSUR - KML REG NO.32080302832 (2508-0-413)</td></tr>';
    echo '<tr><td colspan="6" align="center" style="font-size:14px; font-weight:bold;">' . strtoupper($report_title) . '</td></tr>';
    
    if ($from_date && $to_date) {
        echo '<tr><td colspan="6" align="center" style="font-size:10px;">Period: ' . date('d/m/Y', strtotime($from_date)) . ' to ' . date('d/m/Y', strtotime($to_date)) . '</td></tr>';
    }
    
    echo '<tr><td colspan="6" align="center" style="font-size:9px;">Generated on: ' . date('d/m/Y H:i') . '</td></tr>';
    echo '<tr><td colspan="6">&nbsp;</td></tr>';
    echo '</table>';
    
    // Report content based on type
    if ($report_type == 'summary') {
        // Capital Summary
        echo '<table border="1" style="border-collapse:collapse;">';
        echo '<tr><td colspan="2" align="center" style="font-weight:bold; background-color:#f0f0f0;">CAPITAL SUMMARY</td></tr>';
        echo '<tr style="font-weight:bold; background-color:#e0e0e0;"><td>Particulars</td><td>Amount (₹)</td></tr>';
        echo '<tr><td>Owner Capital Balance</td><td align="right">' . number_format($report_data['capital_balance'], 2) . '</td></tr>';
        echo '<tr><td>Retained Earnings</td><td align="right">' . number_format($report_data['retained_earnings'], 2) . '</td></tr>';
        echo '<tr style="font-weight:bold;"><td>Total Equity</td><td align="right">' . number_format($report_data['total_equity'], 2) . '</td></tr>';
        echo '</table>';
        
        echo '<br><br>';
        
        echo '<table border="1" style="border-collapse:collapse;">';
        echo '<tr><td colspan="2" align="center" style="font-weight:bold; background-color:#f0f0f0;">CURRENT MONTH ACTIVITY</td></tr>';
        echo '<tr style="font-weight:bold; background-color:#e0e0e0;"><td>Activity</td><td>Amount (₹)</td></tr>';
        echo '<tr><td>Monthly Investments</td><td align="right">' . number_format($report_data['month_investments'], 2) . '</td></tr>';
        echo '<tr><td>Monthly Withdrawals</td><td align="right">' . number_format($report_data['month_withdrawals'], 2) . '</td></tr>';
        echo '<tr><td>Number of Transactions</td><td align="center">' . $report_data['month_transactions'] . '</td></tr>';
        echo '</table>';
        
    } elseif ($report_type == 'capital_statement' || $report_type == 'statement') {
        // Capital Statement
        echo '<table border="1" style="border-collapse:collapse;">';
        echo '<tr><td colspan="2" align="center" style="font-weight:bold; background-color:#f0f0f0;">STATEMENT OF CHANGES IN CAPITAL</td></tr>';
        echo '<tr style="font-weight:bold; background-color:#e0e0e0;"><td>Particulars</td><td>Amount (₹)</td></tr>';
        echo '<tr><td>Opening Capital Balance</td><td align="right">' . number_format($report_data['opening_capital'], 2) . '</td></tr>';
        echo '<tr><td>Opening Retained Earnings</td><td align="right">' . number_format($report_data['opening_retained'], 2) . '</td></tr>';
        echo '<tr style="font-weight:bold;"><td>Total Opening Equity</td><td align="right">' . number_format($report_data['opening_capital'] + $report_data['opening_retained'], 2) . '</td></tr>';
        echo '<tr><td colspan="2">&nbsp;</td></tr>';
        echo '<tr style="font-weight:bold;"><td colspan="2">CHANGES DURING THE PERIOD:</td></tr>';
        
        // Calculate changes from transactions
        $total_investments = 0;
        $total_withdrawals = 0;
        
        if ($report_data['transactions'] && $report_data['transactions']->num_rows > 0) {
            $transactions = $report_data['transactions'];
            while ($row = $transactions->fetch_assoc()) {
                if ($row['transaction_type'] == 'Capital Investment') {
                    $total_investments += $row['amount'];
                } else {
                    $total_withdrawals += $row['amount'];
                }
            }
        }
        
        echo '<tr><td>Add: Capital Investments</td><td align="right">' . number_format($total_investments, 2) . '</td></tr>';
        echo '<tr><td>Less: Capital Withdrawals</td><td align="right">' . number_format($total_withdrawals, 2) . '</td></tr>';
        echo '<tr><td colspan="2">&nbsp;</td></tr>';
        echo '<tr style="font-weight:bold;"><td>Closing Capital Balance</td><td align="right">' . number_format($report_data['closing_capital'], 2) . '</td></tr>';
        echo '<tr style="font-weight:bold;"><td>Closing Retained Earnings</td><td align="right">' . number_format($report_data['closing_retained'], 2) . '</td></tr>';
        echo '<tr style="font-weight:bold; background-color:#f0f0f0;"><td>TOTAL CLOSING EQUITY</td><td align="right">' . number_format($report_data['closing_capital'] + $report_data['closing_retained'], 2) . '</td></tr>';
        echo '</table>';
        
    } else {
        // Transactions Report
        echo '<table border="1" style="border-collapse:collapse;">';
        echo '<tr><td colspan="5" align="center" style="font-weight:bold; background-color:#f0f0f0;">CAPITAL TRANSACTIONS</td></tr>';
        echo '<tr style="font-weight:bold; background-color:#e0e0e0;">';
        echo '<td>Date</td><td>Transaction Type</td><td>Amount (₹)</td><td>Description</td><td>Reference</td>';
        echo '</tr>';
        
        $total_investment = 0;
        $total_withdrawal = 0;
        
        if ($report_data && $report_data->num_rows > 0) {
            while ($row = $report_data->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . date('d/m/Y', strtotime($row['transaction_date'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['transaction_type']) . '</td>';
                echo '<td align="right">' . number_format($row['amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                echo '<td>' . htmlspecialchars($row['reference_no'] ?? 'N/A') . '</td>';
                echo '</tr>';
                
                if ($row['transaction_type'] == 'Capital Investment') {
                    $total_investment += $row['amount'];
                } else {
                    $total_withdrawal += $row['amount'];
                }
            }
        } else {
            echo '<tr><td colspan="5" align="center">No transactions found for the selected period</td></tr>';
        }
        
        echo '<tr><td colspan="5">&nbsp;</td></tr>';
        echo '<tr style="font-weight:bold;"><td colspan="2">Total Investments:</td><td align="right">' . number_format($total_investment, 2) . '</td><td colspan="2">&nbsp;</td></tr>';
        echo '<tr style="font-weight:bold;"><td colspan="2">Total Withdrawals:</td><td align="right">' . number_format($total_withdrawal, 2) . '</td><td colspan="2">&nbsp;</td></tr>';
        echo '<tr style="font-weight:bold;"><td colspan="2">Net Capital Change:</td><td align="right">' . number_format($total_investment - $total_withdrawal, 2) . '</td><td colspan="2">&nbsp;</td></tr>';
        echo '</table>';
    }
    
    // Footer
    echo '<br><br>';
    echo '<table border="0" width="100%">';
    echo '<tr><td>Place: Irinjalakuda</td><td align="right">As per our report even date attached</td></tr>';
    echo '<tr><td>Date: ' . date('d/m/Y') . '</td><td>&nbsp;</td></tr>';
    echo '</table>';
    
    echo '</body></html>';
    exit;
}

// If neither PDF nor Excel, redirect back
header('Location: captital_management.php');
exit;
?>