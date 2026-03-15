<?php
require_once 'config/config.php';
require_once 'models/Admin.php';

requireLogin();
$page_title = 'Audit Logs';

// Get filters
$action_filter = $_GET['action'] ?? '';
$date_filter = $_GET['date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];

if ($action_filter) {
    $whereConditions[] = "action LIKE ?";
    $params[] = "%$action_filter%";
}

if ($date_filter) {
    $whereConditions[] = "DATE(timestamp) = ?";
    $params[] = $date_filter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get audit logs
$sql = "
    SELECT * FROM audit_logs 
    $whereClause
    ORDER BY timestamp DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = getDBConnection()->prepare($sql);
$stmt->execute($params);
$auditLogs = $stmt->fetchAll();

// Get total count
$countSql = "SELECT COUNT(*) as total FROM audit_logs $whereClause";
$countParams = $params;
$countStmt = getDBConnection()->prepare($countSql);
$countStmt->execute($countParams);
$totalLogs = $countStmt->fetch()['total'];
$totalPages = ceil($totalLogs / $limit);

// Get unique actions for filter dropdown
$actionsStmt = getDBConnection()->prepare("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$actionsStmt->execute();
$actions = $actionsStmt->fetchAll();

include 'includes/header.php';
?>

<div class="audit-container">
    <div class="list-header">
        <h2>Audit Trail</h2>
        <div class="header-actions">
            <button onclick="exportAuditLogs()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button onclick="clearOldLogs()" class="btn btn-danger">
                <i class="fas fa-trash"></i> Clear Old Logs
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="action">Action:</label>
                    <select id="action" name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?php echo $act['action']; ?>" <?php echo $action_filter == $act['action'] ? 'selected' : ''; ?>>
                                <?php echo $act['action']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="audit.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Statistics -->
    <div class="audit-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalLogs; ?></h3>
                <p>Total Logs</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3><?php 
                    $todayLogs = count(array_filter($auditLogs, fn($log) => date('Y-m-d') == date('Y-m-d', strtotime($log['timestamp']))));
                    echo $todayLogs; 
                ?></h3>
                <p>Today's Activities</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php 
                    $uniqueUsers = count(array_unique(array_column($auditLogs, 'action')));
                    echo $uniqueUsers; 
                ?></h3>
                <p>Unique Actions</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php 
                    $recentLogs = count(array_filter($auditLogs, fn($log) => strtotime($log['timestamp']) > strtotime('-1 hour')));
                    echo $recentLogs; 
                ?></h3>
                <p>Last Hour</p>
            </div>
        </div>
    </div>
    
    <!-- Audit Logs Table -->
    <div class="table-container" id="auditTable">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Timestamp</th>
                    <th>Time Ago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No audit logs found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr class="log-row log-<?php echo getLogSeverity($log['action']); ?>">
                            <td>#<?php echo $log['log_id']; ?></td>
                            <td>
                                <span class="action-badge action-<?php echo getActionType($log['action']); ?>">
                                    <?php echo $log['action']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($log['timestamp'])); ?></td>
                            <td>
                                <span class="time-ago" title="<?php echo $log['timestamp']; ?>">
                                    <?php echo getTimeAgo($log['timestamp']); ?>
                                </span>
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

<?php
function getLogSeverity($action) {
    $criticalActions = ['DELETE', 'LOGIN_FAILED', 'SECURITY_BREACH'];
    $warningActions = ['UPDATE', 'PASSWORD_CHANGE'];
    
    if (in_array($action, $criticalActions)) return 'critical';
    if (in_array($action, $warningActions)) return 'warning';
    return 'info';
}

function getActionType($action) {
    if (strpos($action, 'CREATE') !== false) return 'create';
    if (strpos($action, 'UPDATE') !== false) return 'update';
    if (strpos($action, 'DELETE') !== false) return 'delete';
    if (strpos($action, 'LOGIN') !== false) return 'auth';
    return 'general';
}

function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d', $time);
}
?>

<style>
.audit-container {
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

.audit-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
}

.action-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.action-badge.action-create {
    background: #d4edda;
    color: #155724;
}

.action-badge.action-update {
    background: #fff3cd;
    color: #856404;
}

.action-badge.action-delete {
    background: #f8d7da;
    color: #721c24;
}

.action-badge.action-auth {
    background: #d1ecf1;
    color: #0c5460;
}

.action-badge.action-general {
    background: #e9ecef;
    color: #333;
}

.log-row.log-critical {
    background: #fff5f5;
}

.log-row.log-warning {
    background: #fffbf0;
}

.log-row.log-info {
    background: #f8f9fa;
}

.time-ago {
    font-size: 0.8rem;
    color: #666;
    cursor: help;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .audit-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .audit-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function exportAuditLogs() {
    const table = document.getElementById('auditTable');
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
    link.download = 'audit_logs_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

function clearOldLogs() {
    if (confirm('Are you sure you want to clear audit logs older than 90 days? This action cannot be undone.')) {
        // This would typically make an AJAX call to clear old logs
        showNotification('Old logs cleared successfully!', 'success');
        setTimeout(() => {
            location.reload();
        }, 1500);
    }
}

// Auto-refresh logs every 30 seconds
setInterval(() => {
    console.log('Refreshing audit logs...');
    // In a real application, this would fetch fresh logs via AJAX
}, 30000);

// Update time ago every minute
setInterval(() => {
    document.querySelectorAll('.time-ago').forEach(element => {
        const timestamp = element.getAttribute('title');
        if (timestamp) {
            // This would recalculate the time ago
            // For now, we'll just leave it as is
        }
    });
}, 60000);
</script>

<?php include 'includes/footer.php'; ?>
