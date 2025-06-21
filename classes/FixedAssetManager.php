<?php
$filepath = realpath(dirname(__FILE__));

include_once($filepath . "/../libs/CrudOperation.php");
include_once($filepath . "/../classes/accounting/AccountingIntegration.php");

class FixedAssetManager {
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

    /**
     * Add new fixed asset
     */
    public function addFixedAsset($data) {
        // Start transaction
        $this->db->link->begin_transaction();
        
        try {
            // Validate and sanitize input
            $asset_code = $this->generateAssetCode($data['category']);
            $asset_name = mysqli_real_escape_string($this->db->link, $data['asset_name']);
            $category = mysqli_real_escape_string($this->db->link, $data['category']);
            $purchase_date = mysqli_real_escape_string($this->db->link, $data['purchase_date']);
            $purchase_amount = floatval($data['purchase_amount']);
            $depreciation_rate = floatval($data['depreciation_rate'] ?? 10.00);
            $vendor_name = mysqli_real_escape_string($this->db->link, $data['vendor_name'] ?? '');
            $invoice_number = mysqli_real_escape_string($this->db->link, $data['invoice_number'] ?? '');
            $warranty_period = intval($data['warranty_period'] ?? 0);
            $location = mysqli_real_escape_string($this->db->link, $data['location'] ?? '');
            $remarks = mysqli_real_escape_string($this->db->link, $data['remarks'] ?? '');
            
            // Insert asset record
            $sql = "INSERT INTO tbl_fixed_assets (
                        asset_code, asset_name, category, purchase_date, purchase_amount,
                        depreciation_rate, book_value, vendor_name, invoice_number,
                        warranty_period, location, remarks
                    ) VALUES (
                        '$asset_code', '$asset_name', '$category', '$purchase_date', '$purchase_amount',
                        '$depreciation_rate', '$purchase_amount', '$vendor_name', '$invoice_number',
                        '$warranty_period', '$location', '$remarks'
                    )";
            
            $inserted = $this->db->insert($sql);
            
            if (!$inserted) {
                throw new Exception("Failed to insert asset record");
            }
            
            // Get the inserted asset ID
            $asset_id = $this->db->link->insert_id;
            
            // Create accounting entry for asset purchase
            $acc_result = $this->accounting->postAssetPurchase(
                $asset_id,
                $purchase_amount,
                $purchase_date,
                $asset_name,
                $category
            );
            
            if (is_numeric($acc_result)) {
                // Update asset record with accounting reference
                $update_sql = "UPDATE tbl_fixed_assets 
                             SET accounting_transaction_id = '$acc_result' 
                             WHERE id = '$asset_id'";
                $this->db->update($update_sql);
                
                // Commit transaction
                $this->db->link->commit();
                return $asset_id;
            } else {
                throw new Exception("Accounting entry failed: " . $acc_result);
            }
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->link->rollback();
            return false;
        }
    }

    /**
     * Get all fixed assets with optional filtering
     */
    public function getFixedAssets($category = null, $status = 'Active') {
        $sql = "SELECT fa.*, 
                CASE 
                    WHEN fa.accounting_transaction_id IS NOT NULL THEN 'Posted'
                    ELSE 'Not Posted'
                END as accounting_status,
                (fa.purchase_amount - fa.accumulated_depreciation) as current_book_value
                FROM tbl_fixed_assets fa 
                WHERE fa.status = '$status'";

        if ($category) {
            $sql .= " AND fa.category = '$category'";
        }

        $sql .= " ORDER BY fa.purchase_date DESC";

        return $this->db->select($sql);
    }

    /**
     * Get fixed asset by ID
     */
    public function getFixedAssetById($id) {
        $sql = "SELECT fa.*,
                (fa.purchase_amount - fa.accumulated_depreciation) as current_book_value,
                CASE 
                    WHEN fa.accounting_transaction_id IS NOT NULL THEN 'Posted'
                    ELSE 'Not Posted'
                END as accounting_status
                FROM tbl_fixed_assets fa 
                WHERE fa.id = '$id'";
        
        $result = $this->db->select($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Calculate monthly depreciation for all active assets
     */
    public function calculateMonthlyDepreciation($as_of_date = null) {
        if (!$as_of_date) {
            $as_of_date = date('Y-m-d');
        }
        
        $sql = "SELECT id, asset_code, asset_name, purchase_amount, depreciation_rate, 
                       accumulated_depreciation, purchase_date
                FROM tbl_fixed_assets 
                WHERE status = 'Active' 
                AND purchase_date <= '$as_of_date'";
        
        $assets = $this->db->select($sql);
        $depreciation_entries = [];
        
        if ($assets) {
            while ($asset = $assets->fetch_assoc()) {
                $monthly_depreciation = ($asset['purchase_amount'] * $asset['depreciation_rate'] / 100) / 12;
                
                // Check if depreciation already posted for this month
                if (!$this->isDepreciationPostedForMonth($asset['id'], $as_of_date)) {
                    $depreciation_entries[] = [
                        'asset_id' => $asset['id'],
                        'asset_code' => $asset['asset_code'],
                        'asset_name' => $asset['asset_name'],
                        'monthly_depreciation' => round($monthly_depreciation, 2),
                        'accumulated_before' => $asset['accumulated_depreciation'],
                        'accumulated_after' => $asset['accumulated_depreciation'] + $monthly_depreciation,
                        'book_value_after' => $asset['purchase_amount'] - ($asset['accumulated_depreciation'] + $monthly_depreciation)
                    ];
                }
            }
        }
        
        return $depreciation_entries;
    }

    /**
     * Post monthly depreciation entries
     */
    public function postMonthlyDepreciation($as_of_date = null) {
        if (!$as_of_date) {
            $as_of_date = date('Y-m-d');
        }
        
        $depreciation_entries = $this->calculateMonthlyDepreciation($as_of_date);
        
        if (empty($depreciation_entries)) {
            return "No depreciation entries to post for " . date('F Y', strtotime($as_of_date));
        }
        
        $this->db->link->begin_transaction();
        
        try {
            $posted_count = 0;
            
            foreach ($depreciation_entries as $entry) {
                // Insert depreciation schedule record
                $schedule_sql = "INSERT INTO tbl_depreciation_schedule 
                               (asset_id, depreciation_date, depreciation_amount, 
                                accumulated_depreciation, book_value_after, status) 
                               VALUES 
                               ('{$entry['asset_id']}', '$as_of_date', '{$entry['monthly_depreciation']}',
                                '{$entry['accumulated_after']}', '{$entry['book_value_after']}', 'Pending')";
                
                $schedule_inserted = $this->db->insert($schedule_sql);
                
                if ($schedule_inserted) {
                    $schedule_id = $this->db->link->insert_id;
                    
                    // Create accounting entry
                    $acc_result = $this->accounting->postDepreciation(
                        $entry['asset_id'],
                        $entry['monthly_depreciation'],
                        $as_of_date,
                        $entry['asset_name']
                    );
                    
                    if (is_numeric($acc_result)) {
                        // Update schedule with accounting reference
                        $update_schedule = "UPDATE tbl_depreciation_schedule 
                                          SET accounting_transaction_id = '$acc_result', status = 'Posted'
                                          WHERE id = '$schedule_id'";
                        $this->db->update($update_schedule);
                        
                        // Update asset accumulated depreciation
                        $update_asset = "UPDATE tbl_fixed_assets 
                                       SET accumulated_depreciation = '{$entry['accumulated_after']}',
                                           book_value = '{$entry['book_value_after']}'
                                       WHERE id = '{$entry['asset_id']}'";
                        $this->db->update($update_asset);
                        
                        $posted_count++;
                    } else {
                        throw new Exception("Failed to post accounting entry for asset ID: " . $entry['asset_id']);
                    }
                }
            }
            
            $this->db->link->commit();
            return "Successfully posted depreciation for $posted_count assets for " . date('F Y', strtotime($as_of_date));
            
        } catch (Exception $e) {
            $this->db->link->rollback();
            return "Error posting depreciation: " . $e->getMessage();
        }
    }

    /**
     * Dispose/scrap asset
     */
    public function disposeAsset($asset_id, $disposal_date, $disposal_amount, $disposal_type = 'Disposed', $remarks = '') {
        $this->db->link->begin_transaction();
        
        try {
            // Get asset details
            $asset = $this->getFixedAssetById($asset_id);
            if (!$asset) {
                throw new Exception("Asset not found");
            }
            
            if ($asset['status'] != 'Active') {
                throw new Exception("Only active assets can be disposed. Current status: " . $asset['status']);
            }
            
            // Update asset status and remarks
            $escaped_remarks = mysqli_real_escape_string($this->db->link, $remarks);
            $escaped_disposal_type = mysqli_real_escape_string($this->db->link, $disposal_type);
            $existing_remarks = mysqli_real_escape_string($this->db->link, $asset['remarks']);
            
            $new_remarks = $existing_remarks;
            if (!empty($escaped_remarks)) {
                $new_remarks .= ($existing_remarks ? ' | ' : '') . "Disposed on $disposal_date: $escaped_remarks";
            }
            
            $update_sql = "UPDATE tbl_fixed_assets 
                         SET status = '$escaped_disposal_type',
                             remarks = '$new_remarks'
                         WHERE id = '$asset_id'";
            
            $updated = $this->db->update($update_sql);
            
            if (!$updated) {
                throw new Exception("Failed to update asset status");
            }
            
            // Create accounting entry for disposal (if accounting integration exists)
            if (method_exists($this->accounting, 'postAssetDisposal')) {
                $acc_result = $this->accounting->postAssetDisposal(
                    $asset_id,
                    $disposal_amount,
                    $asset['current_book_value'],
                    $disposal_date,
                    $asset['asset_name']
                );
                
                // Note: Even if accounting fails, we don't rollback the disposal
                // The asset disposal is more important than the accounting entry
                if (!is_numeric($acc_result)) {
                    error_log("Asset disposal accounting failed for asset ID $asset_id: $acc_result");
                }
            }
            
            $this->db->link->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->link->rollback();
            error_log("Asset disposal failed for asset ID $asset_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get depreciation schedule for an asset
     */
    public function getDepreciationSchedule($asset_id = null, $start_date = null, $end_date = null) {
        $sql = "SELECT ds.*, fa.asset_code, fa.asset_name,
                t.reference_no as accounting_ref
                FROM tbl_depreciation_schedule ds
                JOIN tbl_fixed_assets fa ON ds.asset_id = fa.id
                LEFT JOIN tbl_transactions t ON ds.accounting_transaction_id = t.id
                WHERE 1=1";
        
        if ($asset_id) {
            $sql .= " AND ds.asset_id = '$asset_id'";
        }
        
        if ($start_date && $end_date) {
            $sql .= " AND ds.depreciation_date BETWEEN '$start_date' AND '$end_date'";
        }
        
        $sql .= " ORDER BY ds.depreciation_date DESC";
        
        return $this->db->select($sql);
    }

    /**
     * Get asset summary by category
     */
    public function getAssetSummaryByCategory() {
        $sql = "SELECT 
                category,
                COUNT(*) as asset_count,
                SUM(purchase_amount) as total_cost,
                SUM(accumulated_depreciation) as total_depreciation,
                SUM(book_value) as total_book_value,
                ROUND(AVG(depreciation_rate), 2) as avg_depreciation_rate,
                SUM(CASE WHEN accounting_transaction_id IS NOT NULL THEN 1 ELSE 0 END) as posted_assets
                FROM tbl_fixed_assets
                WHERE status = 'Active'
                GROUP BY category
                ORDER BY total_cost DESC";
        
        return $this->db->select($sql);
    }

    /**
     * Generate unique asset code
     */
    private function generateAssetCode($category) {
        $prefix = strtoupper(substr($category, 0, 3));
        
        // Get next sequence number
        $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(asset_code, 4) AS UNSIGNED)), 0) + 1 as next_num
                FROM tbl_fixed_assets 
                WHERE asset_code LIKE '$prefix%'";
        
        $result = $this->db->select($sql);
        $row = $result->fetch_assoc();
        $next_num = str_pad($row['next_num'], 3, '0', STR_PAD_LEFT);
        
        return $prefix . $next_num;
    }

    /**
     * Check if depreciation already posted for month
     */
    private function isDepreciationPostedForMonth($asset_id, $date) {
        $year_month = date('Y-m', strtotime($date));
        
        $sql = "SELECT COUNT(*) as count 
                FROM tbl_depreciation_schedule 
                WHERE asset_id = '$asset_id' 
                AND DATE_FORMAT(depreciation_date, '%Y-%m') = '$year_month'
                AND status = 'Posted'";
        
        $result = $this->db->select($sql);
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }

    /**
     * Get assets requiring maintenance (based on warranty period)
     */
    public function getAssetsRequiringMaintenance() {
        $sql = "SELECT *,
                DATEDIFF(CURDATE(), purchase_date) as days_since_purchase,
                (warranty_period * 365) as warranty_days
                FROM tbl_fixed_assets
                WHERE status = 'Active'
                AND warranty_period > 0
                AND DATEDIFF(CURDATE(), purchase_date) > (warranty_period * 365)
                ORDER BY purchase_date ASC";
        
        return $this->db->select($sql);
    }

    /**
     * Update asset details
     */
    public function updateAsset($asset_id, $data) {
        $asset_name = mysqli_real_escape_string($this->db->link, $data['asset_name']);
        $location = mysqli_real_escape_string($this->db->link, $data['location']);
        $remarks = mysqli_real_escape_string($this->db->link, $data['remarks']);
        $depreciation_rate = floatval($data['depreciation_rate']);
        
        $sql = "UPDATE tbl_fixed_assets 
                SET asset_name = '$asset_name',
                    location = '$location',
                    remarks = '$remarks',
                    depreciation_rate = '$depreciation_rate'
                WHERE id = '$asset_id'";
        
        return $this->db->update($sql);
    }

    /**
     * Get total asset value
     */
    public function getTotalAssetValue($as_of_date = null) {
        if (!$as_of_date) {
            $as_of_date = date('Y-m-d');
        }
        
        $sql = "SELECT 
                SUM(purchase_amount) as total_cost,
                SUM(accumulated_depreciation) as total_depreciation,
                SUM(book_value) as total_book_value
                FROM tbl_fixed_assets 
                WHERE status = 'Active'
                AND purchase_date <= '$as_of_date'";
        
        $result = $this->db->select($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            
            // Ensure we return proper values even if no assets exist
            return [
                'total_cost' => floatval($row['total_cost'] ?? 0),
                'total_depreciation' => floatval($row['total_depreciation'] ?? 0),
                'total_book_value' => floatval($row['total_book_value'] ?? 0)
            ];
        }
        
        // Return zero values if query fails
        return [
            'total_cost' => 0,
            'total_depreciation' => 0,
            'total_book_value' => 0
        ];
    }
}