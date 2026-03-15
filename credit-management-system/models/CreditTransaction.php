<?php
class CreditTransaction {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($data) {
        // Calculate due date based on customer payment terms
        $customer = $this->getCustomerPaymentTerms($data['customer_id']);
        $due_date = date('Y-m-d', strtotime($data['transaction_date'] . " +{$customer['payment_terms']} days"));
        
        $stmt = $this->conn->prepare("INSERT INTO credit_transactions (customer_id, item_description, quantity, unit_price, total_amount, transaction_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $data['customer_id'],
            $data['item_description'],
            $data['quantity'],
            $data['unit_price'],
            $data['total_amount'],
            $data['transaction_date'],
            $due_date
        ]);
        
        if ($result) {
            // Update customer balance
            $this->updateCustomerBalance($data['customer_id'], $data['total_amount']);
            logAudit('TRANSACTION_CREATED', "Credit transaction created for customer ID: {$data['customer_id']}, Amount: {$data['total_amount']}");
        }
        
        return $result;
    }
    
    public function getAll($limit = 50, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = $this->conn->prepare("
            SELECT ct.*, c.full_name 
            FROM credit_transactions ct 
            JOIN customers c ON ct.customer_id = c.customer_id 
            ORDER BY ct.transaction_date DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT ct.*, c.full_name 
            FROM credit_transactions ct 
            JOIN customers c ON ct.customer_id = c.customer_id 
            WHERE ct.transaction_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByCustomerId($id) {
        $stmt = $this->conn->prepare("
            SELECT ct.*, c.full_name 
            FROM credit_transactions ct 
            JOIN customers c ON ct.customer_id = c.customer_id 
            WHERE ct.customer_id = ? 
            ORDER BY ct.transaction_date DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE credit_transactions SET status = ? WHERE transaction_id = ?");
        $result = $stmt->execute([$status, $id]);
        
        if ($result) {
            logAudit('TRANSACTION_STATUS_UPDATED', "Transaction status updated to '$status' for transaction ID: $id");
        }
        
        return $result;
    }
    
    public function getOutstandingTransactions() {
        $stmt = $this->conn->prepare("
            SELECT ct.*, c.full_name, DATEDIFF(CURRENT_DATE, ct.due_date) as days_overdue
            FROM credit_transactions ct 
            JOIN customers c ON ct.customer_id = c.customer_id 
            WHERE ct.status != 'paid'
            ORDER BY ct.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getOverdueTransactions() {
        $stmt = $this->conn->prepare("
            SELECT ct.*, c.full_name, DATEDIFF(CURRENT_DATE, ct.due_date) as days_overdue
            FROM credit_transactions ct 
            JOIN customers c ON ct.customer_id = c.customer_id 
            WHERE ct.status != 'paid' AND ct.due_date < CURRENT_DATE
            ORDER BY ct.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function getCustomerPaymentTerms($customerId) {
        $stmt = $this->conn->prepare("SELECT payment_terms FROM customers WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetch();
    }
    
    private function updateCustomerBalance($customerId, $amount) {
        $stmt = $this->conn->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE customer_id = ?");
        return $stmt->execute([$amount, $customerId]);
    }
    
    public function getTotalCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM credit_transactions");
        $stmt->execute();
        return $stmt->fetch()['total'];
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
        
        // Overdue accounts
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM credit_transactions WHERE status != 'paid' AND due_date < CURRENT_DATE");
        $stmt->execute();
        $stats['overdue_accounts'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    }
}
?>
