<?php
require_once 'AccountingCore.php';

/**
 * AccountingReports Class
 * Handles all financial reporting - Cash Book, Ledgers, Trial Balance, P&L, Balance Sheet
 */
class AccountingReports extends AccountingCore
{
    /**
     * Get cash book entries for a date range
     */
    public function getCashBook($start_date, $end_date, $account_id = 1001)
    {
        // Get opening balance
        $opening_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $opening_balance = $this->getAccountBalance($account_id, $opening_date);

        // Get transactions
        $query = "SELECT 
                    t.id,
                    t.transaction_date,
                    t.description,
                    t.reference_no,
                    t.transaction_type,
                    td.debit,
                    td.credit,
                    td.description as detail_desc
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = '$account_id'
                  AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                  AND t.status = 'Posted'
                  ORDER BY t.transaction_date, t.id";

        $result = $this->db->select($query);
        
        // Calculate running balance
        $entries = [];
        $running_balance = $opening_balance;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $running_balance += ($row['debit'] - $row['credit']);
                $row['balance'] = $running_balance;
                $entries[] = $row;
            }
        }

        return [
            'opening_balance' => $opening_balance,
            'entries' => $entries,
            'closing_balance' => $running_balance
        ];
    }

    /**
     * Get ledger entries for a specific account
     */
    public function getLedger($account_id, $start_date, $end_date)
    {
        // Get account details
        $account_query = "SELECT * FROM tbl_accounts WHERE id = '$account_id'";
        $account_result = $this->db->select($account_query);
        $account = $account_result ? $account_result->fetch_assoc() : null;

        // Get opening balance
        $opening_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $opening_balance = $this->getAccountBalance($account_id, $opening_date);

        // Get transactions with contra accounts
        $query = "SELECT 
                    t.id,
                    t.transaction_date,
                    t.description as trans_desc,
                    t.reference_no,
                    td.debit,
                    td.credit,
                    td.description as detail_desc,
                    GROUP_CONCAT(
                        CASE 
                            WHEN td2.account_id != td.account_id 
                            THEN CONCAT(a2.account_name, ' (', 
                                IF(td2.debit > 0, 'Dr', 'Cr'), ' ', 
                                IF(td2.debit > 0, td2.debit, td2.credit), ')')
                        END SEPARATOR ', '
                    ) as contra_accounts
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  LEFT JOIN tbl_transaction_details td2 ON td2.transaction_id = t.id
                  LEFT JOIN tbl_accounts a2 ON td2.account_id = a2.id
                  WHERE td.account_id = '$account_id'
                  AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                  AND t.status = 'Posted'
                  GROUP BY t.id, td.id
                  ORDER BY t.transaction_date, t.id";

        $result = $this->db->select($query);
        
        // Calculate running balance
        $entries = [];
        $running_balance = $opening_balance;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $running_balance += ($row['debit'] - $row['credit']);
                $row['balance'] = $running_balance;
                $entries[] = $row;
            }
        }

        return [
            'account' => $account,
            'opening_balance' => $opening_balance,
            'entries' => $entries,
            'closing_balance' => $running_balance
        ];
    }

    /**
     * Get trial balance as of a specific date
     */
    public function getTrialBalance($as_of_date = null)
    {
        if ($as_of_date === null) {
            $as_of_date = date('Y-m-d');
        }

        $query = "SELECT 
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    COALESCE(SUM(td.debit), 0) as total_debit,
                    COALESCE(SUM(td.credit), 0) as total_credit,
                    CASE 
                        WHEN a.account_type IN ('Asset', 'Expense') 
                        THEN COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0)
                        ELSE COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0)
                    END as balance
                  FROM tbl_accounts a
                  LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                  LEFT JOIN tbl_transactions t ON td.transaction_id = t.id 
                    AND t.transaction_date <= '$as_of_date' 
                    AND t.status = 'Posted'
                  WHERE a.is_active = TRUE
                  GROUP BY a.id, a.account_code, a.account_name, a.account_type
                  HAVING total_debit != 0 OR total_credit != 0
                  ORDER BY a.account_type, a.account_code";

        $result = $this->db->select($query);
        
        // Calculate totals
        $totals = [
            'debit' => 0,
            'credit' => 0
        ];
        
        $accounts_by_type = [
            'Asset' => [],
            'Liability' => [],
            'Equity' => [],
            'Income' => [],
            'Expense' => []
        ];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $totals['debit'] += $row['total_debit'];
                $totals['credit'] += $row['total_credit'];
                $accounts_by_type[$row['account_type']][] = $row;
            }
        }

        return [
            'accounts' => $accounts_by_type,
            'totals' => $totals,
            'is_balanced' => abs($totals['debit'] - $totals['credit']) < 0.01
        ];
    }

    /**
     * Get profit and loss statement
     */
    public function getProfitLoss($start_date, $end_date)
    {
        // Income accounts
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
                        HAVING amount != 0
                        ORDER BY a.account_code";

        // Expense accounts
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
                         HAVING amount != 0
                         ORDER BY a.account_code";

        $income_result = $this->db->select($income_query);
        $expense_result = $this->db->select($expense_query);

        // Calculate totals
        $income_items = [];
        $expense_items = [];
        $total_income = 0;
        $total_expense = 0;

        if ($income_result) {
            while ($row = $income_result->fetch_assoc()) {
                $total_income += $row['amount'];
                $income_items[] = $row;
            }
        }

        if ($expense_result) {
            while ($row = $expense_result->fetch_assoc()) {
                $total_expense += $row['amount'];
                $expense_items[] = $row;
            }
        }

        return [
            'income' => $income_items,
            'expenses' => $expense_items,
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'net_profit' => $total_income - $total_expense,
            'period' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ];
    }

    /**
     * Get balance sheet as of a specific date
     */
    public function getBalanceSheet($as_of_date = null)
    {
        if ($as_of_date === null) {
            $as_of_date = date('Y-m-d');
        }

        // Assets
        $asset_query = "SELECT 
                         a.id,
                         a.account_code,
                         a.account_name,
                         COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as balance
                       FROM tbl_accounts a
                       LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                       LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                         AND t.transaction_date <= '$as_of_date'
                         AND t.status = 'Posted'
                       WHERE a.account_type = 'Asset'
                       AND a.is_active = TRUE
                       GROUP BY a.id, a.account_code, a.account_name
                       HAVING balance != 0
                       ORDER BY a.account_code";

        // Liabilities
        $liability_query = "SELECT 
                             a.id,
                             a.account_code,
                             a.account_name,
                             COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as balance
                           FROM tbl_accounts a
                           LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                           LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                             AND t.transaction_date <= '$as_of_date'
                             AND t.status = 'Posted'
                           WHERE a.account_type = 'Liability'
                           AND a.is_active = TRUE
                           GROUP BY a.id, a.account_code, a.account_name
                           HAVING balance != 0
                           ORDER BY a.account_code";

        // Equity (excluding current year P&L)
        $equity_query = "SELECT 
                          a.id,
                          a.account_code,
                          a.account_name,
                          COALESCE(SUM(td.credit), 0) - COALESCE(SUM(td.debit), 0) as balance
                        FROM tbl_accounts a
                        LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
                        LEFT JOIN tbl_transactions t ON td.transaction_id = t.id
                          AND t.transaction_date <= '$as_of_date'
                          AND t.status = 'Posted'
                        WHERE a.account_type = 'Equity'
                        AND a.is_active = TRUE
                        GROUP BY a.id, a.account_code, a.account_name
                        HAVING balance != 0
                        ORDER BY a.account_code";

        // Get current year profit/loss
        $current_year_start = date('Y-01-01', strtotime($as_of_date));
        $pl_data = $this->getProfitLoss($current_year_start, $as_of_date);

        // Execute queries
        $assets = $this->db->select($asset_query);
        $liabilities = $this->db->select($liability_query);
        $equity = $this->db->select($equity_query);

        // Process results
        $asset_items = [];
        $liability_items = [];
        $equity_items = [];
        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;

        if ($assets) {
            while ($row = $assets->fetch_assoc()) {
                $total_assets += $row['balance'];
                $asset_items[] = $row;
            }
        }

        if ($liabilities) {
            while ($row = $liabilities->fetch_assoc()) {
                $total_liabilities += $row['balance'];
                $liability_items[] = $row;
            }
        }

        if ($equity) {
            while ($row = $equity->fetch_assoc()) {
                $total_equity += $row['balance'];
                $equity_items[] = $row;
            }
        }

        // Add current year profit/loss to equity
        if ($pl_data['net_profit'] != 0) {
            $equity_items[] = [
                'id' => 0,
                'account_code' => 'CYP',
                'account_name' => 'Current Year Profit/Loss',
                'balance' => $pl_data['net_profit']
            ];
            $total_equity += $pl_data['net_profit'];
        }

        return [
            'assets' => $asset_items,
            'liabilities' => $liability_items,
            'equity' => $equity_items,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'is_balanced' => abs($total_assets - ($total_liabilities + $total_equity)) < 0.01,
            'as_of_date' => $as_of_date
        ];
    }

    /**
     * Get account activity summary
     */
    public function getAccountActivity($account_id, $start_date, $end_date)
    {
        $query = "SELECT 
                    COUNT(*) as transaction_count,
                    SUM(td.debit) as total_debit,
                    SUM(td.credit) as total_credit,
                    MIN(t.transaction_date) as first_transaction,
                    MAX(t.transaction_date) as last_transaction
                  FROM tbl_transaction_details td
                  JOIN tbl_transactions t ON td.transaction_id = t.id
                  WHERE td.account_id = '$account_id'
                  AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                  AND t.status = 'Posted'";

        $result = $this->db->select($query);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get daily cash position
     */
    public function getDailyCashPosition($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        // Cash accounts (can be multiple)
        $cash_accounts = [1001, 1002]; // Cash in Hand, Bank Account
        
        $positions = [];
        $total_cash = 0;

        foreach ($cash_accounts as $account_id) {
            $balance = $this->getAccountBalance($account_id, $date);
            
            // Get account name
            $account_query = "SELECT account_name FROM tbl_accounts WHERE id = '$account_id'";
            $account_result = $this->db->select($account_query);
            $account_name = $account_result ? $account_result->fetch_assoc()['account_name'] : 'Unknown';
            
            $positions[] = [
                'account_id' => $account_id,
                'account_name' => $account_name,
                'balance' => $balance
            ];
            
            $total_cash += $balance;
        }

        return [
            'date' => $date,
            'positions' => $positions,
            'total_cash' => $total_cash
        ];
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV($report_type, $data, $filename = null)
    {
        if ($filename === null) {
            $filename = $report_type . '_' . date('Ymd_His') . '.csv';
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report_type) {
            case 'cashbook':
                fputcsv($output, ['Date', 'Description', 'Reference', 'Debit', 'Credit', 'Balance']);
                foreach ($data['entries'] as $entry) {
                    fputcsv($output, [
                        $entry['transaction_date'],
                        $entry['description'],
                        $entry['reference_no'],
                        $entry['debit'],
                        $entry['credit'],
                        $entry['balance']
                    ]);
                }
                break;
                
            case 'trialbalance':
                fputcsv($output, ['Account Code', 'Account Name', 'Debit', 'Credit']);
                foreach ($data['accounts'] as $type => $accounts) {
                    foreach ($accounts as $account) {
                        fputcsv($output, [
                            $account['account_code'],
                            $account['account_name'],
                            $account['total_debit'],
                            $account['total_credit']
                        ]);
                    }
                }
                break;
        }
        
        fclose($output);
        exit();
    }
}
?>