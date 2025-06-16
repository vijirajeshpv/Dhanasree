<?php
require_once 'AccountingCore.php';

/**
 * AccountManager Class
 * Handles Chart of Accounts management - add, edit, hierarchy, activation
 */
class AccountManager extends AccountingCore
{
    /**
     * Add new account
     */
    public function addAccount($data)
    {
        $account_code = $this->fm->validation($data['account_code']);
        $account_name = $this->fm->validation($data['account_name']);
        $account_type = $this->fm->validation($data['account_type']);
        $parent_account_id = isset($data['parent_account_id']) && $data['parent_account_id'] != '' 
            ? $this->fm->validation($data['parent_account_id']) 
            : 'NULL';

        // Validate account type
        $valid_types = ['Asset', 'Liability', 'Equity', 'Income', 'Expense'];
        if (!in_array($account_type, $valid_types)) {
            return "<span class='error'>Invalid account type!</span>";
        }

        // Check if account code already exists
        $check_query = "SELECT * FROM tbl_accounts WHERE account_code = '$account_code'";
        $exists = $this->db->select($check_query);
        
        if ($exists) {
            return "<span class='error'>Account code already exists!</span>";
        }

        // If parent account is specified, verify it exists and is same type
        if ($parent_account_id !== 'NULL') {
            $parent_query = "SELECT account_type FROM tbl_accounts WHERE id = '$parent_account_id'";
            $parent_result = $this->db->select($parent_query);
            
            if (!$parent_result) {
                return "<span class='error'>Parent account not found!</span>";
            }
            
            $parent = $parent_result->fetch_assoc();
            if ($parent['account_type'] !== $account_type) {
                return "<span class='error'>Account type must match parent account type!</span>";
            }
        }

        $query = "INSERT INTO tbl_accounts 
                  (account_code, account_name, account_type, parent_account_id) 
                  VALUES 
                  ('$account_code', '$account_name', '$account_type', $parent_account_id)";

        $inserted = $this->db->insert($query);
        
        if ($inserted) {
            return "<span class='success'>Account added successfully!</span>";
        } else {
            return "<span class='error'>Failed to add account!</span>";
        }
    }

    /**
     * Update account
     */
    public function updateAccount($account_id, $data)
    {
        $account_name = $this->fm->validation($data['account_name']);
        $is_active = isset($data['is_active']) ? 1 : 0;

        // Check if account has transactions before deactivating
        if ($is_active == 0) {
            $trans_check = "SELECT COUNT(*) as count 
                           FROM tbl_transaction_details 
                           WHERE account_id = '$account_id'";
            $result = $this->db->select($trans_check);
            
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                if ($count > 0) {
                    return "<span class='error'>Cannot deactivate account with existing transactions!</span>";
                }
            }
        }

        $query = "UPDATE tbl_accounts 
                  SET account_name = '$account_name', 
                      is_active = '$is_active' 
                  WHERE id = '$account_id'";

        $updated = $this->db->update($query);
        
        if ($updated) {
            return "<span class='success'>Account updated successfully!</span>";
        } else {
            return "<span class='error'>Failed to update account!</span>";
        }
    }

    /**
     * Get all accounts with hierarchy
     */
    public function getAccountsHierarchy($account_type = null, $active_only = true)
    {
        $query = "SELECT * FROM tbl_accounts WHERE 1=1";
        
        if ($account_type !== null) {
            $query .= " AND account_type = '$account_type'";
        }
        
        if ($active_only) {
            $query .= " AND is_active = TRUE";
        }
        
        $query .= " ORDER BY account_type, account_code";

        $result = $this->db->select($query);
        
        if (!$result) {
            return [];
        }

        // Build hierarchy
        $accounts = [];
        $account_map = [];
        
        while ($row = $result->fetch_assoc()) {
            $account_map[$row['id']] = $row;
            $row['children'] = [];
            
            if ($row['parent_account_id'] === null) {
                $accounts[] = &$account_map[$row['id']];
            }
        }

        // Assign children to parents
        foreach ($account_map as $id => &$account) {
            if ($account['parent_account_id'] !== null && isset($account_map[$account['parent_account_id']])) {
                $account_map[$account['parent_account_id']]['children'][] = &$account;
            }
        }

        return $accounts;
    }

    /**
     * Get account by ID
     */
    public function getAccount($account_id)
    {
        $query = "SELECT * FROM tbl_accounts WHERE id = '$account_id'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get account by code
     */
    public function getAccountByCode($account_code)
    {
        $account_code = $this->fm->validation($account_code);
        $query = "SELECT * FROM tbl_accounts WHERE account_code = '$account_code'";
        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get accounts for dropdown
     */
    public function getAccountsForDropdown($account_type = null, $exclude_parent = false)
    {
        $query = "SELECT id, account_code, account_name, account_type 
                  FROM tbl_accounts 
                  WHERE is_active = TRUE";
        
        if ($account_type !== null) {
            if (is_array($account_type)) {
                $types = "'" . implode("','", $account_type) . "'";
                $query .= " AND account_type IN ($types)";
            } else {
                $query .= " AND account_type = '$account_type'";
            }
        }
        
        if ($exclude_parent) {
            $query .= " AND parent_account_id IS NULL";
        }
        
        $query .= " ORDER BY account_type, account_code";

        return $this->db->select($query);
    }

    /**
     * Search accounts
     */
    public function searchAccounts($search_term)
    {
        $search_term = $this->fm->validation($search_term);
        
        $query = "SELECT * FROM tbl_accounts 
                  WHERE (account_code LIKE '%$search_term%' 
                  OR account_name LIKE '%$search_term%')
                  ORDER BY account_code
                  LIMIT 20";
        
        return $this->db->select($query);
    }

    /**
     * Initialize default accounts for new installation
     */
    public function initializeDefaultAccounts()
    {
        $default_accounts = [
            // Assets
            ['1001', 'Cash in Hand', 'Asset'],
            ['1002', 'Bank Account - Main', 'Asset'],
            ['1003', 'Gold Loans Receivable', 'Asset'],
            ['1004', 'Interest Receivable', 'Asset'],
            ['1005', 'Gold Stock', 'Asset'],
            
            // Liabilities
            ['2001', 'Bank Repledge Payable', 'Liability'],
            ['2002', 'Customer Deposits', 'Liability'],
            
            // Equity
            ['3001', 'Owner Capital', 'Equity'],
            ['3002', 'Retained Earnings', 'Equity'],
            
            // Income
            ['4001', 'Interest Income', 'Income'],
            ['4002', 'Other Income', 'Income'],
            
            // Expenses
            ['5001', 'Interest Expense', 'Expense'],
            ['5002', 'Office Expenses', 'Expense'],
            ['5003', 'Salary Expense', 'Expense'],
            ['5004', 'Rent Expense', 'Expense'],
            ['5005', 'Utilities Expense', 'Expense']
        ];

        $success_count = 0;
        $errors = [];

        foreach ($default_accounts as $account) {
            $data = [
                'account_code' => $account[0],
                'account_name' => $account[1],
                'account_type' => $account[2]
            ];
            
            $result = $this->addAccount($data);
            
            if (strpos($result, 'success') !== false) {
                $success_count++;
            } else {
                $errors[] = $account[0] . ': ' . strip_tags($result);
            }
        }

        $message = "$success_count accounts created successfully.";
        if (count($errors) > 0) {
            $message .= " Errors: " . implode("; ", $errors);
        }

        return $message;
    }

    /**
     * Get account balance with sub-accounts
     */
    public function getAccountBalanceWithChildren($account_id, $as_of_date = null)
    {
        if ($as_of_date === null) {
            $as_of_date = date('Y-m-d');
        }

        // Get all child accounts recursively
        $account_ids = $this->getChildAccountIds($account_id);
        $account_ids[] = $account_id; // Include parent

        $ids_string = implode(',', $account_ids);

        $query = "SELECT 
                    COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as balance
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id IN ($ids_string)
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
     * Get all child account IDs recursively
     */
    private function getChildAccountIds($parent_id)
    {
        $ids = [];
        
        $query = "SELECT id FROM tbl_accounts WHERE parent_account_id = '$parent_id'";
        $result = $this->db->select($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ids[] = $row['id'];
                // Recursive call to get children of children
                $child_ids = $this->getChildAccountIds($row['id']);
                $ids = array_merge($ids, $child_ids);
            }
        }
        
        return $ids;
    }

    /**
     * Validate if account can be deleted
     */
    public function canDeleteAccount($account_id)
    {
        // Check for transactions
        $trans_query = "SELECT COUNT(*) as count 
                       FROM tbl_transaction_details 
                       WHERE account_id = '$account_id'";
        $trans_result = $this->db->select($trans_query);
        
        if ($trans_result) {
            $trans_count = $trans_result->fetch_assoc()['count'];
            if ($trans_count > 0) {
                return ['can_delete' => false, 'reason' => 'Account has transactions'];
            }
        }

        // Check for child accounts
        $child_query = "SELECT COUNT(*) as count 
                       FROM tbl_accounts 
                       WHERE parent_account_id = '$account_id'";
        $child_result = $this->db->select($child_query);
        
        if ($child_result) {
            $child_count = $child_result->fetch_assoc()['count'];
            if ($child_count > 0) {
                return ['can_delete' => false, 'reason' => 'Account has sub-accounts'];
            }
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    /**
     * Delete account (if allowed)
     */
    public function deleteAccount($account_id)
    {
        $can_delete = $this->canDeleteAccount($account_id);
        
        if (!$can_delete['can_delete']) {
            return "<span class='error'>Cannot delete account: " . $can_delete['reason'] . "</span>";
        }

        $query = "DELETE FROM tbl_accounts WHERE id = '$account_id'";
        $deleted = $this->db->delete($query);
        
        if ($deleted) {
            return "<span class='success'>Account deleted successfully!</span>";
        } else {
            return "<span class='error'>Failed to delete account!</span>";
        }
    }

    /**
     * Get account summary (for dashboard)
     */
    public function getAccountTypeSummary($as_of_date = null)
    {
        if ($as_of_date === null) {
            $as_of_date = date('Y-m-d');
        }

        $query = "SELECT 
                    a.account_type,
                    COUNT(DISTINCT a.id) as account_count,
                    SUM(
                        CASE 
                            WHEN a.account_type IN ('Asset', 'Expense') 
                            THEN COALESCE(td.debit, 0) - COALESCE(td.credit, 0)
                            ELSE COALESCE(td.credit, 0) - COALESCE(td.debit, 0)
                        END
                    ) as total_balance
                  FROM tbl_accounts a
                  LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                  LEFT JOIN tbl_transactions t ON td.transaction_id = t.id 
                    AND t.transaction_date <= '$as_of_date' 
                    AND t.status = 'Posted'
                  WHERE a.is_active = TRUE
                  GROUP BY a.account_type
                  ORDER BY a.account_type";

        return $this->db->select($query);
    }

    /**
     * Export chart of accounts
     */
    public function exportChartOfAccounts()
    {
        $filename = 'chart_of_accounts_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Account Code', 'Account Name', 'Account Type', 'Parent Code', 'Status']);
        
        // Get all accounts
        $query = "SELECT 
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    p.account_code as parent_code,
                    IF(a.is_active, 'Active', 'Inactive') as status
                  FROM tbl_accounts a
                  LEFT JOIN tbl_accounts p ON a.parent_account_id = p.id
                  ORDER BY a.account_type, a.account_code";
        
        $result = $this->db->select($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['account_code'],
                    $row['account_name'],
                    $row['account_type'],
                    $row['parent_code'] ?: '',
                    $row['status']
                ]);
            }
        }
        
        fclose($output);
        exit();
    }
}
?>