<?php
// Summary Report
$stats = $report->getDashboardStats();
$agingSummary = $report->getAgingSummary();
$recentTransactions = $report->getRecentTransactions(5);
?>

<div class="report-section">
    <div class="report-header">
        <h3>Accounts Receivable Summary</h3>
        <div class="report-period">
            Period: <?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?>
        </div>
    </div>
    
    <div class="report-stats">
        <div class="report-stat-card">
            <h4>Total Outstanding Receivables</h4>
            <div class="value"><?php echo formatCurrency($stats['total_outstanding']); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Customers with Balance</h4>
            <div class="value"><?php echo $stats['customers_with_balance']; ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Payments Today</h4>
            <div class="value"><?php echo formatCurrency($stats['payments_today']); ?></div>
        </div>
        
        <div class="report-stat-card">
            <h4>Overdue Accounts</h4>
            <div class="value"><?php echo $stats['overdue_accounts']; ?></div>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Aging Summary</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Aging Bucket</th>
                        <th>Transactions</th>
                        <th>Amount</th>
                        <th>Customers</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalAmount = array_sum(array_column($agingSummary, 'total_amount'));
                    foreach ($agingSummary as $aging): 
                        $percentage = $totalAmount > 0 ? ($aging['total_amount'] / $totalAmount) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo $aging['aging_bucket']; ?></td>
                            <td><?php echo $aging['transaction_count']; ?></td>
                            <td><?php echo formatCurrency($aging['total_amount']); ?></td>
                            <td><?php echo $aging['customer_count']; ?></td>
                            <td><?php echo round($percentage, 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Recent Transactions</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td>#<?php echo $tx['transaction_id']; ?></td>
                            <td><?php echo $tx['full_name']; ?></td>
                            <td><?php echo formatCurrency($tx['total_amount']); ?></td>
                            <td><?php echo formatDate($tx['transaction_date']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $tx['status']; ?>">
                                    <?php echo ucfirst($tx['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
