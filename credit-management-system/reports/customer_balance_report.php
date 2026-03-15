<?php
// Customer Balance Report
$customerBalances = $report->getCustomerBalanceReport();
$totalCustomers = count($customerBalances);
$customersWithBalance = count(array_filter($customerBalances, fn($c) => $c['current_balance'] > 0));
$totalOutstanding = array_sum(array_column($customerBalances, 'current_balance'));
$totalCreditLimit = array_sum(array_column($customerBalances, 'credit_limit'));
$avgUtilization = $totalCreditLimit > 0 ? ($totalOutstanding / $totalCreditLimit) * 100 : 0;
?>

<div class="report-section">
    <div class="report-header">
        <h3>Customer Balance Report</h3>
        <div class="report-period">
            As of: <?php echo date('M d, Y'); ?>
        </div>
    </div>
    
    <div class="report-stats">
        <div class="report-stat-card">
            <h4>Total Customers</h4>
            <div class="value"><?php echo $totalCustomers; ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Customers with Balance</h4>
            <div class="value"><?php echo $customersWithBalance; ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Total Outstanding</h4>
            <div class="value"><?php echo formatCurrency($totalOutstanding); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Avg Credit Utilization</h4>
            <div class="value"><?php echo round($avgUtilization, 1); ?>%</div>
        </div>
    </div>
    
    <div class="chart-container">
        <h4>Credit Utilization Distribution</h4>
        <div class="chart-placeholder">
            <i class="fas fa-chart-pie fa-3x"></i>
            <p>Credit utilization distribution chart</p>
            <small>Shows customers by utilization ranges</small>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Customer Balance Details</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Credit Limit</th>
                        <th>Current Balance</th>
                        <th>Available Credit</th>
                        <th>Utilization %</th>
                        <th>Payment Terms</th>
                        <th>Risk Level</th>
                        <th>Risk Assessment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customerBalances)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No customers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customerBalances as $customer): 
                            $availableCredit = $customer['credit_limit'] - $customer['current_balance'];
                            $utilization = $customer['credit_limit'] > 0 ? ($customer['current_balance'] / $customer['credit_limit']) * 100 : 0;
                        ?>
                            <tr class="risk-<?php echo $customer['risk_level']; ?>">
                                <td><?php echo $customer['full_name']; ?></td>
                                <td><?php echo $customer['phone_number'] ?: 'N/A'; ?></td>
                                <td><?php echo formatCurrency($customer['credit_limit']); ?></td>
                                <td><?php echo formatCurrency($customer['current_balance']); ?></td>
                                <td><?php echo formatCurrency($availableCredit); ?></td>
                                <td>
                                    <div class="utilization-bar">
                                        <div class="utilization-fill" style="width: <?php echo min($utilization, 100); ?>%; background: <?php echo $utilization > 80 ? '#dc3545' : ($utilization > 50 ? '#ffc107' : '#28a745'); ?>"></div>
                                        <span class="utilization-text"><?php echo round($utilization); ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo $customer['payment_terms']; ?> days</td>
                                <td>
                                    <span class="risk-badge risk-<?php echo $customer['risk_classification']; ?>">
                                        <?php echo ucfirst($customer['risk_classification']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="risk-assessment risk-<?php echo $customer['risk_level']; ?>">
                                        <?php echo $customer['risk_level']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Balance Distribution Summary</h4>
        <div class="balance-distribution">
            <?php
            $distribution = [
                'No Balance' => 0,
                'Low Risk (<50%)' => 0,
                'Medium Risk (50-80%)' => 0,
                'High Risk (>80%)' => 0
            ];
            
            foreach ($customerBalances as $customer) {
                $utilization = $customer['credit_limit'] > 0 ? ($customer['current_balance'] / $customer['credit_limit']) * 100 : 0;
                if ($customer['current_balance'] <= 0) {
                    $distribution['No Balance']++;
                } elseif ($utilization < 50) {
                    $distribution['Low Risk (<50%)']++;
                } elseif ($utilization < 80) {
                    $distribution['Medium Risk (50-80%)']++;
                } else {
                    $distribution['High Risk (>80%)']++;
                }
            }
            ?>
            
            <div class="distribution-grid">
                <?php foreach ($distribution as $category => $count): ?>
                    <div class="distribution-item">
                        <h5><?php echo $category; ?></h5>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: <?php echo ($count / $totalCustomers) * 100; ?>%;"></div>
                        </div>
                        <span class="distribution-count"><?php echo $count; ?> customers</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.utilization-bar {
    position: relative;
    width: 100px;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.utilization-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.utilization-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.7rem;
    font-weight: 600;
    color: #333;
}

.risk-assessment {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.risk-assessment.risk-no-balance {
    background: #d4edda;
    color: #155724;
}

.risk-assessment.risk-low-risk {
    background: #d4edda;
    color: #155724;
}

.risk-assessment.risk-medium-risk {
    background: #fff3cd;
    color: #856404;
}

.risk-assessment.risk-high-risk {
    background: #f8d7da;
    color: #721c24;
}

.balance-distribution {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.distribution-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.distribution-item h5 {
    margin-bottom: 8px;
    color: #333;
    font-size: 0.9rem;
}

.distribution-bar {
    width: 100%;
    height: 25px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 5px;
}

.distribution-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s ease;
}

.distribution-count {
    font-size: 0.8rem;
    color: #666;
}
</style>
