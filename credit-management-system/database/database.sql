-- Digital Credit Management System Database Schema
-- Created for MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS credit_management;
USE credit_management;

-- Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers Table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    address TEXT,
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    payment_terms INT DEFAULT 30, -- 7, 15, 30 days
    risk_classification ENUM('low', 'medium', 'high') DEFAULT 'medium',
    current_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_name (full_name),
    INDEX idx_phone (phone_number)
);

-- Credit Transactions Table
CREATE TABLE credit_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    item_description TEXT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    transaction_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('unpaid', 'partially_paid', 'paid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);

-- Payments Table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'check', 'mobile_money') DEFAULT 'cash',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES credit_transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    INDEX idx_payment_transaction (transaction_id),
    INDEX idx_payment_customer (customer_id),
    INDEX idx_payment_date (payment_date)
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp)
);

-- Insert Default Admin User (password: admin123)
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create Views for Reports

-- Customer Balance View
CREATE VIEW customer_balance_view AS
SELECT 
    c.customer_id,
    c.full_name,
    c.phone_number,
    c.credit_limit,
    c.current_balance,
    c.payment_terms,
    c.risk_classification,
    COUNT(ct.transaction_id) as total_transactions,
    SUM(CASE WHEN ct.status != 'paid' THEN ct.total_amount ELSE 0 END) as outstanding_amount
FROM customers c
LEFT JOIN credit_transactions ct ON c.customer_id = ct.customer_id
GROUP BY c.customer_id;

-- Aging Report View
CREATE VIEW aging_report_view AS
SELECT 
    c.customer_id,
    c.full_name,
    ct.transaction_id,
    ct.total_amount,
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
WHERE ct.status != 'paid';

-- Payment Summary View
CREATE VIEW payment_summary_view AS
SELECT 
    DATE(payment_date) as payment_date,
    COUNT(payment_id) as payment_count,
    SUM(amount_paid) as total_collected
FROM payments
GROUP BY DATE(payment_date)
ORDER BY payment_date DESC;
