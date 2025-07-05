<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath."/../libs/CrudOperation.php");
include_once ($filepath."/../helpers/Format.php");

class CapitalManager {
    private $db;
    private $fm;
    
    public function __construct() {
        $this->db = new CrudOperation();
        $this->fm = new Format();
    }

    /**
     * Record capital investment
     */
    public function recordCapitalInvestment($amount, $description = '', $transaction_date = null, $source_account = 1001) {
        if ($transaction_date === null) {
            $transaction_date = date('Y-m-d');
        }

        // VALIDATE FINANCIAL PERIOD
        $period_check = $this->validateTransactionDate($transaction_date);
        if (!$period_check['valid']) {
            return "error: " . $period_check['message'];
        }

        $amount = floatval($amount);
        $description = $this->fm->validation($description);
        $source_account = intval($source_account);

        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate transaction reference
            $reference = $this->generateTransactionReference('CAP');

            // Create journal entry with period reference
            $period_id = $period_check['period']['id'];
            $journal_query = "INSERT INTO tbl_transactions (
                                reference_no, transaction_date, description,
                                transaction_type, status, created_at, financial_period_id
                              ) VALUES (
                                '$reference', '$transaction_date', 'Capital Investment - $description',
                                'Capital Investment', 'Posted', NOW(), '$period_id'
                              )";

            $journal_result = $this->db->insert($journal_query);

            if ($journal_result) {
                $transaction_id = $this->db->getLastInsertId();

                // Create transaction details (same as before)
                $debit_query = "INSERT INTO tbl_transaction_details (
                                  transaction_id, account_id, debit, credit, description
                                ) VALUES (
                                  '$transaction_id', '$source_account', '$amount', 0,
                                  'Capital investment received'
                                )";
                $this->db->insert($debit_query);

                $capital_account_id = 3001;
                $credit_query = "INSERT INTO tbl_transaction_details (
                                   transaction_id, account_id, debit, credit, description
                                 ) VALUES (
                                   '$transaction_id', '$capital_account_id', 0, '$amount',
                                   'Capital investment by owners'
                                 )";
                $this->db->insert($credit_query);

                // Record in capital transactions log with period
                $log_query = "INSERT INTO tbl_capital_transactions (
                                transaction_type, amount, description, transaction_date,
                                accounting_transaction_id, financial_period_id, created_at
                              ) VALUES (
                                'Capital Investment', '$amount', '$description',
                                '$transaction_date', '$transaction_id', '$period_id', NOW()
                              )";
                $this->db->insert($log_query);

                $this->db->query("COMMIT");
                return "success: Capital investment recorded successfully. Reference: $reference (Period: {$period_check['period']['period_name']})";
            }

            $this->db->query("ROLLBACK");
            return "error: Failed to record capital investment";

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return "error: " . $e->getMessage();
        }
    }



    /**
     * Record capital withdrawal/drawings
     */
    public function recordCapitalWithdrawal($amount, $description = '', $transaction_date = null, $destination_account = 1001) {
        if ($transaction_date === null) {
            $transaction_date = date('Y-m-d');
        }

        $amount = floatval($amount);
        $description = $this->fm->validation($description);
        $destination_account = intval($destination_account); // Cash/Bank account

        // Check if withdrawal is allowed (capital balance should be sufficient)
        $capital_balance = $this->getCapitalBalance();
        if ($capital_balance < $amount) {
            return "error: Insufficient capital balance. Available: " . number_format($capital_balance, 2);
        }

        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate transaction reference
            $reference = $this->generateTransactionReference('DRAW');

            // Create journal entry
            $journal_query = "INSERT INTO tbl_transactions (
                                reference_no, transaction_date, description,
                                transaction_type, status, created_at
                              ) VALUES (
                                '$reference', '$transaction_date', 'Capital Withdrawal - $description',
                                'Capital Withdrawal', 'Posted', NOW()
                              )";

            $journal_result = $this->db->insert($journal_query);

            if ($journal_result) {
                $transaction_id = $this->db->getLastInsertId();

                // Debit: Owner Capital Account (3001)
                $capital_account_id = 3001;
                $debit_query = "INSERT INTO tbl_transaction_details (
                                  transaction_id, account_id, debit, credit, description
                                ) VALUES (
                                  '$transaction_id', '$capital_account_id', '$amount', 0,
                                  'Capital withdrawal by owners'
                                )";

                $this->db->insert($debit_query);

                // Credit: Cash/Bank Account
                $credit_query = "INSERT INTO tbl_transaction_details (
                                   transaction_id, account_id, debit, credit, description
                                 ) VALUES (
                                   '$transaction_id', '$destination_account', 0, '$amount',
                                   'Capital withdrawal paid'
                                 )";

                $this->db->insert($credit_query);

                // Record in capital transactions log
                $log_query = "INSERT INTO tbl_capital_transactions (
                                transaction_type, amount, description, transaction_date,
                                accounting_transaction_id, created_at
                              ) VALUES (
                                'Capital Withdrawal', '$amount', '$description',
                                '$transaction_date', '$transaction_id', NOW()
                              )";

                $this->db->insert($log_query);

                $this->db->query("COMMIT");
                return "success: Capital withdrawal recorded successfully. Reference: $reference";
            }

            $this->db->query("ROLLBACK");
            return "error: Failed to record capital withdrawal";

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return "error: " . $e->getMessage();
        }
    }
    /**
     * Transfer profit to retained earnings
     */
    public function transferProfitToRetainedEarnings($net_profit, $transfer_date = null, $period_id = null) {
        if ($transfer_date === null) {
            $transfer_date = date('Y-m-d');
        }

        // If no period specified, get current period
        if ($period_id === null) {
            $current_period = $this->getCurrentFinancialPeriod();
            if (!$current_period) {
                return "error: No active financial period found";
            }
            $period_id = $current_period['id'];
        } else {
            // Validate specified period
            $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$period_id'";
            $period_result = $this->db->select($period_query);
            if (!$period_result) {
                return "error: Invalid financial period";
            }
            $current_period = $period_result->fetch_assoc();
        }

        // Check if period is already closed
        if ($current_period['is_closed']) {
            return "error: Cannot transfer profit for closed period: " . $current_period['period_name'];
        }

        $net_profit = floatval($net_profit);

        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate transaction reference
            $reference = $this->generateTransactionReference('PLT');

            // Create journal entry
            $journal_query = "INSERT INTO tbl_transactions (
                                reference_no, transaction_date, description,
                                transaction_type, status, financial_period_id, created_at
                              ) VALUES (
                                '$reference', '$transfer_date', 'P&L Transfer for {$current_period['period_name']}',
                                'Journal', 'Posted', '$period_id', NOW()
                              )";

            $journal_result = $this->db->insert($journal_query);

            if ($journal_result) {
                $transaction_id = $this->db->getLastInsertId();

                if ($net_profit > 0) {
                    // Profit: Debit P&L Summary, Credit Retained Earnings
                    $pl_account_id = $this->getPLSummaryAccountId();
                    $retained_earnings_id = 3002;

                    $debit_query = "INSERT INTO tbl_transaction_details (
                                      transaction_id, account_id, debit, credit, description
                                    ) VALUES (
                                      '$transaction_id', '$pl_account_id', '$net_profit', 0,
                                      'Net profit transfer'
                                    )";
                    $this->db->insert($debit_query);

                    $credit_query = "INSERT INTO tbl_transaction_details (
                                       transaction_id, account_id, debit, credit, description
                                     ) VALUES (
                                       '$transaction_id', '$retained_earnings_id', 0, '$net_profit',
                                       'Profit added to retained earnings'
                                     )";
                    $this->db->insert($credit_query);
                } else {
                    // Loss: Debit Retained Earnings, Credit P&L Summary
                    $loss_amount = abs($net_profit);
                    $retained_earnings_id = 3002;
                    $pl_account_id = $this->getPLSummaryAccountId();

                    $debit_query = "INSERT INTO tbl_transaction_details (
                                      transaction_id, account_id, debit, credit, description
                                    ) VALUES (
                                      '$transaction_id', '$retained_earnings_id', '$loss_amount', 0,
                                      'Net loss absorbed from retained earnings'
                                    )";
                    $this->db->insert($debit_query);

                    $credit_query = "INSERT INTO tbl_transaction_details (
                                       transaction_id, account_id, debit, credit, description
                                     ) VALUES (
                                       '$transaction_id', '$pl_account_id', 0, '$loss_amount',
                                       'Net loss transfer'
                                     )";
                    $this->db->insert($credit_query);
                }

                // Record in capital transactions log
                $log_query = "INSERT INTO tbl_capital_transactions (
                                transaction_type, amount, description, transaction_date,
                                accounting_transaction_id, financial_period_id, created_at
                              ) VALUES (
                                'Profit Transfer', '$net_profit', 'P&L transfer for {$current_period['period_name']}',
                                '$transfer_date', '$transaction_id', '$period_id', NOW()
                              )";
                $this->db->insert($log_query);

                $this->db->query("COMMIT");
                return "success: Profit transferred to retained earnings for period {$current_period['period_name']}. Reference: $reference";
            }

            $this->db->query("ROLLBACK");
            return "error: Failed to transfer profit";

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return "error: " . $e->getMessage();
        }
    }

    /**
     * Get total capital balance (Owner Capital + Retained Earnings)
     */
    public function getCapitalBalance() {
        $query = "SELECT 
                    COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as balance
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = 3001
                  AND t.status = 'Posted'";
        
        $result = $this->db->select($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return floatval($row['balance']);
        }
        
        return 0;
    }

    /**
     * Get retained earnings balance
     */
    public function getRetainedEarningsBalance() {
        $query = "SELECT 
                    COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as balance
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = 3002
                  AND t.status = 'Posted'";
        
        $result = $this->db->select($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return floatval($row['balance']);
        }
        
        return 0;
    }

    /**
     * Get total equity (Capital + Retained Earnings)
     */
    public function getTotalEquity() {
        return $this->getCapitalBalance() + $this->getRetainedEarningsBalance();
    }

    /**
     * Get capital transactions history
     */
    public function getCapitalTransactions($start_date = null, $end_date = null, $limit = 50) {
        $where_clause = "";

        if ($start_date && $end_date) {
            $where_clause = " WHERE ct.transaction_date BETWEEN '$start_date' AND '$end_date'";
        }

        $query = "SELECT 
                    ct.transaction_date,
                    ct.transaction_type,
                    ct.amount,
                    ct.description,
                    t.reference_no,
                    ct.created_at
                  FROM tbl_capital_transactions ct
                  LEFT JOIN tbl_transactions t ON ct.accounting_transaction_id = t.id
                  $where_clause
                  ORDER BY ct.transaction_date DESC, ct.created_at DESC
                  LIMIT $limit";

        return $this->db->select($query);
    }

    /**
     * Get capital summary for dashboard
     */
    public function getCapitalSummary() {
        $capital_balance = $this->getCapitalBalance();
        $retained_earnings = $this->getRetainedEarningsBalance();
        $total_equity = $capital_balance + $retained_earnings;
        
        // Get monthly statistics
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        
        $monthly_query = "SELECT 
                            SUM(CASE WHEN transaction_type = 'Capital Investment' THEN amount ELSE 0 END) as month_investments,
                            SUM(CASE WHEN transaction_type = 'Capital Withdrawal' THEN amount ELSE 0 END) as month_withdrawals,
                            COUNT(*) as month_transactions
                          FROM tbl_capital_transactions
                          WHERE transaction_date BETWEEN '$current_month_start' AND '$current_month_end'";
        
        $monthly_result = $this->db->select($monthly_query);
        $monthly_data = $monthly_result ? $monthly_result->fetch_assoc() : [
            'month_investments' => 0,
            'month_withdrawals' => 0,
            'month_transactions' => 0
        ];
        
        return [
            'capital_balance' => $capital_balance,
            'retained_earnings' => $retained_earnings,
            'total_equity' => $total_equity,
            'month_investments' => floatval($monthly_data['month_investments']),
            'month_withdrawals' => floatval($monthly_data['month_withdrawals']),
            'month_transactions' => intval($monthly_data['month_transactions'])
        ];
    }

    /**
     * Generate capital statement
     */
    public function generateCapitalStatement($start_date, $end_date) {
        // Get opening balance
        $opening_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $opening_balance = $this->getAccountBalanceAsOf(3001, $opening_date);
        $opening_retained = $this->getAccountBalanceAsOf(3002, $opening_date);
        
        // Get transactions for the period
        $transactions = $this->getCapitalTransactions($start_date, $end_date, 1000);
        
        // Calculate closing balances
        $closing_capital = $this->getAccountBalanceAsOf(3001, $end_date);
        $closing_retained = $this->getAccountBalanceAsOf(3002, $end_date);
        
        return [
            'opening_capital' => $opening_balance,
            'opening_retained' => $opening_retained,
            'closing_capital' => $closing_capital,
            'closing_retained' => $closing_retained,
            'transactions' => $transactions,
            'period_start' => $start_date,
            'period_end' => $end_date
        ];
    }

    /**
     * Helper function to get account balance as of a specific date
     */
    private function getAccountBalanceAsOf($account_id, $as_of_date) {
        $query = "SELECT 
                    COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as balance
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = '$account_id'
                  AND t.transaction_date <= '$as_of_date'
                  AND t.status = 'Posted'";
        
        $result = $this->db->select($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return floatval($row['balance']);
        }
        
        return 0;
    }

    /**
     * Get or create P&L Summary account
     */
    private function getPLSummaryAccountId() {
        // Check if P&L Summary account exists
        $query = "SELECT id FROM tbl_accounts WHERE account_code = 'PL001'";
        $result = $this->db->select($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // Create P&L Summary account if it doesn't exist
        $insert_query = "INSERT INTO tbl_accounts (
                           account_code, account_name, account_type, is_active
                         ) VALUES (
                           'PL001', 'P&L Summary', 'Equity', 1
                         )";
        
        $this->db->insert($insert_query);
        return $this->db->getLastInsertId();
    }

    /**
     * Generate transaction reference number
     */
    private function generateTransactionReference($type) {
        // Get current sequence
        $sql = "SELECT last_number FROM tbl_reference_sequences WHERE sequence_type = '$type'";
        $result = $this->db->select($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $next_number = $row['last_number'] + 1;
        } else {
            // Create new sequence
            $insert_sql = "INSERT INTO tbl_reference_sequences (sequence_type, prefix, last_number) 
                          VALUES ('$type', '$type-', 1)";
            $this->db->insert($insert_sql);
            $next_number = 1;
        }
        
        // Update sequence
        $update_sql = "UPDATE tbl_reference_sequences 
                      SET last_number = $next_number 
                      WHERE sequence_type = '$type'";
        $this->db->update($update_sql);
        
        return $type . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
    public function dbcon()
    {
        return $this->db->link;
    }

    // Also add this method to make the database object accessible:
    public function getDb()
    {
        return $this->db;
    }
    
    /**
     * Get current active financial period
     */
    public function getCurrentFinancialPeriod() {
        $query = "SELECT * FROM tbl_financial_periods 
                  WHERE CURDATE() BETWEEN start_date AND end_date 
                  AND is_closed = FALSE
                  LIMIT 1";
        
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Validate if date is in active financial period
     */
    public function validateTransactionDate($transaction_date) {
        $period = $this->getCurrentFinancialPeriod();
        
        if (!$period) {
            return [
                'valid' => false,
                'message' => 'No active financial period found. Please contact administrator.'
            ];
        }

        if ($transaction_date < $period['start_date'] || $transaction_date > $period['end_date']) {
            return [
                'valid' => false,
                'message' => 'Transaction date must be within current financial period (' . 
                            date('d/m/Y', strtotime($period['start_date'])) . ' to ' . 
                            date('d/m/Y', strtotime($period['end_date'])) . ')'
            ];
        }

        return [
            'valid' => true,
            'period' => $period
        ];
    }

    /**
     * Get period-specific capital transactions
     */
    public function getCapitalTransactionsByPeriod($period_id = null, $limit = 50) {
        if ($period_id === null) {
            $current_period = $this->getCurrentFinancialPeriod();
            if (!$current_period) {
                return null;
            }
            $period_id = $current_period['id'];
        }

        $query = "SELECT 
                    ct.transaction_date,
                    ct.transaction_type,
                    ct.amount,
                    ct.description,
                    t.reference_no,
                    fp.period_name,
                    ct.created_at
                  FROM tbl_capital_transactions ct
                  LEFT JOIN tbl_transactions t ON ct.accounting_transaction_id = t.id
                  LEFT JOIN tbl_financial_periods fp ON ct.financial_period_id = fp.id
                  WHERE ct.financial_period_id = '$period_id'
                  ORDER BY ct.transaction_date DESC, ct.created_at DESC
                  LIMIT $limit";

        return $this->db->select($query);
    }

    /**
     * Get period-specific P&L data
     */
    public function getPeriodProfitLoss($period_id = null) {
        if ($period_id === null) {
            $current_period = $this->getCurrentFinancialPeriod();
            if (!$current_period) {
                return null;
            }
            $period_id = $current_period['id'];
            $start_date = $current_period['start_date'];
            $end_date = $current_period['end_date'];
        } else {
            $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$period_id'";
            $period_result = $this->db->select($period_query);
            if (!$period_result) {
                return null;
            }
            $period = $period_result->fetch_assoc();
            $start_date = $period['start_date'];
            $end_date = $period['end_date'];
        }

        // Get income for the period
        $income_query = "SELECT 
                          a.account_name,
                          COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as amount
                        FROM tbl_accounts a
                        LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                        LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                          AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                          AND t.status = 'Posted'
                          AND t.financial_period_id = '$period_id'
                        WHERE a.account_type = 'Income'
                        AND a.is_active = TRUE
                        GROUP BY a.id, a.account_name
                        HAVING amount > 0";

        // Get expenses for the period
        $expense_query = "SELECT 
                           a.account_name,
                           COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as amount
                         FROM tbl_accounts a
                         LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                         LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                           AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                           AND t.status = 'Posted'
                           AND t.financial_period_id = '$period_id'
                         WHERE a.account_type = 'Expense'
                         AND a.is_active = TRUE
                         GROUP BY a.id, a.account_name
                         HAVING amount > 0";

        $income_result = $this->db->select($income_query);
        $expense_result = $this->db->select($expense_query);

        $total_income = 0;
        $total_expenses = 0;
        $income_items = [];
        $expense_items = [];

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

        return [
            'period_id' => $period_id,
            'income' => $income_items,
            'expenses' => $expense_items,
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'net_profit' => $total_income - $total_expenses
        ];
    }

     /**
     * Close financial period (admin function)
     */
    public function closeFinancialPeriod($period_id, $user_id = null) {
        // Get period details
        $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$period_id'";
        $period_result = $this->db->select($period_query);
        
        if (!$period_result) {
            return "error: Period not found";
        }
        
        $period = $period_result->fetch_assoc();
        
        if ($period['is_closed']) {
            return "error: Period is already closed";
        }

        // Get P&L for the period
        $pl_data = $this->getPeriodProfitLoss($period_id);
        
        if (!$pl_data) {
            return "error: Could not calculate P&L for period";
        }

        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Transfer profit/loss to retained earnings
            if ($pl_data['net_profit'] != 0) {
                $transfer_result = $this->transferProfitToRetainedEarnings(
                    $pl_data['net_profit'], 
                    $period['end_date'], 
                    $period_id
                );
                
                if (strpos($transfer_result, 'error') !== false) {
                    $this->db->query("ROLLBACK");
                    return $transfer_result;
                }
            }

            // Close the period
            $close_query = "UPDATE tbl_financial_periods 
                           SET is_closed = TRUE, closed_at = NOW()
                           WHERE id = '$period_id'";
            
            $closed = $this->db->update($close_query);
            
            if ($closed) {
                $this->db->query("COMMIT");
                return "success: Financial period '{$period['period_name']}' closed successfully. Net P&L of ₹" . number_format($pl_data['net_profit'], 2) . " transferred to retained earnings.";
            } else {
                $this->db->query("ROLLBACK");
                return "error: Failed to close period";
            }

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return "error: " . $e->getMessage();
        }
    }

    /**
     * Get all financial periods with status
     */
    public function getFinancialPeriods() {
        $query = "SELECT fp.*, 
                  CASE 
                    WHEN fp.is_closed = TRUE THEN 'Closed'
                    WHEN CURDATE() BETWEEN fp.start_date AND fp.end_date THEN 'Current'
                    WHEN fp.start_date > CURDATE() THEN 'Future'
                    ELSE 'Past'
                  END as status,
                  COUNT(ct.id) as transaction_count
                  FROM tbl_financial_periods fp
                  LEFT JOIN tbl_capital_transactions ct ON fp.id = ct.financial_period_id
                  GROUP BY fp.id
                  ORDER BY fp.start_date DESC";
        
        return $this->db->select($query);
    }

    
/**
 * Create new financial period
 */
public function createFinancialPeriod($period_name, $start_date, $end_date) {
    $period_name = $this->fm->validation($period_name);
    $start_date = $this->fm->validation($start_date);
    $end_date = $this->fm->validation($end_date);
    
    // Validate inputs
    if (empty($period_name) || empty($start_date) || empty($end_date)) {
        return "error: All fields are required";
    }
    
    // Validate dates
    if ($start_date >= $end_date) {
        return "error: End date must be after start date";
    }
    
    // Check for overlapping periods
    $overlap_query = "SELECT * FROM tbl_financial_periods 
                     WHERE (start_date BETWEEN '$start_date' AND '$end_date'
                     OR end_date BETWEEN '$start_date' AND '$end_date'
                     OR (start_date <= '$start_date' AND end_date >= '$end_date'))";
    
    $overlap_result = $this->db->select($overlap_query);
    
    if ($overlap_result && $overlap_result->num_rows > 0) {
        return "error: Period dates overlap with existing period";
    }
    
    // Insert new period
    $insert_query = "INSERT INTO tbl_financial_periods 
                    (period_name, start_date, end_date, is_closed, created_at) 
                    VALUES 
                    ('$period_name', '$start_date', '$end_date', FALSE, NOW())";
    
    $inserted = $this->db->insert($insert_query);
    
    if ($inserted) {
        return "success: Financial period '$period_name' created successfully";
    } else {
        return "error: Failed to create financial period";
    }
}

/**
 * Delete financial period (only if no transactions)
 */
public function deleteFinancialPeriod($period_id) {
    $period_id = intval($period_id);
    
    // Check if period exists
    $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$period_id'";
    $period_result = $this->db->select($period_query);
    
    if (!$period_result || $period_result->num_rows == 0) {
        return "error: Period not found";
    }
    
    $period = $period_result->fetch_assoc();
    
    // Check if period is closed
    if ($period['is_closed']) {
        return "error: Cannot delete closed period";
    }
    
    // Check if period has transactions
    $transaction_query = "SELECT COUNT(*) as count FROM tbl_transactions 
                         WHERE financial_period_id = '$period_id'";
    $transaction_result = $this->db->select($transaction_query);
    
    if ($transaction_result) {
        $transaction_count = $transaction_result->fetch_assoc()['count'];
        if ($transaction_count > 0) {
            return "error: Cannot delete period with existing transactions";
        }
    }
    
    // Check capital transactions
    $capital_transaction_query = "SELECT COUNT(*) as count FROM tbl_capital_transactions 
                                 WHERE financial_period_id = '$period_id'";
    $capital_result = $this->db->select($capital_transaction_query);
    
    if ($capital_result) {
        $capital_count = $capital_result->fetch_assoc()['count'];
        if ($capital_count > 0) {
            return "error: Cannot delete period with existing capital transactions";
        }
    }
    
    // Delete the period
    $delete_query = "DELETE FROM tbl_financial_periods WHERE id = '$period_id'";
    $deleted = $this->db->delete($delete_query);
    
    if ($deleted) {
        return "success: Financial period '{$period['period_name']}' deleted successfully";
    } else {
        return "error: Failed to delete financial period";
    }
}

/**
 * Get period details with statistics
 */
public function getPeriodDetails($period_id) {
    $period_id = intval($period_id);
    
    $query = "SELECT 
                fp.*,
                COUNT(t.id) as transaction_count,
                COUNT(ct.id) as capital_transaction_count,
                COALESCE(SUM(CASE WHEN a.account_type = 'Income' 
                            THEN td.credit - td.debit ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN a.account_type = 'Expense' 
                            THEN td.debit - td.credit ELSE 0 END), 0) as total_expenses
              FROM tbl_financial_periods fp
              LEFT JOIN tbl_transactions t ON fp.id = t.financial_period_id AND t.status = 'Posted'
              LEFT JOIN tbl_capital_transactions ct ON fp.id = ct.financial_period_id
              LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
              LEFT JOIN tbl_accounts a ON td.account_id = a.id
              WHERE fp.id = '$period_id'
              GROUP BY fp.id";
    
    $result = $this->db->select($query);
    
    if ($result && $result->num_rows > 0) {
        $period = $result->fetch_assoc();
        $period['net_profit'] = $period['total_income'] - $period['total_expenses'];
        return $period;
    }
    
    return null;
}

/**
 * Get current active period
 */
public function getCurrentPeriod() {
    $query = "SELECT * FROM tbl_financial_periods 
              WHERE CURDATE() BETWEEN start_date AND end_date 
              AND is_closed = FALSE
              LIMIT 1";
    
    $result = $this->db->select($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}


/**
 * Get all periods with enhanced statistics (COMPLETION of cut-off function)
 */
public function getFinancialPeriodsWithStats() {
    $query = "SELECT 
                fp.*,
                COUNT(DISTINCT t.id) as transaction_count,
                COUNT(DISTINCT ct.id) as capital_transaction_count,
                COALESCE(SUM(CASE WHEN a.account_type = 'Income' 
                            THEN td.credit - td.debit ELSE 0 END), 0) as period_income,
                COALESCE(SUM(CASE WHEN a.account_type = 'Expense' 
                            THEN td.debit - td.credit ELSE 0 END), 0) as period_expenses,
                CASE 
                    WHEN fp.is_closed = TRUE THEN 'Closed'
                    WHEN CURDATE() BETWEEN fp.start_date AND fp.end_date THEN 'Current'
                    WHEN fp.start_date > CURDATE() THEN 'Future'
                    ELSE 'Past'
                END as status
              FROM tbl_financial_periods fp
              LEFT JOIN tbl_transactions t ON fp.id = t.financial_period_id AND t.status = 'Posted'
              LEFT JOIN tbl_capital_transactions ct ON fp.id = ct.financial_period_id
              LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
              LEFT JOIN tbl_accounts a ON td.account_id = a.id
              GROUP BY fp.id, fp.period_name, fp.start_date, fp.end_date, fp.is_closed, fp.created_at
              ORDER BY fp.start_date DESC";
    
    $result = $this->db->select($query);
    
    if ($result && $result->num_rows > 0) {
        $periods = [];
        while ($row = $result->fetch_assoc()) {
            $row['net_profit'] = $row['period_income'] - $row['period_expenses'];
            $periods[] = $row;
        }
        return $periods;
    }
    
    return [];
}


}
?>