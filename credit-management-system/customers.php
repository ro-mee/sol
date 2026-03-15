<?php
require_once 'config/config.php';
require_once 'models/Customer.php';

requireLogin();
$page_title = 'Customers';

$customer = new Customer(getDBConnection());
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add':
            $data = [
                'full_name' => sanitize($_POST['full_name']),
                'phone_number' => sanitize($_POST['phone_number']),
                'address' => sanitize($_POST['address']),
                'credit_limit' => floatval($_POST['credit_limit']),
                'payment_terms' => intval($_POST['payment_terms']),
                'risk_classification' => sanitize($_POST['risk_classification'])
            ];
            
            if ($customer->create($data)) {
                $_SESSION['success'] = 'Customer created successfully';
            } else {
                $_SESSION['error'] = 'Failed to create customer';
            }
            redirect('customers.php');
            break;
            
        case 'edit':
            $id = intval($_POST['customer_id']);
            $data = [
                'full_name' => sanitize($_POST['full_name']),
                'phone_number' => sanitize($_POST['phone_number']),
                'address' => sanitize($_POST['address']),
                'credit_limit' => floatval($_POST['credit_limit']),
                'payment_terms' => intval($_POST['payment_terms']),
                'risk_classification' => sanitize($_POST['risk_classification'])
            ];
            
            if ($customer->update($id, $data)) {
                $_SESSION['success'] = 'Customer updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update customer';
            }
            redirect('customers.php');
            break;
            
        case 'delete':
            $id = intval($_POST['customer_id']);
            if ($customer->delete($id)) {
                $_SESSION['success'] = 'Customer deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete customer';
            }
            redirect('customers.php');
            break;
    }
}

// Handle different actions
switch ($action) {
    case 'add':
        include 'includes/header.php';
        ?>
        <div class="form-container">
            <div class="form-header">
                <h2>Add New Customer</h2>
                <a href="customers.php" class="btn btn-secondary">Back to List</a>
            </div>
            
            <form method="POST" action="" class="customer-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credit_limit">Credit Limit *</label>
                        <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_terms">Payment Terms (Days) *</label>
                        <select id="payment_terms" name="payment_terms" required>
                            <option value="7">7 Days</option>
                            <option value="15">15 Days</option>
                            <option value="30" selected>30 Days</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="risk_classification">Risk Classification *</label>
                        <select id="risk_classification" name="risk_classification" required>
                            <option value="low">Low Risk</option>
                            <option value="medium" selected>Medium Risk</option>
                            <option value="high">High Risk</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                    <a href="customers.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php
        include 'includes/footer.php';
        break;
        
    case 'edit':
        $id = intval($_GET['id']);
        $customerData = $customer->getById($id);
        
        if (!$customerData) {
            $_SESSION['error'] = 'Customer not found';
            redirect('customers.php');
        }
        
        include 'includes/header.php';
        ?>
        <div class="form-container">
            <div class="form-header">
                <h2>Edit Customer</h2>
                <a href="customers.php" class="btn btn-secondary">Back to List</a>
            </div>
            
            <form method="POST" action="" class="customer-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="customer_id" value="<?php echo $customerData['customer_id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $customerData['full_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo $customerData['phone_number']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo $customerData['address']; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credit_limit">Credit Limit *</label>
                        <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" value="<?php echo $customerData['credit_limit']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_terms">Payment Terms (Days) *</label>
                        <select id="payment_terms" name="payment_terms" required>
                            <option value="7" <?php echo $customerData['payment_terms'] == 7 ? 'selected' : ''; ?>>7 Days</option>
                            <option value="15" <?php echo $customerData['payment_terms'] == 15 ? 'selected' : ''; ?>>15 Days</option>
                            <option value="30" <?php echo $customerData['payment_terms'] == 30 ? 'selected' : ''; ?>>30 Days</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="risk_classification">Risk Classification *</label>
                        <select id="risk_classification" name="risk_classification" required>
                            <option value="low" <?php echo $customerData['risk_classification'] == 'low' ? 'selected' : ''; ?>>Low Risk</option>
                            <option value="medium" <?php echo $customerData['risk_classification'] == 'medium' ? 'selected' : ''; ?>>Medium Risk</option>
                            <option value="high" <?php echo $customerData['risk_classification'] == 'high' ? 'selected' : ''; ?>>High Risk</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                    <a href="customers.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php
        include 'includes/footer.php';
        break;
        
    case 'view':
        $id = intval($_GET['id']);
        $customerData = $customer->getById($id);
        $creditHistory = $customer->getCreditHistory($id);
        
        if (!$customerData) {
            $_SESSION['error'] = 'Customer not found';
            redirect('customers.php');
        }
        
        include 'includes/header.php';
        ?>
        <div class="customer-detail">
            <div class="detail-header">
                <h2>Customer Details</h2>
                <div class="header-actions">
                    <a href="customers.php?action=edit&id=<?php echo $id; ?>" class="btn btn-primary">Edit</a>
                    <a href="customers.php" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
            
            <div class="customer-info">
                <div class="info-section">
                    <h3>Basic Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name:</label>
                            <span><?php echo $customerData['full_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone Number:</label>
                            <span><?php echo $customerData['phone_number'] ?: 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Address:</label>
                            <span><?php echo $customerData['address'] ?: 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>Credit Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Credit Limit:</label>
                            <span><?php echo formatCurrency($customerData['credit_limit']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Current Balance:</label>
                            <span><?php echo formatCurrency($customerData['current_balance']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Payment Terms:</label>
                            <span><?php echo $customerData['payment_terms']; ?> Days</span>
                        </div>
                        <div class="info-item">
                            <label>Risk Classification:</label>
                            <span class="risk-badge risk-<?php echo $customerData['risk_classification']; ?>">
                                <?php echo ucfirst($customerData['risk_classification']); ?> Risk
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="credit-history">
                <h3>Credit History</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($creditHistory)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No credit history found</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $runningBalance = 0;
                                foreach ($creditHistory as $record): 
                                    if ($record['amount_paid']) {
                                        $runningBalance -= $record['amount_paid'];
                                    } else {
                                        $runningBalance += $record['total_amount'];
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo formatDate($record['transaction_date'] ?? $record['payment_date']); ?></td>
                                        <td><?php echo $record['item_description'] ?? 'Payment Received'; ?></td>
                                        <td><?php echo $record['total_amount'] ? formatCurrency($record['total_amount']) : '-'; ?></td>
                                        <td><?php echo $record['amount_paid'] ? formatCurrency($record['amount_paid']) : '-'; ?></td>
                                        <td><?php echo formatCurrency($runningBalance); ?></td>
                                        <td>
                                            <?php if ($record['amount_paid']): ?>
                                                <span class="status-badge status-paid">Payment</span>
                                            <?php else: ?>
                                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        include 'includes/footer.php';
        break;
        
    default:
        // List customers
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        if ($search) {
            $customers = $customer->search($search);
            $totalCustomers = count($customers);
        } else {
            $customers = $customer->getAll($limit, $offset);
            $totalCustomers = $customer->getTotalCount();
        }
        
        $totalPages = ceil($totalCustomers / $limit);
        
        include 'includes/header.php';
        ?>
        <div class="customers-list">
            <div class="list-header">
                <h2>Customers</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" id="search" placeholder="Search customers..." value="<?php echo $search; ?>">
                        <button type="button" onclick="searchCustomers()"><i class="fas fa-search"></i></button>
                    </div>
                    <a href="customers.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Credit Limit</th>
                            <th>Current Balance</th>
                            <th>Payment Terms</th>
                            <th>Risk Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No customers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="customer-link">
                                            <?php echo $c['full_name']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $c['phone_number'] ?: 'N/A'; ?></td>
                                    <td><?php echo formatCurrency($c['credit_limit']); ?></td>
                                    <td><?php echo formatCurrency($c['current_balance']); ?></td>
                                    <td><?php echo $c['payment_terms']; ?> days</td>
                                    <td>
                                        <span class="risk-badge risk-<?php echo $c['risk_classification']; ?>">
                                            <?php echo ucfirst($c['risk_classification']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="customers.php?action=view&id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="customers.php?action=edit&id=<?php echo $c['customer_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" onclick="confirmDelete(<?php echo $c['customer_id']; ?>, '<?php echo addslashes($c['full_name']); ?>')" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!$search && $totalPages > 1): ?>
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
        
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Confirm Delete</h3>
                    <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete customer "<span id="deleteCustomerName"></span>"?</p>
                    <p class="warning">This action cannot be undone and will also delete all related transactions and payments.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="customer_id" id="deleteCustomerId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function searchCustomers() {
            const search = document.getElementById('search').value;
            const url = new URL(window.location);
            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        function confirmDelete(id, name) {
            document.getElementById('deleteCustomerName').textContent = name;
            document.getElementById('deleteCustomerId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Search on Enter key
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
        </script>
        <?php
        include 'includes/footer.php';
        break;
}
?>
