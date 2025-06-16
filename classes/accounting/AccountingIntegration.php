<?php
require_once 'AccountingCore.php';

/**
 * AccountingIntegration Class
 * Handles automated journal entries for business events
 */
class AccountingIntegration extends AccountingCore
{
    // Account ID constants for easy maintenance
    const CASH_ACCOUNT = 1001;
    const BANK_ACCOUNT = 1002;
    const LOAN_RECEIVABLE = 1003;
    const INTEREST_RECEIVABLE = 1004;
    const GOLD_STOCK = 1005;
    const REPLEDGE_PAYABLE = 2001;
    const CAPITAL = 3001;
    const INTEREST_INCOME = 4001;
    const OTHER_INCOME = 4002;
    const INTEREST_EXPENSE = 5001;
    const OFFICE_EXPENSE = 5002;

    /**
     * Post automated entry for gold loan disbursement
     */
    public function postLoanDisbursement($gl_no, $loan_amount, $date, $customer_name = '')
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Gold loan disbursement to $customer_name",
            'reference_no' => "GL-$gl_no",
            'transaction_type' => 'Payment'
        ];

        $entries = [
            [
                'account_id' => self::LOAN_RECEIVABLE,
                'debit' => $loan_amount,
                'credit' => 0,
                'description' => "Loan disbursed - GL No: $gl_no"
            ],
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => 0,
                'credit' => $loan_amount,
                'description' => "Cash paid for loan GL-$gl_no"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post automated entry for loan payment received
     */
    public function postLoanPayment($gl_no, $payment_amount, $interest_portion, $principal_portion, $date)
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Loan payment received",
            'reference_no' => "PMT-GL-$gl_no",
            'transaction_type' => 'Receipt'
        ];

        $entries = [
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => $payment_amount,
                'credit' => 0,
                'description' => "Payment received for GL-$gl_no"
            ]
        ];

        // Split between principal and interest
        if ($principal_portion > 0) {
            $entries[] = [
                'account_id' => self::LOAN_RECEIVABLE,
                'debit' => 0,
                'credit' => $principal_portion,
                'description' => "Principal payment"
            ];
        }

        if ($interest_portion > 0) {
            $entries[] = [
                'account_id' => self::INTEREST_INCOME,
                'debit' => 0,
                'credit' => $interest_portion,
                'description' => "Interest received"
            ];
        }

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post automated entry for loan closure
     */
    public function postLoanClosure($gl_no, $total_amount, $principal, $interest, $date, $customer_name = '')
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Gold loan closure - $customer_name",
            'reference_no' => "CLS-GL-$gl_no",
            'transaction_type' => 'Receipt'
        ];

        $entries = [
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => $total_amount,
                'credit' => 0,
                'description' => "Final payment for loan closure"
            ],
            [
                'account_id' => self::LOAN_RECEIVABLE,
                'debit' => 0,
                'credit' => $principal,
                'description' => "Principal closed"
            ],
            [
                'account_id' => self::INTEREST_INCOME,
                'debit' => 0,
                'credit' => $interest,
                'description' => "Interest on closure"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post automated entry for repledge to bank
     */
    public function postRepledgeToBank($bank_gl_no, $amount, $date, $bank_name = '')
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Repledge to bank - $bank_name",
            'reference_no' => "RPL-$bank_gl_no",
            'transaction_type' => 'Receipt'
        ];

        $entries = [
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Cash received from bank repledge"
            ],
            [
                'account_id' => self::REPLEDGE_PAYABLE,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Liability to bank"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post automated entry for repledge settlement
     */
    public function postRepledgeSettlement($bank_gl_no, $principal, $interest, $date)
    {
        $total = $principal + $interest;
        
        $data = [
            'transaction_date' => $date,
            'description' => "Repledge settlement",
            'reference_no' => "STL-$bank_gl_no",
            'transaction_type' => 'Payment'
        ];

        $entries = [
            [
                'account_id' => self::REPLEDGE_PAYABLE,
                'debit' => $principal,
                'credit' => 0,
                'description' => "Repledge principal settled"
            ]
        ];

        if ($interest > 0) {
            $entries[] = [
                'account_id' => self::INTEREST_EXPENSE,
                'debit' => $interest,
                'credit' => 0,
                'description' => "Interest paid to bank"
            ];
        }

        $entries[] = [
            'account_id' => self::CASH_ACCOUNT,
            'debit' => 0,
            'credit' => $total,
            'description' => "Cash paid for settlement"
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post automated entry for expenses
     */
    public function postExpense($expense_id, $category, $amount, $date, $description = '')
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Expense: $category - $description",
            'reference_no' => "EXP-$expense_id",
            'transaction_type' => 'Payment'
        ];

        // Map expense categories to accounts
        $expense_account = $this->mapExpenseCategory($category);

        $entries = [
            [
                'account_id' => $expense_account,
                'debit' => $amount,
                'credit' => 0,
                'description' => "$category expense"
            ],
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Cash paid"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Map expense categories to chart of accounts
     */
    private function mapExpenseCategory($category)
    {
        $mapping = [
            'Food' => self::OFFICE_EXPENSE,
            'Transportation' => self::OFFICE_EXPENSE,
            'Utilities' => self::OFFICE_EXPENSE,
            'Office' => self::OFFICE_EXPENSE,
            'Interest' => self::INTEREST_EXPENSE
        ];

        return isset($mapping[$category]) ? $mapping[$category] : self::OFFICE_EXPENSE;
    }

    /**
     * Post daily interest accrual
     */
    public function postDailyInterest($gl_no, $interest_amount, $date, $customer_name = '')
    {
        if ($interest_amount <= 0) {
            return "<span class='error'>Interest amount must be positive!</span>";
        }

        $data = [
            'transaction_date' => $date,
            'description' => "Daily interest accrual - $customer_name",
            'reference_no' => "INT-GL-$gl_no-" . date('Ymd', strtotime($date)),
            'transaction_type' => 'Journal'
        ];

        $entries = [
            [
                'account_id' => self::INTEREST_RECEIVABLE,
                'debit' => $interest_amount,
                'credit' => 0,
                'description' => "Interest accrued for GL-$gl_no"
            ],
            [
                'account_id' => self::INTEREST_INCOME,
                'debit' => 0,
                'credit' => $interest_amount,
                'description' => "Interest income recognized"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Post capital injection
     */
    public function postCapitalInjection($amount, $date, $description = '')
    {
        $data = [
            'transaction_date' => $date,
            'description' => "Capital injection - $description",
            'reference_no' => "CAP-" . date('Ymd', strtotime($date)),
            'transaction_type' => 'Receipt'
        ];

        $entries = [
            [
                'account_id' => self::CASH_ACCOUNT,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Cash received"
            ],
            [
                'account_id' => self::CAPITAL,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Owner's capital"
            ]
        ];

        return $this->createTransaction($data, $entries);
    }

    /**
     * Batch process daily interest for all active loans
     */
    public function processDailyInterestBatch($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        // Get all active loans
        $query = "SELECT gl_no, loan_amnt, b_id, name 
                  FROM tbl_gold_loan 
                  WHERE status = 0 
                  AND date <= '$date'";

        $loans = $this->db->select($query);
        
        if (!$loans) {
            return "<span class='error'>No active loans found</span>";
        }

        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        while ($loan = $loans->fetch_assoc()) {
            // Calculate daily interest (18% annually / 365 days)
            $daily_interest = round(($loan['loan_amnt'] * 0.18) / 365, 2);
            
            if ($daily_interest > 0) {
                $result = $this->postDailyInterest(
                    $loan['gl_no'], 
                    $daily_interest, 
                    $date, 
                    $loan['name']
                );
                
                if (is_numeric($result)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "GL-{$loan['gl_no']}: $result";
                }
            }
        }

        $message = "Interest processed: $success_count successful, $error_count failed.";
        if ($error_count > 0) {
            $message .= " Errors: " . implode("; ", $errors);
        }

        return $message;
    }
}
?>