<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath."/../../libs/CrudOperation.php");
include_once ($filepath."/../../helpers/Format.php");

/**
 * AccountingCore Class
 * Handles core accounting operations - journal entries, validation, basic queries
 */
class AccountingCore
{
    protected $db;
    protected $fm;
    
    function __construct()
    {
        $this->db = new CrudOperation();
        $this->fm = new Format();
    }

    /**
     * Create a new transaction with journal entries
     * @param array $data Transaction header data
     * @param array $entries Array of debit/credit entries
     * @return int|string Transaction ID on success, error message on failure
     */
    public function createTransaction($data, $entries)
    {
        // Validate double-entry accounting
        if (!$this->validateDoubleEntry($entries)) {
            return "<span class='error'>Transaction not balanced! Total debits must equal total credits.</span>";
        }

        // Check if period is closed
        if ($this->isPeriodClosed($data['transaction_date'])) {
            return "<span class='error'>Cannot post to a closed period!</span>";
        }

        // Start transaction
        $this->db->link->begin_transaction();

        try {
            // Insert main transaction
            $transaction_date = $this->fm->validation($data['transaction_date']);
            $description = $this->fm->validation($data['description']);
            $reference_no = $this->fm->validation($data['reference_no']);
            $transaction_type = $this->fm->validation($data['transaction_type']);
            $created_by = isset($data['created_by']) ? $data['created_by'] : $_SESSION['user_id'];
            $status = isset($data['status']) ? $data['status'] : 'Posted';

            $query = "INSERT INTO tbl_transactions 
                     (transaction_date, description, reference_no, transaction_type, created_by, status) 
                     VALUES 
                     ('$transaction_date', '$description', '$reference_no', '$transaction_type', '$created_by', '$status')";
            
            $inserted = $this->db->insert($query);
            
            if (!$inserted) {
                throw new Exception("Failed to create transaction");
            }

            $transaction_id = $this->db->link->insert_id;

            // Insert transaction details
            foreach ($entries as $entry) {
                $account_id = $this->fm->validation($entry['account_id']);
                $debit = isset($entry['debit']) ? $this->fm->validation($entry['debit']) : 0;
                $credit = isset($entry['credit']) ? $this->fm->validation($entry['credit']) : 0;
                $detail_desc = isset($entry['description']) ? $this->fm->validation($entry['description']) : '';

                $detail_query = "INSERT INTO tbl_transaction_details 
                               (transaction_id, account_id, debit, credit, description) 
                               VALUES 
                               ('$transaction_id', '$account_id', '$debit', '$credit', '$detail_desc')";
                
                $detail_inserted = $this->db->insert($detail_query);
                
                if (!$detail_inserted) {
                    throw new Exception("Failed to create transaction details");
                }
            }

            // Commit transaction
            $this->db->link->commit();
            return $transaction_id;

        } catch (Exception $e) {
            // Rollback on error
            $this->db->link->rollback();
            return "<span class='error'>" . $e->getMessage() . "</span>";
        }
    }

    /**
     * Validate that debits equal credits
     */
    protected function validateDoubleEntry($entries)
    {
        $total_debit = 0;
        $total_credit = 0;

        foreach ($entries as $entry) {
            $total_debit += isset($entry['debit']) ? $entry['debit'] : 0;
            $total_credit += isset($entry['credit']) ? $entry['credit'] : 0;
        }

        return abs($total_debit - $total_credit) < 0.01; // Allow for minor rounding differences
    }

    /**
     * Get account balance as of a specific date
     */
    public function getAccountBalance($account_id, $as_of_date = null)
    {
        if ($as_of_date === null) {
            $as_of_date = date('Y-m-d');
        }

        $query = "SELECT 
                    COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as balance
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = '$account_id'
                  AND t.transaction_date <= '$as_of_date'
                  AND t.status = 'Posted'";

        $result = $this->db->select($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['balance'];
        }
        
        return 0;
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction($transaction_id)
    {
        $query = "SELECT * FROM tbl_transactions WHERE id = '$transaction_id'";
        return $this->db->select($query);
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails($transaction_id)
    {
        $query = "SELECT td.*, a.account_name, a.account_code 
                  FROM tbl_transaction_details td
                  JOIN tbl_accounts a ON td.account_id = a.id
                  WHERE td.transaction_id = '$transaction_id'";
        return $this->db->select($query);
    }

    /**
     * Reverse a transaction
     */
    public function reverseTransaction($transaction_id, $reversal_date, $reason)
    {
        // Get original transaction
        $result = $this->getTransaction($transaction_id);
        
        if (!$result) {
            return "<span class='error'>Transaction not found!</span>";
        }

        $original = $result->fetch_assoc();

        // Get original entries
        $details = $this->getTransactionDetails($transaction_id);

        if (!$details) {
            return "<span class='error'>Transaction details not found!</span>";
        }

        // Create reversal data
        $reversal_data = [
            'transaction_date' => $reversal_date,
            'description' => "Reversal of: " . $original['description'] . " - Reason: $reason",
            'reference_no' => "REV-" . $original['reference_no'],
            'transaction_type' => $original['transaction_type']
        ];

        // Create reversed entries (swap debits and credits)
        $reversal_entries = [];
        while ($detail = $details->fetch_assoc()) {
            $reversal_entries[] = [
                'account_id' => $detail['account_id'],
                'debit' => $detail['credit'], // Swap
                'credit' => $detail['debit'], // Swap
                'description' => "Reversal: " . $detail['description']
            ];
        }

        return $this->createTransaction($reversal_data, $reversal_entries);
    }

    /**
     * Check if financial period is closed
     */
    protected function isPeriodClosed($transaction_date)
    {
        $query = "SELECT * FROM tbl_financial_periods 
                  WHERE '$transaction_date' BETWEEN start_date AND end_date 
                  AND is_closed = TRUE";
        
        $result = $this->db->select($query);
        return ($result !== false);
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus($transaction_id, $status)
    {
        $query = "UPDATE tbl_transactions 
                  SET status = '$status' 
                  WHERE id = '$transaction_id'";
        
        return $this->db->update($query);
    }

    /**
     * Delete transaction (only if draft)
     */
    public function deleteTransaction($transaction_id)
    {
        // Check if transaction is draft
        $trans = $this->getTransaction($transaction_id);
        if (!$trans) {
            return "<span class='error'>Transaction not found!</span>";
        }

        $data = $trans->fetch_assoc();
        if ($data['status'] != 'Draft') {
            return "<span class='error'>Only draft transactions can be deleted!</span>";
        }

        // Start transaction
        $this->db->link->begin_transaction();

        try {
            // Delete details first
            $detail_query = "DELETE FROM tbl_transaction_details WHERE transaction_id = '$transaction_id'";
            $this->db->delete($detail_query);

            // Delete main transaction
            $main_query = "DELETE FROM tbl_transactions WHERE id = '$transaction_id'";
            $this->db->delete($main_query);

            $this->db->link->commit();
            return "<span class='success'>Transaction deleted successfully!</span>";

        } catch (Exception $e) {
            $this->db->link->rollback();
            return "<span class='error'>Failed to delete transaction!</span>";
        }
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange($start_date, $end_date, $status = 'Posted')
    {
        $query = "SELECT * FROM tbl_transactions 
                  WHERE transaction_date BETWEEN '$start_date' AND '$end_date'
                  AND status = '$status'
                  ORDER BY transaction_date DESC, id DESC";
        
        return $this->db->select($query);
    }

    /**
     * Search transactions
     */
    public function searchTransactions($search_term)
    {
        $search_term = $this->fm->validation($search_term);
        
        $query = "SELECT * FROM tbl_transactions 
                  WHERE (description LIKE '%$search_term%' 
                  OR reference_no LIKE '%$search_term%')
                  ORDER BY transaction_date DESC
                  LIMIT 50";
        
        return $this->db->select($query);
    }
}
?>