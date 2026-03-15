<?php
require_once 'config/config.php';
require_once 'models/Customer.php';
require_once 'models/CreditTransaction.php';
require_once 'models/Payment.php';

requireLogin();
$page_title = 'Accounts Receivable Ledger';

$customer = new Customer(getDBConnection());
$transaction = new CreditTransaction(getDBConnection());
$payment = new Payment(getDBConnection());

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$risk_filter = $_GET['risk'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query conditions
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(c.full_name LIKE ? OR c.phone_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    switch ($status_filter) {
        case 'with_balance':
            $whereConditions[] = "c.current_balance > 0";
            break;
        case 'no_balance':
            $whereConditions[] = "c.current_balance <= 0";
            break;
    }
}

if ($risk_filter) {
    $whereConditions[] = "c.risk_classification = ?";
    $params[] = $risk_filter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get customers with their transaction summary
$sql = "
    SELECT 
        c.customer_id,
        c.full_name,
        c.phone_number,
        c.credit_limit,
        c.current_balance,
        c.payment_terms,
        c.risk_classification,
        c.created_at,
        COUNT(ct.transaction_id) as total_transactions,
        SUM(CASE WHEN ct.status != 'paid' THEN ct.total_amount ELSE 0 END) as outstanding_amount,
        SUM(CASE WHEN ct.status = 'paid' THEN ct.total_amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN ct.status != 'paid' AND ct.due_date < CURRENT_DATE THEN ct.total_amount ELSE 0 END) as overdue_amount,
        COUNT(CASE WHEN ct.status != 'paid' AND ct.due_date < CURRENT_DATE THEN 1 END) as overdue_count
    FROM customers c
    LEFT JOIN credit_transactions ct ON c.customer_id = ct.customer_id
    $whereClause
    GROUP BY c.customer_id
    ORDER BY c.current_balance DESC
    LIMIT $limit OFFSET $offset
";

$stmt = getDBConnection()->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get total count for pagination
$countSql = "
    SELECT COUNT(DISTINCT c.customer_id) as total
    FROM customers c
    LEFT JOIN credit_transactions ct ON c.customer_id = ct.customer_id
    $whereClause
";

$countParams = array_slice($params, 0, -2); // Remove limit and offset
$countStmt = getDBConnection()->prepare($countSql);
$countStmt->execute($countParams);
$totalCustomers = $countStmt->fetch()['total'];
$totalPages = ceil($totalCustomers / $limit);

include 'includes/header.php';
?>

<div class="ledger-container">
    <div class="list-header">
        <h2>Accounts Receivable Ledger</h2>
        <div class="header-actions">
            <button onclick="exportToCSV()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Search Customer:</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Name or phone...">
                </div>
                
                <div class="filter-group">
                    <label for="status">Balance Status:</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="with_balance" <?php echo $status_filter == 'with_balance' ? 'selected' : ''; ?>>With Balance</option>
                        <option value="no_balance" <?php echo $status_filter == 'no_balance' ? 'selected' : ''; ?>>No Balance</option>
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
                    <a href="ledger.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="ledger-summary">
        <div class="summary-card">
            <h3>Total Outstanding</h3>
            <p><?php echo formatCurrency(array_sum(array_column($customers, 'outstanding_amount'))); ?></p>
        </div>
        <div class="summary-card">
            <h3>Customers with Balance</h3>
            <p><?php echo count(array_filter($customers, fn($c) => $c['current_balance'] > 0)); ?></p>
        </div>
        <div class="summary-card">
            <h3>Overdue Amount</h3>
            <p><?php echo formatCurrency(array_sum(array_column($customers, 'overdue_amount'))); ?></p>
        </div>
        <div class="summary-card">
            <h3>Overdue Accounts</h3>
            <p><?php echo count(array_filter($customers, fn($c) => $c['overdue_count'] > 0)); ?></p>
        </div>
    </div>
    
    <!-- Ledger Table -->
    <div class="table-container" id="ledgerTable">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Total Credit</th>
                    <th>Paid Amount</th>
                    <th>Balance</th>
                    <th>Overdue</th>
                    <th>Credit Limit</th>
                    <th>Utilization</th>
                    <th>Risk</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="10" class="text-center">No customers found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): 
                        $utilization = $c['credit_limit'] > 0 ? ($c['current_balance'] / $c['credit_limit']) * 100 : 0;
                        $rowClass = $c['overdue_count'] > 0 ? 'overdue-row' : '';
                    ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td>
                                <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="customer-link">
                                    <?php echo $c['full_name']; ?>
                                </a>
                            </td>
                            <td><?php echo $c['phone_number'] ?: 'N/A'; ?></td>
                            <td><?php echo formatCurrency($c['outstanding_amount'] + $c['paid_amount']); ?></td>
                            <td><?php echo formatCurrency($c['paid_amount']); ?></td>
                            <td>
                                <span class="<?php echo $c['current_balance'] > 0 ? 'balance-positive' : 'balance-zero'; ?>">
                                    <?php echo formatCurrency($c['current_balance']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['overdue_count'] > 0): ?>
                                    <span class="overdue-amount">
                                        <?php echo formatCurrency($c['overdue_amount']); ?>
                                        <small>(<?php echo $c['overdue_count']; ?>)</small>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($c['credit_limit']); ?></td>
                            <td>
                                <div class="utilization-bar">
                                    <div class="utilization-fill" style="width: <?php echo min($utilization, 100); ?>%; background: <?php echo $utilization > 80 ? '#dc3545' : ($utilization > 50 ? '#ffc107' : '#28a745'); ?>"></div>
                                    <span class="utilization-text"><?php echo round($utilization); ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="risk-badge risk-<?php echo $c['risk_classification']; ?>">
                                    <?php echo ucfirst($c['risk_classification']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="payments.php?action=add&customer_id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-success" title="Receive Payment">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                    <a href="transactions.php?action=add&customer_id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-primary" title="New Transaction">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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

<style>
.ledger-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
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

.filter-group input,
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

.ledger-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
}

.summary-card {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-card h3 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.summary-card p {
    font-size: 1.3rem;
    font-weight: bold;
    color: #333;
}

.balance-positive {
    color: #dc3545;
    font-weight: 600;
}

.balance-zero {
    color: #28a745;
    font-weight: 600;
}

.overdue-amount {
    color: #dc3545;
    font-weight: 600;
}

.overdue-amount small {
    font-weight: normal;
    color: #666;
}

.overdue-row {
    background: #fff5f5;
}

.utilization-bar {
    position: relative;
    width: 100%;
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

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .filter-actions {
        justify-content: stretch;
    }
    
    .filter-actions button,
    .filter-actions a {
        flex: 1;
    }
    
    .ledger-summary {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .ledger-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function exportToCSV() {
    const table = document.getElementById('ledgerTable');
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
            // Remove commas and quotes to avoid CSV issues
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
    link.download = 'accounts_receivable_ledger_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
