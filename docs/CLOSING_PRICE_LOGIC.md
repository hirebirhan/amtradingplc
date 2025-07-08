# Closing Price Logic for Payable Credits

## Overview

This document describes the implementation of closing price logic for payable credits in the Muhdin General Trading system. This feature allows users to negotiate final prices with suppliers when closing payable credits early.

## Key Features

### 1. Payable Credits Only
- **Scope**: This logic applies ONLY to payable credits (credits where we owe the supplier)
- **Purpose**: Ensures accurate cost recording and profit/loss calculations
- **Exclusion**: Receivable credits (customers owe us) are not affected

### 2. Eligibility Requirements

#### Early Closure (50%+ Payment)
- Credit must be at least 50% paid
- Credit must have remaining balance > 0
- Only applies to payable credits

#### Full Payment with Closing Prices
- For payable credits, closing prices are REQUIRED before full payment
- System blocks full payment without closing prices
- Ensures proper cost recording

### 3. Closing Price Entry

#### Price Structure
- **Base Unit**: Prices are entered per base unit (kg, liter, piece, etc.)
- **Per Piece**: System calculates per-piece costs automatically
- **Unit Quantity**: Accounts for items with multiple units per piece (e.g., 1 box = 3 items)

#### Calculation Logic
```
Original Unit Cost (per piece) = Purchase unit cost
Closing Unit Price (per piece) = User entered price
Unit Cost per Item = Unit Cost / Unit Quantity
Savings per Item = (Original - Closing) × Quantity
```

### 4. Savings Calculation

#### Per Item
- **Original Cost**: Original unit cost × quantity
- **Closing Cost**: Closing unit price × quantity
- **Profit/Loss**: Original cost - Closing cost
- **Percentage**: (Profit/Loss / Original cost) × 100

#### Total Credit
- **Total Savings**: Sum of all item savings
- **Final Payment**: Credit balance - Total savings
- **Can Close**: (Paid amount + Total savings) ≥ Original credit amount

### 5. Credit Closure Logic

#### Successful Closure
- Total savings + paid amount ≥ original credit amount
- Credit marked as fully paid
- Purchase items updated with closing prices
- Profit/loss data stored for reporting

#### Insufficient Savings
- Shows shortfall amount
- Prevents credit closure
- User must adjust prices or make additional payment

## User Interface

### 1. Early Closure Offer
- **Trigger**: 50%+ payment on payable credit
- **Display**: Info card with payment progress
- **Action**: "Accept & Negotiate" button

### 2. Negotiation Form
- **Items**: Shows all purchased items
- **Prices**: Input fields for closing prices per piece
- **Real-time**: Profit/loss calculation updates as prices change
- **Validation**: Ensures all required prices are entered

### 3. Results Display
- **Profit/Loss**: Color-coded results (green for profit, red for loss)
- **Closure Status**: Shows if credit can be closed
- **Shortfall**: Displays amount needed if insufficient

### 4. Payment Blocking
- **Warning**: Shows when attempting full payment without closing prices
- **Block**: Prevents payment until closing prices are entered
- **Guidance**: Directs user to early closure option

## Database Changes

### PurchaseItem Model
```php
protected $fillable = [
    // ... existing fields
    'closing_unit_price',      // Final negotiated price per piece
    'total_closing_cost',      // Total cost with closing price
    'profit_loss_per_item',    // Profit/loss for this item
];
```

### Migration
```php
$table->decimal('closing_unit_price', 15, 2)->nullable();
$table->decimal('total_closing_cost', 15, 2)->nullable();
$table->decimal('profit_loss_per_item', 15, 2)->nullable();
```

## Business Rules

### 1. Payable Credits Only
- Feature only applies to `credit_type = 'payable'`
- Receivable credits bypass all closing price logic

### 2. Full Payment Requirement
- Payable credits require closing prices for full payment
- Partial payments are allowed without closing prices
- System enforces this rule at validation level

### 3. Savings Calculation
- Savings can be positive (profit) or negative (loss)
- Negative savings increase final payment amount
- Positive savings reduce final payment amount

### 4. Credit Closure
- Credit can only be closed when sufficient savings are achieved
- Shortfall prevents closure and shows required amount
- All purchase items must have closing prices entered

## Error Handling

### 1. Validation Errors
- Missing closing prices for payable credits
- Insufficient savings for credit closure
- Invalid price entries (negative, zero, etc.)

### 2. User Feedback
- Clear error messages explaining requirements
- Guidance on how to resolve issues
- Real-time validation feedback

### 3. Data Integrity
- Database transactions ensure atomicity
- Rollback on errors prevents partial updates
- Audit trail maintained for all changes

## Testing Scenarios

### 1. Eligible Credit
- 50%+ payment on payable credit
- Early closure offer appears
- User can enter closing prices

### 2. Full Payment Block
- Attempt full payment without closing prices
- System blocks payment with clear message
- User directed to early closure option

### 3. Insufficient Savings
- Enter closing prices that don't cover balance
- System shows shortfall amount
- Credit cannot be closed

### 4. Successful Closure
- Enter sufficient closing prices
- Credit closes with final payment
- Purchase items updated with closing data

## Future Enhancements

### 1. Bulk Price Entry
- Allow setting same price for multiple items
- Copy prices from previous transactions
- Template-based price entry

### 2. Advanced Calculations
- Tax implications of price changes
- Currency conversion for international suppliers
- Inflation adjustments

### 3. Reporting
- Closing price analysis reports
- Supplier negotiation history
- Cost variance tracking

## Security Considerations

### 1. Authorization
- Only authorized users can enter closing prices
- Audit trail for all price changes
- Role-based access control

### 2. Data Validation
- Input sanitization for price entries
- Range validation for reasonable prices
- Duplicate entry prevention

### 3. Audit Trail
- Log all closing price changes
- Track who made changes and when
- Maintain historical price records 