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

    /**
     * Post asset purchase transaction
     */
    public function postAssetPurchase($asset_id, $amount, $date, $asset_name, $category) {
        try {
            // Determine asset account based on category
            $asset_account_code = $this->getAssetAccountByCategory($category);
            
            // Generate reference number
            $reference_no = $this->generateReferenceNumber('ASSET');
            
            // Get account IDs
            $asset_account_id = $this->getAccountIdByCode($asset_account_code);
            $cash_account_id = self::CASH_ACCOUNT;
            
            if (!$asset_account_id) {
                return "Account mapping error - Asset account not found for category: $category";
            }
            
            // Prepare transaction data (as array - matching AccountingCore format)
            $data = [
                'transaction_date' => $date,
                'description' => "Asset Purchase - $asset_name",
                'reference_no' => $reference_no,
                'transaction_type' => 'Payment'
            ];
            
            // Prepare journal entries
            $entries = [
                [
                    'account_id' => $asset_account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Asset purchased - $asset_name"
                ],
                [
                    'account_id' => $cash_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "Cash paid for asset purchase"
                ]
            ];
            
            // Create transaction using parent method
            return $this->createTransaction($data, $entries);
            
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Post monthly depreciation transaction
     */
    public function postDepreciation($asset_id, $depreciation_amount, $date, $asset_name) {
        try {
            // Get asset details to determine category
            $asset_sql = "SELECT category FROM tbl_fixed_assets WHERE id = '$asset_id'";
            $asset_result = $this->db->select($asset_sql);
            
            if (!$asset_result) {
                return "Asset not found";
            }
            
            $asset = $asset_result->fetch_assoc();
            
            // Determine depreciation account based on category
            $depreciation_account_code = $this->getDepreciationAccountByCategory($asset['category']);
            
            // Generate reference number
            $reference_no = $this->generateReferenceNumber('DEP');
            
            // Get account IDs
            $depreciation_expense_id = $this->getAccountIdByCode('5006'); // Depreciation Expense
            $accumulated_depreciation_id = $this->getAccountIdByCode($depreciation_account_code);
            
            if (!$depreciation_expense_id || !$accumulated_depreciation_id) {
                return "Account mapping error - Depreciation accounts not found";
            }
            
            // Prepare transaction data
            $data = [
                'transaction_date' => $date,
                'description' => "Monthly Depreciation - $asset_name",
                'reference_no' => $reference_no,
                'transaction_type' => 'Journal'
            ];
            
            // Prepare journal entries
            $entries = [
                [
                    'account_id' => $depreciation_expense_id,
                    'debit' => $depreciation_amount,
                    'credit' => 0,
                    'description' => "Depreciation expense - $asset_name"
                ],
                [
                    'account_id' => $accumulated_depreciation_id,
                    'debit' => 0,
                    'credit' => $depreciation_amount,
                    'description' => "Accumulated depreciation - $asset_name"
                ]
            ];
            
            // Create transaction using parent method
            return $this->createTransaction($data, $entries);
            
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Post asset disposal transaction
     */
    public function postAssetDisposal($asset_id, $disposal_amount, $book_value, $date, $asset_name) {
        try {
            // Get asset details
            $asset_sql = "SELECT category, purchase_amount, accumulated_depreciation 
                         FROM tbl_fixed_assets WHERE id = '$asset_id'";
            $asset_result = $this->db->select($asset_sql);
            
            if (!$asset_result) {
                return "Asset not found";
            }
            
            $asset = $asset_result->fetch_assoc();
            
            // Calculate gain/loss on disposal
            $gain_loss = $disposal_amount - $book_value;
            
            // Determine accounts
            $asset_account_code = $this->getAssetAccountByCategory($asset['category']);
            $depreciation_account_code = $this->getDepreciationAccountByCategory($asset['category']);
            
            // Generate reference number
            $reference_no = $this->generateReferenceNumber('DISP');
            
            // Get account IDs
            $cash_account_id = self::CASH_ACCOUNT;
            $asset_account_id = $this->getAccountIdByCode($asset_account_code);
            $accumulated_depreciation_id = $this->getAccountIdByCode($depreciation_account_code);
            $other_income_id = self::OTHER_INCOME;
            
            if (!$asset_account_id || !$accumulated_depreciation_id) {
                return "Account mapping error - Asset disposal accounts not found";
            }
            
            // Prepare transaction data
            $data = [
                'transaction_date' => $date,
                'description' => "Asset Disposal - $asset_name",
                'reference_no' => $reference_no,
                'transaction_type' => 'Receipt'
            ];
            
            // Prepare journal entries
            $entries = [];
            
            // Cash received (if any)
            if ($disposal_amount > 0) {
                $entries[] = [
                    'account_id' => $cash_account_id,
                    'debit' => $disposal_amount,
                    'credit' => 0,
                    'description' => "Cash received from asset disposal"
                ];
            }
            
            // Remove accumulated depreciation
            $entries[] = [
                'account_id' => $accumulated_depreciation_id,
                'debit' => $asset['accumulated_depreciation'],
                'credit' => 0,
                'description' => "Remove accumulated depreciation"
            ];
            
            // Remove asset cost
            $entries[] = [
                'account_id' => $asset_account_id,
                'debit' => 0,
                'credit' => $asset['purchase_amount'],
                'description' => "Remove asset cost"
            ];
            
            // Handle gain/loss
            if ($gain_loss != 0) {
                if ($gain_loss > 0) {
                    // Gain on disposal
                    $entries[] = [
                        'account_id' => $other_income_id,
                        'debit' => 0,
                        'credit' => abs($gain_loss),
                        'description' => "Gain on asset disposal"
                    ];
                } else {
                    // Loss on disposal
                    $entries[] = [
                        'account_id' => $other_income_id,
                        'debit' => abs($gain_loss),
                        'credit' => 0,
                        'description' => "Loss on asset disposal"
                    ];
                }
            }
            
            // Create transaction using parent method
            return $this->createTransaction($data, $entries);
            
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Get asset account code by category
     */
    private function getAssetAccountByCategory($category) {
        $mapping = [
            'Furniture' => 'FA001',
            'Fixtures' => 'FA001', 
            'Equipment' => 'FA002',
            'Electronics' => 'FA003',
            'Others' => 'FA004'
        ];
        
        return $mapping[$category] ?? 'FA004';
    }

    /**
     * Get depreciation account code by category
     */
    private function getDepreciationAccountByCategory($category) {
        $mapping = [
            'Furniture' => 'DEP001',
            'Fixtures' => 'DEP001',
            'Equipment' => 'DEP002', 
            'Electronics' => 'DEP003',
            'Others' => 'DEP004'
        ];
        
        return $mapping[$category] ?? 'DEP004';
    }

    /**
     * Get account ID by account code
     */
    private function getAccountIdByCode($account_code) {
        $sql = "SELECT id FROM tbl_accounts WHERE account_code = '$account_code' AND is_active = 1";
        $result = $this->db->select($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return null;
    }

    /**
     * Generate reference number with sequence
     */
    private function generateReferenceNumber($type) {
        // Get current sequence
        $sql = "SELECT last_number FROM tbl_reference_sequences WHERE sequence_type = '$type'";
        $result = $this->db->select($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $next_number = $row['last_number'] + 1;
        } else {
            // Create new sequence if doesn't exist
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
}
?>