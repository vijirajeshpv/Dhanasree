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

        $amount = floatval($amount);
        $description = $this->fm->validation($description);
        $source_account = intval($source_account); // Cash/Bank account

        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate transaction reference
            $reference = $this->generateTransactionReference('CAP');

            // Create journal entry
            $journal_query = "INSERT INTO tbl_transactions (
                                reference_no, transaction_date, description,
                                transaction_type, status, created_at
                              ) VALUES (
                                '$reference', '$transaction_date', 'Capital Investment - $description',
                                'Capital Investment', 'Posted', NOW()
                              )";

            $journal_result = $this->db->insert($journal_query);

            if ($journal_result) {
                $transaction_id = $this->db->getLastInsertId();

                // Debit: Cash/Bank Account
                $debit_query = "INSERT INTO tbl_transaction_details (
                                  transaction_id, account_id, debit, credit, description
                                ) VALUES (
                                  '$transaction_id', '$source_account', '$amount', 0,
                                  'Capital investment received'
                                )";

                $this->db->insert($debit_query);

                // Credit: Owner Capital Account (3001)
                $capital_account_id = 3001;
                $credit_query = "INSERT INTO tbl_transaction_details (
                                   transaction_id, account_id, debit, credit, description
                                 ) VALUES (
                                   '$transaction_id', '$capital_account_id', 0, '$amount',
                                   'Capital investment by owners'
                                 )";

                $this->db->insert($credit_query);

                // Record in capital transactions log
                $log_query = "INSERT INTO tbl_capital_transactions (
                                transaction_type, amount, description, transaction_date,
                                accounting_transaction_id, created_at
                              ) VALUES (
                                'Capital Investment', '$amount', '$description',
                                '$transaction_date', '$transaction_id', NOW()
                              )";

                $this->db->insert($log_query);

                $this->db->query("COMMIT");
                return "success: Capital investment recorded successfully. Reference: $reference";
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
    public function transferProfitToRetainedEarnings($net_profit, $transfer_date = null) {
        if ($transfer_date === null) {
            $transfer_date = date('Y-m-d');
        }
        
        $net_profit = floatval($net_profit);
        
        if ($net_profit == 0) {
            return "error: No profit to transfer";
        }
        
        // Start transaction
        $this->db->query("START TRANSACTION");
        
        try {
            // Generate transaction reference
            $reference = $this->generateTransactionReference('PROFIT');
            
            // Create journal entry
            $journal_query = "INSERT INTO tbl_transactions (
                                reference_no, transaction_date, description,
                                transaction_type, status, created_at
                              ) VALUES (
                                '$reference', '$transfer_date', 'Transfer of Net Profit to Retained Earnings',
                                'Profit Transfer', 'Posted', NOW()
                              )";
            
            $journal_result = $this->db->insert($journal_query);
            
            if ($journal_result) {
                $transaction_id = $this->db->getLastInsertId();
                
                if ($net_profit > 0) {
                    // Profit: Debit P&L Summary, Credit Retained Earnings
                    
                    // Debit: P&L Summary Account (temporary account for profit calculation)
                    $pl_account_id = $this->getPLSummaryAccountId();
                    $debit_query = "INSERT INTO tbl_transaction_details (
                                      transaction_id, account_id, debit, credit, description
                                    ) VALUES (
                                      '$transaction_id', '$pl_account_id', '$net_profit', 0,
                                      'Net profit transfer'
                                    )";
                    
                    $this->db->insert($debit_query);
                    
                    // Credit: Retained Earnings (3002)
                    $retained_earnings_id = 3002;
                    $credit_query = "INSERT INTO tbl_transaction_details (
                                       transaction_id, account_id, debit, credit, description
                                     ) VALUES (
                                       '$transaction_id', '$retained_earnings_id', 0, '$net_profit',
                                       'Net profit retained in business'
                                     )";
                    
                    $this->db->insert($credit_query);
                } else {
                    // Loss: Debit Retained Earnings, Credit P&L Summary
                    $loss_amount = abs($net_profit);
                    
                    // Debit: Retained Earnings (3002)
                    $retained_earnings_id = 3002;
                    $debit_query = "INSERT INTO tbl_transaction_details (
                                      transaction_id, account_id, debit, credit, description
                                    ) VALUES (
                                      '$transaction_id', '$retained_earnings_id', '$loss_amount', 0,
                                      'Net loss absorbed from retained earnings'
                                    )";
                    
                    $this->db->insert($debit_query);
                    
                    // Credit: P&L Summary Account
                    $pl_account_id = $this->getPLSummaryAccountId();
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
                                accounting_transaction_id, created_at
                              ) VALUES (
                                'Profit Transfer', '$net_profit', 'Transfer to retained earnings',
                                '$transfer_date', '$transaction_id', NOW()
                              )";
                
                $this->db->insert($log_query);
                
                $this->db->query("COMMIT");
                return "success: Profit transferred to retained earnings successfully. Reference: $reference";
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
        $closing_capital = $this->getCapitalBalance();
        $closing_retained = $this->getRetainedEarningsBalance();
        
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
    
}
?>