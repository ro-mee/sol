<?php
require_once 'config/config.php';
require_once 'models/Report.php';
require_once 'models/Payment.php';
require_once 'models/CreditTransaction.php';

requireLogin();
$page_title = 'Dashboard';

$report = new Report(getDBConnection());
$payment = new Payment(getDBConnection());
$transaction = new CreditTransaction(getDBConnection());

// Get dashboard statistics
$stats = $report->getDashboardStats();
$recentTransactions = $report->getRecentTransactions(10);

// Get today's payments
$todayPayments = $payment->getTodayPayments();

include 'includes/header.php';
?>

<!-- Dashboard Stats Cards -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($stats['total_outstanding']); ?></h3>
            <p>Total Outstanding Receivables</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['customers_with_balance']; ?></h3>
            <p>Customers with Balance</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($todayPayments['total'] ?? 0); ?></h3>
            <p>Payments Today (<?php echo $todayPayments['count'] ?? 0; ?>)</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['overdue_accounts']; ?></h3>
            <p>Overdue Accounts</p>
        </div>
    </div>
</div>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Transactions</h2>
            <a href="transactions.php" class="btn btn-secondary">View All</a>
        </div>
        
        <div class="table-container">
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
                    <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No transactions found</td>
                        </tr>
                    <?php else: ?>
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Quick Actions</h2>
        </div>
        
        <div class="quick-actions">
            <a href="customers.php?action=add" class="action-card">
                <i class="fas fa-user-plus"></i>
                <h3>Add Customer</h3>
                <p>Register a new customer for credit</p>
            </a>
            
            <a href="transactions.php?action=add" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h3>New Transaction</h3>
                <p>Record a credit transaction</p>
            </a>
            
            <a href="payments.php?action=add" class="action-card">
                <i class="fas fa-hand-holding-usd"></i>
                <h3>Receive Payment</h3>
                <p>Record customer payment</p>
            </a>
            
            <a href="collections.php" class="action-card">
                <i class="fas fa-bell"></i>
                <h3>Collections</h3>
                <p>View overdue accounts</p>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
