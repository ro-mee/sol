<?php
require_once 'config/config.php';
require_once 'models/CreditTransaction.php';
require_once 'models/Customer.php';

requireLogin();
$page_title = 'Credit Transactions';

$transaction = new CreditTransaction(getDBConnection());
$customer = new Customer(getDBConnection());
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add':
            $customerId = intval($_POST['customer_id']);
            $quantity = intval($_POST['quantity']);
            $unitPrice = floatval($_POST['unit_price']);
            $totalAmount = $quantity * $unitPrice;
            
            // Check credit limit
            $customerData = $customer->getById($customerId);
            $newBalance = $customerData['current_balance'] + $totalAmount;
            
            if ($newBalance > $customerData['credit_limit']) {
                if (!isset($_POST['override_credit_limit'])) {
                    $_SESSION['error'] = 'Transaction exceeds credit limit! Current balance: ' . formatCurrency($customerData['current_balance']) . ', Credit limit: ' . formatCurrency($customerData['credit_limit']) . ', Transaction amount: ' . formatCurrency($totalAmount);
                    redirect('transactions.php?action=add');
                    exit;
                }
            }
            
            $data = [
                'customer_id' => $customerId,
                'item_description' => sanitize($_POST['item_description']),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'transaction_date' => $_POST['transaction_date']
            ];
            
            if ($transaction->create($data)) {
                $_SESSION['success'] = 'Transaction recorded successfully';
            } else {
                $_SESSION['error'] = 'Failed to record transaction';
            }
            redirect('transactions.php');
            break;
    }
}

// Handle different actions
switch ($action) {
    case 'add':
        $customers = $customer->getAll();
        
        include 'includes/header.php';
        ?>
        <div class="form-container">
            <div class="form-header">
                <h2>New Credit Transaction</h2>
                <a href="transactions.php" class="btn btn-secondary">Back to List</a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="transaction-form" id="transactionForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_id">Customer *</label>
                        <select id="customer_id" name="customer_id" required onchange="updateCustomerInfo()">
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['customer_id']; ?>" 
                                        data-balance="<?php echo $c['current_balance']; ?>"
                                        data-limit="<?php echo $c['credit_limit']; ?>">
                                    <?php echo $c['full_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_date">Transaction Date *</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div id="customerInfo" style="display: none;">
                    <div class="customer-info-box">
                        <h4>Customer Credit Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Current Balance:</label>
                                <span id="currentBalance">₱0.00</span>
                            </div>
                            <div class="info-item">
                                <label>Credit Limit:</label>
                                <span id="creditLimit">₱0.00</span>
                            </div>
                            <div class="info-item">
                                <label>Available Credit:</label>
                                <span id="availableCredit">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="item_description">Item Description *</label>
                    <textarea id="item_description" name="item_description" rows="3" required placeholder="Describe the items or services purchased on credit"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required onchange="calculateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="unit_price">Unit Price (₱) *</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" required onchange="calculateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="total_amount">Total Amount (₱) *</label>
                        <input type="number" id="total_amount" name="total_amount" step="0.01" min="0" readonly style="background: #f8f9fa;">
                    </div>
                </div>
                
                <div id="creditWarning" class="alert alert-warning" style="display: none;">
                    <strong>Credit Limit Warning:</strong> This transaction will exceed the customer's credit limit.
                    <div style="margin-top: 10px;">
                        <label>
                            <input type="checkbox" name="override_credit_limit" id="override_credit_limit">
                            I understand and want to proceed with this transaction
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Record Transaction</button>
                    <a href="transactions.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <script>
        function updateCustomerInfo() {
            const select = document.getElementById('customer_id');
            const option = select.options[select.selectedIndex];
            
            if (select.value) {
                const balance = parseFloat(option.dataset.balance) || 0;
                const limit = parseFloat(option.dataset.limit) || 0;
                const available = limit - balance;
                
                document.getElementById('currentBalance').textContent = '₱' + balance.toFixed(2);
                document.getElementById('creditLimit').textContent = '₱' + limit.toFixed(2);
                document.getElementById('availableCredit').textContent = '₱' + available.toFixed(2);
                document.getElementById('customerInfo').style.display = 'block';
                
                checkCreditLimit();
            } else {
                document.getElementById('customerInfo').style.display = 'none';
                document.getElementById('creditWarning').style.display = 'none';
            }
        }
        
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const price = parseFloat(document.getElementById('unit_price').value) || 0;
            const total = quantity * price;
            
            document.getElementById('total_amount').value = total.toFixed(2);
            checkCreditLimit();
        }
        
        function checkCreditLimit() {
            const customerId = document.getElementById('customer_id').value;
            const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
            
            if (!customerId) return;
            
            const select = document.getElementById('customer_id');
            const option = select.options[select.selectedIndex];
            const currentBalance = parseFloat(option.dataset.balance) || 0;
            const creditLimit = parseFloat(option.dataset.limit) || 0;
            const newBalance = currentBalance + totalAmount;
            
            const warningDiv = document.getElementById('creditWarning');
            const submitBtn = document.getElementById('submitBtn');
            const overrideCheckbox = document.getElementById('override_credit_limit');
            
            if (newBalance > creditLimit) {
                warningDiv.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-disabled');
                
                overrideCheckbox.addEventListener('change', function() {
                    submitBtn.disabled = !this.checked;
                    if (this.checked) {
                        submitBtn.classList.remove('btn-disabled');
                    } else {
                        submitBtn.classList.add('btn-disabled');
                    }
                });
            } else {
                warningDiv.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-disabled');
            }
        }
        
        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
        </script>
        
        <style>
        .customer-info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .customer-info-box h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .btn-disabled {
            background: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        </style>
        <?php
        include 'includes/footer.php';
        break;
        
    default:
        // List transactions
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $transactions = $transaction->getAll($limit, $offset);
        $totalTransactions = $transaction->getTotalCount();
        $totalPages = ceil($totalTransactions / $limit);
        
        include 'includes/header.php';
        ?>
        <div class="transactions-list">
            <div class="list-header">
                <h2>Credit Transactions</h2>
                <div class="header-actions">
                    <a href="transactions.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Transaction
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Item Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td>#<?php echo $tx['transaction_id']; ?></td>
                                    <td><?php echo $tx['full_name']; ?></td>
                                    <td><?php echo substr($tx['item_description'], 0, 50) . (strlen($tx['item_description']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo $tx['quantity']; ?></td>
                                    <td><?php echo formatCurrency($tx['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($tx['total_amount']); ?></td>
                                    <td><?php echo formatDate($tx['transaction_date']); ?></td>
                                    <td><?php echo formatDate($tx['due_date']); ?></td>
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
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $currentUrl = $_SERVER['PHP_SELF'];
                    for ($i = 1; $i <= $totalPages; $i++):
                        $active = $i == $page ? 'active' : '';
                    ?>
                        <a href="<?php echo $currentUrl; ?>?page=<?php echo $i; ?>" class="page-link <?php echo $active; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        include 'includes/footer.php';
        break;
}
?>
