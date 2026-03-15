<?php
require_once 'config/config.php';
require_once 'models/CreditTransaction.php';
require_once 'models/Customer.php';

requireLogin();
$page_title = 'Collections & Reminders';

$transaction = new CreditTransaction(getDBConnection());
$customer = new Customer(getDBConnection());

// Get filters
$days_filter = $_GET['days'] ?? '';
$risk_filter = $_GET['risk'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get overdue transactions
$overdueTransactions = $transaction->getOverdueTransactions();

// Apply filters
if ($days_filter) {
    $overdueTransactions = array_filter($overdueTransactions, function($tx) use ($days_filter) {
        $days = $tx['days_overdue'];
        switch ($days_filter) {
            case '1-30':
                return $days >= 1 && $days <= 30;
            case '31-60':
                return $days >= 31 && $days <= 60;
            case '61-90':
                return $days >= 61 && $days <= 90;
            case '90+':
                return $days > 90;
            default:
                return true;
        }
    });
}

if ($risk_filter) {
    $customerIds = [];
    foreach ($overdueTransactions as $tx) {
        $customerData = $customer->getById($tx['customer_id']);
        if ($customerData['risk_classification'] === $risk_filter) {
            $customerIds[] = $tx['customer_id'];
        }
    }
    $overdueTransactions = array_filter($overdueTransactions, function($tx) use ($customerIds) {
        return in_array($tx['customer_id'], $customerIds);
    });
}

// Sort by days overdue (most overdue first)
usort($overdueTransactions, function($a, $b) {
    return $b['days_overdue'] - $a['days_overdue'];
});

// Pagination
$totalOverdue = count($overdueTransactions);
$totalPages = ceil($totalOverdue / $limit);
$paginatedTransactions = array_slice($overdueTransactions, $offset, $limit);

// Calculate collection statistics
$stats = [
    'total_overdue' => count($overdueTransactions),
    'total_amount' => array_sum(array_column($overdueTransactions, 'total_amount')),
    'avg_days_overdue' => count($overdueTransactions) > 0 ? round(array_sum(array_column($overdueTransactions, 'days_overdue')) / count($overdueTransactions)) : 0,
    'critical_overdue' => count(array_filter($overdueTransactions, fn($tx) => $tx['days_overdue'] > 60))
];

// Group by customer for summary view
$customerOverdue = [];
foreach ($overdueTransactions as $tx) {
    $customerId = $tx['customer_id'];
    if (!isset($customerOverdue[$customerId])) {
        $customerData = $customer->getById($customerId);
        $customerOverdue[$customerId] = [
            'customer_id' => $customerId,
            'full_name' => $tx['full_name'],
            'phone_number' => $customerData['phone_number'],
            'risk_classification' => $customerData['risk_classification'],
            'total_overdue' => 0,
            'transaction_count' => 0,
            'max_days_overdue' => 0,
            'transactions' => []
        ];
    }
    
    $customerOverdue[$customerId]['total_overdue'] += $tx['total_amount'];
    $customerOverdue[$customerId]['transaction_count']++;
    $customerOverdue[$customerId]['max_days_overdue'] = max($customerOverdue[$customerId]['max_days_overdue'], $tx['days_overdue']);
    $customerOverdue[$customerId]['transactions'][] = $tx;
}

include 'includes/header.php';
?>

<div class="collections-container">
    <div class="list-header">
        <h2>Collections & Reminders</h2>
        <div class="header-actions">
            <button onclick="sendReminders()" class="btn btn-warning">
                <i class="fas fa-bell"></i> Send Reminders
            </button>
            <button onclick="exportCollectionReport()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
    </div>
    
    <!-- Collection Statistics -->
    <div class="collection-stats">
        <div class="stat-card critical">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_overdue']; ?></h3>
                <p>Overdue Accounts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($stats['total_amount']); ?></h3>
                <p>Total Overdue Amount</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['avg_days_overdue']; ?> days</h3>
                <p>Average Days Overdue</p>
            </div>
        </div>
        
        <div class="stat-card critical">
            <div class="stat-icon">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['critical_overdue']; ?></h3>
                <p>Critical (>60 days)</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="days">Days Overdue:</label>
                    <select id="days" name="days">
                        <option value="">All</option>
                        <option value="1-30" <?php echo $days_filter == '1-30' ? 'selected' : ''; ?>>1-30 days</option>
                        <option value="31-60" <?php echo $days_filter == '31-60' ? 'selected' : ''; ?>>31-60 days</option>
                        <option value="61-90" <?php echo $days_filter == '61-90' ? 'selected' : ''; ?>>61-90 days</option>
                        <option value="90+" <?php echo $days_filter == '90+' ? 'selected' : ''; ?>>90+ days</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="risk">Risk Level:</label>
                    <select id="risk" name="risk">
                        <option value="">All</option>
                        <option value="low" <?php echo $risk_filter == 'low' ? 'selected' : ''; ?>>Low Risk</option>
                        <option value="medium" <?php echo $risk_filter == 'medium' ? 'selected' : ''; ?>>Medium Risk</option>
                        <option value="high" <?php echo $risk_filter == 'high' ? 'selected' : ''; ?>>High Risk</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="collections.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- View Toggle -->
    <div class="view-toggle">
        <button onclick="showView('transactions')" id="transactionsViewBtn" class="btn btn-primary">Transactions View</button>
        <button onclick="showView('customers')" id="customersViewBtn" class="btn btn-secondary">Customers View</button>
    </div>
    
    <!-- Transactions View -->
    <div id="transactionsView" class="collections-view">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Risk Level</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedTransactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No overdue transactions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedTransactions as $tx): 
                            $customerData = $customer->getById($tx['customer_id']);
                            $priority = getPriority($tx['days_overdue'], $customerData['risk_classification']);
                        ?>
                            <tr class="priority-<?php echo $priority; ?>">
                                <td>#<?php echo $tx['transaction_id']; ?></td>
                                <td>
                                    <a href="customers.php?action=view&id=<?php echo $tx['customer_id']; ?>" class="customer-link">
                                        <?php echo $tx['full_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo formatCurrency($tx['total_amount']); ?></td>
                                <td><?php echo formatDate($tx['due_date']); ?></td>
                                <td>
                                    <span class="days-overdue days-<?php echo getDaysCategory($tx['days_overdue']); ?>">
                                        <?php echo $tx['days_overdue']; ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="risk-badge risk-<?php echo $customerData['risk_classification']; ?>">
                                        <?php echo ucfirst($customerData['risk_classification']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?php echo $priority; ?>">
                                        <?php echo ucfirst($priority); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="payments.php?action=add&transaction_id=<?php echo $tx['transaction_id']; ?>" class="btn btn-sm btn-success" title="Receive Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <button onclick="sendReminder(<?php echo $tx['transaction_id']; ?>)" class="btn btn-sm btn-warning" title="Send Reminder">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                        <a href="customers.php?action=view&id=<?php echo $tx['customer_id']; ?>" class="btn btn-sm btn-info" title="View Customer">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Customers View -->
    <div id="customersView" class="collections-view" style="display: none;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Overdue Amount</th>
                        <th>Transactions</th>
                        <th>Max Days Overdue</th>
                        <th>Risk Level</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customerOverdue)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No customers with overdue accounts</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $customerSlice = array_slice($customerOverdue, $offset, $limit);
                        foreach ($customerSlice as $c): 
                            $priority = getPriority($c['max_days_overdue'], $c['risk_classification']);
                        ?>
                            <tr class="priority-<?php echo $priority; ?>">
                                <td>
                                    <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="customer-link">
                                        <?php echo $c['full_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo $c['phone_number'] ?: 'N/A'; ?></td>
                                <td><?php echo formatCurrency($c['total_overdue']); ?></td>
                                <td><?php echo $c['transaction_count']; ?></td>
                                <td>
                                    <span class="days-overdue days-<?php echo getDaysCategory($c['max_days_overdue']); ?>">
                                        <?php echo $c['max_days_overdue']; ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="risk-badge risk-<?php echo $c['risk_classification']; ?>">
                                        <?php echo ucfirst($c['risk_classification']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?php echo $priority; ?>">
                                        <?php echo ucfirst($priority); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="payments.php?action=add&customer_id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-success" title="Receive Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <button onclick="sendCustomerReminder(<?php echo $c['customer_id']; ?>)" class="btn btn-sm btn-warning" title="Send Reminder">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                        <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-info" title="View Customer">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $currentUrl = $_SERVER['PHP_SELF'];
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            
            for ($i = 1; $i <= $totalPages; $i++):
                $active = $i == $page ? 'active' : '';
                $url = $currentUrl . '?page=' . $i . ($queryString ? '&' . $queryString : '');
            ?>
                <a href="<?php echo $url; ?>" class="page-link <?php echo $active; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Helper functions
function getPriority($daysOverdue, $riskLevel) {
    if ($daysOverdue > 60 || $riskLevel === 'high') {
        return 'critical';
    } elseif ($daysOverdue > 30 || $riskLevel === 'medium') {
        return 'high';
    } else {
        return 'medium';
    }
}

function getDaysCategory($daysOverdue) {
    if ($daysOverdue > 60) return 'critical';
    if ($daysOverdue > 30) return 'high';
    return 'medium';
}
?>

<style>
.collections-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.collection-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
}

.collection-stats .stat-card.critical .stat-icon {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.filters-section {
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
    width: 100%;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.view-toggle {
    padding: 15px 25px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
}

.collections-view {
    padding: 0;
}

.days-overdue {
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.days-overdue.days-medium {
    background: #fff3cd;
    color: #856404;
}

.days-overdue.days-high {
    background: #f8d7da;
    color: #721c24;
}

.days-overdue.days-critical {
    background: #d1ecf1;
    color: #0c5460;
}

.priority-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.priority-badge.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-badge.priority-high {
    background: #f8d7da;
    color: #721c24;
}

.priority-badge.priority-critical {
    background: #d1ecf1;
    color: #0c5460;
}

.priority-critical {
    background: #fff5f5;
}

.priority-high {
    background: #fffbf0;
}

.priority-medium {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .collection-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .collection-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showView(view) {
    const transactionsView = document.getElementById('transactionsView');
    const customersView = document.getElementById('customersView');
    const transactionsBtn = document.getElementById('transactionsViewBtn');
    const customersBtn = document.getElementById('customersViewBtn');
    
    if (view === 'transactions') {
        transactionsView.style.display = 'block';
        customersView.style.display = 'none';
        transactionsBtn.className = 'btn btn-primary';
        customersBtn.className = 'btn btn-secondary';
    } else {
        transactionsView.style.display = 'none';
        customersView.style.display = 'block';
        transactionsBtn.className = 'btn btn-secondary';
        customersBtn.className = 'btn btn-primary';
    }
}

function sendReminder(transactionId) {
    if (confirm('Send payment reminder for this transaction?')) {
        // This would typically make an AJAX call to send the reminder
        showNotification('Reminder sent successfully!', 'success');
    }
}

function sendCustomerReminder(customerId) {
    if (confirm('Send payment reminders for all overdue transactions of this customer?')) {
        // This would typically make an AJAX call to send the reminders
        showNotification('Reminders sent successfully!', 'success');
    }
}

function sendReminders() {
    if (confirm('Send payment reminders to all customers with overdue accounts?')) {
        // This would typically make an AJAX call to send bulk reminders
        showNotification('Bulk reminders sent successfully!', 'success');
    }
}

function exportCollectionReport() {
    const table = document.querySelector('.collections-view:not([style*="display: none"]) .data-table');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Add headers
    const headers = [];
    table.querySelectorAll('th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Add data rows
    rows.forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach(td => {
            let text = td.textContent.trim();
            text = text.replace(/,/g, '');
            text = text.replace(/"/g, '');
            rowData.push(text);
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'collection_report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
