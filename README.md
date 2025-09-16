# Muhdin General Trading - Inventory Management System

A modern, robust inventory management system for Muhdin General Trading, built with Laravel and Livewire. This system provides comprehensive tools for managing inventory, sales, purchases, and customer relationships across multiple locations.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-8892BF.svg)](https://php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg)](https://laravel.com/)
[![Bootstrap 5.3](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg)](https://getbootstrap.com/)

## ✨ Key Features

- **Multi-location Inventory** - Manage stock across multiple branches and warehouses
- **Sales & Purchases** - Complete sales and purchase order management
- **Customer Management** - Track customer information and credit history
- **Reporting** - Generate detailed reports for business insights
- **Role-based Access** - Granular permissions for different user roles
- **Modern UI** - Clean, responsive interface with dark/light mode

## 🚀 Docker-Based Quick Start

This project is fully containerized with Docker. No local PHP, Node, or MySQL installation is required.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) or Docker Engine.

### Installation & Setup

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-org/muhdin-trading.git
    cd muhdin-trading
    ```

2.  **Copy the environment file:**
    A `.env` file is created from `production.env` automatically if it doesn't exist. Make sure the database credentials in `.env` are set as follows for the Docker environment:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=db
    DB_PORT=3306
    DB_DATABASE=amtradingplc
    DB_USERNAME=root
    DB_PASSWORD=root
    ```

3.  **Build and run the containers:**
    ```bash
    docker-compose up -d --build
    ```
    This command will build the images and start the `app`, `webserver`, and `db` services in detached mode.

4.  **Install Composer dependencies:**
    ```bash
    docker-compose exec app composer install
    ```

5.  **Generate application key:**
    ```bash
    docker-compose exec app php artisan key:generate
    ```

6.  **Run database migrations:**
    ```bash
    docker-compose exec app php artisan migrate --seed
    ```

7.  **Install NPM dependencies:**
    ```bash
    docker-compose run --rm node npm install
    ```

8.  **Access the application:**
    You can now access the application at [http://localhost:8000](http://localhost:8000).

### Running Commands

All commands (`php artisan`, `composer`, `npm`) should be run inside their respective containers.

-   **Artisan commands:**
    ```bash
    docker-compose exec app php artisan <command>
    ```
    Example: `docker-compose exec app php artisan cache:clear`

-   **Composer commands:**
    ```bash
    docker-compose exec app composer <command>
    ```
    Example: `docker-compose exec app composer update`

-   **NPM commands:**
    ```bash
    docker-compose run --rm node npm <command>
    ```
    Example: `docker-compose run --rm node npm run dev`

### Default Credentials

- **Super Admin**:
  - Email: admin@muhdin.com
  - Password: password

## 📚 Documentation

For detailed documentation, please refer to the [Documentation Wiki](https://github.com/your-org/muhdin-trading/wiki).

## 🛠 Development

Please refer to our [Development Rules](DEVELOPMENT_RULES.md) for coding standards and contribution guidelines.

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) before submitting pull requests.

## Features

- **Multi-branch & Multi-warehouse Support**: Manage inventory across multiple locations
- **Role-based Access Control**: Granular permissions for different user roles
- **Inventory Management**: Track stock levels, transfers, and adjustments
- **Advanced Sales Management**: Comprehensive sales processing with multiple payment options
- **Purchase Management**: Create and manage purchase orders
- **Credit Management**: Track customer credits and payment history with early closure negotiation
- **Reporting**: Generate reports for inventory, sales, and purchases
- **User Management**: Create and manage users with specific roles and permissions

## System Requirements

- PHP >= 8.1
- MySQL >= 8.0
- Node.js >= 16
- Composer
- NPM

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/stock360.git
   cd stock360
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install NPM dependencies:
   ```bash
   npm install
   ```

4. Create environment file:
   ```bash
   cp .env.example .env
   ```

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Configure your database in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=stock360
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

7. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

8. Compile assets:
   ```bash
   npm run dev
   ```

9. Start the development server:
   ```bash
   php artisan serve
   ```

## System Users

The system comes with several pre-configured users with different roles and permissions:

### Super Admin
- **Email**: superadmin@stock360.com
- **Password**: password
- **Role**: SuperAdmin
- **Permissions**: Full system access
- **Position**: System Administrator

### General Manager
- **Email**: gm@stock360.com
- **Password**: password
- **Role**: GeneralManager
- **Permissions**: Full system access (similar to SuperAdmin)
- **Position**: General Manager

### Branch Managers
- **Email Pattern**: branch-manager-{index}@stock360.com
- **Password**: password
- **Role**: BranchManager
- **Permissions**:
  - View/Create/Edit items and categories
  - View warehouses, users, and branches
  - View stock and stock history
  - Create and approve purchases
  - Create sales
  - View reports
- **Position**: Branch Manager

### Warehouse Users
- **Email Pattern**: warehouse-{index}@stock360.com
- **Password**: password
- **Role**: WarehouseUser
- **Permissions**:
  - View and edit items
  - View categories
  - View warehouses
  - View, adjust, and track stock
  - Create transfers
- **Position**: Warehouse Supervisor

### Sales Users
- **Email Pattern**: sales-{index}@stock360.com
- **Password**: password
- **Role**: Sales
- **Permissions**:
  - View items and categories
  - View stock
  - Create and edit sales
  - Manage customers
- **Position**: Sales Representative

## Sales Management System

### Sales Creation Flow

The system provides a comprehensive sales creation interface with multiple payment options and intelligent features:

#### **Step 1: Basic Information**
- **Date Selection**: Set the sale date (defaults to current date)
- **Customer Selection**: Search and select customers by name, phone, or email
- **Warehouse Selection**: Choose warehouse (auto-assigned based on user permissions)

#### **Step 2: Item Management**
- **Smart Item Search**: Search items by name or SKU with real-time filtering
- **Stock Validation**: Automatic stock level checking and validation
- **Price Management**: Auto-fill selling prices with manual override capability
- **Quantity Controls**: Increment/decrement buttons with stock limit validation
- **Item Editing**: Full edit capability for added items
- **Duplicate Prevention**: Prevents adding the same item twice

#### **Step 3: Payment Options**

The system supports five different payment methods with comprehensive validation and processing:

##### **1. Cash Payment**
- **Status**: ✅ Paid immediately
- **Process**: Full payment received in cash
- **Required Fields**: None (default selection)
- **Validation**: No additional validation required
- **Database Records**:
  - `payment_method`: "cash"
  - `payment_status`: "paid"
  - `paid_amount`: Full sale amount
  - `due_amount`: 0
- **Result**: Sale marked as "Paid", no outstanding balance
- **Credit Record**: None created

##### **2. Bank Transfer**
- **Status**: ✅ Paid immediately
- **Process**: Payment transferred to selected bank account
- **Required Fields**:
  - `bank_account_id`: Must select from configured bank accounts
- **Optional Fields**:
  - `receipt_url`: Bank transfer receipt URL
  - `receipt_image`: Upload receipt image
- **Validation**: 
  - Bank account must exist and be active
  - Bank account selection is mandatory
- **Database Records**:
  - `payment_method`: "bank_transfer"
  - `payment_status`: "paid"
  - `bank_account_id`: Selected bank account ID
  - `paid_amount`: Full sale amount
  - `due_amount`: 0
- **Result**: Sale marked as "Paid" with bank reference
- **Credit Record**: None created

##### **3. Telebirr Payment**
- **Status**: ✅ Paid immediately
- **Process**: Digital payment via Telebirr mobile money
- **Required Fields**:
  - `transaction_number`: Telebirr transaction ID (mandatory)
- **Optional Fields**:
  - `receipt_url`: Telebirr receipt URL
  - `receipt_image`: Screenshot of transaction
- **Validation**:
  - Transaction number cannot be empty
  - Transaction number format validation
- **Database Records**:
  - `payment_method`: "telebirr"
  - `payment_status`: "paid"
  - `transaction_number`: Telebirr transaction ID
  - `paid_amount`: Full sale amount
  - `due_amount`: 0
- **Result**: Sale marked as "Paid" with transaction reference
- **Credit Record**: None created

##### **4. Credit with Advance**
- **Status**: ⚠️ Partially Paid
- **Process**: Customer pays partial amount upfront, remainder becomes credit
- **Required Fields**:
  - `advance_amount`: Must be greater than 0 and less than total amount
- **Optional Fields**:
  - `notes`: Payment terms or credit notes
- **Validation**:
  - Advance amount > 0
  - Advance amount < total sale amount
  - Advance amount must be numeric
- **Database Records**:
  - `payment_method`: "credit_advance"
  - `payment_status`: "partial"
  - `advance_amount`: Customer advance payment
  - `paid_amount`: Advance amount
  - `due_amount`: Total - advance amount
- **Credit Record**: ✅ Automatically created
  - `amount`: Due amount (total - advance)
  - `paid_amount`: 0 (advance is recorded in sale, not credit)
  - `balance`: Due amount
  - `status`: "active"
  - `due_date`: 30 days from sale date
- **Result**: Sale shows paid amount and creates receivable credit

##### **5. Full Credit**
- **Status**: ❌ Credit (Unpaid)
- **Process**: No immediate payment required, full amount becomes credit
- **Required Fields**: None
- **Optional Fields**:
  - `notes`: Credit terms or customer agreement
- **Validation**: No additional validation required
- **Database Records**:
  - `payment_method`: "credit_full"
  - `payment_status`: "due"
  - `paid_amount`: 0
  - `due_amount`: Full sale amount
- **Credit Record**: ✅ Automatically created
  - `amount`: Full sale amount
  - `paid_amount`: 0
  - `balance`: Full sale amount
  - `status`: "active"
  - `due_date`: 30 days from sale date
- **Result**: Complete sale amount becomes receivable credit

#### **Step 4: Additional Features**
- **Shipping Fees**: Optional shipping charges added to total
- **Notes**: Additional sale notes and comments
- **Real-time Totals**: Live calculation of subtotals and final amounts

#### **Step 5: Confirmation & Processing**
- **Review Dialog**: Comprehensive sale summary before confirmation
- **Payment Summary**: Clear breakdown of amounts and payment status
- **Item Verification**: Final review of all items and quantities
- **One-Click Save**: Confirm and process the entire sale

### **Automatic System Actions**

When a sale is processed, the system automatically:

1. **Stock Management**
   - Deducts quantities from warehouse stock
   - Creates stock movement history
   - Updates available inventory levels

2. **Credit Management** (for credit sales)
   - Creates customer credit records
   - Sets 30-day default payment terms
   - Tracks credit balances and payment history

3. **Branch Assignment**
   - Auto-assigns sale to user's branch
   - Maintains branch-level sales tracking
   - Supports multi-branch reporting

4. **Reference Generation**
   - Creates unique sale reference numbers
   - Format: SALE-XXXXXXXX (8 random characters)
   - Ensures traceability and record keeping

### **User Permissions & Restrictions**

- **Warehouse Assignment**: Users assigned to specific warehouses can only operate within their warehouse
- **Branch Restrictions**: Branch users see only warehouses within their branch
- **Auto-Selection**: Single warehouse branches auto-select the warehouse
- **Permission Validation**: Real-time validation of user access rights

### **Mobile-First Design**

The sales interface is optimized for both desktop and mobile use:
- **Responsive Layout**: Adapts to all screen sizes
- **Touch-Friendly**: Large buttons and touch targets
- **Progressive Enhancement**: Works on basic devices
- **Offline Capability**: Core functions work without internet

### **Testing Guide - Payment Methods**

Use these detailed test scenarios to verify all payment functionality:

#### **Test Scenario 1: Cash Payment**
**Objective**: Test immediate cash payment processing
```
1. Navigate to: /admin/sales/create
2. Select customer: Any active customer
3. Add items: Any item with available stock
4. Payment method: Select "Cash Payment"
5. Expected behavior:
   - No additional fields required
   - Payment status badge shows "Paid" (green)
   - Total amount displayed correctly
6. Click "Review & Save"
7. Verify confirmation modal shows:
   - Payment Method: "Cash Payment"
   - Payment Status: "Paid"
8. Confirm sale
9. Expected results:
   - Sale created with payment_status = "paid"
   - paid_amount = total_amount
   - due_amount = 0
   - No credit record created
   - Stock deducted from warehouse
   - Redirects to sale details page
```

#### **Test Scenario 2: Bank Transfer**
**Objective**: Test bank transfer payment with account selection
```
1. Navigate to: /admin/sales/create
2. Select customer and add items (total: e.g., $500)
3. Payment method: Select "Bank Transfer"
4. Expected behavior:
   - Bank Account dropdown appears (required)
   - Payment status badge shows "Paid" (green)
5. Test validation:
   - Try to proceed without selecting bank account
   - Should show error: "Bank account is required for bank transfers"
6. Select a bank account from dropdown
7. Optional: Add receipt URL
8. Click "Review & Save"
9. Verify confirmation modal shows:
   - Payment Method: "Bank Transfer"
   - Payment Status: "Paid"
10. Confirm sale
11. Expected results:
    - Sale created with payment_status = "paid"
    - bank_account_id = selected account ID
    - paid_amount = total_amount
    - due_amount = 0
    - No credit record created
```

#### **Test Scenario 3: Telebirr Payment**
**Objective**: Test Telebirr digital payment processing
```
1. Navigate to: /admin/sales/create
2. Select customer and add items (total: e.g., $750)
3. Payment method: Select "Telebirr"
4. Expected behavior:
   - Transaction Number field appears (required)
   - Receipt URL field appears (optional)
   - Payment status badge shows "Paid" (green)
5. Test validation:
   - Try to proceed without transaction number
   - Should show error: "Transaction number is required for Telebirr payments"
6. Enter transaction number: "TB123456789"
7. Optional: Add receipt URL: "https://telebirr.com/receipt/123"
8. Click "Review & Save"
9. Verify confirmation modal shows:
   - Payment Method: "Telebirr"
   - Payment Status: "Paid"
10. Confirm sale
11. Expected results:
    - Sale created with payment_status = "paid"
    - transaction_number = "TB123456789"
    - paid_amount = total_amount
    - due_amount = 0
    - No credit record created
```

#### **Test Scenario 4: Credit with Advance**
**Objective**: Test partial payment with credit creation
```
1. Navigate to: /admin/sales/create
2. Select customer and add items (total: e.g., $1000)
3. Payment method: Select "Credit with Advance"
4. Expected behavior:
   - Advance Amount field appears (required)
   - Payment status badge shows "Partial" (blue)
   - Real-time credit calculation displayed
5. Test validation:
   - Enter advance amount = 0 → Should show error
   - Enter advance amount = $1000 (equal to total) → Should show error
   - Enter advance amount = $1200 (greater than total) → Should show error
6. Enter valid advance amount: $400
7. Expected behavior:
   - Credit amount shows: $600 ($1000 - $400)
   - Payment status updates correctly
8. Click "Review & Save"
9. Verify confirmation modal shows:
   - Payment Method: "Credit with Advance"
   - Payment Status: "Partially Paid"
   - Advance: $400.00
   - Balance: $600.00
10. Confirm sale
11. Expected results:
    - Sale created with payment_status = "partial"
    - advance_amount = 400
    - paid_amount = 400
    - due_amount = 600
    - Credit record created:
      * amount = 600
      * balance = 600
      * status = "active"
      * due_date = 30 days from today
```

#### **Test Scenario 5: Full Credit**
**Objective**: Test full credit sale without immediate payment
```
1. Navigate to: /admin/sales/create
2. Select customer and add items (total: e.g., $800)
3. Payment method: Select "Full Credit"
4. Expected behavior:
   - No additional fields required
   - Payment status badge shows "Due" (yellow/orange)
   - Info alert shows full amount will be recorded as credit
5. Optional: Add notes about credit terms
6. Click "Review & Save"
7. Verify confirmation modal shows:
   - Payment Method: "Full Credit"
   - Payment Status: "Credit (Unpaid)"
8. Confirm sale
9. Expected results:
   - Sale created with payment_status = "due"
   - paid_amount = 0
   - due_amount = 800
   - Credit record created:
     * amount = 800
     * balance = 800
     * status = "active"
     * due_date = 30 days from today
```

#### **Test Scenario 6: Shipping Fees**
**Objective**: Test shipping fee calculation with different payment methods
```
1. Navigate to: /admin/sales/create
2. Select customer and add items (subtotal: e.g., $500)
3. Add shipping fee: $50
4. Expected behavior:
   - Total amount updates to $550
   - Shipping fee shows in summary
5. Test with each payment method:
   - Cash: Total $550 paid immediately
   - Credit with Advance ($200): Advance $200, Credit $350
   - Full Credit: Full $550 becomes credit
6. Verify shipping fee is included in all calculations
```

#### **Test Scenario 7: Error Handling**
**Objective**: Test validation and error scenarios
```
1. Try to create sale without customer → Should show validation error
2. Try to create sale without items → Should show validation error
3. Try to add item with quantity > available stock → Should show error
4. Try Telebirr without transaction number → Should show validation error
5. Try Bank Transfer without selecting account → Should show validation error
6. Try Credit with Advance with invalid amounts → Should show validation errors
7. Verify all error messages are clear and helpful
```

### **Database Verification**

After each test, verify the following database records:

#### **Sales Table**
```sql
SELECT id, reference_no, payment_method, payment_status, 
       total_amount, paid_amount, due_amount, advance_amount,
       transaction_number, bank_account_id
FROM sales 
ORDER BY created_at DESC LIMIT 5;
```

#### **Credits Table** (for credit sales)
```sql
SELECT id, customer_id, amount, paid_amount, balance, 
       status, reference_no, due_date
FROM credits 
WHERE reference_type = 'sale'
ORDER BY created_at DESC LIMIT 5;
```

#### **Stock Movements**
```sql
SELECT id, item_id, warehouse_id, quantity_change, 
       movement_type, reference_id, notes
FROM stock_movements 
ORDER BY created_at DESC LIMIT 10;
```

### **Navigation**

- **Access**: `/admin/sales/create`
- **Permissions**: Users with sales creation rights
- **Return**: Automatically redirects to sale details after creation

## Credit Payment System with Early Closure

### Overview

The Credit Payment System provides advanced features for managing outstanding credits with intelligent early closure opportunities and negotiated pricing. It enables businesses to maximize profit through vendor negotiations and early credit settlement.

### Key Features

- **Early Closure Opportunities**: Automatic detection when 50%+ of credit is paid
- **Full Payment Closing**: Special handling for 100% paid credits  
- **Negotiated Pricing**: Enter negotiated unit prices for purchased items
- **Profit/Loss Calculation**: Real-time calculation of savings from negotiations
- **Purchase Integration**: Updates purchase items with closing prices and profit/loss data
- **Simplified Interface**: Clean, mobile-responsive design for field use

### Early Closure Workflow

#### **1. Eligibility Detection**
- System automatically detects credits with 50%+ payment
- Shows early closure opportunity with payment progress
- Different handling for 100% paid credits (closing prices only)
- Visual indicators for payment status and eligibility

#### **2. Negotiation Interface**
- Clean, simplified form showing purchased items
- Unit costs displayed per individual item (divided by unit_quantity)
- Real-time profit/loss calculation as prices are entered
- Mobile-responsive design for field use
- Professional header showing customer/supplier with purchase link

#### **3. Profit/Loss Analysis**
- Calculates savings per item and total
- Shows profit/loss percentages
- Updates purchase records with closing data
- Stores negotiated prices for future reference
- Color-coded profit/loss indicators

### Payment Methods Supported

- **Cash Payment**: Immediate full payment
- **Bank Transfer**: Payment to selected bank account
- **Telebirr**: Digital payment with transaction reference
- **Credit with Advance**: Partial payment with remaining credit
- **Full Credit**: Complete credit without immediate payment

### Database Integration

The system automatically:
- Updates purchase items with closing prices
- Calculates and stores profit/loss per item
- Records total savings in purchase record
- Maintains complete audit trail of negotiations
- Adds new columns: `closing_unit_price`, `total_closing_cost`, `profit_loss_per_item`

### User Interface Features

- **Simplified Header**: Shows customer/supplier name with purchase link
- **Payment Progress**: Real-time display of total, paid, and percentage
- **Clean Form**: Removed unnecessary fields for better UX
- **Responsive Design**: Works on desktop and mobile devices
- **Visual Feedback**: Color-coded profit/loss indicators

### Business Benefits

- **Maximize Profit**: Negotiate better prices with vendors
- **Early Closure**: Close credits before due date with savings
- **Data Tracking**: Complete record of negotiated prices
- **Profit Analysis**: Understand profit margins per item
- **Vendor Relationships**: Build better supplier relationships through negotiations

### Testing Guide - Credit Payment Scenarios

#### **Test Scenario 1: Early Closure Opportunity (50%+ Paid)**
```
Prerequisites:
- Credit with 50%+ payment made
- Purchase with multiple items
- User with credit payment permissions

Test Steps:
1. Navigate to: /admin/credit-payments/create/{credit_id}
2. Verify early closure opportunity appears
3. Click "Accept & Negotiate"
4. Enter negotiated prices for items
5. Verify real-time profit/loss calculation
6. Complete payment process

Expected Results:
- Early closure opportunity detected
- Negotiation form shows purchased items
- Unit costs displayed per individual item
- Profit/loss calculated in real-time
- Purchase items updated with closing prices
```

#### **Test Scenario 2: Full Payment Closing (100% Paid)**
```
Prerequisites:
- Credit with 100% payment made
- Purchase with items

Test Steps:
1. Navigate to credit payment page
2. Verify warning appears for full payment
3. Click "Enter Closing Prices"
4. Enter final closing prices
5. Complete the process

Expected Results:
- Warning message for full payment
- Different UI styling (warning colors)
- Closing prices recorded
- Purchase items updated
```

#### **Test Scenario 3: Unit Cost Calculation**
```
Prerequisites:
- Item with unit_quantity > 1 (e.g., 1 box = 10 items)
- Purchase with this item

Test Steps:
1. Create purchase with item (unit_cost = 6250 ETB, unit_quantity = 10)
2. Make credit payment
3. View negotiation form

Expected Results:
- Unit cost displayed as 625 ETB (6250 ÷ 10)
- Negotiated price input works correctly
- Calculations based on per-item costs
```

### Database Schema Updates

#### **Purchase Items Table**
```sql
-- New columns added for closing price tracking
ALTER TABLE purchase_items ADD COLUMN closing_unit_price DECIMAL(15,2) NULL;
ALTER TABLE purchase_items ADD COLUMN total_closing_cost DECIMAL(15,2) NULL;
ALTER TABLE purchase_items ADD COLUMN profit_loss_per_item DECIMAL(15,2) NULL;
```

#### **Sample Data Flow**
```sql
-- Original purchase item
INSERT INTO purchase_items (unit_cost, quantity) VALUES (6250.00, 5);

-- After negotiation (6250 ÷ 10 = 625 per item, negotiated to 600)
UPDATE purchase_items SET 
    closing_unit_price = 6000.00,  -- 600 per item × 10 items
    total_closing_cost = 30000.00, -- 6000 × 5 pieces
    profit_loss_per_item = 1250.00 -- (6250 - 6000) × 5 pieces
WHERE id = 1;
```

### API Endpoints

```
GET    /admin/credit-payments/create/{credit}  - Credit payment form
POST   /admin/credit-payments                  - Process payment
GET    /admin/credits/{credit}                 - View credit details
PATCH  /admin/credits/{credit}/close           - Close credit early
```

### Console Commands

```bash
# Verify credit payment calculations
php artisan credit:verify-calculations

# Generate credit payment report
php artisan credit:payment-report --from=2024-01-01 --to=2024-12-31

# Clean up old credit data
php artisan credit:cleanup --older-than=90
```

## Transfer Management System

### Overview

The Transfer Management System provides comprehensive inventory movement capabilities between warehouses and branches with advanced stock management, reservation system, and approval workflows. It ensures accurate stock tracking and prevents overselling through intelligent filtering and real-time availability calculations.

### Key Features

- **Multi-location Support**: Transfer between warehouses and branches
- **Smart Stock Filtering**: Only shows items with actual available stock
- **Stock Reservation System**: Prevents race conditions and overselling
- **Approval Workflow**: Configurable approval process with role-based permissions
- **Real-time Stock Calculation**: Accounts for pending transfers and reservations
- **Atomic Transactions**: Complete rollback on failures
- **Audit Trail**: Complete history of all transfer activities

### Transfer Flow Overview

The transfer system follows a structured workflow designed to ensure inventory accuracy and proper authorization:

```
[Creation] → [Stock Reservation] → [Pending] → [Approval] → [In Transit] → [Completion] → [Stock Movement]
     ↓              ↓                  ↓            ↓           ↓               ↓               ↓
Stock Check → Reserve Items → Await Review → Authorize → Track Movement → Execute Transfer → Update Inventory
```

### Transfer Creation Flow

#### **Step 1: Location Selection**

**Source Location Configuration**
- **Warehouse to Warehouse**: Direct warehouse-to-warehouse transfers
- **Warehouse to Branch**: Send stock from warehouse to branch warehouses
- **Branch to Warehouse**: Consolidate stock from branch to main warehouse
- **Branch to Branch**: Inter-branch inventory movement

**Smart Location Filtering**
- **User Permissions**: Only shows accessible locations based on user role
- **Auto-selection**: Single warehouse branches auto-select their warehouse
- **Validation**: Prevents selecting same source and destination

**Location Types Supported**
```
Source Types:
├── Warehouse (Direct warehouse access)
└── Branch (All warehouses within branch)

Destination Types:
├── Warehouse (Specific warehouse delivery)
└── Branch (Primary warehouse in branch)
```

#### **Step 2: Smart Item Selection**

**Intelligent Item Filtering**
The system implements advanced filtering to prevent transfer failures:

- **Stock Availability Check**: Only shows items with available stock > 0
- **Reservation Awareness**: Considers currently reserved stock
- **Real-time Calculation**: Updates as items are added/removed
- **Search Functionality**: Find items by name or SKU
- **Duplicate Prevention**: Prevents adding same item twice

**Enhanced Dropdown Display**
```
Item Display Format:
[Item Name] ([SKU]) - Available: [X.XX] [⚠️ if low stock]

Examples:
✅ Ethiopian Berbere Spice Mix (SPC-BERB-500G-001) - Available: 15.00
⚠️ Teff Seeds White Premium (SED-TEFF-WHT-001) - Available: 8.00 ⚠️
❌ Out of Stock Items - Not shown in dropdown
```

**Stock Calculation Logic**
```php
Available Stock = Total Stock - Reserved Stock - Pending Transfers
```

#### **Step 3: Item Management**

**Adding Items**
- **Quantity Validation**: Maximum allowed = available stock
- **Real-time Updates**: Stock availability updates instantly
- **Visual Indicators**: Low stock warnings and availability status
- **Editing Capability**: Modify quantities before transfer creation

**Item Display Features**
- **Available Stock Column**: Shows current availability
- **Status Indicators**: Visual confirmation of stock status
- **Edit/Remove Actions**: Full management capabilities
- **Validation Feedback**: Immediate error messages for invalid quantities

#### **Step 4: Transfer Confirmation**

**Review Modal**
- **Transfer Summary**: Source, destination, and item count
- **Error Display**: Any validation errors shown in modal
- **Status Updates**: Real-time processing feedback
- **Confirmation Required**: Explicit user confirmation before processing

### Transfer Status Workflow

The system implements a comprehensive status workflow to track transfers through their lifecycle:

#### **Status Definitions**

**1. Pending** 🟡
- **Description**: Transfer created, awaiting approval
- **Stock Impact**: Items reserved but not moved
- **Actions Available**: Approve, Reject, Cancel
- **Auto-transitions**: None (requires manual approval)

**2. Approved** 🟢
- **Description**: Transfer authorized, ready for processing
- **Stock Impact**: Items still reserved
- **Actions Available**: Mark In Transit, Complete, Cancel
- **Auto-transitions**: SuperAdmins may auto-approve on creation

**3. In Transit** 🔵
- **Description**: Items being physically moved
- **Stock Impact**: Items still at source location
- **Actions Available**: Complete, Cancel
- **Auto-transitions**: None (manual confirmation required)

**4. Completed** ✅
- **Description**: Transfer finished, stock moved successfully
- **Stock Impact**: Items moved to destination, reservations released
- **Actions Available**: View only (read-only)
- **Auto-transitions**: Stock automatically updated

**5. Rejected** ❌
- **Description**: Transfer denied by approver
- **Stock Impact**: Reservations released immediately
- **Actions Available**: View only
- **Auto-transitions**: Automatic reservation cleanup

**6. Cancelled** ❌
- **Description**: Transfer cancelled by user
- **Stock Impact**: Reservations released immediately
- **Actions Available**: View only
- **Auto-transitions**: Automatic reservation cleanup

#### **Workflow Transitions**

```
Transfer Status Flow:

    [Create Transfer]
           ↓
    🟡 PENDING ────────────────┐
           ↓                   ↓
    [Approve Transfer]    [Reject/Cancel]
           ↓                   ↓
    🟢 APPROVED               ❌ REJECTED/CANCELLED
           ↓                   ↓
    [Mark In Transit]    [Release Reservations]
           ↓                   
    🔵 IN TRANSIT              
           ↓                   
    [Complete Transfer]        
           ↓                   
    ✅ COMPLETED               
           ↓                   
    [Move Stock & Release Reservations]
```

### User Permissions & Role-Based Access

#### **Permission Matrix**

| User Role | Create Transfer | Approve Transfer | View All Transfers | Location Access |
|-----------|----------------|------------------|-------------------|-----------------|
| **SuperAdmin** | ✅ | ✅ (Auto-approve) | ✅ All | 🌐 All Locations |
| **GeneralManager** | ✅ | ✅ | ✅ All | 🌐 All Locations |
| **BranchManager** | ✅ | ✅ Branch Only | ✅ Branch | 🏢 Branch Locations |
| **WarehouseUser** | ✅ | ❌ | ✅ Warehouse | 🏭 Assigned Warehouse |
| **Sales** | ❌ | ❌ | ❌ | ❌ No Transfer Access |

#### **Location Access Rules**

**SuperAdmin & General Manager**
- Can transfer from/to any warehouse or branch
- Full system visibility and control
- Auto-approval capabilities

**Branch Manager**
- Can transfer within their branch
- Can transfer to/from other locations
- Branch-level approval authority
- See transfers affecting their branch

**Warehouse User**
- Can only transfer from their assigned warehouse
- Can send to any destination
- Cannot approve transfers
- See transfers from their warehouse

### Stock Reservation System

#### **How Reservations Work**

**Automatic Reservation Creation**
```php
When Transfer Created:
1. Calculate required stock for each item
2. Check available stock (total - reserved)
3. Create reservation record for 24 hours
4. Block stock from other transfers
5. Maintain reservation until completion/cancellation
```

**Reservation Properties**
- **Duration**: 24 hours (auto-expires)
- **Scope**: Item + Location specific
- **Effect**: Reduces available stock calculations
- **Cleanup**: Automatic when transfer completes/expires

**Stock Calculation with Reservations**
```php
Total Stock: 50 units
Reserved Stock: 12 units (from pending transfers)
Available Stock: 38 units (what users can transfer)
```

#### **Reservation Management**

**Automatic Actions**
- **Created**: When transfer moves to "pending" status
- **Released**: When transfer completes, rejects, or cancels
- **Expired**: After 24 hours of inactivity
- **Extended**: Can be manually extended by admins

**Manual Management** (Admin Only)
- **View Reservations**: Monitor all active reservations
- **Release Early**: Free up stock before completion
- **Extend Duration**: Add time for special cases
- **Cleanup Expired**: Remove old reservations

### Testing Guide - Transfer Scenarios

Use these comprehensive test scenarios to verify all transfer functionality:

#### **Test Scenario 1: Warehouse to Warehouse Transfer**
**Objective**: Test basic warehouse-to-warehouse transfer with approval workflow

```
Prerequisites:
- User with transfer creation permissions
- Source warehouse with stock (e.g., Warehouse A: 50 units of Item X)
- Destination warehouse (e.g., Warehouse B)

Test Steps:
1. Navigate to: /admin/transfers/create
2. Select Source Type: "Warehouse"
3. Select Source Location: "Warehouse A"
4. Select Destination Type: "Warehouse"
5. Select Destination Location: "Warehouse B"
6. Add Items:
   - Search for Item X
   - Verify dropdown shows: "Item X (SKU) - Available: 50.00"
   - Select item
   - Enter quantity: 20
   - Click "Add Item"
7. Verify item appears in transfer list
8. Click "Create Transfer"
9. In confirmation modal:
   - Verify transfer details
   - Click "Confirm & Create"

Expected Results:
- Transfer created with status "pending"
- Stock reservation created: Item X = 20 units
- Available stock updates: Item X shows 30 available (50 - 20 reserved)
- Transfer redirects to details page
- Transfer awaits approval
```

#### **Test Scenario 2: SuperAdmin Auto-Approval Flow**
**Objective**: Test automatic approval and completion for SuperAdmin users

```
Prerequisites:
- User with SuperAdmin role
- Source location with adequate stock

Test Steps:
1. Login as SuperAdmin
2. Create transfer following Scenario 1 steps
3. Complete transfer creation

Expected Results:
- Transfer created with status "pending"
- Transfer automatically approved (status: "approved")
- Transfer automatically completed (status: "completed")
- Stock physically moved from source to destination
- Reservations released
- Stock levels updated in both locations
```

#### **Test Scenario 3: Branch to Branch Transfer**
**Objective**: Test inter-branch transfer with warehouse selection

```
Prerequisites:
- Source branch with multiple warehouses and stock
- Destination branch
- User with branch-level permissions

Test Steps:
1. Navigate to transfer creation
2. Select Source Type: "Branch"
3. Select Source Branch: "Branch A"
4. Select Destination Type: "Branch"
5. Select Destination Branch: "Branch B"
6. Add items from branch stock
7. Verify system selects appropriate warehouses:
   - Source: Takes from warehouses with most stock first
   - Destination: Adds to primary warehouse in branch

Expected Results:
- Transfer creation succeeds
- Stock taken from multiple source warehouses if needed
- Stock added to primary warehouse in destination branch
- All warehouse movements tracked in stock history
```

#### **Test Scenario 4: Smart Stock Filtering**
**Objective**: Verify only available items appear in dropdown

```
Prerequisites:
- Items with various stock levels:
  * Item A: 50 total, 0 reserved = 50 available ✅
  * Item B: 20 total, 20 reserved = 0 available ❌
  * Item C: 15 total, 5 reserved = 10 available ✅
  * Item D: 0 total = 0 available ❌

Test Steps:
1. Navigate to transfer creation
2. Select source location
3. Open item dropdown
4. Search for items

Expected Results:
- Dropdown shows only Item A and Item C
- Item A displays: "Item A (SKU) - Available: 50.00"
- Item C displays: "Item C (SKU) - Available: 10.00 ⚠️" (low stock warning)
- Item B and Item D not shown in dropdown
- If no items available: "No items with available stock found at this location"
```

#### **Test Scenario 5: Transfer Approval Workflow**
**Objective**: Test manual approval process

```
Prerequisites:
- Transfer in "pending" status
- User with approval permissions
- User without approval permissions

Test Steps:
1. Create transfer as regular user (non-SuperAdmin)
2. Verify transfer status: "pending"
3. Login as user without approval permissions
4. Navigate to transfer details
5. Verify approval buttons not visible
6. Login as user with approval permissions
7. Navigate to transfer details
8. Test approval actions:
   - Click "Approve Transfer"
   - Verify status changes to "approved"
   - Click "Mark In Transit" 
   - Verify status changes to "in_transit"
   - Click "Complete Transfer"
   - Verify status changes to "completed"
   - Verify stock moved and reservations released

Expected Results:
- Status workflow followed correctly
- Buttons appear/disappear based on permissions
- Stock movements occur only on completion
- Audit trail maintained throughout process
```

#### **Test Scenario 6: Transfer Rejection & Cancellation**
**Objective**: Test rejection and cancellation workflows

```
Transfer Rejection Test:
1. Create transfer (status: "pending")
2. Login as approver
3. Navigate to transfer details
4. Click "Reject Transfer"
5. Add rejection reason
6. Confirm rejection

Expected Results:
- Transfer status: "rejected"
- Stock reservations released immediately
- Stock availability restored
- Rejection reason recorded
- Rejection timestamp and user logged

Transfer Cancellation Test:
1. Create transfer (status: "pending" or "approved")
2. Navigate to transfer details
3. Click "Cancel Transfer"
4. Confirm cancellation

Expected Results:
- Transfer status: "cancelled"
- Stock reservations released
- Cancellation timestamp recorded
```

#### **Test Scenario 7: Error Handling & Validation**
**Objective**: Test all validation scenarios and error handling

```
Test Cases:
1. Create transfer without selecting locations
   → Should show validation errors

2. Create transfer with same source and destination
   → Should prevent selection/show error

3. Try to add item without stock
   → Item should not appear in dropdown

4. Try to add quantity > available stock
   → Should show error and prevent addition

5. Try to create transfer without items
   → Should show validation error

6. Try to process transfer with insufficient stock (edge case)
   → Should show detailed error message

7. Test network/database errors
   → Should show user-friendly error messages

Expected Results:
- All validation errors display clearly
- Error messages are helpful and specific
- No transfers created with invalid data
- User can correct errors and retry
```

#### **Test Scenario 8: Stock Reservation Edge Cases**
**Objective**: Test reservation system under various conditions

```
Test Cases:
1. Multiple Concurrent Transfers:
   - User A creates transfer for 30 units
   - User B tries to create transfer for remaining stock
   - Verify User B sees reduced availability

2. Reservation Expiration:
   - Create transfer but don't complete within 24 hours
   - Verify reservation auto-expires
   - Verify stock becomes available again

3. Manual Reservation Management:
   - Create transfer
   - Use admin panel to release reservation early
   - Verify stock immediately available

Expected Results:
- No overselling occurs
- Stock calculations always accurate
- Expired reservations cleaned automatically
- Manual reservation management works correctly
```

### Database Verification

After each test, verify the following database records to ensure data integrity:

#### **Transfers Table**
```sql
-- Verify transfer creation and status updates
SELECT id, reference_code, source_type, source_id, 
       destination_type, destination_id, status, 
       user_id, approved_by, approved_at,
       created_at, updated_at
FROM transfers 
ORDER BY created_at DESC LIMIT 5;
```

#### **Transfer Items Table**
```sql
-- Verify transfer items are recorded correctly
SELECT ti.transfer_id, ti.item_id, ti.quantity, ti.unit_cost,
       i.name as item_name, i.sku,
       t.reference_code
FROM transfer_items ti
JOIN items i ON ti.item_id = i.id
JOIN transfers t ON ti.transfer_id = t.id
ORDER BY ti.created_at DESC LIMIT 10;
```

#### **Stock Reservations Table**
```sql
-- Verify reservations are created and managed properly
SELECT id, item_id, location_type, location_id, 
       quantity, reference_type, reference_id,
       expires_at, created_by, created_at
FROM stock_reservations 
WHERE reference_type = 'transfer'
ORDER BY created_at DESC LIMIT 10;
```

#### **Stock Movements Table**
```sql
-- Verify stock movements on transfer completion
SELECT id, warehouse_id, item_id, quantity_before, 
       quantity_after, quantity_change, reference_type,
       reference_id, description, user_id, created_at
FROM stock_history 
WHERE reference_type = 'transfer'
ORDER BY created_at DESC LIMIT 10;
```

#### **Stock Levels Verification**
```sql
-- Verify current stock levels match expectations
SELECT s.warehouse_id, s.item_id, s.quantity,
       w.name as warehouse_name, i.name as item_name,
       COALESCE(reserved.total_reserved, 0) as reserved_quantity
FROM stocks s
JOIN warehouses w ON s.warehouse_id = w.id
JOIN items i ON s.item_id = i.id
LEFT JOIN (
    SELECT item_id, location_id, SUM(quantity) as total_reserved
    FROM stock_reservations 
    WHERE location_type = 'warehouse' 
    AND expires_at > NOW()
    GROUP BY item_id, location_id
) reserved ON s.item_id = reserved.item_id AND s.warehouse_id = reserved.location_id
ORDER BY w.name, i.name;
```

### Advanced Admin Features

#### **Stock Reservations Dashboard**

**Access**: `/admin/stock-reservations`
**Permission**: `transfers.view`

**Features Available**:
- **Real-time Statistics**: Active, expired, total reservations
- **Reservation Management**: View, release, extend reservations
- **Bulk Operations**: Clean up expired reservations
- **Search & Filter**: Find specific reservations quickly

**Dashboard Metrics**:
```php
Active Reservations: 45        Expired Reservations: 3
Total Reserved Quantity: 1,247  Items with Reservations: 28
```

#### **Stock Reports Integration**

**Access**: `/admin/stock-reports`
**Permission**: `items.view`

**Enhanced Features**:
- **Reservation-Aware Reports**: Shows total vs available stock
- **Transfer Impact Analysis**: See how pending transfers affect availability
- **Multi-format Export**: CSV, JSON with reservation data
- **Real-time Calculations**: Always current stock status

### Console Commands

#### **Stock Reservation Management**
```bash
# Clean up expired reservations
php artisan stock:cleanup-reservations

# Preview what would be cleaned (dry run)
php artisan stock:cleanup-reservations --dry-run

# Extended cleanup with statistics
php artisan stock:cleanup-reservations --verbose
```

#### **Stock Reporting with Transfers**
```bash
# Generate stock report including reservations
php artisan stock:report --with-reservations

# Export transfer-aware stock report
php artisan stock:report --format=csv --with-reservations

# Generate report for specific location
php artisan stock:report --location=1 --location-type=warehouse
```

#### **Transfer System Maintenance**
```bash
# Verify transfer data integrity
php artisan transfer:verify-integrity

# Recalculate stock after transfer issues
php artisan stock:recalculate --verify

# Generate transfer audit report
php artisan transfer:audit-report --from=2024-01-01 --to=2024-12-31
```

### API Endpoints

#### **Transfer Management**
```
GET    /admin/transfers              - List all transfers
POST   /admin/transfers              - Create new transfer
GET    /admin/transfers/create       - Transfer creation form
GET    /admin/transfers/{id}         - View transfer details
PATCH  /admin/transfers/{id}/approve - Approve transfer
PATCH  /admin/transfers/{id}/reject  - Reject transfer
PATCH  /admin/transfers/{id}/transit - Mark in transit
PATCH  /admin/transfers/{id}/complete - Complete transfer
PATCH  /admin/transfers/{id}/cancel  - Cancel transfer
```

#### **Stock Reservation Management**
```
GET    /admin/stock-reservations                - Reservations dashboard
POST   /admin/stock-reservations/cleanup        - Cleanup expired
GET    /admin/stock-reservations/{id}           - View reservation details
POST   /admin/stock-reservations/{id}/release   - Release reservation
PATCH  /admin/stock-reservations/{id}/extend    - Extend expiry
GET    /admin/stock-reservations/item/{itemId}  - Item reservations
```

#### **Stock Reporting**
```
GET    /admin/stock-reports                     - Reports dashboard
GET    /admin/stock-reports/api/generate        - Generate report data
GET    /admin/stock-reports/api/export          - Export report
GET    /admin/stock-reports/api/statistics      - Get statistics
```

### Best Practices

#### **For Users**

**Creating Transfers**
1. **Check Stock First**: Always verify available quantities before creating transfers
2. **Use Appropriate Quantities**: Don't transfer more than needed
3. **Add Clear Notes**: Document the purpose and any special instructions
4. **Review Before Confirming**: Double-check all details in confirmation modal
5. **Follow Up**: Monitor transfer progress through the system

**Managing Approvals**
1. **Timely Reviews**: Approve or reject transfers promptly to free reservations
2. **Verify Requirements**: Ensure transfers are necessary and appropriate
3. **Check Stock Impact**: Consider how transfers affect overall inventory levels
4. **Document Decisions**: Add rejection reasons for audit trail

#### **For Administrators**

**System Monitoring**
1. **Daily Reservation Review**: Check for stuck or expired reservations
2. **Stock Level Monitoring**: Watch for items becoming unavailable due to reservations
3. **Transfer Audit**: Review transfer patterns and identify issues
4. **Performance Monitoring**: Watch for slow operations or bottlenecks

**Maintenance Tasks**
1. **Regular Cleanup**: Run cleanup commands during off-peak hours
2. **Data Verification**: Periodically verify stock calculations
3. **Permission Reviews**: Ensure users have appropriate access levels
4. **Backup Procedures**: Regular backups before major operations

#### **Performance Optimization**

**Database Optimization**
```sql
-- Ensure proper indexes for common queries
CREATE INDEX idx_stock_reservations_location ON stock_reservations(location_type, location_id);
CREATE INDEX idx_stock_reservations_item ON stock_reservations(item_id);
CREATE INDEX idx_stock_reservations_expires ON stock_reservations(expires_at);
CREATE INDEX idx_transfers_status ON transfers(status);
CREATE INDEX idx_transfers_source ON transfers(source_type, source_id);
```

**Caching Strategies**
- Cache available stock calculations for frequently accessed items
- Cache user permission lookups
- Cache location hierarchies (branch-warehouse relationships)

### Troubleshooting

#### **Common Issues**

**"Insufficient Stock" Errors**
```
Symptoms: Transfer fails with stock availability errors
Causes:
1. Reservations not released from cancelled transfers
2. Concurrent transfers creating race conditions
3. Manual stock adjustments not accounted for

Solutions:
1. Check and clean up expired reservations
2. Verify current stock levels vs. expected levels
3. Use admin panel to manually release stuck reservations
4. Recalculate stock if needed
```

**Transfer Stuck in "Pending" Status**
```
Symptoms: Transfer not moving through approval workflow
Causes:
1. User lacks approval permissions
2. Approval notifications not working
3. System performance issues

Solutions:
1. Verify user permissions in admin panel
2. Check that approvers have necessary roles
3. Review transfer details for any blocking issues
4. Use admin tools to manually progress transfer
```

**Stock Reservations Not Expiring**
```
Symptoms: Old reservations blocking new transfers
Causes:
1. Cleanup command not running
2. Cron job misconfiguration
3. Database issues

Solutions:
1. Manually run: php artisan stock:cleanup-reservations
2. Verify cron job setup
3. Check Laravel scheduler configuration
4. Investigate database connection issues
```

**Performance Issues with Large Inventories**
```
Symptoms: Slow loading of item dropdowns or transfer creation
Causes:
1. Large number of items in system
2. Complex stock calculations
3. Inefficient database queries

Solutions:
1. Implement search-based item selection
2. Add database indexes
3. Use pagination for large item lists
4. Consider caching strategies
```

#### **Debugging Commands**

```bash
# Check current reservations
php artisan tinker
>>> App\Models\StockReservation::with('item')->get()

# Verify stock calculations
php artisan tinker
>>> $service = new App\Services\StockMovementService()
>>> $service->getAvailableStock($itemId, $locationType, $locationId)

# Check transfer status
php artisan tinker
>>> App\Models\Transfer::where('status', 'pending')->count()

# View recent transfers
php artisan tinker
>>> App\Models\Transfer::with('items')->latest()->take(5)->get()
```

#### **Log Monitoring**

**Key Log Locations**
```bash
# Transfer-related logs
tail -f storage/logs/laravel.log | grep -i transfer

# Stock movement logs
tail -f storage/logs/laravel.log | grep -i stock

# Reservation system logs
tail -f storage/logs/laravel.log | grep -i reservation
```

**Important Log Patterns**
```
Transfer creation: "Transfer creation started"
Stock validation: "Insufficient stock for"
Reservation creation: "Stock reserved for transfer"
Transfer completion: "Transfer completed successfully"
Error conditions: "Transfer failed:" or "TransferException"
```

### Security Considerations

#### **Permission-Based Access**
- All transfer operations require proper permissions
- Location access restricted by user role
- Approval workflows prevent unauthorized stock movements
- Audit trail maintains complete activity history

#### **Data Validation**
- All input validated before processing
- Stock calculations verified in real-time
- Duplicate prevention at multiple levels
- SQL injection prevention through parameterized queries

#### **Stock Protection**
- Reservation system prevents overselling
- Atomic transactions ensure data consistency
- Rollback mechanisms for failed operations
- Real-time stock level verification

### Integration Points

#### **With Sales System**
- Shared stock calculation logic
- Coordinated reservation management
- Unified inventory tracking
- Cross-system stock validation

#### **With User Management**
- Role-based permission integration
- Location-based access control
- Audit trail user tracking
- Activity monitoring

#### **With Reporting System**
- Transfer history reporting
- Stock movement analysis
- Reservation impact reporting
- Performance metrics tracking

### Navigation

- **Transfer List**: `/admin/transfers`
- **Create Transfer**: `/admin/transfers/create`
- **Transfer Details**: `/admin/transfers/{id}`
- **Stock Reservations**: `/admin/stock-reservations`
- **Stock Reports**: `/admin/stock-reports`

**Required Permissions**:
- **Create Transfers**: `transfers.create`
- **View Transfers**: `transfers.view`
- **Approve Transfers**: `transfers.edit`
- **Manage Reservations**: `transfers.edit`
- **View Reports**: `items.view`

## Development

### Code Style
- Follow PSR-12 coding standards
- Use Laravel's coding style guide
- Run `composer test` before committing

### Testing
```bash
php artisan test
```

### Building Assets
```bash
npm run dev     # Development
npm run build   # Production
```

## Security

- Change default passwords after installation
- Keep dependencies updated
- Follow security best practices
- Regular security audits
- Implement proper access controls

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📦 Stock Management System

### Overview
The enhanced stock management system provides advanced inventory control with stock reservations and comprehensive reporting capabilities.

### 🔒 Stock Reservations

#### What are Stock Reservations?
Stock reservations are temporary holds on inventory items to ensure availability during transfer processing. They prevent race conditions and overselling.

#### Key Features
- **24-hour Auto-expiring Reservations** - Prevents indefinite stock locks
- **Atomic Operations** - All stock movements are transaction-safe
- **Real-time Availability** - Calculations include reserved quantities
- **Admin Monitoring** - Full visibility and control over reservations

#### Accessing Stock Reservations
1. **Navigation**: Transactions → Stock Reservations
2. **Permission Required**: `transfers.view`
3. **Management Permission**: `transfers.edit`

#### Features Available
- **View Active Reservations** - See all current stock holds
- **Monitor Expiring Reservations** - Get early warnings
- **Release Reservations** - Manually free up stock (admin only)
- **Cleanup Expired** - Remove old reservations
- **View Statistics** - Dashboard with key metrics

#### Dashboard Statistics
- **Active Reservations** - Currently held stock
- **Expired Reservations** - Awaiting cleanup
- **Total Reserved Quantity** - Sum of all reserved items
- **Items with Reservations** - Unique items affected

#### Managing Reservations

**Automatic Actions**
- **Created**: When transfer is initiated
- **Released**: When transfer completes/cancels
- **Expired**: After 24 hours of inactivity

**Manual Actions (Admin Only)**
- **Release**: Free stock immediately
- **Extend**: Add more time (up to 1 week)
- **Cleanup**: Remove all expired reservations

#### API Endpoints
```
GET    /admin/stock-reservations           - View dashboard
POST   /admin/stock-reservations/cleanup   - Cleanup expired
GET    /admin/stock-reservations/{id}      - View details
POST   /admin/stock-reservations/{id}/release - Release reservation
PATCH  /admin/stock-reservations/{id}/extend  - Extend expiry
```

### 📊 Stock Reports

#### What are Stock Reports?
Comprehensive inventory reports with advanced filtering, reservation tracking, and multiple export formats.

#### Key Features
- **Multi-format Export** - CSV, JSON
- **Advanced Filtering** - By location, type, stock level
- **Reservation Integration** - See reserved vs available stock
- **Real-time Statistics** - Live inventory metrics
- **Custom Queries** - Flexible report generation

#### Accessing Stock Reports
1. **Navigation**: Transactions → Stock Reports
2. **Permission Required**: `items.view`

#### Report Types
- **All Items** - Complete inventory overview
- **Low Stock Only** - Items below reorder level
- **Zero Stock** - Out-of-stock items
- **With Reservations** - Items currently reserved

#### Filters Available

**Location Filters**
- **All Locations** - System-wide view
- **Specific Warehouse** - Single warehouse focus
- **Specific Branch** - Branch-level reporting

**Content Filters**
- **Report Type** - All, Low Stock, Zero Stock, With Reservations
- **Include Reservations** - Show reserved quantities

#### Report Statistics
- **Total Items** - Count of inventory items
- **Low Stock Items** - Items needing attention
- **Total Inventory Value** - Dollar value of stock
- **Total Reserved** - Quantity currently held

#### Export Formats

**CSV Export**
- Spreadsheet-compatible format
- All columns included
- Timestamp in filename
- Direct download

**JSON Export**
- Machine-readable format
- Complete metadata included
- API-compatible structure
- Programmatic access

#### Sample Report Data
```csv
Item Name,SKU,Warehouse,Total Qty,Reserved,Available,Reorder Level,Status,Unit Cost,Total Value
Red Kidney Beans,BLK-BEAN-RK-50KG,Main Warehouse,100.00,5.00,95.00,10,OK,$4200.00,$420000.00
```

#### API Endpoints
```
GET /admin/stock-reports                 - View report page
GET /admin/stock-reports/api/generate    - Generate report data
GET /admin/stock-reports/api/export      - Export report file
```

### 🔧 Console Commands

#### Stock Cleanup
```bash
# Clean up expired reservations
php artisan stock:cleanup-reservations

# Dry run (preview only)
php artisan stock:cleanup-reservations --dry-run
```

#### Stock Reports
```bash
# Generate basic stock report
php artisan stock:report

# Generate with reservations
php artisan stock:report --with-reservations

# Export to CSV
php artisan stock:report --format=csv

# Filter by location
php artisan stock:report --location=1 --location-type=warehouse

# Show only low stock
php artisan stock:report --low-stock
```

### 🚀 Automated Features

#### Scheduled Tasks
- **Hourly Cleanup** - Automatically removes expired reservations
- **No Overlap Protection** - Prevents concurrent cleanup runs
- **Background Processing** - Doesn't block other operations

#### Cron Configuration
```bash
# Add to crontab for automatic cleanup
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 🔐 Stock Management Permissions

#### Required Permissions

**Stock Reservations**
- **View**: `transfers.view`
- **Manage**: `transfers.edit`

**Stock Reports**
- **Access**: `items.view`

#### Role Access
- **SuperAdmin** - Full access to all features
- **BranchManager** - Location-based access
- **WarehouseUser** - Warehouse-specific access
- **Other Roles** - Permission-based access

### 🎯 Best Practices

#### Stock Reservations
1. **Monitor Regularly** - Check dashboard daily
2. **Clean Up Promptly** - Remove expired reservations
3. **Investigate Issues** - Look into frequently expired items
4. **Use Extensions Sparingly** - Only when necessary

#### Stock Reports
1. **Filter Appropriately** - Use location filters for focused analysis
2. **Include Reservations** - For accurate availability
3. **Export Regularly** - Maintain historical records
4. **Monitor Low Stock** - Use for reorder decisions

#### Performance Tips
1. **Limit Large Reports** - Filter by location for big inventories
2. **Schedule Heavy Reports** - Run during off-peak hours
3. **Use Console Commands** - For automated reporting
4. **Cache Results** - For frequently accessed data

### ⚠️ Troubleshooting

#### Common Issues

**"Table doesn't exist" Error**
- **Cause**: Migration not run
- **Solution**: `php artisan migrate`

**Permission Denied**
- **Cause**: User lacks required permissions
- **Solution**: Check role assignments

**Reservation Not Releasing**
- **Cause**: Transfer not completed properly
- **Solution**: Manual release via admin panel

**Report Taking Too Long**
- **Cause**: Large dataset without filters
- **Solution**: Add location/type filters

#### Getting Help
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify permissions: User management panel
3. Test with console commands first
4. Contact system administrator

### 📈 Advanced Features

#### Integration Points
- **Transfer System** - Automatic reservation creation
- **Inventory Management** - Real-time availability
- **User Management** - Permission-based access
- **Audit Trail** - Complete history tracking

#### Customization Options
- **Reservation Duration** - Modify in StockMovementService
- **Permission Requirements** - Update in routes
- **Report Formats** - Add new export types
- **Cleanup Frequency** - Adjust in Console/Kernel

---

## Support

For support, email support@stock360.com or create an issue in the repository.
