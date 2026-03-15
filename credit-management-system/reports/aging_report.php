<?php
// Aging Report
$agingData = $report->getAgingReport();
$agingSummary = $report->getAgingSummary();
?>

<div class="report-section">
    <div class="report-header">
        <h3>Aging Report</h3>
        <div class="report-period">
            As of: <?php echo date('M d, Y'); ?>
        </div>
    </div>
    
    <div class="report-stats">
        <?php foreach ($agingSummary as $aging): ?>
            <div class="report-stat-card">
                <h4><?php echo $aging['aging_bucket']; ?></h4>
                <div class="value"><?php echo formatCurrency($aging['total_amount']); ?></div>
                <small><?php echo $aging['transaction_count']; ?> transactions</small>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="chart-container">
        <h4>Aging Distribution</h4>
        <div class="chart-placeholder">
            <i class="fas fa-chart-bar fa-3x"></i>
            <p>Chart visualization would be displayed here</p>
            <small>Integrate with Chart.js or similar library for visual charts</small>
        </div>
    </div>
    
    <div class="report-section">
        <h4>Detailed Aging Breakdown</h4>
        <div class="report-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Transaction Date</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Aging Bucket</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agingData)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No overdue transactions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agingData as $aging): ?>
                            <tr class="aging-<?php echo strtolower(str_replace(' ', '-', $aging['aging_bucket'])); ?>">
                                <td><?php echo $aging['full_name']; ?></td>
                                <td>#<?php echo $aging['transaction_id']; ?></td>
                                <td><?php echo formatCurrency($aging['total_amount']); ?></td>
                                <td><?php echo formatDate($aging['transaction_date']); ?></td>
                                <td><?php echo formatDate($aging['due_date']); ?></td>
                                <td>
                                    <span class="days-overdue days-<?php echo $aging['days_overdue'] > 60 ? 'critical' : ($aging['days_overdue'] > 30 ? 'high' : 'medium'); ?>">
                                        <?php echo $aging['days_overdue']; ?> days
                                    </span>
                                </td>
                                <td><?php echo $aging['aging_bucket']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $aging['status']; ?>">
                                        <?php echo ucfirst($aging['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.aging-current {
    background: #d4edda;
}

.aging-0-30-days {
    background: #fff3cd;
}

.aging-31-60-days {
    background: #f8d7da;
}

.aging-61-90-days {
    background: #f5c6cb;
}

.aging-90+-days {
    background: #d1ecf1;
}
</style>
