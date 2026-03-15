<?php
// Payment Collection Report
$paymentData = $report->getPaymentCollectionReport($startDate, $endDate);
$todayPayments = $report->getTodayPayments();
?>

<div class="report-section">
    <div class="report-header">
        <h3>Payment Collection Report</h3>
        <div class="report-period">
            Period: <?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?>
        </div>
    </div>
    
    <div class="report-stats">
        <div class="report-stat-card">
            <h4>Total Collected</h4>
            <div class="value"><?php echo formatCurrency(array_sum(array_column($paymentData, 'total_collected'))); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Total Payments</h4>
            <div class="value"><?php echo array_sum(array_column($paymentData, 'payment_count')); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Today's Collection</h4>
            <div class="value"><?php echo formatCurrency($todayPayments['total'] ?? 0); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Today's Payments</h4>
            <div class="value"><?php echo $todayPayments['count'] ?? 0; ?></div>
        </div>
    </div>
    
    <div class="chart-container">
        <h4>Payment Trends</h4>
        <div class="chart-placeholder">
            <i class="fas fa-chart-line fa-3x"></i>
            <p>Payment collection trend chart</p>
            <small>Daily/weekly payment collection trends</small>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Payment Method Breakdown</h4>
        <div class="payment-methods">
            <?php
            $methodTotals = [];
            foreach ($paymentData as $payment) {
                if (!isset($methodTotals[$payment['payment_method']])) {
                    $methodTotals[$payment['payment_method']] = [
                        'amount' => 0,
                        'count' => 0
                    ];
                }
                $methodTotals[$payment['payment_method']]['amount'] += $payment['total_collected'];
                $methodTotals[$payment['payment_method']]['count'] += $payment['payment_count'];
            }
            
            $totalCollected = array_sum(array_column($methodTotals, 'amount'));
            ?>
            
            <div class="method-grid">
                <?php foreach ($methodTotals as $method => $data): 
                    $percentage = $totalCollected > 0 ? ($data['amount'] / $totalCollected) * 100 : 0;
                ?>
                    <div class="method-card">
                        <h5><?php echo ucfirst(str_replace('_', ' ', $method)); ?></h5>
                        <div class="method-amount"><?php echo formatCurrency($data['amount']); ?></div>
                        <div class="method-count"><?php echo $data['count']; ?> payments</div>
                        <div class="method-percentage"><?php echo round($percentage, 1); ?>%</div>
                        <div class="method-bar">
                            <div class="method-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Daily Payment Summary</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Total Collected</th>
                        <th>Payment Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentData)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No payments found in this period</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Group payments by date for better display
                        $groupedPayments = [];
                        foreach ($paymentData as $payment) {
                            $date = $payment['collection_date'];
                            if (!isset($groupedPayments[$date])) {
                                $groupedPayments[$date] = [];
                            }
                            $groupedPayments[$date][] = $payment;
                        }
                        
                        foreach ($groupedPayments as $date => $payments): 
                            $dailyTotal = array_sum(array_column($payments, 'total_collected'));
                            $dailyCount = array_sum(array_column($payments, 'payment_count'));
                        ?>
                            <tr class="date-row">
                                <td colspan="4">
                                    <strong><?php echo formatDate($date); ?></strong>
                                    <span class="daily-summary">
                                        (<?php echo $dailyCount; ?> payments, <?php echo formatCurrency($dailyTotal); ?>)
                                    </span>
                                </td>
                            </tr>
                            <?php foreach ($payments as $payment): ?>
                                <tr class="payment-detail">
                                    <td></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td><?php echo formatCurrency($payment['total_collected']); ?></td>
                                    <td><?php echo $payment['payment_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Collection Performance</h4>
        <div class="performance-metrics">
            <?php
            $avgPaymentPerTransaction = count($paymentData) > 0 ? 
                array_sum(array_column($paymentData, 'total_collected')) / array_sum(array_column($paymentData, 'payment_count')) : 0;
            
            $daysInPeriod = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
            $avgDailyCollection = $daysInPeriod > 0 ? array_sum(array_column($paymentData, 'total_collected')) / $daysInPeriod : 0;
            ?>
            
            <div class="metric-grid">
                <div class="metric-card">
                    <h5>Average Payment Size</h5>
                    <div class="metric-value"><?php echo formatCurrency($avgPaymentPerTransaction); ?></div>
                </div>
                
                <div class="metric-card">
                    <h5>Daily Average Collection</h5>
                    <div class="metric-value"><?php echo formatCurrency($avgDailyCollection); ?></div>
                </div>
                
                <div class="metric-card">
                    <h5>Most Used Method</h5>
                    <div class="metric-value">
                        <?php 
                        $mostUsedMethod = array_keys($methodTotals, max($methodTotals))[0] ?? 'N/A';
                        echo ucfirst(str_replace('_', ' ', $mostUsedMethod));
                        ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h5>Collection Days</h5>
                    <div class="metric-value"><?php echo $daysInPeriod; ?> days</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-methods {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.method-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.method-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.method-card h5 {
    margin-bottom: 10px;
    color: #333;
}

.method-amount {
    font-size: 1.3rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.method-count {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.method-percentage {
    font-size: 0.8rem;
    color: #28a745;
    font-weight: 600;
    margin-bottom: 10px;
}

.method-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.method-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s ease;
}

.date-row {
    background: #f8f9fa;
    font-weight: 600;
}

.date-row td {
    padding: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.daily-summary {
    font-weight: normal;
    color: #666;
    margin-left: 10px;
}

.payment-detail td {
    padding-left: 30px;
}

.performance-metrics {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.metric-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.metric-card h5 {
    margin-bottom: 10px;
    color: #666;
    font-size: 0.9rem;
}

.metric-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}
</style>
