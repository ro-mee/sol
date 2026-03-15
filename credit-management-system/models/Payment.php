<?php
class Payment {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($data) {
        $this->conn->beginTransaction();
        
        try {
            // Record payment
            $stmt = $this->conn->prepare("INSERT INTO payments (transaction_id, customer_id, amount_paid, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['transaction_id'],
                $data['customer_id'],
                $data['amount_paid'],
                $data['payment_date'],
                $data['payment_method'],
                $data['notes']
            ]);
            
            // Update transaction status
            $this->updateTransactionStatus($data['transaction_id']);
            
            // Update customer balance
            $this->updateCustomerBalance($data['customer_id'], -$data['amount_paid']);
            
            $this->conn->commit();
            logAudit('PAYMENT_RECEIVED', "Payment received: {$data['amount_paid']} for transaction ID: {$data['transaction_id']}");
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    public function getAll($limit = 50, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = $this->conn->prepare("
            SELECT p.*, c.full_name, ct.total_amount as transaction_amount
            FROM payments p 
            JOIN customers c ON p.customer_id = c.customer_id 
            JOIN credit_transactions ct ON p.transaction_id = ct.transaction_id 
            ORDER BY p.payment_date DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, c.full_name, ct.total_amount as transaction_amount
            FROM payments p 
            JOIN customers c ON p.customer_id = c.customer_id 
            JOIN credit_transactions ct ON p.transaction_id = ct.transaction_id 
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByTransactionId($transactionId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, c.full_name 
            FROM payments p 
            JOIN customers c ON p.customer_id = c.customer_id 
            WHERE p.transaction_id = ? 
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    }
    
    public function getByCustomerId($customerId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, ct.total_amount as transaction_amount
            FROM payments p 
            JOIN credit_transactions ct ON p.transaction_id = ct.transaction_id 
            WHERE p.customer_id = ? 
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    public function getTodayPayments() {
        $stmt = $this->conn->prepare("
            SELECT SUM(amount_paid) as total, COUNT(payment_id) as count
            FROM payments 
            WHERE DATE(payment_date) = CURRENT_DATE
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    private function updateTransactionStatus($transactionId) {
        // Get total paid amount for this transaction
        $stmt = $this->conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $paid = $stmt->fetch()['total_paid'] ?? 0;
        
        // Get transaction amount
        $stmt = $this->conn->prepare("SELECT total_amount FROM credit_transactions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) return false;
        
        // Determine status
        $status = 'unpaid';
        if ($paid >= $transaction['total_amount']) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        }
        
        // Update status
        $stmt = $this->conn->prepare("UPDATE credit_transactions SET status = ? WHERE transaction_id = ?");
        return $stmt->execute([$status, $transactionId]);
    }
    
    private function updateCustomerBalance($customerId, $amount) {
        $stmt = $this->conn->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE customer_id = ?");
        return $stmt->execute([$amount, $customerId]);
    }
    
    public function getTotalCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM payments");
        $stmt->execute();
        return $stmt->fetch()['total'];
    }
    
    public function getTotalPaidForTransaction($transactionId) {
        $stmt = $this->conn->prepare("SELECT SUM(amount_paid) as total FROM payments WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        return $stmt->fetch()['total'] ?? 0;
    }
    
    public function getPaymentSummary($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                DATE(payment_date) as date,
                COUNT(payment_id) as payment_count,
                SUM(amount_paid) as total_amount,
                payment_method
            FROM payments
        ";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE payment_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $sql .= " GROUP BY DATE(payment_date), payment_method ORDER BY payment_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
