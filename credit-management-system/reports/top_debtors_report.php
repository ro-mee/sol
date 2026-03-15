<?php
// Top Debtors Report
$topDebtors = $report->getTopDebtors(20);
$totalDebtors = count($topDebtors);
$totalOverdueAmount = array_sum(array_column($topDebtors, 'total_outstanding'));
$avgOverdue = $totalDebtors > 0 ? $totalOverdueAmount / $totalDebtors : 0;
$avgDaysOverdue = $totalDebtors > 0 ? array_sum(array_column($topDebtors, 'avg_days_overdue')) / $totalDebtors : 0;
?>

<div class="report-section">
    <div class="report-header">
        <h3>Top Debtors Report</h3>
        <div class="report-period">
            As of: <?php echo date('M d, Y'); ?>
        </div>
    </div>
    
    <div class="report-stats">
        <div class="report-stat-card">
            <h4>Total Debtors Listed</h4>
            <div class="value"><?php echo $totalDebtors; ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Total Overdue Amount</h4>
            <div class="value"><?php echo formatCurrency($totalOverdueAmount); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Average Overdue</h4>
            <div class="value"><?php echo formatCurrency($avgOverdue); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Average Days Overdue</h4>
            <div class="value"><?php echo round($avgDaysOverdue); ?> days</div>
        </div>
    </div>
    
    <div class="chart-container">
        <h4>Top 10 Debtors Visualization</h4>
        <div class="chart-placeholder">
            <i class="fas fa-chart-bar fa-3x"></i>
            <p>Top debtors bar chart</p>
            <small>Shows the top 10 customers by overdue amount</small>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Debtor Risk Analysis</h4>
        <div class="risk-analysis">
            <?php
            $riskDistribution = [
                'Low Risk' => 0,
                'Medium Risk' => 0,
                'High Risk' => 0
            ];
            
            foreach ($topDebtors as $debtor) {
                if ($debtor['avg_days_overdue'] > 60 || $debtor['total_outstanding'] > 50000) {
                    $riskDistribution['High Risk']++;
                } elseif ($debtor['avg_days_overdue'] > 30 || $debtor['total_outstanding'] > 20000) {
                    $riskDistribution['Medium Risk']++;
                } else {
                    $riskDistribution['Low Risk']++;
                }
            }
            ?>
            
            <div class="risk-grid">
                <?php foreach ($riskDistribution as $risk => $count): 
                    $percentage = $totalDebtors > 0 ? ($count / $totalDebtors) * 100 : 0;
                ?>
                    <div class="risk-card risk-<?php echo strtolower(str_replace(' ', '-', $risk)); ?>">
                        <h5><?php echo $risk; ?></h5>
                        <div class="risk-count"><?php echo $count; ?> debtors</div>
                        <div class="risk-percentage"><?php echo round($percentage, 1); ?>%</div>
                        <div class="risk-bar">
                            <div class="risk-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Detailed Debtor List</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Outstanding Amount</th>
                        <th>Outstanding Transactions</th>
                        <th>Average Days Overdue</th>
                        <th>Current Balance</th>
                        <th>Credit Limit</th>
                        <th>Risk Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topDebtors)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No debtors found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topDebtors as $index => $debtor): 
                            $riskScore = calculateRiskScore($debtor);
                            $riskLevel = getRiskLevel($riskScore);
                        ?>
                            <tr class="risk-<?php echo strtolower(str_replace(' ', '-', $riskLevel)); ?>">
                                <td><strong>#<?php echo $index + 1; ?></strong></td>
                                <td>
                                    <a href="customers.php?action=view&id=<?php echo $debtor['customer_id']; ?>" class="customer-link">
                                        <?php echo $debtor['full_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo $debtor['phone_number'] ?: 'N/A'; ?></td>
                                <td>
                                    <span class="overdue-amount">
                                        <?php echo formatCurrency($debtor['total_outstanding']); ?>
                                    </span>
                                </td>
                                <td><?php echo $debtor['outstanding_transactions']; ?></td>
                                <td>
                                    <span class="days-overdue days-<?php echo $debtor['avg_days_overdue'] > 60 ? 'critical' : ($debtor['avg_days_overdue'] > 30 ? 'high' : 'medium'); ?>">
                                        <?php echo round($debtor['avg_days_overdue']); ?> days
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($debtor['current_balance']); ?></td>
                                <td><?php echo formatCurrency($debtor['credit_limit']); ?></td>
                                <td>
                                    <div class="risk-score">
                                        <div class="score-value"><?php echo $riskScore; ?>/100</div>
                                        <div class="score-bar">
                                            <div class="score-fill" style="width: <?php echo $riskScore; ?>%; background: <?php echo getRiskColor($riskScore); ?>;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="payments.php?action=add&customer_id=<?php echo $debtor['customer_id']; ?>" class="btn btn-sm btn-success" title="Receive Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <a href="customers.php?action=view&id=<?php echo $debtor['customer_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="sendDebtorReminder(<?php echo $debtor['customer_id']; ?>)" class="btn btn-sm btn-warning" title="Send Reminder">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Collection Recommendations</h4>
        <div class="recommendations">
            <div class="recommendation-card">
                <h5><i class="fas fa-exclamation-triangle"></i> Immediate Action Required</h5>
                <p>Contact customers with overdue amounts exceeding ₱50,000 or more than 60 days overdue immediately.</p>
            </div>
            
            <div class="recommendation-card">
                <h5><i class="fas fa-clock"></i> Schedule Follow-ups</h5>
                <p>Set up automated reminders for customers with 30-60 days overdue amounts.</p>
            </div>
            
            <div class="recommendation-card">
                <h5><i class="fas fa-chart-line"></i> Monitor Trends</h5>
                <p>Regularly review debtor patterns to identify potential payment issues early.</p>
            </div>
        </div>
    </div>
</div>

<?php
function calculateRiskScore($debtor) {
    $score = 0;
    
    // Amount overdue (40% weight)
    if ($debtor['total_outstanding'] > 100000) $score += 40;
    elseif ($debtor['total_outstanding'] > 50000) $score += 30;
    elseif ($debtor['total_outstanding'] > 20000) $score += 20;
    elseif ($debtor['total_outstanding'] > 0) $score += 10;
    
    // Days overdue (40% weight)
    if ($debtor['avg_days_overdue'] > 90) $score += 40;
    elseif ($debtor['avg_days_overdue'] > 60) $score += 30;
    elseif ($debtor['avg_days_overdue'] > 30) $score += 20;
    elseif ($debtor['avg_days_overdue'] > 0) $score += 10;
    
    // Number of transactions (20% weight)
    if ($debtor['outstanding_transactions'] > 10) $score += 20;
    elseif ($debtor['outstanding_transactions'] > 5) $score += 15;
    elseif ($debtor['outstanding_transactions'] > 2) $score += 10;
    elseif ($debtor['outstanding_transactions'] > 0) $score += 5;
    
    return min(100, $score);
}

function getRiskLevel($score) {
    if ($score >= 70) return 'High Risk';
    if ($score >= 40) return 'Medium Risk';
    return 'Low Risk';
}

function getRiskColor($score) {
    if ($score >= 70) return '#dc3545';
    if ($score >= 40) return '#ffc107';
    return '#28a745';
}
?>

<style>
.risk-analysis {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.risk-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.risk-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.risk-card h5 {
    margin-bottom: 10px;
    color: #333;
}

.risk-count {
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.risk-percentage {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 10px;
}

.risk-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.risk-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.risk-card.risk-low-risk .risk-fill {
    background: #28a745;
}

.risk-card.risk-medium-risk .risk-fill {
    background: #ffc107;
}

.risk-card.risk-high-risk .risk-fill {
    background: #dc3545;
}

.risk-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.score-value {
    font-size: 0.8rem;
    font-weight: bold;
}

.score-bar {
    width: 50px;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.score-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.recommendations {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.recommendation-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.recommendation-card h5 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: #333;
}

.recommendation-card p {
    color: #666;
    line-height: 1.5;
}

.overdue-amount {
    color: #dc3545;
    font-weight: 600;
}
</style>

<script>
function sendDebtorReminder(customerId) {
    if (confirm('Send payment reminder to this debtor?')) {
        showNotification('Reminder sent successfully!', 'success');
    }
}
</script>
