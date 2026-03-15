# Digital Credit Management System

A comprehensive web-based credit and accounts receivable management system designed for small retail and wholesale businesses to digitally track customer credit transactions, payments, and outstanding balances.

## Features

### Core Modules
- **Admin Authentication** - Secure login system with session management
- **Dashboard** - Real-time business insights and statistics
- **Customer Management** - Complete CRUD operations for customer profiles
- **Credit Transactions** - Record and track credit sales with automatic balance updates
- **Payment Management** - Handle partial and full payments with transaction linking
- **Accounts Receivable Ledger** - Digital replacement for manual logbooks with filtering
- **Collections & Reminders** - Track overdue accounts and send reminders
- **Reporting & Analytics** - Comprehensive reports with PDF/CSV export
- **Audit Trail** - Complete system action logging

### Key Features
- Credit limit control with override options
- Risk classification and assessment
- Aging reports (0-30, 31-60, 61-90+ days)
- Payment method tracking
- Customer credit utilization monitoring
- Top debtors identification
- Bulk reminder functionality
- Responsive design for mobile devices

## Technology Stack

### Backend
- **PHP 7.4+** (MVC-style without heavy frameworks)
- **MySQL/MariaDB** database
- **PDO** for secure database operations
- **Prepared statements** for SQL injection prevention

### Frontend
- **HTML5** semantic markup
- **CSS3** with responsive design
- **Vanilla JavaScript** (no frameworks required)
- **Font Awesome** icons
- **AJAX** for dynamic interactions

## Installation

### Prerequisites
- XAMPP/WAMP/MAMP or similar local server environment
- PHP 7.4 or higher
- MySQL/MariaDB
- Modern web browser

### Setup Instructions

1. **Download and Extract**
   ```bash
   # Extract the project to your web server directory
   # For XAMPP: C:/xampp/htdocs/credit-management-system/
   ```

2. **Database Setup**
   ```sql
   # Import the database schema
   # Open phpMyAdmin and import database/database.sql
   ```

3. **Configuration**
   ```php
   # Edit config/config.php if needed
   # Default database settings:
   DB_HOST = 'localhost'
   DB_NAME = 'credit_management'
   DB_USER = 'root'
   DB_PASS = '' (empty for XAMPP)
   ```

4. **Access the Application**
   ```
   http://localhost/credit-management-system/
   ```

5. **Default Login**
   ```
   Username: admin
   Password: admin123
   ```

## Project Structure

```
credit-management-system/
├── config/
│   └── config.php              # Database and app configuration
├── database/
│   └── database.sql            # Complete database schema
├── models/
│   ├── Admin.php               # Admin authentication model
│   ├── Customer.php            # Customer management model
│   ├── CreditTransaction.php   # Transaction management model
│   ├── Payment.php             # Payment processing model
│   └── Report.php              # Reporting and analytics model
├── views/
│   └── login.php               # Login page template
├── reports/
│   ├── summary_report.php      # Summary report template
│   ├── aging_report.php        # Aging report template
│   ├── customer_balance_report.php # Customer balance report
│   ├── payment_collection_report.php # Payment collection report
│   └── top_debtors_report.php  # Top debtors report
├── includes/
│   ├── header.php              # Common header template
│   └── footer.php              # Common footer template
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet
│   └── js/
│       ├── main.js             # Main JavaScript functions
│       └── validation.js       # Form validation functions
├── login.php                   # Login page controller
├── logout.php                  # Logout handler
├── dashboard.php               # Dashboard controller
├── customers.php               # Customer management controller
├── transactions.php            # Credit transactions controller
├── payments.php                # Payment management controller
├── ledger.php                  # Accounts receivable ledger
├── collections.php             # Collections and reminders
├── reports.php                 # Reports and analytics
├── audit.php                   # Audit trail viewer
└── README.md                   # This file
```

## Database Schema

### Main Tables
- **admins** - Administrator accounts
- **customers** - Customer credit profiles
- **credit_transactions** - Credit sale records
- **payments** - Payment records
- **audit_logs** - System action logs

### Key Features
- Foreign key relationships for data integrity
- Proper indexing for performance
- Views for complex reporting queries
- Automatic timestamp tracking

## Usage Guide

### Customer Management
1. Navigate to **Customers** from the sidebar
2. Click **Add Customer** to create new customer profiles
3. Set credit limits, payment terms, and risk classification
4. View customer details and credit history

### Credit Transactions
1. Go to **Credit Transactions** → **New Transaction**
2. Select customer and enter transaction details
3. System automatically calculates due dates based on payment terms
4. Credit limit validation with override option

### Payment Processing
1. Navigate to **Payments** → **Record Payment**
2. Select transaction or customer
3. Enter payment amount (partial or full)
4. System automatically updates customer balance

### Collections Management
1. Access **Collections** from the sidebar
2. View overdue accounts by aging buckets
3. Send reminders to individual or multiple customers
4. Track collection effectiveness

### Reporting
1. Go to **Reports & Analytics**
2. Choose from various report types:
   - Summary Report
   - Aging Report
   - Customer Balance Report
   - Payment Collection Report
   - Top Debtors Report
3. Export reports to PDF or CSV

## Security Features

- **Password Hashing** - Using PHP's PASSWORD_DEFAULT
- **Session Management** - Secure session handling
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Input sanitization
- **CSRF Protection** - Form tokens (can be enhanced)
- **Audit Trail** - Complete action logging

## Customization

### Adding New Fields
1. Update database schema in `database/database.sql`
2. Modify corresponding model files in `models/`
3. Update view templates in `views/` or controller files

### Custom Reports
1. Create new report template in `reports/`
2. Add report logic to `models/Report.php`
3. Update `reports.php` to include new report type

### Styling
- Modify `assets/css/style.css` for visual changes
- Responsive design built with CSS Grid and Flexbox
- Color scheme uses CSS variables for easy theming

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Performance Considerations

- Database indexes on frequently queried columns
- Pagination for large datasets
- Optimized SQL queries with proper joins
- Minimal JavaScript for fast loading
- CSS optimization for quick rendering

## Future Enhancements

- SMS/Email reminder integration
- Advanced reporting with charts
- Multi-user role management
- API integration for accounting software
- Mobile app development
- Automated payment processing
- Advanced analytics dashboard

## Support

For issues and questions:
1. Check the error logs in XAMPP
2. Verify database connection settings
3. Ensure proper file permissions
4. Test with default admin credentials

## License

This project is provided as-is for educational and development purposes. Feel free to modify and distribute according to your needs.

---

**Note**: This system is designed for local deployment with XAMPP. For production use, additional security measures and optimizations are recommended.
