<?php
// classes/accounting/BalanceSheetManager.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . "/../../libs/CrudOperation.php");
include_once($filepath . "/../../helpers/Format.php");

class BalanceSheetManager {
    private $db;
    private $fm;
    
    function __construct() {
        $this->db = new CrudOperation();
        $this->fm = new Format();
    }

    function dbcon() {
        return $this->db->link;
    }
    
    /**
     * Get Balance Sheet as of a specific date
     * Balance Sheet shows financial position at a point in time (end date)
     */
    public function getBalanceSheet($as_of_date) {
        $as_of_date = $this->fm->validation($as_of_date);
        
        // Get all Asset, Liability, and Equity accounts with their balances as of the date
        $accounts_query = "SELECT 
                            a.id,
                            a.account_code,
                            a.account_name,
                            a.account_type,
                            COALESCE(SUM(td.debit), 0) as total_debit,
                            COALESCE(SUM(td.credit), 0) as total_credit,
                            CASE 
                                WHEN a.account_type = 'Asset' THEN 
                                    COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0)
                                WHEN a.account_type IN ('Liability', 'Equity') THEN 
                                    COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0)
                                ELSE 0
                            END as balance
                          FROM tbl_accounts a
                          LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                          LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                            AND t.transaction_date <= '$as_of_date'
                            AND t.status = 'Posted'
                            AND t.description NOT LIKE '%year-end closing%'
                            AND t.description NOT LIKE '%Year-end closing%'
                            AND t.description NOT LIKE '%Close Income%'
                            AND t.description NOT LIKE '%Close Expenses%'
                            AND t.reference_no NOT LIKE 'YE-%'
                          WHERE a.account_type IN ('Asset', 'Liability', 'Equity')
                          AND a.is_active = TRUE
                          GROUP BY a.id, a.account_code, a.account_name, a.account_type
                          ORDER BY a.account_type, a.account_code";

        $result = $this->db->select($accounts_query);
        
        $assets = [];
        $liabilities = [];
        $equity = [];
        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $balance = floatval($row['balance']);
                
                // Only include accounts with non-zero balances
                if ($balance != 0) {
                    $account_data = [
                        'id' => $row['id'],
                        'account_code' => $row['account_code'],
                        'account_name' => $row['account_name'],
                        'balance' => $balance,
                        'total_debit' => floatval($row['total_debit']),
                        'total_credit' => floatval($row['total_credit'])
                    ];
                    
                    switch ($row['account_type']) {
                        case 'Asset':
                            $assets[] = $account_data;
                            $total_assets += $balance;
                            break;
                        case 'Liability':
                            $liabilities[] = $account_data;
                            $total_liabilities += $balance;
                            break;
                        case 'Equity':
                            $equity[] = $account_data;
                            $total_equity += $balance;
                            break;
                    }
                }
            }
        }
        
        // CRITICAL FIX: Calculate and include P&L in Retained Earnings
        $pl_data = $this->calculatePLAsOfDate($as_of_date);
        
        // Add current period P&L to equity as "Current Year Earnings"
        if ($pl_data['net_profit'] != 0) {
            $equity[] = [
                'id' => 'current_pl',
                'account_code' => 'CYE001',
                'account_name' => $pl_data['net_profit'] >= 0 ? 'Current Year Earnings (Profit)' : 'Current Year Earnings (Loss)',
                'balance' => $pl_data['net_profit'],
                'total_debit' => 0,
                'total_credit' => 0,
                'is_calculated' => true,
                'calculation_details' => $pl_data
            ];
            $total_equity += $pl_data['net_profit'];
        }
        
        return [
            'as_of_date' => $as_of_date,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'total_liabilities_equity' => $total_liabilities + $total_equity,
            'is_balanced' => abs($total_assets - ($total_liabilities + $total_equity)) < 0.01,
            'pl_included' => $pl_data
        ];
    }

    /**
     * Calculate P&L (Income - Expenses) as of a specific date
     * This is what makes the Balance Sheet balance!
     */
    private function calculatePLAsOfDate($as_of_date) {
        $as_of_date = $this->fm->validation($as_of_date);
        
        // Get Income and Expense totals up to the date (excluding year-end closing)
        $pl_query = "SELECT 
                       a.account_type,
                       a.account_name,
                       a.account_code,
                       COALESCE(SUM(td.debit), 0) as total_debit,
                       COALESCE(SUM(td.credit), 0) as total_credit,
                       CASE 
                           WHEN a.account_type = 'Income' THEN 
                               COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0)
                           WHEN a.account_type = 'Expense' THEN 
                               COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0)
                           ELSE 0
                       END as net_amount
                     FROM tbl_accounts a
                     LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                     LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                       AND t.transaction_date <= '$as_of_date'
                       AND t.status = 'Posted'
                       AND t.description NOT LIKE '%year-end closing%'
                       AND t.description NOT LIKE '%Year-end closing%'
                       AND t.description NOT LIKE '%Close Income%'
                       AND t.description NOT LIKE '%Close Expenses%'
                       AND t.reference_no NOT LIKE 'YE-%'
                     WHERE a.account_type IN ('Income', 'Expense')
                     AND a.is_active = TRUE
                     GROUP BY a.id, a.account_type, a.account_name, a.account_code
                     HAVING net_amount != 0
                     ORDER BY a.account_type, a.account_code";
        
        $result = $this->db->select($pl_query);
        
        $income_accounts = [];
        $expense_accounts = [];
        $total_income = 0;
        $total_expenses = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $net_amount = floatval($row['net_amount']);
                
                $account_data = [
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'amount' => $net_amount,
                    'total_debit' => floatval($row['total_debit']),
                    'total_credit' => floatval($row['total_credit'])
                ];
                
                if ($row['account_type'] == 'Income' && $net_amount > 0) {
                    $income_accounts[] = $account_data;
                    $total_income += $net_amount;
                } elseif ($row['account_type'] == 'Expense' && $net_amount > 0) {
                    $expense_accounts[] = $account_data;
                    $total_expenses += $net_amount;
                }
            }
        }
        
        $net_profit = $total_income - $total_expenses;
        
        return [
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
            'income_accounts' => $income_accounts,
            'expense_accounts' => $expense_accounts,
            'as_of_date' => $as_of_date
        ];
    }

    
    /**
     * Calculate retained earnings as of date (accumulated P&L)
     */
    private function calculateRetainedEarnings($as_of_date) {
        $as_of_date = $this->fm->validation($as_of_date);
        
        // Get cumulative P&L up to the date
        $pl_query = "SELECT 
                       COALESCE(SUM(CASE WHEN a.account_type = 'Income' 
                                         THEN td.credit - td.debit ELSE 0 END), 0) as total_income,
                       COALESCE(SUM(CASE WHEN a.account_type = 'Expense' 
                                         THEN td.debit - td.credit ELSE 0 END), 0) as total_expenses
                     FROM tbl_transaction_details td
                     JOIN tbl_transactions t ON td.transaction_id = t.id
                     JOIN tbl_accounts a ON td.account_id = a.id
                     WHERE t.transaction_date <= '$as_of_date'
                     AND t.status = 'Posted'
                     AND a.account_type IN ('Income', 'Expense')
                     AND t.description NOT LIKE '%year-end closing%'
                     AND t.description NOT LIKE '%Year-end closing%'
                     AND t.description NOT LIKE '%Close Income%'
                     AND t.description NOT LIKE '%Close Expenses%'
                     AND t.reference_no NOT LIKE 'YE-%'";
        
        $result = $this->db->select($pl_query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $total_income = floatval($row['total_income']);
            $total_expenses = floatval($row['total_expenses']);
            return $total_income - $total_expenses;
        }
        
        return 0;
    }
    
    /**
     * Get Balance Sheet for a financial period (using period end date)
     */
    public function getBalanceSheetByPeriod($financial_period_id) {
        $financial_period_id = $this->fm->validation($financial_period_id);
        
        // Get period details
        include_once "classes/accounting/AccountingHelper.php";
        $helper = new AccountingHelper();
        $period = $helper->getFinancialPeriodById($financial_period_id);
        
        if (!$period) {
            return ['error' => 'Financial period not found'];
        }
        
        // Use period end date for balance sheet
        $balance_sheet = $this->getBalanceSheet($period['end_date']);
        
        // Add period information
        $balance_sheet['period'] = [
            'id' => $period['id'],
            'name' => $period['period_name'],
            'start_date' => $period['start_date'],
            'end_date' => $period['end_date'],
            'is_closed' => $period['is_closed']
        ];
        
        return $balance_sheet;
    }
    
    /**
     * Get categorized balance sheet with groupings
     */
    public function getCategorizedBalanceSheet($as_of_date) {
        $balance_sheet = $this->getBalanceSheet($as_of_date);
        
        // Categorize assets
        $current_assets = [];
        $fixed_assets = [];
        $other_assets = [];
        
        foreach ($balance_sheet['assets'] as $asset) {
            $code = $asset['account_code'];
            $name = strtolower($asset['account_name']);
            
            if (strpos($code, 'CASH') !== false || 
                strpos($code, 'BANK') !== false || 
                strpos($code, 'LOANS') !== false ||
                strpos($code, 'CA') === 0 ||
                strpos($name, 'receivable') !== false) {
                $current_assets[] = $asset;
            } elseif (strpos($code, 'FA') === 0 || 
                     strpos($code, 'FURNITURE') !== false ||
                     strpos($code, 'EQUIPMENT') !== false ||
                     strpos($name, 'furniture') !== false ||
                     strpos($name, 'equipment') !== false) {
                $fixed_assets[] = $asset;
            } else {
                $other_assets[] = $asset;
            }
        }
        
        // Categorize liabilities
        $current_liabilities = [];
        $long_term_liabilities = [];
        
        foreach ($balance_sheet['liabilities'] as $liability) {
            $code = $liability['account_code'];
            $name = strtolower($liability['account_name']);
            
            if (strpos($code, 'CL') === 0 || 
                strpos($name, 'payable') !== false ||
                strpos($name, 'current') !== false) {
                $current_liabilities[] = $liability;
            } else {
                $long_term_liabilities[] = $liability;
            }
        }
        
        // Ensure all arrays are properly initialized and calculate totals safely
        $total_current_assets = !empty($current_assets) ? array_sum(array_column($current_assets, 'balance')) : 0;
        $total_fixed_assets = !empty($fixed_assets) ? array_sum(array_column($fixed_assets, 'balance')) : 0;
        $total_other_assets = !empty($other_assets) ? array_sum(array_column($other_assets, 'balance')) : 0;
        $total_current_liabilities = !empty($current_liabilities) ? array_sum(array_column($current_liabilities, 'balance')) : 0;
        $total_long_term_liabilities = !empty($long_term_liabilities) ? array_sum(array_column($long_term_liabilities, 'balance')) : 0;
        
        return [
            'as_of_date' => $balance_sheet['as_of_date'],
            'current_assets' => $current_assets,
            'fixed_assets' => $fixed_assets,
            'other_assets' => $other_assets,
            'current_liabilities' => $current_liabilities,
            'long_term_liabilities' => $long_term_liabilities,
            'equity' => $balance_sheet['equity'],
            'total_current_assets' => $total_current_assets,
            'total_fixed_assets' => $total_fixed_assets,
            'total_other_assets' => $total_other_assets,
            'total_current_liabilities' => $total_current_liabilities,
            'total_long_term_liabilities' => $total_long_term_liabilities,
            'total_assets' => $balance_sheet['total_assets'],
            'total_liabilities' => $balance_sheet['total_liabilities'],
            'total_equity' => $balance_sheet['total_equity'],
            'total_liabilities_equity' => $balance_sheet['total_liabilities_equity'],
            'is_balanced' => $balance_sheet['is_balanced'],
            'period' => $balance_sheet['period'] ?? null,
            'pl_included' => $balance_sheet['pl_included'] ?? null
        ];
    }

    /**
     * Get detailed balance sheet with P&L breakdown
     */
    public function getDetailedBalanceSheet($as_of_date) {
        $categorized = $this->getCategorizedBalanceSheet($as_of_date);
        
        return [
            'balance_sheet' => $categorized,
            'pl_breakdown' => $categorized['pl_included'],
            'balance_verification' => [
                'total_assets' => $categorized['total_assets'],
                'total_liabilities' => $categorized['total_liabilities'],
                'total_equity_excluding_pl' => $categorized['total_equity'] - $categorized['pl_included']['net_profit'],
                'current_year_earnings' => $categorized['pl_included']['net_profit'],
                'total_equity_including_pl' => $categorized['total_equity'],
                'total_liabilities_equity' => $categorized['total_liabilities_equity'],
                'difference' => $categorized['total_assets'] - $categorized['total_liabilities_equity'],
                'is_balanced' => $categorized['is_balanced']
            ]
        ];
    }
    
    /**
     * Compare balance sheets between two dates
     */
    public function compareBalanceSheets($date1, $date2) {
        $bs1 = $this->getBalanceSheet($date1);
        $bs2 = $this->getBalanceSheet($date2);
        
        return [
            'date1' => $date1,
            'date2' => $date2,
            'balance_sheet1' => $bs1,
            'balance_sheet2' => $bs2,
            'changes' => [
                'assets' => $bs2['total_assets'] - $bs1['total_assets'],
                'liabilities' => $bs2['total_liabilities'] - $bs1['total_liabilities'],
                'equity' => $bs2['total_equity'] - $bs1['total_equity']
            ]
        ];
    }
    
    /**
     * Get balance sheet ratios and analysis
     */
    public function getBalanceSheetAnalysis($as_of_date) {
        $categorized = $this->getCategorizedBalanceSheet($as_of_date);
        
        $current_ratio = $categorized['total_current_liabilities'] > 0 ? 
            $categorized['total_current_assets'] / $categorized['total_current_liabilities'] : 0;
        
        $debt_to_equity = $categorized['total_equity'] > 0 ? 
            $categorized['total_liabilities'] / $categorized['total_equity'] : 0;
        
        $equity_ratio = $categorized['total_assets'] > 0 ? 
            $categorized['total_equity'] / $categorized['total_assets'] : 0;
        
        return [
            'balance_sheet' => $categorized,
            'ratios' => [
                'current_ratio' => $current_ratio,
                'debt_to_equity_ratio' => $debt_to_equity,
                'equity_ratio' => $equity_ratio,
                'debt_ratio' => 1 - $equity_ratio
            ],
            'analysis' => [
                'liquidity' => $current_ratio >= 2 ? 'Good' : ($current_ratio >= 1 ? 'Fair' : 'Poor'),
                'leverage' => $debt_to_equity <= 1 ? 'Conservative' : ($debt_to_equity <= 2 ? 'Moderate' : 'High'),
                'financial_health' => $categorized['is_balanced'] ? 'Balanced' : 'Unbalanced'
            ]
        ];
    }
    
    /**
     * Export balance sheet to Excel format
     */
    public function exportBalanceSheetToExcel($as_of_date) {
        $balance_sheet = $this->getCategorizedBalanceSheet($as_of_date);
        
        $filename = 'Balance_Sheet_' . $as_of_date . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<!DOCTYPE html>';
        echo '<html><head><meta charset="UTF-8"></head><body>';
        
        // Header
        echo '<table width="100%" style="border-collapse: collapse;">';
        echo '<tr><td colspan="2" style="text-align:center; font-weight:bold; font-size:16px;">JAYALAKSHMI ENTERPRISES</td></tr>';
        echo '<tr><td colspan="2" style="text-align:center; font-weight:bold; font-size:14px;">BALANCE SHEET</td></tr>';
        echo '<tr><td colspan="2" style="text-align:center;">As of ' . date('d/m/Y', strtotime($as_of_date)) . '</td></tr>';
        echo '<tr><td colspan="2">&nbsp;</td></tr>';
        
        // Assets section
        echo '<tr><td style="font-weight:bold; background-color:#f0f0f0;">ASSETS</td><td style="font-weight:bold; background-color:#f0f0f0; text-align:right;">AMOUNT (₹)</td></tr>';
        
        if (!empty($balance_sheet['current_assets'])) {
            echo '<tr><td style="font-weight:bold; padding-left:10px;">Current Assets:</td><td></td></tr>';
            foreach ($balance_sheet['current_assets'] as $asset) {
                echo '<tr><td style="padding-left:20px;">' . htmlspecialchars($asset['account_name']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($asset['balance'], 2) . '</td></tr>';
            }
            echo '<tr><td style="padding-left:10px; font-weight:bold;">Total Current Assets</td>';
            echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_current_assets'], 2) . '</td></tr>';
            echo '<tr><td>&nbsp;</td><td></td></tr>';
        }
        
        if (!empty($balance_sheet['fixed_assets'])) {
            echo '<tr><td style="font-weight:bold; padding-left:10px;">Fixed Assets:</td><td></td></tr>';
            foreach ($balance_sheet['fixed_assets'] as $asset) {
                echo '<tr><td style="padding-left:20px;">' . htmlspecialchars($asset['account_name']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($asset['balance'], 2) . '</td></tr>';
            }
            echo '<tr><td style="padding-left:10px; font-weight:bold;">Total Fixed Assets</td>';
            echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_fixed_assets'], 2) . '</td></tr>';
            echo '<tr><td>&nbsp;</td><td></td></tr>';
        }
        
        if (!empty($balance_sheet['other_assets'])) {
            foreach ($balance_sheet['other_assets'] as $asset) {
                echo '<tr><td style="padding-left:10px;">' . htmlspecialchars($asset['account_name']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($asset['balance'], 2) . '</td></tr>';
            }
        }
        
        echo '<tr><td style="font-weight:bold; border-top:2px solid black;">TOTAL ASSETS</td>';
        echo '<td style="text-align:right; font-weight:bold; border-top:2px solid black;">' . number_format($balance_sheet['total_assets'], 2) . '</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        
        // Liabilities section
        echo '<tr><td style="font-weight:bold; background-color:#f0f0f0;">LIABILITIES</td><td style="font-weight:bold; background-color:#f0f0f0;"></td></tr>';
        
        if (!empty($balance_sheet['current_liabilities'])) {
            echo '<tr><td style="font-weight:bold; padding-left:10px;">Current Liabilities:</td><td></td></tr>';
            foreach ($balance_sheet['current_liabilities'] as $liability) {
                echo '<tr><td style="padding-left:20px;">' . htmlspecialchars($liability['account_name']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($liability['balance'], 2) . '</td></tr>';
            }
            echo '<tr><td style="padding-left:10px; font-weight:bold;">Total Current Liabilities</td>';
            echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_current_liabilities'], 2) . '</td></tr>';
            echo '<tr><td>&nbsp;</td><td></td></tr>';
        }
        
        if (!empty($balance_sheet['long_term_liabilities'])) {
            echo '<tr><td style="font-weight:bold; padding-left:10px;">Long-term Liabilities:</td><td></td></tr>';
            foreach ($balance_sheet['long_term_liabilities'] as $liability) {
                echo '<tr><td style="padding-left:20px;">' . htmlspecialchars($liability['account_name']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($liability['balance'], 2) . '</td></tr>';
            }
            echo '<tr><td style="padding-left:10px; font-weight:bold;">Total Long-term Liabilities</td>';
            echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_long_term_liabilities'], 2) . '</td></tr>';
            echo '<tr><td>&nbsp;</td><td></td></tr>';
        }
        
        echo '<tr><td style="font-weight:bold;">Total Liabilities</td>';
        echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_liabilities'], 2) . '</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        
        // Equity section
        echo '<tr><td style="font-weight:bold; background-color:#f0f0f0;">EQUITY</td><td style="font-weight:bold; background-color:#f0f0f0;"></td></tr>';
        foreach ($balance_sheet['equity'] as $equity) {
            echo '<tr><td style="padding-left:10px;">' . htmlspecialchars($equity['account_name']) . '</td>';
            echo '<td style="text-align:right;">' . number_format($equity['balance'], 2) . '</td></tr>';
        }
        echo '<tr><td style="font-weight:bold;">Total Equity</td>';
        echo '<td style="text-align:right; font-weight:bold;">' . number_format($balance_sheet['total_equity'], 2) . '</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        
        echo '<tr><td style="font-weight:bold; border-top:2px solid black;">TOTAL LIABILITIES & EQUITY</td>';
        echo '<td style="text-align:right; font-weight:bold; border-top:2px solid black;">' . number_format($balance_sheet['total_liabilities_equity'], 2) . '</td></tr>';
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }

    /**
     * Debug method to show why balance sheet balances or doesn't balance
     */
    public function debugBalanceSheet($as_of_date) {
        $detailed = $this->getDetailedBalanceSheet($as_of_date);
        
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border: 1px solid #ddd;'>";
        echo "<h5>BALANCE SHEET DEBUG - As of $as_of_date</h5>";
        
        echo "<h6>Assets:</h6>";
        foreach ($detailed['balance_sheet']['current_assets'] as $asset) {
            echo "- {$asset['account_name']}: ₹" . number_format($asset['balance'], 2) . "<br>";
        }
        foreach ($detailed['balance_sheet']['fixed_assets'] as $asset) {
            echo "- {$asset['account_name']}: ₹" . number_format($asset['balance'], 2) . "<br>";
        }
        foreach ($detailed['balance_sheet']['other_assets'] as $asset) {
            echo "- {$asset['account_name']}: ₹" . number_format($asset['balance'], 2) . "<br>";
        }
        echo "<strong>Total Assets: ₹" . number_format($detailed['balance_verification']['total_assets'], 2) . "</strong><br><br>";
        
        echo "<h6>Liabilities:</h6>";
        foreach ($detailed['balance_sheet']['current_liabilities'] as $liability) {
            echo "- {$liability['account_name']}: ₹" . number_format($liability['balance'], 2) . "<br>";
        }
        foreach ($detailed['balance_sheet']['long_term_liabilities'] as $liability) {
            echo "- {$liability['account_name']}: ₹" . number_format($liability['balance'], 2) . "<br>";
        }
        echo "<strong>Total Liabilities: ₹" . number_format($detailed['balance_verification']['total_liabilities'], 2) . "</strong><br><br>";
        
        echo "<h6>Equity:</h6>";
        foreach ($detailed['balance_sheet']['equity'] as $equity) {
            if (isset($equity['is_calculated']) && $equity['is_calculated']) {
                echo "- <span style='color: blue;'>{$equity['account_name']}: ₹" . number_format($equity['balance'], 2) . " (Calculated from P&L)</span><br>";
            } else {
                echo "- {$equity['account_name']}: ₹" . number_format($equity['balance'], 2) . "<br>";
            }
        }
        echo "<strong>Total Equity: ₹" . number_format($detailed['balance_verification']['total_equity_including_pl'], 2) . "</strong><br><br>";
        
        echo "<h6>P&L Integration:</h6>";
        echo "- Total Income (as of date): ₹" . number_format($detailed['pl_breakdown']['total_income'], 2) . "<br>";
        echo "- Total Expenses (as of date): ₹" . number_format($detailed['pl_breakdown']['total_expenses'], 2) . "<br>";
        echo "- <strong>Net Profit/Loss: ₹" . number_format($detailed['pl_breakdown']['net_profit'], 2) . "</strong><br><br>";
        
        echo "<h6>Balance Verification:</h6>";
        echo "Assets: ₹" . number_format($detailed['balance_verification']['total_assets'], 2) . "<br>";
        echo "Liabilities + Equity: ₹" . number_format($detailed['balance_verification']['total_liabilities_equity'], 2) . "<br>";
        echo "Difference: ₹" . number_format($detailed['balance_verification']['difference'], 2) . "<br>";
        echo "<strong>Status: " . ($detailed['balance_verification']['is_balanced'] ? "✅ BALANCED" : "❌ UNBALANCED") . "</strong><br>";
        
        echo "</div>";
    }
}
?>