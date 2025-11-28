# Branch Manager Mapping Implementation

## Branch → Manager Mapping
```
Branch 0: Bicha Fok Branch (BR-BFOK) → branch-manager-0@amtradingplc.com
Branch 1: Merkato Branch (BR-MERC) → branch-manager-1@amtradingplc.com
Branch 2: Furi Branch (BR-FURI) → branch-manager-2@amtradingplc.com
```

## Enum-Based Implementation

### BranchCode.php
```php
enum BranchCode: string {
    case BICHA_FOK = 'BR-BFOK';
    case MERKATO = 'BR-MERC';
    case FURI = 'BR-FURI';
    
    public function getManagerEmail(): string {
        return match($this) {
            self::BICHA_FOK => 'branch-manager-0@amtradingplc.com',
            self::MERKATO => 'branch-manager-1@amtradingplc.com',
            self::FURI => 'branch-manager-2@amtradingplc.com',
        };
    }
}
```

### AuthorizationLevel.php
```php
enum AuthorizationLevel: string {
    case FULL_ACCESS = 'full_access';      // SuperAdmin/GeneralManager
    case BRANCH_RESTRICTED = 'branch_restricted'; // Branch Managers
    case NO_ACCESS = 'no_access';          // Other Users
}
```

## Authorization Rules

| User Type | Access Level | Transfer Rules |
|-----------|-------------|----------------|
| **SuperAdmin/GeneralManager** | `FULL_ACCESS` | Can create/approve ANY transfer, including self-transfers |
| **Branch Managers** | `BRANCH_RESTRICTED` | Create FROM own branch only, Approve TO own branch only |
| **Other Users** | `NO_ACCESS` | No transfer access by default |

## Transfer Logic Implementation

### TransferService.php - Hard Enforcement
```php
// SuperAdmin bypass - can do everything
if ($user->isSuperAdmin() || $user->isGeneralManager()) {
    return; // Skip all validation - can self-transfer
}

// Branch managers: FROM own branch, NOT TO own branch
if ($user->isBranchManager()) {
    if ($user->branch_id !== $transferData['source_id']) {
        throw new TransferException('You can only create transfers from your assigned branch.');
    }
    if ($user->branch_id === $transferData['destination_id']) {
        throw new TransferException('You cannot transfer to your own branch.');
    }
}

// Approval: SuperAdmin can approve any, Branch managers only TO their branch
if ($user->isBranchManager()) {
    if ($transfer->source_id === $user->branch_id) {
        throw new TransferException('You cannot approve transfers from your own branch.');
    }
    if ($transfer->destination_id !== $user->branch_id) {
        throw new TransferException('You can only approve transfers to your branch.');
    }
}
```

### Middleware Protection
```php
// EnforceBranchAuthorization.php
if ($user->isSuperAdmin() || $user->isGeneralManager()) {
    return $next($request); // SuperAdmin bypass
}

if ($user->isBranchManager()) {
    // Prevent branch_id tampering
    if ($request->has('branch_id') && $request->input('branch_id') != $user->branch_id) {
        abort(403, 'You cannot modify branch assignments.');
    }
    
    // Transfer validation
    if ($request->routeIs('admin.transfers.*')) {
        if ($request->has('source_id') && $request->input('source_id') != $user->branch_id) {
            abort(403, 'You can only create transfers from your branch.');
        }
        if ($request->has('destination_id') && $request->input('destination_id') == $user->branch_id) {
            abort(403, 'You cannot transfer to your own branch.');
        }
    }
}
```

### Protected Routes
```php
// web.php - Middleware applied to:
Route::prefix('transfers')->middleware('EnforceBranchAuthorization')
Route::prefix('purchases')->middleware('EnforceBranchAuthorization')
Route::prefix('sales')->middleware('EnforceBranchAuthorization')
Route::prefix('items')->middleware('EnforceBranchAuthorization')
Route::prefix('warehouses')->middleware('EnforceBranchAuthorization')
```

## Security Guarantees

✅ **SuperAdmin Override**: Can perform ANY operation on ANY branch (including self-transfers)  
✅ **Branch Isolation**: Managers restricted to their assigned branch only  
✅ **No Self-Transfer**: Branch managers cannot transfer to own branch  
✅ **No Cross-Approval**: Branch managers cannot approve from own branch  
✅ **URL Protection**: Middleware blocks unauthorized access  
✅ **Payload Protection**: Request tampering prevention  
✅ **Enum-Based**: Type-safe authorization with `FULL_ACCESS`/`BRANCH_RESTRICTED`/`NO_ACCESS`

## Testing

### Branch Manager Tests
```
1. branch-manager-0@amtradingplc.com (Bicha Fok)
   - Can create transfers FROM Bicha Fok only
   - Can approve transfers TO Bicha Fok only
   - Cannot self-transfer within Bicha Fok

2. SuperAdmin
   - Can create/approve ANY transfer
   - Can perform self-transfers
   - Bypasses all restrictions
```