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

/**
 * Get ledger entries for a specific account with enhanced filtering
 * @param int $account_id - Account ID
 * @param string $start_date - Start date (optional if using financial period)
 * @param string $end_date - End date (optional if using financial period)  
 * @param int $financial_period_id - Financial period ID (optional if using dates)
 * @param bool $include_draft - Include draft transactions (default: false)
 * @return array - Ledger data with entries, balances, and metadata
 */
public function getLedger($account_id, $start_date = null, $end_date = null, $financial_period_id = null, $include_draft = false)
{
    // Validate inputs
    $account_id = $this->fm->validation($account_id);
    
    // Get account details
    $account_query = "SELECT * FROM tbl_accounts WHERE id = '$account_id' AND is_active = TRUE";
    $account_result = $this->db->select($account_query);
    
    if (!$account_result) {
        return ['error' => 'Account not found or inactive'];
    }
    
    $account = $account_result->fetch_assoc();
    
    // Determine date range based on input
    if ($financial_period_id !== null) {
        // Use financial period
        $financial_period_id = $this->fm->validation($financial_period_id);
        $period_query = "SELECT * FROM tbl_financial_periods WHERE id = '$financial_period_id'";
        $period_result = $this->db->select($period_query);
        
        if (!$period_result) {
            return ['error' => 'Financial period not found'];
        }
        
        $period = $period_result->fetch_assoc();
        $start_date = $period['start_date'];
        $end_date = $period['end_date'];
        $filter_type = 'financial_period';
        $filter_info = [
            'type' => 'Financial Period',
            'name' => $period['period_name'],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_closed' => $period['is_closed']
        ];
    } else {
        // Use date range
        if ($start_date === null || $end_date === null) {
            // Default to current financial year if no dates provided
            $current_year = date('Y');
            $current_month = date('m');
            
            if ($current_month >= 4) {
                $start_date = $current_year . '-04-01';
                $end_date = ($current_year + 1) . '-03-31';
            } else {
                $start_date = ($current_year - 1) . '-04-01';
                $end_date = $current_year . '-03-31';
            }
        }
        
        $start_date = $this->fm->validation($start_date);
        $end_date = $this->fm->validation($end_date);
        $filter_type = 'date_range';
        $filter_info = [
            'type' => 'Date Range',
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }
    
    // Build status condition
    $status_condition = $include_draft ? "t.status IN ('Posted', 'Draft')" : "t.status = 'Posted'";
    
    // Get opening balance (transactions before start date)
    $opening_query = "SELECT 
                        COALESCE(SUM(td.debit), 0) - COALESCE(SUM(td.credit), 0) as opening_balance
                      FROM tbl_transaction_details td
                      JOIN tbl_transactions t ON td.transaction_id = t.id
                      WHERE td.account_id = '$account_id'
                      AND t.transaction_date < '$start_date'
                      AND $status_condition";
    
    $opening_result = $this->db->select($opening_query);
    $opening_balance = $opening_result ? $opening_result->fetch_assoc()['opening_balance'] : 0;
    
    // Get ledger entries for the specified period
    $ledger_query = "SELECT 
                        t.id,
                        t.transaction_date,
                        t.description,
                        t.reference_no,
                        t.transaction_type,
                        t.status,
                        td.debit,
                        td.credit,
                        td.description as detail_description,
                        u.name as created_by_name
                     FROM tbl_transaction_details td
                     JOIN tbl_transactions t ON td.transaction_id = t.id
                     LEFT JOIN tbl_user u ON t.created_by = u.id
                     WHERE td.account_id = '$account_id'
                     AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                     AND $status_condition
                     ORDER BY t.transaction_date ASC, t.id ASC";
    
    $ledger_result = $this->db->select($ledger_query);
    
    // Process entries and calculate running balance
    $entries = [];
    $running_balance = $opening_balance;
    $total_debit = 0;
    $total_credit = 0;
    
    if ($ledger_result) {
        while ($row = $ledger_result->fetch_assoc()) {
            // Calculate balance based on account type
            if (in_array($account['account_type'], ['Asset', 'Expense'])) {
                $running_balance += ($row['debit'] - $row['credit']);
            } else {
                $running_balance += ($row['credit'] - $row['debit']);
            }
            
            $row['running_balance'] = $running_balance;
            $total_debit += $row['debit'];
            $total_credit += $row['credit'];
            
            $entries[] = $row;
        }
    }
    
    $closing_balance = $running_balance;
    
    // Get period summary statistics
    $period_totals = [
        'total_debit' => $total_debit,
        'total_credit' => $total_credit,
        'net_movement' => $total_debit - $total_credit,
        'transaction_count' => count($entries)
    ];
    
    // Get related account information for contra entries
    $related_accounts = $this->getRelatedAccounts($account_id, $start_date, $end_date, $status_condition);
    
    return [
        'account' => $account,
        'filter_info' => $filter_info,
        'opening_balance' => $opening_balance,
        'closing_balance' => $closing_balance,
        'entries' => $entries,
        'period_totals' => $period_totals,
        'related_accounts' => $related_accounts,
        'generated_at' => date('Y-m-d H:i:s'),
        'generated_by' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System'
    ];
}

/**
 * Get accounts that have transactions with the specified account (contra accounts)
 * @param int $account_id
 * @param string $start_date  
 * @param string $end_date
 * @param string $status_condition
 * @return array
 */
private function getRelatedAccounts($account_id, $start_date, $end_date, $status_condition)
{
    $query = "SELECT 
                a.account_code,
                a.account_name,
                a.account_type,
                COUNT(DISTINCT t.id) as transaction_count,
                SUM(td2.debit) as total_debit,
                SUM(td2.credit) as total_credit
              FROM tbl_transaction_details td1
              JOIN tbl_transactions t ON td1.transaction_id = t.id
              JOIN tbl_transaction_details td2 ON t.id = td2.transaction_id
              JOIN tbl_accounts a ON td2.account_id = a.id
              WHERE td1.account_id = '$account_id'
              AND td2.account_id != '$account_id'
              AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
              AND $status_condition
              GROUP BY a.id, a.account_code, a.account_name, a.account_type
              ORDER BY transaction_count DESC, a.account_name";
              
    $result = $this->db->select($query);
    $related = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $related[] = $row;
        }
    }
    
    return $related;
}

/**
 * Get multiple account ledgers for comparison
 * @param array $account_ids - Array of account IDs
 * @param string $start_date
 * @param string $end_date
 * @param int $financial_period_id
 * @return array
 */
public function getMultiAccountLedger($account_ids, $start_date = null, $end_date = null, $financial_period_id = null)
{
    $ledgers = [];
    $summary = [
        'total_accounts' => count($account_ids),
        'total_opening_balance' => 0,
        'total_closing_balance' => 0,
        'total_transactions' => 0
    ];
    
    foreach ($account_ids as $account_id) {
        $ledger = $this->getLedger($account_id, $start_date, $end_date, $financial_period_id);
        
        if (!isset($ledger['error'])) {
            $ledgers[] = $ledger;
            $summary['total_opening_balance'] += $ledger['opening_balance'];
            $summary['total_closing_balance'] += $ledger['closing_balance'];
            $summary['total_transactions'] += $ledger['period_totals']['transaction_count'];
        }
    }
    
    return [
        'ledgers' => $ledgers,
        'summary' => $summary,
        'filter_info' => isset($ledgers[0]['filter_info']) ? $ledgers[0]['filter_info'] : null
    ];
}

/**
 * Export ledger to CSV format
 * @param int $account_id
 * @param string $start_date
 * @param string $end_date
 * @param int $financial_period_id
 */
public function exportLedgerToCSV($account_id, $start_date = null, $end_date = null, $financial_period_id = null)
{
    $ledger = $this->getLedger($account_id, $start_date, $end_date, $financial_period_id);
    
    if (isset($ledger['error'])) {
        return false;
    }
    
    $account_name = str_replace(' ', '_', $ledger['account']['account_name']);
    $filename = "ledger_{$account_name}_" . date('Ymd_His') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Date', 'Description', 'Reference', 'Type', 'Debit', 'Credit', 'Balance', 'Status'
    ]);
    
    // Opening balance
    fputcsv($output, [
        $ledger['filter_info']['start_date'],
        'Opening Balance',
        '',
        '',
        $ledger['opening_balance'] > 0 ? $ledger['opening_balance'] : '',
        $ledger['opening_balance'] < 0 ? abs($ledger['opening_balance']) : '',
        $ledger['opening_balance'],
        ''
    ]);
    
    // Transactions
    foreach ($ledger['entries'] as $entry) {
        fputcsv($output, [
            $entry['transaction_date'],
            $entry['description'],
            $entry['reference_no'],
            $entry['transaction_type'],
            $entry['debit'] > 0 ? $entry['debit'] : '',
            $entry['credit'] > 0 ? $entry['credit'] : '',
            $entry['running_balance'],
            $entry['status']
        ]);
    }
    
    // Closing balance
    fputcsv($output, [
        $ledger['filter_info']['end_date'],
        'Closing Balance',
        '',
        '',
        $ledger['closing_balance'] > 0 ? $ledger['closing_balance'] : '',
        $ledger['closing_balance'] < 0 ? abs($ledger['closing_balance']) : '',
        $ledger['closing_balance'],
        ''
    ]);
    
    fclose($output);
    return true;
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