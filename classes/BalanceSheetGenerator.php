<?php
$filepath = realpath(dirname(__FILE__));

include_once($filepath . "/../libs/CrudOperation.php");

class BalanceSheetGenerator {
    private $crud;

    public function __construct() {
        $this->crud = new CrudOperation();
    }

    public function getExpenseSummary($start_date = null, $end_date = null) {
        $start_date = $start_date ?? date('Y-m-01');
        $end_date = $end_date ?? date('Y-m-t');

        $query = "SELECT category, 
                  SUM(amount) as total_amount, 
                  COUNT(*) as transaction_count 
                  FROM tbl_expenses 
                  WHERE date BETWEEN '$start_date' AND '$end_date' 
                  GROUP BY category";
        
        $result = $this->crud->select($query);
        return $result ? $result : [];
    }

    public function getGoldLoanSummary($start_date = null, $end_date = null) {
        $dateFilter = "";
        if ($start_date && $end_date) {
            $dateFilter = "WHERE date BETWEEN '$start_date' AND '$end_date'";
        }

        $query = "SELECT 
                    COUNT(*) as total_loans,
                    SUM(loan_amnt) as total_loan_amount,
                    SUM(market_value) as total_market_value,
                    SUM(interest) as total_interest,
                    SUM(CASE WHEN status = 1 THEN loan_amnt ELSE 0 END) as closed_loan_amount
                  FROM tbl_gold_loan
                  $dateFilter";
        
        $result = $this->crud->select($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getRepledgeSummary($start_date = null, $end_date = null) {
        $dateFilter = "";
        if ($start_date && $end_date) {
            $dateFilter = "WHERE date BETWEEN '$start_date' AND '$end_date'";
        }

        $query = "SELECT 
                    COUNT(*) as total_repledges,
                    SUM(amount_bank) as total_bank_amount,
                    SUM(interest) as total_interest,
                    SUM(total_amount) as total_repledge_amount
                  FROM tbl_repledge
                  $dateFilter";
        
        $result = $this->crud->select($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function calculatePotentialNetIncome($start_date = null, $end_date = null) {
        $expenseSummary = $this->getExpenseSummary($start_date, $end_date);
        $goldLoanSummary = $this->getGoldLoanSummary($start_date, $end_date);
        $repledgeSummary = $this->getRepledgeSummary($start_date, $end_date);

        // Calculate total expenses
        $totalExpenses = 0;
        if ($expenseSummary) {
            foreach ($expenseSummary as $expense) {
                $totalExpenses += $expense['total_amount'];
            }
        }

        // Calculate potential net income
        $potentialNetIncome = 
            ($goldLoanSummary['total_loan_amount'] ?? 0) + 
            ($repledgeSummary['total_bank_amount'] ?? 0) - 
            $totalExpenses;

        return [
            'total_expenses' => $totalExpenses,
            'total_loan_amount' => $goldLoanSummary['total_loan_amount'] ?? 0,
            'total_bank_amount' => $repledgeSummary['total_bank_amount'] ?? 0,
            'potential_net_income' => $potentialNetIncome
        ];
    }

    public function getExpenseCategories($start_date = null, $end_date = null) {
        $expenseSummary = $this->getExpenseSummary($start_date, $end_date);
        $expenseCategories = [];
        
        if ($expenseSummary) {
            foreach ($expenseSummary as $expense) {
                $expenseCategories[$expense['category']] = $expense['total_amount'];
            }
        }

        return $expenseCategories;
    }
}