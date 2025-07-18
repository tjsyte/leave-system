# Leave Management System

A comprehensive, modern Leave Filing and Approval System built with PHP, MySQL, and Bootstrap 5. This system provides a complete solution for managing employee leave requests with role-based access control, real-time dashboards, and automated workflow processes.

## System Architecture & How It Works

### System Workflow Overview

The Leave Management System operates on a **role-based workflow** with two main user types:

1. **Employees** - File leave requests, track status, manage personal leave balance
2. **Managers** - Review, approve/reject requests, oversee team leave management, generate reports

### Complete Leave Request Workflow

```
Employee Files Request → Manager Reviews → Decision Made → System Updates → Notifications
     ↓                      ↓              ↓              ↓              ↓
1. Fill Leave Form     2. View Pending   3. Approve/     4. Update      5. Email/Dashboard
2. Check Balance       3. Review Details    Reject        Database       Notifications
3. Submit Request      4. Add Remarks    5. Add Remarks  6. Adjust      6. Status Updates
4. Get Confirmation                                      Balances
```

## User Roles & Permissions

### Employee Role
**Access Level**: Personal leave management only

**Capabilities:**
- File new leave requests with multiple leave types
- View personal leave request history and status
- Check real-time leave balance across all leave types
- Edit pending requests (before manager review)
- Cancel pending requests
- Update personal profile information
- View personal dashboard with statistics

**Restrictions:**
- Cannot view other employees' requests
- Cannot approve/reject any requests
- Cannot access management reports
- Cannot modify leave balances

### Manager Role
**Access Level**: Full system access and team management

**Capabilities:**
- All employee capabilities for personal requests
- View all employee leave requests across departments
- Approve or reject pending leave requests
- Add manager remarks to decisions
- View team statistics and department analytics
- Generate and export comprehensive reports
- Manage employee information and leave balances
- Access advanced dashboard with team insights
- Bulk process multiple requests
- View urgent requests requiring immediate attention

## Database Schema & Structure

### Core Tables

#### 1. `users` Table
```sql
- id (Primary Key)
- username (Unique login identifier)
- password (Hashed password)
- role (employee/manager)
- full_name, email, department
- created_at (Account creation timestamp)
```

#### 2. `leave_requests` Table
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- leave_type (Vacation, Sick, Personal, etc.)
- date_from, date_to (Leave period)
- days_requested (Calculated duration)
- reason (Employee's reason for leave)
- status (pending/approved/rejected)
- filed_date (When request was submitted)
- manager_remarks (Manager's comments)
- approved_by (Manager who processed request)
- approved_date (When decision was made)
```

#### 3. `leave_balances` Table
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- leave_type (Type of leave)
- total_earned (Annual allocation)
- used (Days already taken)
- balance (Remaining days available)
- year (Leave year tracking)
```

#### 4. `leave_types` Table
```sql
- id (Primary Key)
- leave_type (Name of leave type)
- default_balance (Standard allocation)
- description (Leave type details)
- is_active (Enable/disable leave type)
```

## System Components & File Structure

### Core Application Files

#### **Entry Point & Authentication**
- `index.php` - Landing page with role-based redirection
- `auth/login.php` - Secure login with session management
- `auth/logout.php` - Session cleanup and logout

#### **Configuration & Database**
- `config/db_connect.php` - Database connection and utility functions
- `leave_system.sql` - Complete database schema with sample data

#### **Employee Module** (`employee/`)
- `dashboard.php` - Personal statistics, recent requests, upcoming leaves
- `file_leave.php` - Leave request form with balance validation
- `my_leaves.php` - Personal request history with search/filter
- `leave_balance.php` - Detailed leave balance breakdown
- `edit_leave.php` - Modify pending requests
- `profile.php` - Personal profile management

#### **Manager Module** (`manager/`)
- `dashboard.php` - Team overview, pending approvals, department stats
- `pending_approvals.php` - Review and process leave requests
- `all_leaves.php` - Complete leave request management
- `employees.php` - Employee information and balance management
- `reports.php` - Analytics, trends, and export functionality
- `process_request.php` - Backend request processing logic

#### **Shared Components** (`includes/`)
- `header.php` - Navigation sidebar and top header
- `footer.php` - Common footer and JavaScript includes
- `table_component.php` - Reusable data table with pagination

## Key System Features

### Real-time Dashboard Analytics
- **Employee Dashboard**: Personal leave statistics, upcoming approved leaves, recent activity
- **Manager Dashboard**: Team metrics, urgent requests, department comparisons, leave trends

### Advanced Search & Filtering
- Multi-field search across employee names, leave types, reasons
- Date range filtering for specific periods
- Status-based filtering (pending, approved, rejected)
- Department and leave type categorization

### Reporting & Export System
- Comprehensive leave reports with date ranges
- Department-wise analytics and comparisons
- CSV export functionality for external analysis
- Leave trend visualization with Chart.js integration

### Smart Validation & Balance Management
- Real-time leave balance checking before submission
- Date validation preventing past dates and invalid ranges
- Automatic days calculation including weekends
- Balance deduction upon approval

### Status Tracking & Notifications
- Real-time status updates (pending → approved/rejected)
- Manager remarks system for decision transparency
- Urgent request alerts for time-sensitive leaves
- Dashboard notifications for pending actions

## Technology Stack

### Backend Technologies
- **PHP 7.4+** - Server-side scripting with MySQLi
- **MySQL 5.7+** - Relational database with foreign key constraints
- **Session Management** - Secure user authentication and state management

### Frontend Technologies
- **Bootstrap 5.3.0** - Responsive CSS framework
- **FontAwesome 6.4.0** - Icon library for UI elements
- **Chart.js** - Interactive charts for analytics
- **Custom CSS** - Enhanced styling and animations

### Development Environment
- **Apache Server** - Web server (XAMPP recommended)
- **phpMyAdmin** - Database administration interface

## Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Modern web browser
- Text editor (optional for customization)

### Step-by-Step Installation

1. **Download and Install XAMPP**
   ```bash
   Download from: https://www.apachefriends.org/
   Install and start Apache + MySQL services
   ```

2. **Setup Project Files**
   ```bash
   Copy project to XAMPP htdocs
   Linux/Mac: /opt/lampp/htdocs/leave-system/
   Windows: C:\xampp\htdocs\leave-system\
   ```

3. **Database Configuration**
   ```bash
   Open phpMyAdmin: http://localhost/phpmyadmin
   Create database: leave_system
   Import: leave_system.sql
   ```

4. **Access Application**
   ```bash
   Open browser: http://localhost/leave-system/
   Auto-redirects to login page
   ```

## Demo Accounts & Testing

### Manager Account
- **Username**: `manager` | **Password**: `password`
- **Full Access**: All system features, approval rights
- **Department**: Human Resources

### Employee Accounts
- **Username**: `employee` | **Password**: `password` (IT Department)
- **Username**: `employee2` | **Password**: `password` (Marketing)
- **Username**: `employee3` | **Password**: `password` (Finance)

### Sample Data Included
- 30+ sample leave requests across 2024
- Pre-configured leave balances for all users
- Multiple leave types with different allocations
- Approved, pending, and rejected request examples

## Security Features

### Authentication & Authorization
- Session-based authentication with timeout
- Role-based access control (RBAC)
- SQL injection prevention with prepared statements
- Input sanitization and validation

### Data Protection
- Password hashing ready for production
- CSRF protection mechanisms
- XSS prevention with output escaping
- Secure session configuration

## How to Use the System

### For Employees:

1. **Login** with your credentials
2. **Dashboard** shows your leave overview and quick stats
3. **File Leave Request**:
   - Select leave type and dates
   - System validates against your balance
   - Add detailed reason
   - Submit for manager approval
4. **Track Requests** in "My Leave Requests"
5. **Check Balance** anytime in "Leave Balance"

### For Managers:

1. **Login** with manager credentials
2. **Dashboard** shows team overview and urgent items
3. **Review Pending Requests**:
   - View employee details and request information
   - Add manager remarks
   - Approve or reject with reasons
4. **Monitor Team** through "All Leave Requests"
5. **Generate Reports** for analytics and planning

## Customization Options

### Adding New Leave Types
```php
$leave_types = [
    'Vacation Leave',
    'Sick Leave',
    'Your New Leave Type',
];
```

### Modifying Leave Balances
```sql
UPDATE leave_balances 
SET total_earned = 20, balance = balance + 5 
WHERE leave_type = 'Vacation Leave';
```

### UI Customization
```css
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
}
```

## Troubleshooting

### Common Issues & Solutions

**Database Connection Failed**
```bash
Check XAMPP MySQL service is running
Verify credentials in config/db_connect.php
Ensure leave_system database exists
```

**Login Not Working**
```bash
Use exact demo credentials: manager/password
Check if users table has data
Verify session configuration
```

**Styling Issues**
```bash
Check Bootstrap CDN connection
Verify FontAwesome CDN access
Clear browser cache
```

**Permission Errors**
```bash
Linux/Mac: chmod 755 leave-system/
Windows: Run XAMPP as administrator
```

## Production Deployment

### Security Checklist
- [ ] Change all default passwords
- [ ] Enable password hashing
- [ ] Configure HTTPS/SSL
- [ ] Set secure session settings
- [ ] Add input validation
- [ ] Enable error logging

### Performance Optimization
- [ ] Add database indexes
- [ ] Enable caching
- [ ] Minify CSS/JS files
- [ ] Optimize images
- [ ] Configure server compression

## System Statistics

- **Database Tables**: 4 core tables with relationships
- **User Roles**: 2 distinct roles with different permissions
- **Leave Types**: 8+ configurable leave types
- **Demo Data**: 30+ sample requests for testing
- **File Structure**: 25+ PHP files organized by functionality
- **Responsive Design**: Mobile, tablet, and desktop optimized

## Support & Documentation

### Getting Help
- Review this comprehensive README
- Check code comments for implementation details
- Use demo accounts for testing
- Examine sample data for understanding

### System Requirements
- **Minimum**: PHP 7.4, MySQL 5.7, Apache 2.4
- **Recommended**: PHP 8.0+, MySQL 8.0+, SSD storage
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+

## License

This project is open source and available under the MIT License.

---

## Acknowledgments

- **Bootstrap Team** - Responsive CSS framework
- **FontAwesome** - Comprehensive icon library
- **Chart.js** - Interactive data visualization
- **PHP Community** - Robust server-side scripting
- **MySQL** - Reliable database management

---

**Efficient Leave Management Made Simple!**

*This system streamlines the entire leave management process from request filing to approval, providing transparency, efficiency, and comprehensive tracking for both employees and managers.*
