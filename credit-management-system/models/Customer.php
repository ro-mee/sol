<?php
class Customer {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO customers (full_name, phone_number, address, credit_limit, payment_terms, risk_classification) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['address'],
            $data['credit_limit'],
            $data['payment_terms'],
            $data['risk_classification']
        ]);
        
        if ($result) {
            logAudit('CUSTOMER_CREATED', "New customer created: {$data['full_name']}");
        }
        
        return $result;
    }
    
    public function getAll($limit = 50, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = $this->conn->prepare("SELECT * FROM customers ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $stmt = $this->conn->prepare("UPDATE customers SET full_name = ?, phone_number = ?, address = ?, credit_limit = ?, payment_terms = ?, risk_classification = ? WHERE customer_id = ?");
        $result = $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['address'],
            $data['credit_limit'],
            $data['payment_terms'],
            $data['risk_classification'],
            $id
        ]);
        
        if ($result) {
            logAudit('CUSTOMER_UPDATED', "Customer updated: {$data['full_name']} (ID: $id)");
        }
        
        return $result;
    }
    
    public function delete($id) {
        $customer = $this->getById($id);
        $stmt = $this->conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logAudit('CUSTOMER_DELETED', "Customer deleted: {$customer['full_name']} (ID: $id)");
        }
        
        return $result;
    }
    
    public function search($query) {
        $stmt = $this->conn->prepare("SELECT * FROM customers WHERE full_name LIKE ? OR phone_number LIKE ? ORDER BY full_name");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    public function getCreditHistory($id) {
        $stmt = $this->conn->prepare("
            SELECT ct.*, p.amount_paid, p.payment_date 
            FROM credit_transactions ct 
            LEFT JOIN payments p ON ct.transaction_id = p.transaction_id 
            WHERE ct.customer_id = ? 
            ORDER BY ct.transaction_date DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
    
    public function updateBalance($id, $amount) {
        $stmt = $this->conn->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE customer_id = ?");
        return $stmt->execute([$amount, $id]);
    }
    
    public function getTotalCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM customers");
        $stmt->execute();
        return $stmt->fetch()['total'];
    }
}
?>
