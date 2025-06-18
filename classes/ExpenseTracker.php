<?php
$filepath = realpath(dirname(__FILE__));

include_once($filepath . "/../libs/CrudOperation.php");
include_once($filepath . "/accounting/AccountingIntegration.php");

class ExpenseTracker {
    private $db;
    private $accounting;
    
    function __construct()
    {
        $this->db = new CrudOperation();
        $this->accounting = new AccountingIntegration();
    }

    function dbcon()
    {
        return $this->db->link;
    }

    public function addExpense($category, $amount, $description, $date) {
        // Start transaction
        $this->db->link->begin_transaction();
        
        try {
            // Insert expense record
            $sql = "INSERT INTO tbl_expenses (category, amount, description, date) 
                    VALUES ('$category', '$amount', '$description', '$date')";
            
            $inserted = $this->db->insert($sql);
            
            if (!$inserted) {
                throw new Exception("Failed to insert expense record");
            }
            
            // Get the inserted expense ID
            $expense_id = $this->db->link->insert_id;
            
            // Create accounting entry
            $acc_result = $this->accounting->postExpense(
                $expense_id,
                $category,
                $amount,
                $date,
                $description
            );
            
            if (is_numeric($acc_result)) {
                // Update expense record with accounting reference
                $update_sql = "UPDATE tbl_expenses 
                             SET accounting_transaction_id = '$acc_result' 
                             WHERE id = '$expense_id'";
                $this->db->update($update_sql);
                
                // Commit transaction
                $this->db->link->commit();
                return $expense_id;
            } else {
                throw new Exception("Accounting entry failed: " . $acc_result);
            }
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->link->rollback();
            return false;
        }
    }

    public function getExpenses($start_date = null, $end_date = null, $category = null) {
        $sql = "SELECT e.*, 
                CASE 
                    WHEN e.accounting_transaction_id IS NOT NULL THEN 'Posted'
                    ELSE 'Not Posted'
                END as accounting_status
                FROM tbl_expenses e 
                WHERE 1=1";

        if ($start_date && $end_date) {
            $sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
        }

        if ($category) {
            $sql .= " AND category = '$category'";
        }

        $sql .= " ORDER BY date DESC";

        $result = $this->db->select($sql);
        return $result;
    }

    public function getTotalExpenses($start_date = null, $end_date = null) {
        $sql = "SELECT SUM(amount) as total FROM tbl_expenses WHERE 1=1";

        if ($start_date && $end_date) {
            $sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
        }

        $result = $this->db->select($sql);

        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    public function getExpenseById($id) {
        $sql = "SELECT * FROM tbl_expenses WHERE id ='$id'";
        $result = $this->db->select($sql);

        return $result->fetch_assoc();
    }

    public function updateExpense($id, $category, $amount, $description, $date) {
        // Get original expense
        $original = $this->getExpenseById($id);
        if (!$original) {
            return false;
        }
        
        // Start transaction
        $this->db->link->begin_transaction();
        
        try {
            // Update expense record
            $sql = "UPDATE tbl_expenses 
                   SET category = '$category', 
                       amount = '$amount', 
                       description = '$description', 
                       date = '$date' 
                   WHERE id ='$id'";
            
            $updated = $this->db->update($sql);
            
            if (!$updated) {
                throw new Exception("Failed to update expense");
            }
            
            // If accounting entry exists, reverse it and create new one
            if ($original['accounting_transaction_id']) {
                // Reverse original transaction
                $reverse_result = $this->accounting->reverseTransaction(
                    $original['accounting_transaction_id'],
                    $date,
                    "Update expense #$id"
                );
                
                if (!is_numeric($reverse_result)) {
                    throw new Exception("Failed to reverse original transaction");
                }
            }
            
            // Create new accounting entry
            $acc_result = $this->accounting->postExpense(
                $id,
                $category,
                $amount,
                $date,
                $description
            );
            
            if (is_numeric($acc_result)) {
                // Update with new accounting reference
                $update_acc = "UPDATE tbl_expenses 
                             SET accounting_transaction_id = '$acc_result' 
                             WHERE id = '$id'";
                $this->db->update($update_acc);
                
                $this->db->link->commit();
                return true;
            } else {
                throw new Exception("Failed to create new accounting entry");
            }
            
        } catch (Exception $e) {
            $this->db->link->rollback();
            return false;
        }
    }

    public function deleteExpense($id) {
        // Get expense details
        $expense = $this->getExpenseById($id);
        if (!$expense) {
            return false;
        }
        
        // Start transaction
        $this->db->link->begin_transaction();
        
        try {
            // If accounting entry exists, reverse it
            if ($expense['accounting_transaction_id']) {
                $reverse_result = $this->accounting->reverseTransaction(
                    $expense['accounting_transaction_id'],
                    date('Y-m-d'),
                    "Delete expense #$id"
                );
                
                if (!is_numeric($reverse_result)) {
                    throw new Exception("Failed to reverse accounting entry");
                }
            }
            
            // Delete expense record
            $sql = "DELETE FROM tbl_expenses WHERE id = '$id'";
            $deleted = $this->db->delete($sql);
            
            if ($deleted) {
                $this->db->link->commit();
                return true;
            } else {
                throw new Exception("Failed to delete expense");
            }
            
        } catch (Exception $e) {
            $this->db->link->rollback();
            return false;
        }
    }
    
    /**
     * Get expenses with accounting details
     */
    public function getExpensesWithAccounting($start_date = null, $end_date = null) {
        $sql = "SELECT 
                e.*,
                t.reference_no as accounting_ref,
                t.status as accounting_status,
                t.created_at as posted_at
                FROM tbl_expenses e
                LEFT JOIN tbl_transactions t ON e.accounting_transaction_id = t.id
                WHERE 1=1";
        
        if ($start_date && $end_date) {
            $sql .= " AND e.date BETWEEN '$start_date' AND '$end_date'";
        }
        
        $sql .= " ORDER BY e.date DESC";
        
        return $this->db->select($sql);
    }
    
    /**
     * Post accounting for expenses that don't have accounting entries
     * Useful for migrating existing expenses
     */
    public function postMissingAccountingEntries() {
        // Get expenses without accounting entries
        $sql = "SELECT * FROM tbl_expenses WHERE accounting_transaction_id IS NULL";
        $expenses = $this->db->select($sql);
        
        if (!$expenses) {
            return "No expenses without accounting entries found";
        }
        
        $success = 0;
        $failed = 0;
        
        while ($expense = $expenses->fetch_assoc()) {
            $acc_result = $this->accounting->postExpense(
                $expense['id'],
                $expense['category'],
                $expense['amount'],
                $expense['date'],
                $expense['description']
            );
            
            if (is_numeric($acc_result)) {
                // Update expense with accounting reference
                $update_sql = "UPDATE tbl_expenses 
                             SET accounting_transaction_id = '$acc_result' 
                             WHERE id = '{$expense['id']}'";
                $this->db->update($update_sql);
                $success++;
            } else {
                $failed++;
            }
        }
        
        return "Posted accounting for $success expenses, $failed failed";
    }
    
    /**
     * Get expense summary by category with accounting
     */
    public function getExpenseSummaryByCategory($start_date, $end_date) {
        $sql = "SELECT 
                category,
                COUNT(*) as count,
                SUM(amount) as total,
                SUM(CASE WHEN accounting_transaction_id IS NOT NULL THEN 1 ELSE 0 END) as posted_count
                FROM tbl_expenses
                WHERE date BETWEEN '$start_date' AND '$end_date'
                GROUP BY category
                ORDER BY total DESC";
        
        return $this->db->select($sql);
    }
}
?>