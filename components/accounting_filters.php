<?php
// components/accounting_filters.php - Reusable filter component for all accounting modules

/**
 * Reusable Accounting Filter Component
 * Provides consistent filtering UI for date ranges and financial periods
 * 
 * Usage:
 * include_once "components/accounting_filters.php";
 * echo renderAccountingFilters($config);
 */

function renderAccountingFilters($config = []) {
    // Default configuration
    $defaults = [
        'show_financial_periods' => true,
        'show_quick_dates' => true,
        'show_export_buttons' => true,
        'default_filter_type' => 'date_range',
        'form_action' => '',
        'form_method' => 'GET',
        'submit_button_text' => 'Generate Report',
        'submit_button_icon' => 'fa-search',
        'export_formats' => ['pdf', 'excel'],
        'additional_fields' => [],
        'css_classes' => [
            'container' => 'filter-card no-print',
            'card_body' => 'card-body',
            'form_id' => 'accountingFilterForm'
        ]
    ];
    
    $config = array_merge($defaults, $config);
    
    // Get current values
    $filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : $config['default_filter_type'];
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    $financialPeriodId = isset($_GET['financial_period_id']) ? $_GET['financial_period_id'] : null;
    $hasData = isset($_GET['start_date']) || isset($_GET['financial_period_id']);
    
    ob_start();
    ?>
    
    <!-- Accounting Filter Component -->
    <div class="<?php echo $config['css_classes']['container']; ?>">
        <div class="<?php echo $config['css_classes']['card_body']; ?>">
            <h5 class="card-title mb-3">
                <i class="fa fa-filter"></i> Filter Options
            </h5>
            
            <form method="<?php echo $config['form_method']; ?>" id="<?php echo $config['css_classes']['form_id']; ?>" 
                  action="<?php echo $config['form_action']; ?>">
                
                <!-- Filter Type Toggle -->
                <?php if ($config['show_financial_periods']): ?>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="filter-toggle">
                            <input type="radio" name="filter_type" value="date_range" id="date_filter"
                                   <?php echo ($filterType == 'date_range') ? 'checked' : ''; ?>>
                            <label for="date_filter">Date Range</label>
                            
                            <input type="radio" name="filter_type" value="financial_period" id="period_filter"
                                   <?php echo ($filterType == 'financial_period') ? 'checked' : ''; ?>>
                            <label for="period_filter">Financial Period</label>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa <?php echo $config['submit_button_icon']; ?>"></i> 
                            <?php echo $config['submit_button_text']; ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearAccountingForm()">
                            <i class="fa fa-times"></i> Clear
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Date Range Filters -->
                <div class="row mt-3" id="dateFilters" 
                     style="<?php echo ($filterType == 'financial_period') ? 'display:none' : ''; ?>">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    
                    <!-- Export Buttons for Date Range -->
                    <?php if ($config['show_export_buttons'] && $hasData): ?>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="btn-group" role="group">
                            <?php foreach ($config['export_formats'] as $format): ?>
                            <a href="?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&export=<?php echo $format; ?>" 
                               class="btn btn-<?php echo $format == 'pdf' ? 'danger' : 'success'; ?>">
                                <i class="fa fa-file-<?php echo $format; ?>"></i> 
                                <?php echo strtoupper($format); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Financial Period Filter -->
                <?php if ($config['show_financial_periods']): ?>
                <div class="row mt-3" id="periodFilters" 
                     style="<?php echo ($filterType == 'date_range') ? 'display:none' : ''; ?>">
                    <div class="col-md-4">
                        <label class="form-label">Financial Period</label>
                        <select name="financial_period_id" class="form-control">
                            <option value="">Select Period</option>
                            <?php
                            // Get financial periods
                            include_once "classes/accounting/AccountingHelper.php";
                            $helper = new AccountingHelper();
                            $periods = $helper->getFinancialPeriods();
                            
                            if ($periods) {
                                while ($period = $periods->fetch_assoc()) {
                                    $selected = ($financialPeriodId == $period['id']) ? 'selected' : '';
                                    $statusText = $period['is_closed'] ? ' (Closed)' : ' (Open)';
                                    echo "<option value='{$period['id']}' {$selected}>";
                                    echo htmlspecialchars($period['period_name'] . $statusText);
                                    echo "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Export Buttons for Financial Period -->
                    <?php if ($config['show_export_buttons'] && $hasData): ?>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="btn-group" role="group">
                            <?php foreach ($config['export_formats'] as $format): ?>
                            <a href="?financial_period_id=<?php echo urlencode($financialPeriodId); ?>&export=<?php echo $format; ?>" 
                               class="btn btn-<?php echo $format == 'pdf' ? 'danger' : 'success'; ?>">
                                <i class="fa fa-file-<?php echo $format; ?>"></i> 
                                <?php echo strtoupper($format); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Quick Date Filters -->
                <?php if ($config['show_quick_dates']): ?>
                <div class="row mt-3" id="quickDateFilters" 
                     style="<?php echo ($filterType == 'financial_period') ? 'display:none' : ''; ?>">
                    <div class="col-12">
                        <label class="form-label">Quick Filters:</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('today')">Today</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('thisMonth')">This Month</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('lastMonth')">Last Month</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('thisYear')">This Year</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('lastYear')">Last Year</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAccountingDateRange('currentFY')">Current FY</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Additional Fields -->
                <?php foreach ($config['additional_fields'] as $field): ?>
                <div class="row mt-3">
                    <?php echo $field; ?>
                </div>
                <?php endforeach; ?>
                
            </form>
        </div>
    </div>

    <!-- Component Styles -->
    <style>
    .filter-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .filter-toggle {
        background: #e9ecef;
        border-radius: 25px;
        padding: 5px;
        display: inline-flex;
        margin-bottom: 15px;
    }

    .filter-toggle label {
        padding: 8px 20px;
        margin: 0;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }

    .filter-toggle input[type="radio"]:checked + label {
        background: #007bff;
        color: white;
    }

    .filter-toggle input[type="radio"] {
        display: none;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }
    </style>

    <!-- Component JavaScript -->
    <script>
    // Toggle between date range and financial period filters
    document.querySelectorAll('input[name="filter_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const dateFilters = document.getElementById('dateFilters');
            const periodFilters = document.getElementById('periodFilters');
            const quickDateFilters = document.getElementById('quickDateFilters');
            
            if (this.value === 'date_range') {
                dateFilters.style.display = 'flex';
                if (quickDateFilters) quickDateFilters.style.display = 'block';
                if (periodFilters) periodFilters.style.display = 'none';
            } else {
                if (dateFilters) dateFilters.style.display = 'none';
                if (quickDateFilters) quickDateFilters.style.display = 'none';
                if (periodFilters) periodFilters.style.display = 'flex';
            }
        });
    });

    // Clear form function
    function clearAccountingForm() {
        document.getElementById('<?php echo $config['css_classes']['form_id']; ?>').reset();
        document.getElementById('dateFilters').style.display = 'flex';
        if (document.getElementById('quickDateFilters')) {
            document.getElementById('quickDateFilters').style.display = 'block';
        }
        if (document.getElementById('periodFilters')) {
            document.getElementById('periodFilters').style.display = 'none';
        }
    }

    // Quick date range functions
    function setAccountingDateRange(range) {
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        const today = new Date();
        
        switch(range) {
            case 'today':
                const todayStr = today.toISOString().split('T')[0];
                startDate.value = todayStr;
                endDate.value = todayStr;
                break;
                
            case 'thisMonth':
                const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                const thisMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                startDate.value = thisMonthStart.toISOString().split('T')[0];
                endDate.value = thisMonthEnd.toISOString().split('T')[0];
                break;
                
            case 'lastMonth':
                const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                startDate.value = lastMonthStart.toISOString().split('T')[0];
                endDate.value = lastMonthEnd.toISOString().split('T')[0];
                break;
                
            case 'thisYear':
                const thisYearStart = new Date(today.getFullYear(), 0, 1);
                const thisYearEnd = new Date(today.getFullYear(), 11, 31);
                startDate.value = thisYearStart.toISOString().split('T')[0];
                endDate.value = thisYearEnd.toISOString().split('T')[0];
                break;
                
            case 'lastYear':
                const lastYearStart = new Date(today.getFullYear() - 1, 0, 1);
                const lastYearEnd = new Date(today.getFullYear() - 1, 11, 31);
                startDate.value = lastYearStart.toISOString().split('T')[0];
                endDate.value = lastYearEnd.toISOString().split('T')[0];
                break;
                
            case 'currentFY':
                // Financial Year Apr 1 - Mar 31
                const currentMonth = today.getMonth();
                let fyStart, fyEnd;
                
                if (currentMonth >= 3) { // Apr onwards
                    fyStart = new Date(today.getFullYear(), 3, 1); // Apr 1
                    fyEnd = new Date(today.getFullYear() + 1, 2, 31); // Mar 31 next year
                } else { // Jan-Mar
                    fyStart = new Date(today.getFullYear() - 1, 3, 1); // Apr 1 prev year
                    fyEnd = new Date(today.getFullYear(), 2, 31); // Mar 31 this year
                }
                
                startDate.value = fyStart.toISOString().split('T')[0];
                endDate.value = fyEnd.toISOString().split('T')[0];
                break;
        }
    }
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * Get filter parameters from request
 * Returns standardized filter data for use in accounting modules
 */
function getAccountingFilterParams() {
    $filterType = isset($_REQUEST['filter_type']) ? $_REQUEST['filter_type'] : 'date_range';
    $startDate = '';
    $endDate = '';
    $financialPeriodId = null;
    $periodInfo = null;
    $errorMessage = '';
    
    if ($filterType == 'financial_period') {
        $financialPeriodId = isset($_REQUEST['financial_period_id']) ? intval($_REQUEST['financial_period_id']) : null;
        
        if ($financialPeriodId) {
            // Get period details
            include_once "classes/accounting/AccountingHelper.php";
            $helper = new AccountingHelper();
            $periodInfo = $helper->getFinancialPeriodById($financialPeriodId);
            
            if ($periodInfo) {
                $startDate = $periodInfo['start_date'];
                $endDate = $periodInfo['end_date'];
            } else {
                $errorMessage = "Financial period not found.";
            }
        } else {
            $errorMessage = "Please select a financial period.";
        }
    } else {
        $startDate = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
        $endDate = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
        
        if (empty($startDate) || empty($endDate)) {
            // Set defaults
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            $errorMessage = "Start date cannot be later than end date.";
        }
    }
    
    return [
        'filter_type' => $filterType,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'financial_period_id' => $financialPeriodId,
        'period_info' => $periodInfo,
        'error_message' => $errorMessage,
        'has_data' => !empty($startDate) && !empty($endDate) && empty($errorMessage)
    ];
}
?>