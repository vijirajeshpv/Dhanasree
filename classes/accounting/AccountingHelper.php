<?php
require_once 'AccountingCore.php';

/**
 * AccountingHelper Class
 * Utility functions, period management, dashboard summaries, and other helpers
 */
class AccountingHelper extends AccountingCore
{
    /**
     * Create new financial period
     */
    public function createFinancialPeriod($data)
    {
        $period_name = $this->fm->validation($data['period_name']);
        $start_date = $this->fm->validation($data['start_date']);
        $end_date = $this->fm->validation($data['end_date']);

        // Validate dates
        if ($start_date >= $end_date) {
            return "<span class='error'>End date must be after start date!</span>";
        }

        // Check for overlapping periods
        $overlap_query = "SELECT * FROM tbl_financial_periods 
                         WHERE (start_date BETWEEN '$start_date' AND '$end_date'
                         OR end_date BETWEEN '$start_date' AND '$end_date'
                         OR (start_date <= '$start_date' AND end_date >= '$end_date'))";
        
        $overlap = $this->db->select($overlap_query);
        
        if ($overlap) {
            return "<span class='error'>Period overlaps with existing period!</span>";
        }

        $query = "INSERT INTO tbl_financial_periods 
                  (period_name, start_date, end_date) 
                  VALUES 
                  ('$period_name', '$start_date', '$end_date')";

        $inserted = $this->db->insert($query);
        
        if ($inserted) {
            return "<span class='success'>Financial period created successfully!</span>";
        } else {
            return "<span class='error'>Failed to create period!</span>";
        }
    }

    /**
     * Get all financial periods
     */
    public function getFinancialPeriods()
    {
        $query = "SELECT *, 
                  CASE 
                    WHEN CURDATE() BETWEEN start_date AND end_date THEN 'Current'
                    WHEN end_date < CURDATE() THEN 'Past'
                    ELSE 'Future'
                  END as period_status
                  FROM tbl_financial_periods 
                  ORDER BY start_date DESC";
        
        return $this->db->select($query);
    }

    /**
     * Get current financial period
     */
    public function getCurrentPeriod()
    {
        $query = "SELECT * FROM tbl_financial_periods 
                  WHERE CURDATE() BETWEEN start_date AND end_date 
                  LIMIT 1";
        
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Close financial period
     */
    public function closePeriod($period_id)
    {
        // Get period details
        $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$period_id'";
        $period_result = $this->db->select($period_query);
        
        if (!$period_result) {
            return "<span class='error'>Period not found!</span>";
        }
        
        $period = $period_result->fetch_assoc();
        
        if ($period['is_closed']) {
            return "<span class='error'>Period is already closed!</span>";
        }

        // Check if all previous periods are closed
        $check_query = "SELECT * FROM tbl_financial_periods 
                        WHERE end_date < '{$period['start_date']}' 
                        AND is_closed = FALSE";
        
        $unclosed = $this->db->select($check_query);
        
        if ($unclosed) {
            return "<span class='error'>Please close all previous periods first!</span>";
        }

        // Close the period
        $update_query = "UPDATE tbl_financial_periods 
                        SET is_closed = TRUE 
                        WHERE id = '$period_id'";

        $updated = $this->db->update($update_query);
        
        if ($updated) {
            // Create closing entries if needed
            $this->createClosingEntries($period);
            return "<span class='success'>Period closed successfully!</span>";
        } else {
            return "<span class='error'>Failed to close period!</span>";
        }
    }

    /**
     * Create closing entries for a period
     */
    private function createClosingEntries($period)
    {
        // This would typically:
        // 1. Close all income accounts to Income Summary
        // 2. Close all expense accounts to Income Summary
        // 3. Close Income Summary to Retained Earnings
        // For now, this is a placeholder
        return true;
    }

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        // Cash position
        $cash_query = "SELECT 
                        SUM(CASE WHEN a.account_code IN ('1001', '1002') 
                            THEN COALESCE(td.debit, 0) - COALESCE(td.credit, 0) 
                            ELSE 0 END) as cash_balance
                      FROM tbl_accounts a
                      LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                      LEFT JOIN tbl_transactions t ON td.transaction_id = t.id 
                        AND t.transaction_date <= '$date' 
                        AND t.status = 'Posted'";
        
        $cash_result = $this->db->select($cash_query);
        $cash_balance = $cash_result ? $cash_result->fetch_assoc()['cash_balance'] : 0;

        // Today's transactions
        $today_query = "SELECT 
                         COUNT(DISTINCT id) as transaction_count,
                         SUM(CASE WHEN transaction_type = 'Receipt' THEN 1 ELSE 0 END) as receipts,
                         SUM(CASE WHEN transaction_type = 'Payment' THEN 1 ELSE 0 END) as payments,
                         SUM(CASE WHEN transaction_type = 'Journal' THEN 1 ELSE 0 END) as journals
                       FROM tbl_transactions
                       WHERE transaction_date = '$date'
                       AND status = 'Posted'";
        
        $today_result = $this->db->select($today_query);
        $today_data = $today_result ? $today_result->fetch_assoc() : null;

        // Outstanding loans
        $loan_query = "SELECT COUNT(*) as active_loans, 
                              SUM(loan_amnt) as total_loan_amount
                      FROM tbl_gold_loan 
                      WHERE status = 0";
        
        $loan_result = $this->db->select($loan_query);
        $loan_data = $loan_result ? $loan_result->fetch_assoc() : null;

        // Month to date income/expense
        $month_start = date('Y-m-01', strtotime($date));
        
        $month_query = "SELECT 
                         SUM(CASE WHEN a.account_type = 'Income' 
                             THEN COALESCE(td.credit, 0) - COALESCE(td.debit, 0) 
                             ELSE 0 END) as month_income,
                         SUM(CASE WHEN a.account_type = 'Expense' 
                             THEN COALESCE(td.debit, 0) - COALESCE(td.credit, 0) 
                             ELSE 0 END) as month_expense
                       FROM tbl_accounts a
                       LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                       LEFT JOIN tbl_transactions t ON td.transaction_id = t.id 
                         AND t.transaction_date BETWEEN '$month_start' AND '$date' 
                         AND t.status = 'Posted'";
        
        $month_result = $this->db->select($month_query);
        $month_data = $month_result ? $month_result->fetch_assoc() : null;

        return [
            'date' => $date,
            'cash_balance' => $cash_balance,
            'today_transactions' => $today_data,
            'active_loans' => $loan_data,
            'month_income' => $month_data['month_income'] ?? 0,
            'month_expense' => $month_data['month_expense'] ?? 0,
            'month_profit' => ($month_data['month_income'] ?? 0) - ($month_data['month_expense'] ?? 0)
        ];
    }

    /**
     * Format currency for display
     */
    public function formatCurrency($amount)
    {
        return '₹' . number_format(abs($amount), 2);
    }

    /**
     * Format currency with Dr/Cr
     */
    public function formatDebitCredit($amount, $account_type = null)
    {
        if ($amount == 0) {
            return '₹0.00';
        }

        $formatted = $this->formatCurrency($amount);
        
        if ($account_type) {
            if (in_array($account_type, ['Asset', 'Expense'])) {
                return $amount > 0 ? $formatted . ' Dr' : $formatted . ' Cr';
            } else {
                return $amount > 0 ? $formatted . ' Cr' : $formatted . ' Dr';
            }
        }
        
        return $amount > 0 ? $formatted : '(' . $formatted . ')';
    }

    /**
     * Backup accounting data
     */
    public function backupData($tables = null)
    {
        if ($tables === null) {
            $tables = [
                'tbl_accounts',
                'tbl_transactions', 
                'tbl_transaction_details',
                'tbl_financial_periods'
            ];
        }

        $backup = "";
        $backup .= "-- Accounting System Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            // Get table structure
            $create_query = "SHOW CREATE TABLE $table";
            $create_result = $this->db->select($create_query);
            
            if ($create_result) {
                $create_row = $create_result->fetch_assoc();
                $backup .= "\n-- Table structure for $table\n";
                $backup .= "DROP TABLE IF EXISTS $table;\n";
                $backup .= $create_row['Create Table'] . ";\n\n";
            }

            // Get table data
            $data_query = "SELECT * FROM $table";
            $data_result = $this->db->select($data_query);
            
            if ($data_result && $data_result->num_rows > 0) {
                $backup .= "-- Data for $table\n";
                
                while ($row = $data_result->fetch_assoc()) {
                    $backup .= "INSERT INTO $table VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    $backup .= implode(',', $values);
                    $backup .= ");\n";
                }
                $backup .= "\n";
            }
        }

        // Save to file
        $filename = 'backup/accounting_backup_' . date('Ymd_His') . '.sql';
        $directory = dirname($filename);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        file_put_contents($filename, $backup);
        
        return $filename;
    }

    /**
     * Get recent transactions for activity feed
     */
    public function getRecentTransactions($limit = 10)
    {
        $query = "SELECT 
                    t.*,
                    u.name as created_by_name,
                    COUNT(td.id) as entry_count,
                    SUM(td.debit) as total_amount
                  FROM tbl_transactions t
                  LEFT JOIN tbl_user u ON t.created_by = u.id
                  LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
                  WHERE t.status = 'Posted'
                  GROUP BY t.id
                  ORDER BY t.created_at DESC
                  LIMIT $limit";
        
        return $this->db->select($query);
    }

    /**
     * Calculate interest for a specific loan
     */
    public function calculateLoanInterest($principal, $rate, $days)
    {
        // Simple interest calculation
        // Interest = Principal × Rate × Time / 365
        $annual_rate = $rate / 100;
        $interest = ($principal * $annual_rate * $days) / 365;
        
        return round($interest, 2);
    }

    /**
     * Get accounting settings (placeholder for future)
     */
    public function getSettings()
    {
        return [
            'currency_symbol' => '₹',
            'date_format' => 'Y-m-d',
            'decimal_places' => 2,
            'interest_rate' => 18, // Annual interest rate
            'financial_year_start' => '04-01', // April 1st
        ];
    }

    /**
     * Verify system integrity
     */
    public function verifyIntegrity()
    {
        $issues = [];

        // Check if all transactions balance
        $balance_query = "SELECT 
                           t.id,
                           t.reference_no,
                           SUM(td.debit) as total_debit,
                           SUM(td.credit) as total_credit
                         FROM tbl_transactions t
                         JOIN tbl_transaction_details td ON t.id = td.transaction_id
                         WHERE t.status = 'Posted'
                         GROUP BY t.id
                         HAVING ABS(total_debit - total_credit) > 0.01";
        
        $unbalanced = $this->db->select($balance_query);
        
        if ($unbalanced) {
            while ($row = $unbalanced->fetch_assoc()) {
                $issues[] = "Transaction {$row['reference_no']} is not balanced";
            }
        }

        // Check for orphaned transaction details
        $orphan_query = "SELECT COUNT(*) as count 
                        FROM tbl_transaction_details td
                        LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                        WHERE t.id IS NULL";
        
        $orphan_result = $this->db->select($orphan_query);
        
        if ($orphan_result) {
            $orphan_count = $orphan_result->fetch_assoc()['count'];
            if ($orphan_count > 0) {
                $issues[] = "$orphan_count orphaned transaction details found";
            }
        }

        return [
            'is_healthy' => count($issues) == 0,
            'issues' => $issues
        ];
    }

    /**
     * Generate unique reference number
     */
    public function generateReferenceNumber($prefix)
    {
        $date_part = date('Ymd');
        
        // Get the last reference number for today
        $query = "SELECT reference_no 
                  FROM tbl_transactions 
                  WHERE reference_no LIKE '$prefix-$date_part-%' 
                  ORDER BY id DESC 
                  LIMIT 1";
        
        $result = $this->db->select($query);
        
        if ($result) {
            $last_ref = $result->fetch_assoc()['reference_no'];
            $parts = explode('-', $last_ref);
            $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $date_part, $sequence);
    }
}
?>