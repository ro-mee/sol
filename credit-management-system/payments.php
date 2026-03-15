<?php
require_once 'config/config.php';
require_once 'models/Payment.php';
require_once 'models/CreditTransaction.php';
require_once 'models/Customer.php';

requireLogin();
$page_title = 'Payments';

$payment = new Payment(getDBConnection());
$transaction = new CreditTransaction(getDBConnection());
$customer = new Customer(getDBConnection());
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add':
            $transactionId = intval($_POST['transaction_id']);
            $customerId = intval($_POST['customer_id']);
            $amountPaid = floatval($_POST['amount_paid']);
            
            // Validate payment amount
            $transactionData = $transaction->getById($transactionId);
            $paidAmount = $payment->getTotalPaidForTransaction($transactionId);
            $remainingAmount = $transactionData['total_amount'] - $paidAmount;
            
            if ($amountPaid > $remainingAmount) {
                $_SESSION['error'] = 'Payment amount exceeds remaining balance. Remaining balance: ' . formatCurrency($remainingAmount);
                redirect('payments.php?action=add&transaction_id=' . $transactionId);
                exit;
            }
            
            $data = [
                'transaction_id' => $transactionId,
                'customer_id' => $customerId,
                'amount_paid' => $amountPaid,
                'payment_date' => $_POST['payment_date'],
                'payment_method' => sanitize($_POST['payment_method']),
                'notes' => sanitize($_POST['notes'])
            ];
            
            if ($payment->create($data)) {
                $_SESSION['success'] = 'Payment recorded successfully';
            } else {
                $_SESSION['error'] = 'Failed to record payment';
            }
            redirect('payments.php');
            break;
    }
}

// Handle different actions
switch ($action) {
    case 'add':
        $transactionId = intval($_GET['transaction_id'] ?? 0);
        $transactionData = null;
        $customerData = null;
        $previousPayments = [];
        $remainingAmount = 0;
        
        if ($transactionId) {
            $transactionData = $transaction->getById($transactionId);
            $customerData = $customer->getById($transactionData['customer_id']);
            $previousPayments = $payment->getByTransactionId($transactionId);
            
            $totalPaid = array_sum(array_column($previousPayments, 'amount_paid'));
            $remainingAmount = $transactionData['total_amount'] - $totalPaid;
        }
        
        // Get all unpaid transactions for dropdown
        $unpaidTransactions = $transaction->getOutstandingTransactions();
        
        include 'includes/header.php';
        ?>
        <div class="form-container">
            <div class="form-header">
                <h2>Record Payment</h2>
                <a href="payments.php" class="btn btn-secondary">Back to List</a>
            </div>
            
            <form method="POST" action="" class="payment-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="transaction_id">Select Transaction *</label>
                    <select id="transaction_id" name="transaction_id" required onchange="loadTransactionDetails()">
                        <option value="">Select Transaction</option>
                        <?php foreach ($unpaidTransactions as $tx): ?>
                            <option value="<?php echo $tx['transaction_id']; ?>" 
                                    data-customer="<?php echo $tx['customer_id']; ?>"
                                    data-amount="<?php echo $tx['total_amount']; ?>"
                                    data-description="<?php echo htmlspecialchars($tx['item_description']); ?>"
                                    data-date="<?php echo $tx['transaction_date']; ?>"
                                    data-customer-name="<?php echo htmlspecialchars($tx['full_name']); ?>">
                                #<?php echo $tx['transaction_id']; ?> - <?php echo $tx['full_name']; ?> - <?php echo formatCurrency($tx['total_amount']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="transactionDetails" style="display: none;">
                    <div class="transaction-info-box">
                        <h4>Transaction Details</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Customer:</label>
                                <span id="detailCustomer">-</span>
                            </div>
                            <div class="info-item">
                                <label>Transaction Date:</label>
                                <span id="detailDate">-</span>
                            </div>
                            <div class="info-item">
                                <label>Description:</label>
                                <span id="detailDescription">-</span>
                            </div>
                            <div class="info-item">
                                <label>Total Amount:</label>
                                <span id="detailAmount">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-history">
                        <h4>Previous Payments</h4>
                        <div id="previousPaymentsList">
                            <p class="text-center">No previous payments</p>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="customer_id" name="customer_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount_paid">Payment Amount (₱) *</label>
                        <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="0" required onchange="updateRemainingBalance()">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_date">Payment Date *</label>
                        <input type="date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Optional notes about this payment"></textarea>
                </div>
                
                <div id="paymentSummary" class="payment-summary-box" style="display: none;">
                    <h4>Payment Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <label>Original Amount:</label>
                            <span id="originalAmount">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <label>Previous Payments:</label>
                            <span id="previousPaymentsTotal">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <label>Current Payment:</label>
                            <span id="currentPayment">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <label>Remaining Balance:</label>
                            <span id="remainingBalance">₱0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                    <a href="payments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <script>
        function loadTransactionDetails() {
            const select = document.getElementById('transaction_id');
            const option = select.options[select.selectedIndex];
            
            if (select.value) {
                const customerId = option.dataset.customer;
                const amount = parseFloat(option.dataset.amount);
                const description = option.dataset.description;
                const date = option.dataset.date;
                const customerName = option.dataset.customerName;
                
                document.getElementById('customer_id').value = customerId;
                document.getElementById('detailCustomer').textContent = customerName;
                document.getElementById('detailDate').textContent = formatDate(date);
                document.getElementById('detailDescription').textContent = description;
                document.getElementById('detailAmount').textContent = '₱' + amount.toFixed(2);
                document.getElementById('originalAmount').textContent = '₱' + amount.toFixed(2);
                
                // Load previous payments via AJAX
                loadPreviousPayments(select.value, amount);
                
                document.getElementById('transactionDetails').style.display = 'block';
                document.getElementById('paymentSummary').style.display = 'block';
            } else {
                document.getElementById('transactionDetails').style.display = 'none';
                document.getElementById('paymentSummary').style.display = 'none';
            }
        }
        
        function loadPreviousPayments(transactionId, totalAmount) {
            // This would typically be an AJAX call to get payment history
            // For now, we'll simulate it
            fetch(`payments.php?action=get_payments&transaction_id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    displayPreviousPayments(data, totalAmount);
                })
                .catch(error => {
                    console.error('Error loading payments:', error);
                    displayPreviousPayments([], totalAmount);
                });
        }
        
        function displayPreviousPayments(payments, totalAmount) {
            const listContainer = document.getElementById('previousPaymentsList');
            
            if (payments.length === 0) {
                listContainer.innerHTML = '<p class="text-center">No previous payments</p>';
                updatePaymentSummary(0, totalAmount);
                return;
            }
            
            let html = '<div class="table-container"><table class="data-table">';
            html += '<thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead><tbody>';
            
            let totalPaid = 0;
            payments.forEach(payment => {
                html += `<tr>
                    <td>${formatDate(payment.payment_date)}</td>
                    <td>₱${parseFloat(payment.amount_paid).toFixed(2)}</td>
                    <td>${payment.payment_method}</td>
                </tr>`;
                totalPaid += parseFloat(payment.amount_paid);
            });
            
            html += '</tbody></table></div>';
            listContainer.innerHTML = html;
            
            updatePaymentSummary(totalPaid, totalAmount);
        }
        
        function updatePaymentSummary(totalPaid, totalAmount) {
            const currentPayment = parseFloat(document.getElementById('amount_paid').value) || 0;
            const remaining = totalAmount - totalPaid - currentPayment;
            
            document.getElementById('previousPaymentsTotal').textContent = '₱' + totalPaid.toFixed(2);
            document.getElementById('currentPayment').textContent = '₱' + currentPayment.toFixed(2);
            document.getElementById('remainingBalance').textContent = '₱' + remaining.toFixed(2);
            
            // Update remaining balance color
            const remainingElement = document.getElementById('remainingBalance');
            if (remaining < 0) {
                remainingElement.style.color = '#dc3545';
            } else if (remaining === 0) {
                remainingElement.style.color = '#28a745';
            } else {
                remainingElement.style.color = '#333';
            }
        }
        
        function updateRemainingBalance() {
            const select = document.getElementById('transaction_id');
            const option = select.options[select.selectedIndex];
            
            if (select.value) {
                const totalAmount = parseFloat(option.dataset.amount);
                // This would need to be updated with actual previous payments
                updatePaymentSummary(0, totalAmount);
            }
        }
        
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }
        
        // Initialize if transaction ID is provided
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const transactionId = urlParams.get('transaction_id');
            if (transactionId) {
                document.getElementById('transaction_id').value = transactionId;
                loadTransactionDetails();
            }
        });
        </script>
        
        <style>
        .transaction-info-box, .payment-summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .transaction-info-box h4, .payment-summary-box h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .payment-history {
            margin-bottom: 20px;
        }
        
        .payment-history h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
        }
        </style>
        <?php
        include 'includes/footer.php';
        break;
        
    default:
        // List payments
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $payments = $payment->getAll($limit, $offset);
        $totalPayments = $payment->getTotalCount();
        $totalPages = ceil($totalPayments / $limit);
        
        include 'includes/header.php';
        ?>
        <div class="payments-list">
            <div class="list-header">
                <h2>Payment History</h2>
                <div class="header-actions">
                    <a href="payments.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Record Payment
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Customer</th>
                            <th>Transaction ID</th>
                            <th>Amount Paid</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td>#<?php echo $p['payment_id']; ?></td>
                                    <td><?php echo $p['full_name']; ?></td>
                                    <td>#<?php echo $p['transaction_id']; ?></td>
                                    <td><?php echo formatCurrency($p['amount_paid']); ?></td>
                                    <td><?php echo formatDate($p['payment_date']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $p['payment_method'])); ?></td>
                                    <td><?php echo $p['notes'] ? substr($p['notes'], 0, 30) . '...' : '-'; ?></td>
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
