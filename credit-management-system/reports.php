<?php
require_once 'config/config.php';
require_once 'models/Report.php';

requireLogin();
$page_title = 'Reports & Analytics';

$report = new Report(getDBConnection());
$reportType = $_GET['type'] ?? 'summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

include 'includes/header.php';
$show_date_filter = true;
?>

<div class="reports-container">
    <div class="list-header">
        <h2>Reports & Analytics</h2>
        <div class="header-actions">
            <button onclick="exportToPDF('reportContent', '<?php echo $reportType; ?>_report')" class="btn btn-secondary">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="exportToCSV()" class="btn btn-secondary">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>
    
    <!-- Report Type Selection -->
    <div class="report-tabs">
        <button onclick="loadReport('summary')" class="tab-btn <?php echo $reportType == 'summary' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Summary
        </button>
        <button onclick="loadReport('aging')" class="tab-btn <?php echo $reportType == 'aging' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Aging Report
        </button>
        <button onclick="loadReport('customer_balance')" class="tab-btn <?php echo $reportType == 'customer_balance' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customer Balance
        </button>
        <button onclick="loadReport('payment_collection')" class="tab-btn <?php echo $reportType == 'payment_collection' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> Payment Collection
        </button>
        <button onclick="loadReport('top_debtors')" class="tab-btn <?php echo $reportType == 'top_debtors' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Top Debtors
        </button>
    </div>
    
    <!-- Report Content -->
    <div id="reportContent" class="report-content">
        <?php
        switch ($reportType) {
            case 'summary':
                include 'reports/summary_report.php';
                break;
            case 'aging':
                include 'reports/aging_report.php';
                break;
            case 'customer_balance':
                include 'reports/customer_balance_report.php';
                break;
            case 'payment_collection':
                include 'reports/payment_collection_report.php';
                break;
            case 'top_debtors':
                include 'reports/top_debtors_report.php';
                break;
            default:
                include 'reports/summary_report.php';
        }
        ?>
    </div>
</div>

<style>
.reports-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.report-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    overflow-x: auto;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 20px;
    border: none;
    background: none;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-size: 0.9rem;
}

.tab-btn:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.tab-btn.active {
    background: #667eea;
    color: white;
}

.report-content {
    padding: 25px;
}

.report-section {
    margin-bottom: 30px;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.report-header h3 {
    font-size: 1.3rem;
    color: #333;
}

.report-period {
    color: #666;
    font-size: 0.9rem;
}

.report-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.report-stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.report-stat-card h4 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 8px;
}

.report-stat-card .value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.report-table {
    margin-top: 20px;
}

.chart-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.chart-placeholder {
    text-align: center;
    padding: 40px;
    color: #666;
    background: white;
    border-radius: 5px;
    border: 2px dashed #ddd;
}

@media (max-width: 768px) {
    .report-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        justify-content: center;
    }
    
    .report-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .report-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function loadReport(type) {
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
}

function exportToCSV() {
    const reportType = '<?php echo $reportType; ?>';
    let csvData = [];
    
    switch(reportType) {
        case 'summary':
            csvData = exportSummaryCSV();
            break;
        case 'aging':
            csvData = exportAgingCSV();
            break;
        case 'customer_balance':
            csvData = exportCustomerBalanceCSV();
            break;
        case 'payment_collection':
            csvData = exportPaymentCollectionCSV();
            break;
        case 'top_debtors':
            csvData = exportTopDebtorsCSV();
            break;
    }
    
    if (csvData.length > 0) {
        const csvContent = csvData.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = reportType + '_report_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }
}

function exportSummaryCSV() {
    const rows = [];
    rows.push('Metric,Value');
    
    // Extract data from summary report
    document.querySelectorAll('.report-stat-card').forEach(card => {
        const label = card.querySelector('h4').textContent.trim();
        const value = card.querySelector('.value').textContent.trim();
        rows.push(`"${label}","${value}"`);
    });
    
    return rows;
}

function exportAgingCSV() {
    const rows = [];
    const table = document.querySelector('.data-table');
    
    if (table) {
        // Get headers
        const headers = [];
        table.querySelectorAll('th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        rows.push(headers.join(','));
        
        // Get data rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            const rowData = [];
            tr.querySelectorAll('td').forEach(td => {
                let text = td.textContent.trim();
                text = text.replace(/,/g, '');
                text = text.replace(/"/g, '');
                rowData.push(text);
            });
            if (rowData.length > 0) {
                rows.push(rowData.join(','));
            }
        });
    }
    
    return rows;
}

function exportCustomerBalanceCSV() {
    return exportAgingCSV(); // Same structure
}

function exportPaymentCollectionCSV() {
    return exportAgingCSV(); // Same structure
}

function exportTopDebtorsCSV() {
    return exportAgingCSV(); // Same structure
}

// Auto-refresh reports every 5 minutes
setInterval(() => {
    const currentType = '<?php echo $reportType; ?>';
    console.log('Refreshing report data...');
    // In a real application, this would fetch fresh data via AJAX
}, 300000);
</script>

<?php include 'includes/footer.php'; ?>
