<?php
class Report {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAccountsReceivableSummary() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.customer_id,
                c.full_name,
                c.phone_number,
                c.credit_limit,
                c.current_balance,
                c.risk_classification,
                COUNT(ct.transaction_id) as total_transactions,
                SUM(CASE WHEN ct.status != 'paid' THEN ct.total_amount ELSE 0 END) as outstanding_amount,
                SUM(CASE WHEN ct.status = 'paid' THEN ct.total_amount ELSE 0 END) as paid_amount
            FROM customers c
            LEFT JOIN credit_transactions ct ON c.customer_id = ct.customer_id
            GROUP BY c.customer_id
            HAVING outstanding_amount > 0
            ORDER BY outstanding_amount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getCustomerBalanceReport() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.customer_id,
                c.full_name,
                c.phone_number,
                c.credit_limit,
                c.current_balance,
                c.payment_terms,
                c.risk_classification,
                (c.current_balance / c.credit_limit * 100) as credit_utilization,
                CASE 
                    WHEN c.current_balance <= 0 THEN 'No Balance'
                    WHEN c.current_balance < (c.credit_limit * 0.5) THEN 'Low Risk'
                    WHEN c.current_balance < (c.credit_limit * 0.8) THEN 'Medium Risk'
                    ELSE 'High Risk'
                END as risk_level
            FROM customers c
            ORDER BY c.current_balance DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPaymentCollectionReport($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                DATE(p.payment_date) as collection_date,
                COUNT(p.payment_id) as payment_count,
                SUM(p.amount_paid) as total_collected,
                p.payment_method,
                c.full_name
            FROM payments p
            JOIN customers c ON p.customer_id = c.customer_id
        ";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE p.payment_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $sql .= " GROUP BY DATE(p.payment_date), p.payment_method ORDER BY collection_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAgingReport() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.customer_id,
                c.full_name,
                ct.transaction_id,
                ct.total_amount,
                ct.transaction_date,
                ct.due_date,
                DATEDIFF(CURRENT_DATE, ct.due_date) as days_overdue,
                CASE 
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 0 THEN 'Current'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 30 THEN '0-30 Days'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 60 THEN '31-60 Days'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 90 THEN '61-90 Days'
                    ELSE '90+ Days'
                END as aging_bucket,
                ct.status
            FROM customers c
            INNER JOIN credit_transactions ct ON c.customer_id = ct.customer_id
            WHERE ct.status != 'paid'
            ORDER BY ct.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAgingSummary() {
        $stmt = $this->conn->prepare("
            SELECT 
                CASE 
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 0 THEN 'Current'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 30 THEN '0-30 Days'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 60 THEN '31-60 Days'
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 90 THEN '61-90 Days'
                    ELSE '90+ Days'
                END as aging_bucket,
                COUNT(ct.transaction_id) as transaction_count,
                SUM(ct.total_amount) as total_amount,
                COUNT(DISTINCT ct.customer_id) as customer_count
            FROM credit_transactions ct
            WHERE ct.status != 'paid'
            GROUP BY aging_bucket
            ORDER BY 
                CASE 
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 0 THEN 1
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 30 THEN 2
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 60 THEN 3
                    WHEN DATEDIFF(CURRENT_DATE, ct.due_date) <= 90 THEN 4
                    ELSE 5
                END
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTopDebtors($limit = 10) {
        $limit = (int)$limit;
        $stmt = $this->conn->prepare("
            SELECT 
                c.customer_id,
                c.full_name,
                c.phone_number,
                c.current_balance,
                c.credit_limit,
                COUNT(ct.transaction_id) as outstanding_transactions,
                SUM(ct.total_amount) as total_outstanding,
                AVG(DATEDIFF(CURRENT_DATE, ct.due_date)) as avg_days_overdue
            FROM customers c
            INNER JOIN credit_transactions ct ON c.customer_id = ct.customer_id
            WHERE ct.status != 'paid'
            GROUP BY c.customer_id
            ORDER BY total_outstanding DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total outstanding receivables
        $stmt = $this->conn->prepare("SELECT SUM(total_amount) as total FROM credit_transactions WHERE status != 'paid'");
        $stmt->execute();
        $stats['total_outstanding'] = $stmt->fetch()['total'] ?? 0;
        
        // Customers with unpaid balances
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT customer_id) as total FROM credit_transactions WHERE status != 'paid'");
        $stmt->execute();
        $stats['customers_with_balance'] = $stmt->fetch()['total'] ?? 0;
        
        // Payments received today
        $stmt = $this->conn->prepare("SELECT SUM(amount_paid) as total, COUNT(payment_id) as count FROM payments WHERE DATE(payment_date) = CURRENT_DATE");
        $stmt->execute();
        $today = $stmt->fetch();
        $stats['payments_today'] = $today['total'] ?? 0;
        $stats['payments_today_count'] = $today['count'] ?? 0;
        
        // Total overdue accounts
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM credit_transactions WHERE status != 'paid' AND due_date < CURRENT_DATE");
        $stmt->execute();
        $stats['overdue_accounts'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    }
    
    public function getRecentTransactions($limit = 10) {
        $limit = (int)$limit;
        $stmt = $this->conn->prepare("
            SELECT 
                ct.transaction_id,
                ct.total_amount,
                ct.transaction_date,
                ct.status,
                c.full_name
            FROM credit_transactions ct
            JOIN customers c ON ct.customer_id = c.customer_id
            ORDER BY ct.transaction_date DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTodayPayments() {
        $stmt = $this->conn->prepare("SELECT SUM(amount_paid) as total, COUNT(payment_id) as count FROM payments WHERE DATE(payment_date) = CURRENT_DATE");
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
