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
    public function generateReferenceNumber($type, $options = []) {
        $type = $this->fm->validation($type);
        
        // Default options
        $defaults = [
            'prefix' => '',
            'include_date' => false,
            'date_format' => 'Ymd',      // YYYYMMDD
            'sequence_length' => 4,       // 0001, 0002, etc.
            'reset_daily' => false        // Reset sequence each day
        ];
        
        $options = array_merge($defaults, $options);
        
        // Set default prefix if not provided
        if (empty($options['prefix'])) {
            $options['prefix'] = strtoupper($type);
        }
        
        // Create sequence key
        $sequence_key = $type;
        if ($options['reset_daily']) {
            $sequence_key .= '_' . date('Ymd');
        }
        
        // Start transaction for thread safety
        $this->db->query("START TRANSACTION");
        
        try {
            // Get or create sequence
            $query = "SELECT last_number FROM tbl_reference_sequences 
                      WHERE sequence_type = '$sequence_key' FOR UPDATE";
            $result = $this->db->select($query);
            
            if ($result && $row = $result->fetch_assoc()) {
                $next_number = $row['last_number'] + 1;
                
                // Update sequence
                $update_query = "UPDATE tbl_reference_sequences 
                                SET last_number = $next_number,
                                    updated_at = NOW()
                                WHERE sequence_type = '$sequence_key'";
                $this->db->update($update_query);
            } else {
                // Create new sequence
                $next_number = 1;
                $insert_query = "INSERT INTO tbl_reference_sequences 
                                (sequence_type, prefix, last_number) 
                                VALUES ('$sequence_key', '{$options['prefix']}', 1)";
                $this->db->insert($insert_query);
            }
            
            $this->db->query("COMMIT");
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw new Exception("Failed to generate reference number: " . $e->getMessage());
        }
        
        // Build reference number
        $reference = $options['prefix'];
        
        if ($options['include_date']) {
            $reference .= '-' . date($options['date_format']);
        }
        
        $reference .= '-' . str_pad($next_number, $options['sequence_length'], '0', STR_PAD_LEFT);
        
        return $reference;
    }

    /**
     * Get all financial periods
     */
    public function getFinancialPeriods() {
        $query = "SELECT 
                    id, 
                    period_name, 
                    start_date, 
                    end_date, 
                    is_closed,
                    created_at,
                    closed_at
                  FROM tbl_financial_periods 
                  ORDER BY start_date DESC";
        
        return $this->db->select($query);
    }
    
    /**
     * Get financial period by ID
     */
    public function getFinancialPeriodById($period_id) {
        $period_id = $this->fm->validation($period_id);
        
        $query = "SELECT 
                    id, 
                    period_name, 
                    start_date, 
                    end_date, 
                    is_closed,
                    created_at,
                    closed_at
                  FROM tbl_financial_periods 
                  WHERE id = '$period_id'";
        
        $result = $this->db->select($query);
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get current open financial period
     */
    public function getCurrentFinancialPeriod() {
        $query = "SELECT 
                    id, 
                    period_name, 
                    start_date, 
                    end_date, 
                    is_closed
                  FROM tbl_financial_periods 
                  WHERE is_closed = 0 
                  AND CURDATE() BETWEEN start_date AND end_date
                  ORDER BY start_date DESC 
                  LIMIT 1";
        
        $result = $this->db->select($query);
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get financial period for a specific date
     */
    public function getFinancialPeriodForDate($date) {
        $date = $this->fm->validation($date);
        
        $query = "SELECT 
                    id, 
                    period_name, 
                    start_date, 
                    end_date, 
                    is_closed
                  FROM tbl_financial_periods 
                  WHERE '$date' BETWEEN start_date AND end_date
                  LIMIT 1";
        
        $result = $this->db->select($query);
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Validate date range
     */
    public function validateDateRange($start_date, $end_date) {
        if (empty($start_date) || empty($end_date)) {
            return "Both start and end dates are required.";
        }
        
        if (!strtotime($start_date) || !strtotime($end_date)) {
            return "Invalid date format.";
        }
        
        if (strtotime($start_date) > strtotime($end_date)) {
            return "Start date cannot be later than end date.";
        }
        
        return null; // No error
    }
    
    /**
     * Format period display name
     */
    public function formatPeriodDisplay($period) {
        $status = $period['is_closed'] ? 'Closed' : 'Open';
        $dates = date('M Y', strtotime($period['start_date'])) . ' - ' . date('M Y', strtotime($period['end_date']));
        
        return $period['period_name'] . ' (' . $dates . ') - ' . $status;
    }
    
    /**
     * Get accounts for dropdown
     */
    public function getAccountsForDropdown($account_type = null) {
        $where_clause = "WHERE is_active = 1";
        
        if ($account_type) {
            $account_type = $this->fm->validation($account_type);
            $where_clause .= " AND account_type = '$account_type'";
        }
        
        $query = "SELECT 
                    id, 
                    account_code, 
                    account_name, 
                    account_type
                  FROM tbl_accounts 
                  $where_clause
                  ORDER BY account_type, account_code";
        
        return $this->db->select($query);
    }
    
    /**
     * Get account by ID
     */
    public function getAccountById($account_id) {
        $account_id = $this->fm->validation($account_id);
        
        $query = "SELECT 
                    id, 
                    account_code, 
                    account_name, 
                    account_type,
                    parent_account_id,
                    is_active
                  FROM tbl_accounts 
                  WHERE id = '$account_id'";
        
        $result = $this->db->select($query);
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    
    /**
     * Check if period is closed
     */
    public function isPeriodClosed($period_id) {
        $period_id = $this->fm->validation($period_id);
        
        $query = "SELECT is_closed FROM tbl_financial_periods WHERE id = '$period_id'";
        $result = $this->db->select($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (bool)$row['is_closed'];
        }
        
        return false;
    }
    
    /**
     * Get period summary
     */
    public function getPeriodSummary($period_id) {
        $period_id = $this->fm->validation($period_id);
        
        $query = "SELECT 
                    fp.period_name,
                    fp.start_date,
                    fp.end_date,
                    fp.is_closed,
                    COUNT(t.id) as transaction_count,
                    COALESCE(SUM(CASE WHEN a.account_type = 'Income' THEN td.credit - td.debit ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN a.account_type = 'Expense' THEN td.debit - td.credit ELSE 0 END), 0) as total_expenses
                  FROM tbl_financial_periods fp
                  LEFT JOIN tbl_transactions t ON fp.id = t.financial_period_id AND t.status = 'Posted'
                  LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
                  LEFT JOIN tbl_accounts a ON td.account_id = a.id
                  WHERE fp.id = '$period_id'
                  GROUP BY fp.id";
        
        $result = $this->db->select($query);
        
        if ($result) {
            $summary = $result->fetch_assoc();
            $summary['net_profit'] = $summary['total_income'] - $summary['total_expenses'];
            return $summary;
        }
        
        return null;
    }
}
?>