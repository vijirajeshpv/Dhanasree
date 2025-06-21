<?php
// classes/accounting/PLAccountManager.php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . "/../../libs/CrudOperation.php");
include_once($filepath . "/../../helpers/Format.php");

class PLAccountManager {
    private $db;
    private $fm;
    
    function __construct()
    {
        $this->db = new CrudOperation();
        $this->fm = new Format();
    }

    function dbcon()
    {
        return $this->db->link;
    }
    
    /**
     * Get comprehensive P&L data with proper account classification
     */
    public function getProfitLossAccount($start_date, $end_date) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        
        // Get all income accounts with transactions
        $income_query = "SELECT 
                          a.id,
                          a.account_code,
                          a.account_name,
                          COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as amount
                        FROM tbl_accounts a
                        LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                        LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                          AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                          AND t.status = 'Posted'
                        WHERE a.account_type = 'Income'
                        AND a.is_active = TRUE
                        GROUP BY a.id, a.account_code, a.account_name
                        HAVING amount > 0
                        ORDER BY a.account_code";

        // Get all expense accounts with transactions
        $expense_query = "SELECT 
                           a.id,
                           a.account_code,
                           a.account_name,
                           COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as amount
                         FROM tbl_accounts a
                         LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                         LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                           AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                           AND t.status = 'Posted'
                         WHERE a.account_type = 'Expense'
                         AND a.is_active = TRUE
                         GROUP BY a.id, a.account_code, a.account_name
                         HAVING amount > 0
                         ORDER BY a.account_code";

        $income_result = $this->db->select($income_query);
        $expense_result = $this->db->select($expense_query);

        // Process results
        $income_items = [];
        $expense_items = [];
        $total_income = 0;
        $total_expenses = 0;

        if ($income_result) {
            while ($row = $income_result->fetch_assoc()) {
                $income_items[] = $row;
                $total_income += $row['amount'];
            }
        }

        if ($expense_result) {
            while ($row = $expense_result->fetch_assoc()) {
                $expense_items[] = $row;
                $total_expenses += $row['amount'];
            }
        }

        $net_profit = $total_income - $total_expenses;

        return [
            'income' => $income_items,
            'expenses' => $expense_items,
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ];
    }
    
    /**
     * Get monthly P&L comparison
     */
    public function getMonthlyPLComparison($year = null) {
        if ($year === null) {
            $year = date('Y');
        }
        
        $year = $this->fm->validation($year);
        $monthly_data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $start_date = sprintf('%d-%02d-01', $year, $month);
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $pl_data = $this->getProfitLossAccount($start_date, $end_date);
            
            $monthly_data[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'total_income' => $pl_data['total_income'],
                'total_expenses' => $pl_data['total_expenses'],
                'net_profit' => $pl_data['net_profit']
            ];
        }
        
        return $monthly_data;
    }
    
    /**
     * Get quarterly P&L summary
     */
    public function getQuarterlyPLSummary($year = null) {
        if ($year === null) {
            $year = date('Y');
        }
        
        $year = $this->fm->validation($year);
        
        $quarters = [
            'Q1' => ['01-01', '03-31'],
            'Q2' => ['04-01', '06-30'],
            'Q3' => ['07-01', '09-30'],
            'Q4' => ['10-01', '12-31']
        ];
        
        $quarterly_data = [];
        
        foreach ($quarters as $quarter => $dates) {
            $start_date = $year . '-' . $dates[0];
            $end_date = $year . '-' . $dates[1];
            
            $pl_data = $this->getProfitLossAccount($start_date, $end_date);
            
            $quarterly_data[] = [
                'quarter' => $quarter,
                'total_income' => $pl_data['total_income'],
                'total_expenses' => $pl_data['total_expenses'],
                'net_profit' => $pl_data['net_profit'],
                'profit_margin' => $pl_data['total_income'] > 0 ? ($pl_data['net_profit'] / $pl_data['total_income']) * 100 : 0
            ];
        }
        
        return $quarterly_data;
    }
    
    /**
     * Get expense analysis by category
     */
    public function getExpenseAnalysis($start_date, $end_date) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        
        $query = "SELECT 
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as amount,
                    COUNT(td.id) as transaction_count
                  FROM tbl_accounts a
                  LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                  LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                    AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    AND t.status = 'Posted'
                  WHERE a.account_type = 'Expense'
                  AND a.is_active = TRUE
                  GROUP BY a.id, a.account_code, a.account_name
                  HAVING amount > 0
                  ORDER BY amount DESC";
        
        $result = $this->db->select($query);
        $expenses = [];
        $total = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
                $total += $row['amount'];
            }
        }
        
        // Calculate percentages
        foreach ($expenses as &$expense) {
            $expense['percentage'] = $total > 0 ? ($expense['amount'] / $total) * 100 : 0;
        }
        
        return [
            'expenses' => $expenses,
            'total' => $total
        ];
    }
    
    /**
     * Get income analysis by source
     */
    public function getIncomeAnalysis($start_date, $end_date) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        
        $query = "SELECT 
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as amount,
                    COUNT(td.id) as transaction_count
                  FROM tbl_accounts a
                  LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                  LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                    AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    AND t.status = 'Posted'
                  WHERE a.account_type = 'Income'
                  AND a.is_active = TRUE
                  GROUP BY a.id, a.account_code, a.account_name
                  HAVING amount > 0
                  ORDER BY amount DESC";
        
        $result = $this->db->select($query);
        $income_sources = [];
        $total = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $income_sources[] = $row;
                $total += $row['amount'];
            }
        }
        
        // Calculate percentages
        foreach ($income_sources as &$income) {
            $income['percentage'] = $total > 0 ? ($income['amount'] / $total) * 100 : 0;
        }
        
        return [
            'income_sources' => $income_sources,
            'total' => $total
        ];
    }
    
    /**
     * Get P&L trend analysis
     */
    public function getPLTrendAnalysis($months = 12) {
        $months = intval($months);
        $trend_data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));
            
            $pl_data = $this->getProfitLossAccount($month_start, $month_end);
            
            $trend_data[] = [
                'period' => date('M Y', strtotime($month_start)),
                'month' => date('Y-m', strtotime($month_start)),
                'income' => $pl_data['total_income'],
                'expenses' => $pl_data['total_expenses'],
                'profit' => $pl_data['net_profit'],
                'margin' => $pl_data['total_income'] > 0 ? ($pl_data['net_profit'] / $pl_data['total_income']) * 100 : 0
            ];
        }
        
        return $trend_data;
    }
    
    /**
     * Get top performing income/expense accounts
     */
    public function getTopPerformers($start_date, $end_date, $limit = 5) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        $limit = intval($limit);
        
        // Top income accounts
        $top_income_query = "SELECT 
                              a.account_name,
                              COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as amount
                            FROM tbl_accounts a
                            LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                            LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                              AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                              AND t.status = 'Posted'
                            WHERE a.account_type = 'Income' AND a.is_active = TRUE
                            GROUP BY a.id, a.account_name
                            HAVING amount > 0
                            ORDER BY amount DESC
                            LIMIT $limit";
        
        // Top expense accounts
        $top_expense_query = "SELECT 
                               a.account_name,
                               COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as amount
                             FROM tbl_accounts a
                             LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                             LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                               AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                               AND t.status = 'Posted'
                             WHERE a.account_type = 'Expense' AND a.is_active = TRUE
                             GROUP BY a.id, a.account_name
                             HAVING amount > 0
                             ORDER BY amount DESC
                             LIMIT $limit";
        
        $top_income = [];
        $top_expenses = [];
        
        $income_result = $this->db->select($top_income_query);
        if ($income_result) {
            while ($row = $income_result->fetch_assoc()) {
                $top_income[] = $row;
            }
        }
        
        $expense_result = $this->db->select($top_expense_query);
        if ($expense_result) {
            while ($row = $expense_result->fetch_assoc()) {
                $top_expenses[] = $row;
            }
        }
        
        return [
            'top_income' => $top_income,
            'top_expenses' => $top_expenses
        ];
    }
    
    /**
     * Get comparative P&L analysis (current vs previous period)
     */
    public function getComparativePLAnalysis($start_date, $end_date) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        
        // Current period
        $current_pl = $this->getProfitLossAccount($start_date, $end_date);
        
        // Calculate previous period (same duration)
        $period_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
        $prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $prev_start_date = date('Y-m-d', strtotime($prev_end_date . ' -' . $period_days . ' days'));
        
        $previous_pl = $this->getProfitLossAccount($prev_start_date, $prev_end_date);
        
        // Calculate variances
        $income_variance = $current_pl['total_income'] - $previous_pl['total_income'];
        $expense_variance = $current_pl['total_expenses'] - $previous_pl['total_expenses'];
        $profit_variance = $current_pl['net_profit'] - $previous_pl['net_profit'];
        
        return [
            'current' => $current_pl,
            'previous' => $previous_pl,
            'variances' => [
                'income' => $income_variance,
                'expense' => $expense_variance,
                'profit' => $profit_variance,
                'income_percent' => $previous_pl['total_income'] > 0 ? ($income_variance / $previous_pl['total_income']) * 100 : 0,
                'expense_percent' => $previous_pl['total_expenses'] > 0 ? ($expense_variance / $previous_pl['total_expenses']) * 100 : 0,
                'profit_percent' => $previous_pl['net_profit'] != 0 ? ($profit_variance / abs($previous_pl['net_profit'])) * 100 : 0
            ]
        ];
    }
    
    /**
     * Generate P&L summary for dashboard
     */
    public function getPLDashboardSummary() {
        // Current month
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $current_month_pl = $this->getProfitLossAccount($current_month_start, $current_month_end);
        
        // Year to date
        $ytd_start = date('Y-01-01');
        $ytd_end = date('Y-m-d');
        $ytd_pl = $this->getProfitLossAccount($ytd_start, $ytd_end);
        
        // Last 12 months trend
        $trend_data = $this->getPLTrendAnalysis(12);
        
        $avg_monthly_profit = 0;
        $best_month = null;
        $worst_month = null;
        
        if (count($trend_data) > 0) {
            $avg_monthly_profit = array_sum(array_column($trend_data, 'profit')) / count($trend_data);
            
            // Find best and worst months
            $profits = array_column($trend_data, 'profit');
            $max_index = array_search(max($profits), $profits);
            $min_index = array_search(min($profits), $profits);
            $best_month = $trend_data[$max_index];
            $worst_month = $trend_data[$min_index];
        }
        
        return [
            'current_month' => $current_month_pl,
            'year_to_date' => $ytd_pl,
            'trend' => $trend_data,
            'summary' => [
                'avg_monthly_profit' => $avg_monthly_profit,
                'best_month' => $best_month,
                'worst_month' => $worst_month
            ]
        ];
    }
    
    /**
     * Validate P&L data integrity
     */
    public function validatePLData($start_date, $end_date) {
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        $errors = [];
        
        // Check for unposted transactions
        $unposted_query = "SELECT COUNT(*) as count FROM tbl_transactions 
                          WHERE transaction_date BETWEEN '$start_date' AND '$end_date' 
                          AND status != 'Posted'";
        
        $result = $this->db->select($unposted_query);
        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['count'];
            if ($count > 0) {
                $errors[] = "$count transactions are not posted for the period";
            }
        }
        
        // Check for accounts without proper classification
        $unclassified_query = "SELECT COUNT(*) as count FROM tbl_accounts 
                              WHERE account_type NOT IN ('Asset', 'Liability', 'Equity', 'Income', 'Expense') 
                              AND is_active = TRUE";
        
        $result = $this->db->select($unclassified_query);
        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['count'];
            if ($count > 0) {
                $errors[] = "$count accounts have improper classification";
            }
        }
        
        return [
            'is_valid' => count($errors) == 0,
            'errors' => $errors
        ];
    }
    
    /**
     * Generate next reference number for P&L reports
     */
    private function generateReferenceNumber($type = 'PL') {
        $type = $this->fm->validation($type);
        
        $query = "SELECT last_number FROM tbl_reference_sequences WHERE sequence_type = '$type'";
        $result = $this->db->select($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $next_number = $row['last_number'] + 1;
            
            // Update sequence
            $update_query = "UPDATE tbl_reference_sequences SET last_number = $next_number WHERE sequence_type = '$type'";
            $this->db->update($update_query);
        } else {
            // Create new sequence
            $insert_query = "INSERT INTO tbl_reference_sequences (sequence_type, prefix, last_number) VALUES ('$type', '$type-', 1)";
            $this->db->insert($insert_query);
            $next_number = 1;
        }
        
        return $type . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
}
?>