# Sales Credit Creation Fix

## Problem
Sale creation was failing with the error:
```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'branch_id' cannot be null
```

This occurred when creating credit records for credit-based sales because the `branch_id` field in the `credits` table was NOT NULL but wasn't being properly set.

## Root Cause
1. The `credits` table had `branch_id` as a required (NOT NULL) field
2. The Sales Create component wasn't properly resolving the `branch_id` for credit creation
3. Unlike the Purchase component which had robust branch resolution, Sales was missing this logic

## Solution Implemented

### 1. Database Schema Fix
- **Migration**: `2025_11_29_222100_make_branch_id_nullable_in_credits_table.php`
  - Makes `branch_id` nullable in the credits table
  - Allows for graceful handling when branch cannot be determined

### 2. Credit Model Enhancement
- **File**: `app/Models/Credit.php`
- **Changes**:
  - Added `boot()` method with `creating` event listener
  - Added `getDefaultBranchId()` method for fallback branch resolution
  - Ensures every credit gets a valid `branch_id` even if not explicitly provided

### 3. Sales Component Improvements
- **File**: `app/Livewire/Sales/Create.php`
- **Changes**:
  - Added `resolveBranchId()` method with comprehensive branch resolution logic
  - Updated credit creation methods to accept and use resolved `branch_id`
  - Added validation to ensure `branch_id` is available before sale creation
  - Improved error handling and fallback mechanisms

### 4. Branch Resolution Priority
The system now resolves `branch_id` in this order:
1. Explicitly selected branch (`form['branch_id']`)
2. Branch from selected warehouse
3. User's assigned branch
4. Branch from user's assigned warehouse  
5. First active branch (fallback)

## Files Modified

1. **app/Livewire/Sales/Create.php**
   - Enhanced branch resolution logic
   - Updated credit creation methods
   - Added comprehensive fallback mechanisms

2. **app/Models/Credit.php**
   - Added automatic branch_id resolution on creation
   - Enhanced model boot method

3. **database/migrations/2025_11_29_222100_make_branch_id_nullable_in_credits_table.php**
   - New migration to make branch_id nullable

## Database Fix Scripts

### Option 1: Run Migration
```bash
docker compose exec app php artisan migrate
```

### Option 2: Manual Database Fix
```bash
php fix_database.php
```

### Option 3: Direct SQL
```sql
ALTER TABLE credits MODIFY COLUMN branch_id BIGINT UNSIGNED NULL;
UPDATE credits SET branch_id = (SELECT id FROM branches WHERE is_active = 1 LIMIT 1) WHERE branch_id IS NULL;
```

## Testing
After applying the fix:
1. Create a sale with credit payment method
2. Verify credit record is created successfully
3. Check that `branch_id` is properly set in the credit record
4. Test with different user types (branch users, warehouse users, admins)

## Benefits
- ✅ Fixes immediate constraint violation error
- ✅ Provides robust branch resolution logic
- ✅ Maintains data integrity with fallback mechanisms
- ✅ Follows same pattern as Purchase component
- ✅ Backward compatible with existing data
- ✅ Handles edge cases gracefully

## Prevention
The enhanced logic prevents future issues by:
- Always resolving a valid branch_id before credit creation
- Providing multiple fallback options
- Adding validation at multiple levels
- Following established patterns from Purchase component