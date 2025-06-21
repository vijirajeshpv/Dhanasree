<?php
// export_pl_excel.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . "/classes/accounting/PLAccountManager.php");

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $format = isset($_GET['format']) ? $_GET['format'] : 'excel';
    
    $plManager = new PLAccountManager();
    
    if ($format === 'xlsx') {
        // Check if PHPSpreadsheet is available - if not, fallback to basic Excel
        try {
            $spreadsheet = $plManager->exportPLToExcel($start_date, $end_date);
            
            $filename = 'Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            // Fallback to basic Excel export
            $format = 'excel';
        }
    }
    
    // Basic Excel export (HTML table format)
    if ($format === 'excel') {
        $pl_data = $plManager->getProfitLossAccount($start_date, $end_date);
        
        $filename = 'Profit_Loss_Account_' . $start_date . '_to_' . $end_date . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid black; padding: 8px; text-align: left; }';
        echo '.center { text-align: center; }';
        echo '.right { text-align: right; }';
        echo '.bold { font-weight: bold; }';
        echo '.header { background-color: #f0f0f0; font-weight: bold; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        // Company header
        echo '<table>';
        echo '<tr><td colspan="6" class="center bold" style="font-size: 16px;">JAYALAKSHMI ENTERPRISES</td></tr>';
        echo '<tr><td colspan="6" class="center bold" style="font-size: 14px;">PROFIT & LOSS ACCOUNT</td></tr>';
        echo '<tr><td colspan="6" class="center">For the period from ' . date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date)) . '</td></tr>';
        echo '<tr><td colspan="6">&nbsp;</td></tr>';
        
        // P&L Table Headers
        echo '<tr>';
        echo '<td class="header center">PARTICULARS</td>';
        echo '<td class="header center">AMOUNT</td>';
        echo '<td>&nbsp;</td>';
        echo '<td class="header center">PARTICULARS</td>';
        echo '<td class="header center">AMOUNT</td>';
        echo '</tr>';
        
        // Prepare data
        $expenses = $pl_data['expenses'];
        $income = $pl_data['income'];
        
        // Add Net Profit/Loss
        if ($pl_data['net_profit'] > 0) {
            $expenses[] = ['account_name' => 'Net Profit', 'amount' => $pl_data['net_profit']];
        } else if ($pl_data['net_profit'] < 0) {
            $income[] = ['account_name' => 'Net Loss', 'amount' => abs($pl_data['net_profit'])];
        }
        
        $max_rows = max(count($expenses), count($income));
        
        // Data rows
        for ($i = 0; $i < $max_rows; $i++) {
            echo '<tr>';
            
            // Expenses side
            if ($i < count($expenses)) {
                echo '<td>' . htmlspecialchars($expenses[$i]['account_name']) . '</td>';
                echo '<td class="right">' . number_format($expenses[$i]['amount'], 2) . '</td>';
            } else {
                echo '<td>&nbsp;</td>';
                echo '<td>&nbsp;</td>';
            }
            
            echo '<td>&nbsp;</td>';
            
            // Income side
            if ($i < count($income)) {
                echo '<td>' . htmlspecialchars($income[$i]['account_name']) . '</td>';
                echo '<td class="right">' . number_format($income[$i]['amount'], 2) . '</td>';
            } else {
                echo '<td>&nbsp;</td>';
                echo '<td>&nbsp;</td>';
            }
            
            echo '</tr>';
        }
        
        // Total row
        echo '<tr class="header">';
        echo '<td class="center bold">Total</td>';
        echo '<td class="right bold">' . number_format($pl_data['total_expenses'] + max(0, $pl_data['net_profit']), 2) . '</td>';
        echo '<td>&nbsp;</td>';
        echo '<td class="center bold">Total</td>';
        echo '<td class="right bold">' . number_format($pl_data['total_income'] + abs(min(0, $pl_data['net_profit'])), 2) . '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        // Footer
        echo '<br><br>';
        echo '<table>';
        echo '<tr>';
        echo '<td colspan="2"><strong>Place:</strong> Irinjalakuda</td>';
        echo '<td colspan="3" class="right"><em>As per our report even date attached</em></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="2"><strong>Date:</strong> ' . date('d/m/Y') . '</td>';
        echo '<td colspan="3">&nbsp;</td>';
        echo '</tr>';
        echo '</table>';
        
        // Summary section
        echo '<br><br>';
        echo '<table>';
        echo '<tr><td colspan="5" class="center bold" style="font-size: 14px;">FINANCIAL SUMMARY</td></tr>';
        echo '<tr><td colspan="5">&nbsp;</td></tr>';
        echo '<tr class="header">';
        echo '<td class="center bold">Particulars</td>';
        echo '<td class="center bold">Amount (₹)</td>';
        echo '<td class="center bold">Percentage</td>';
        echo '<td colspan="2">&nbsp;</td>';
        echo '</tr>';
        
        $profit_margin = $pl_data['total_income'] > 0 ? ($pl_data['net_profit'] / $pl_data['total_income']) * 100 : 0;
        
        echo '<tr>';
        echo '<td>Total Income</td>';
        echo '<td class="right">' . number_format($pl_data['total_income'], 2) . '</td>';
        echo '<td class="right">100.00%</td>';
        echo '<td colspan="2">&nbsp;</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>Total Expenses</td>';
        echo '<td class="right">' . number_format($pl_data['total_expenses'], 2) . '</td>';
        echo '<td class="right">' . number_format($pl_data['total_income'] > 0 ? ($pl_data['total_expenses'] / $pl_data['total_income']) * 100 : 0, 2) . '%</td>';
        echo '<td colspan="2">&nbsp;</td>';
        echo '</tr>';
        
        echo '<tr class="header">';
        echo '<td class="bold">Net ' . ($pl_data['net_profit'] >= 0 ? 'Profit' : 'Loss') . '</td>';
        echo '<td class="right bold">' . number_format(abs($pl_data['net_profit']), 2) . '</td>';
        echo '<td class="right bold">' . number_format($profit_margin, 2) . '%</td>';
        echo '<td colspan="2">&nbsp;</td>';
        echo '</tr>';
        
        echo '</table>';
        
        // Detailed breakdown
        echo '<br><br>';
        echo '<table>';
        echo '<tr><td colspan="6" class="center bold" style="font-size: 14px;">DETAILED BREAKDOWN</td></tr>';
        echo '<tr><td colspan="6">&nbsp;</td></tr>';
        
        // Expense breakdown
        echo '<tr>';
        echo '<td colspan="3" class="header center bold">EXPENSE BREAKDOWN</td>';
        echo '<td colspan="3" class="header center bold">INCOME BREAKDOWN</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td class="header">Account</td>';
        echo '<td class="header">Amount</td>';
        echo '<td class="header">%</td>';
        echo '<td class="header">Account</td>';
        echo '<td class="header">Amount</td>';
        echo '<td class="header">%</td>';
        echo '</tr>';
        
        $expense_analysis = $plManager->getExpenseAnalysis($start_date, $end_date);
        $income_analysis = $plManager->getIncomeAnalysis($start_date, $end_date);
        
        $max_breakdown_rows = max(count($expense_analysis['expenses']), count($income_analysis['income_sources']));
        
        for ($i = 0; $i < $max_breakdown_rows; $i++) {
            echo '<tr>';
            
            // Expense breakdown
            if ($i < count($expense_analysis['expenses'])) {
                $expense = $expense_analysis['expenses'][$i];
                echo '<td>' . htmlspecialchars($expense['account_name']) . '</td>';
                echo '<td class="right">' . number_format($expense['amount'], 2) . '</td>';
                echo '<td class="right">' . number_format($expense['percentage'], 1) . '%</td>';
            } else {
                echo '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>';
            }
            
            // Income breakdown
            if ($i < count($income_analysis['income_sources'])) {
                $income_item = $income_analysis['income_sources'][$i];
                echo '<td>' . htmlspecialchars($income_item['account_name']) . '</td>';
                echo '<td class="right">' . number_format($income_item['amount'], 2) . '</td>';
                echo '<td class="right">' . number_format($income_item['percentage'], 1) . '%</td>';
            } else {
                echo '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</body>';
        echo '</html>';
        exit;
    }
} else {
    // Redirect back if no parameters
    header('Location: profit_loss_account.php');
    exit;
}
?>