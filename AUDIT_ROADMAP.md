# Audit Roadmap — AM Trading PLC

> Generated from the senior architect audit (Step 2 + Step 3).
> Work top-to-bottom. Each item has a checkbox, severity, effort estimate,
> the exact files to touch, and the precise change required.
> Check the box when the fix is committed and verified.

---

## How to use this file

1. Pick the top unchecked item in the lowest-numbered phase.
2. Read the **Files** and **Fix** sections.
3. Make the change, run `php artisan test` (once the test suite exists), commit.
4. Tick the box and move to the next item.

Severity scale: **P0** = crashes production · **P1** = corrupts data · **P2** = silent business rule violation · **P3** = structural / maintainability risk.

---

## Phase 1 — Fix Runtime Crashes (P0)

These will throw exceptions or return completely wrong data for real users right now.

---

### 1.1 `Manager` role is invisible to access control
**Severity:** P0 | **Effort:** ~1 hour

**Problem:**
`isGeneralManager()` checks for the `GeneralManager` role (`UserRole::GENERAL_MANAGER = 'GeneralManager'`).
The active role created by `SystemCleanupCommand` is named `Manager` (`UserRole::MANAGER = 'Manager'`).
Every Manager user gets treated as having no access — they receive empty index results, fail policy checks,
and get `BRANCH_RESTRICTED` authorization level.

**Root cause file:** `app/Models/User.php:96-98`
```php
public function isGeneralManager(): bool
{
    return $this->hasRole(UserRole::GENERAL_MANAGER->value); // checks 'GeneralManager', not 'Manager'
}
```

**Files to change (15 locations):**

| File | Line(s) | Change |
|------|---------|--------|
| `app/Support/Access/UserAccess.php` | 15 | `isSuperAdmin() \|\| $user->isGeneralManager()` → `isSuperAdmin() \|\| $user->isManager()` |
| `app/Enums/AuthorizationLevel.php` | 13 | Same pattern |
| `app/Providers/AuthServiceProvider.php` | 64 | Same pattern |
| `app/Policies/SalePolicy.php` | 37 | Same pattern |
| `app/Policies/CreditPolicy.php` | 37 | Same pattern |
| `app/Policies/EmployeePolicy.php` | 37 | Same pattern |
| `app/Policies/BankAccountPolicy.php` | 37 | Same pattern |
| `app/Policies/ExpensePolicy.php` | 37 | Same pattern |
| `app/Policies/PurchasePolicy.php` | 117 | `isSuperAdmin()` check — add `\|\| $user->isManager()` |
| `app/Models/User.php` | 212, 226 | Same pattern |
| `app/Models/Item.php` | 263, 591, 619, 633 | Same pattern |

- [ ] Replace all `isGeneralManager()` calls in the above files with `isManager()`
- [ ] Verify `User::isManager()` already includes both `GeneralManager` and `Manager` roles (it does — see `User.php:128-136`)
- [ ] Manual smoke test: log in as a `Manager` user, confirm `/api/v1/sales` returns all branches' data

---

### 1.2 `negotiated_prices` format mismatch silently zeroes all savings
**Severity:** P0 | **Effort:** 30 minutes

**Problem:**
`CreditController::calculateClosingOffer` and `acceptClosingOffer` pass
`$request->input('negotiated_prices')` directly to `CreditPaymentService`.
The client sends `[{"item_id": 1, "price": 100}, ...]` (array of objects).
The service reads `$negotiatedPrices[$itemId]` (expects a map keyed by item ID).
`isset($negotiatedPrices[1])` on a list always returns false → every item falls back
to its original cost → the negotiation has zero effect, silently.

**File:** `app/Http/Requests/Api/V1/Credits/ClosingOfferRequest.php`

**Fix — add `prepareForValidation()`:**
```php
protected function prepareForValidation(): void
{
    if (is_array($this->negotiated_prices)) {
        $keyed = collect($this->negotiated_prices)
            ->keyBy('item_id')
            ->map(fn ($p) => (float) ($p['price'] ?? 0))
            ->all();
        $this->merge(['negotiated_prices' => $keyed]);
    }
}
```

- [ ] Add `prepareForValidation()` to `ClosingOfferRequest`
- [ ] Update `rules()` to validate the now-keyed format: `'negotiated_prices' => ['required', 'array']`, `'negotiated_prices.*' => ['required', 'numeric', 'min:0']`
- [ ] Test: send `[{"item_id":1,"price":500}]`, confirm the returned savings reflect the reduced price

---

### 1.3 Warehouse-type transfers are always rejected
**Severity:** P0 | **Effort:** 10 minutes

**Problem:**
`TransferService::validateTransferData()` throws a `TransferException` if either
`source_type` or `destination_type` is not `'branch'`. The route, `StockMovementService`,
and `TransferPolicy` all fully support `warehouse` type. The block is an accidental
leftover from an earlier branch-only design.

**File:** `app/Services/TransferService.php:155-160`

**Fix — delete these lines:**
```php
// DELETE:
if (($transferData['source_type'] ?? 'branch') !== 'branch' || ($transferData['destination_type'] ?? 'branch') !== 'branch') {
    throw new TransferException('Only branch-to-branch transfers are supported in this setup.');
}
```

- [ ] Remove the branch-only guard from `validateTransferData()`
- [ ] Confirm `StoreTransferRequest` already validates `source_type` and `destination_type` against allowed enum values
- [ ] Test: create a warehouse-to-warehouse transfer, confirm it proceeds to stock reservation

---

### 1.4 Sale stock movement writes non-existent columns to `stock_histories`
**Severity:** P0 | **Effort:** ~2 hours

**Problem:**
`Sale::processWarehouseSaleItem()` and `Sale::processBranchSaleItem()` call
`StockHistory::create()` with `movement_type`, `notes`, `created_by`, `branch_id`.
The `stock_histories` schema has `description`, `user_id` — not those names.
This causes silent data loss (unguarded fields dropped by mass-assignment) or DB errors.
Additionally these methods bypass `Stock::sellByPiece()` / `Stock::sellByUnit()`,
so `total_units` is never updated during sales.

**Files:** `app/Models/Sale.php:260-323`, `app/Models/Sale.php:326-420`

**Fix — replace inline stock mutation with `Stock` model methods:**

```php
private function processWarehouseSaleItem($item, $saleItem): void
{
    $stock = Stock::where('warehouse_id', $this->warehouse_id)
        ->where('item_id', $item->id)
        ->lockForUpdate()
        ->first();

    if (! $stock) {
        $stock = Stock::create([
            'warehouse_id' => $this->warehouse_id,
            'item_id'      => $item->id,
            'branch_id'    => $this->branch_id,
            'quantity'     => 0,
            'piece_count'  => 0,
            'total_units'  => 0,
        ]);
    }

    $unitCapacity = max($item->unit_quantity ?? 1, 1);

    if ($saleItem->isSoldByUnit()) {
        $stock->sellByUnit($saleItem->quantity, $unitCapacity, 'sale', $this->id,
            'Sale #' . $this->reference_no, $this->user_id);
    } else {
        $stock->sellByPiece((int) $saleItem->quantity, $unitCapacity, 'sale', $this->id,
            'Sale #' . $this->reference_no, $this->user_id);
    }
}
```

Apply the same pattern to `processBranchSaleItem()` (iterate warehouses in the branch
with locking, call `sellByPiece()`/`sellByUnit()` on each).

- [ ] Rewrite `processWarehouseSaleItem()` to use `Stock::sellByPiece()` / `Stock::sellByUnit()`
- [ ] Rewrite `processBranchSaleItem()` to use the same methods
- [ ] Verify `Stock::createStockHistory()` uses `description` and `user_id` (matches schema) ✓
- [ ] Test: create a sale, confirm `stock_histories` row has `description` populated and `quantity_after` equals expected value

---

## Phase 2 — Fix Data Corruption (P1)

These produce incorrect records in the database without crashing.

---

### 2.1 `sale_date->addDays(30)` mutates the model attribute
**Severity:** P1 | **Effort:** 5 minutes

**Problem:**
In `Sale::createCreditRecord()`, the `due_date` calculation calls `$this->sale_date->addDays(30)`.
Carbon's `addDays()` is mutable — it modifies the stored Carbon instance in place.
After this line, `$this->sale_date` contains sale_date + 30 days for the rest of the request.
Any code reading `$sale->sale_date` after `createCreditRecord()` runs will get the wrong date.

**File:** `app/Models/Sale.php:254`

**Fix:**
```php
// Before:
'due_date' => $this->sale_date->addDays(30),

// After:
'due_date' => $this->sale_date->copy()->addDays(30),
```

- [ ] Apply the one-line fix
- [ ] Add a test that reads `$sale->sale_date` after `createCreditRecord()` runs and asserts it equals the original sale date

---

### 2.2 Credit is created before sale items are inserted
**Severity:** P1 | **Effort:** ~2 hours

**Problem:**
`SaleService::createSale()` calls `Sale::create()` (line 28) which fires the `Sale::saved`
observer. The observer calls `createCreditRecord()` at this point — before any `SaleItem`
rows exist. This means:
- The credit `amount` field is correct (set from `total_amount`)
- But `$sale->sale_date->addDays(30)` mutation (item 2.1) runs here
- Then `processSale()` calls `$this->save()` which fires `saved` again
- Each `saved` fire runs `calculateCorrectPaymentStatus()` + `saveQuietly()` (another fire)
- Multiple redundant DB queries per sale creation

The cleanest fix is to remove credit-creation from the observer entirely and
create it explicitly in the service after items and stock are settled.

**Files:** `app/Models/Sale.php:90-119`, `app/Services/SaleService.php:14-68`

**Fix:**

In `Sale::saved` observer — remove the `createCreditRecord()` call:
```php
static::saved(function ($sale) {
    // Keep payment status recalculation ONLY:
    if ($sale->paid_amount === null) { ... }
    $correctStatus = $sale->calculateCorrectPaymentStatus();
    if ($sale->payment_status !== $correctStatus) { $sale->payment_status = $correctStatus; $sale->saveQuietly(); }
    $correctDue = $sale->total_amount - ($sale->paid_amount ?? 0);
    if ($sale->due_amount != $correctDue) { $sale->due_amount = $correctDue; $sale->saveQuietly(); }
    // REMOVE the createCreditRecord() block entirely
});
```

In `SaleService::createSale()` — add explicit credit creation after `processSale()`:
```php
$sale->processSale();

if ($sale->due_amount > 0 && ! $sale->is_walking_customer) {
    $sale->createCreditRecord();
}

return $sale->fresh();
```

- [ ] Remove credit-creation block from `Sale::saved` observer
- [ ] Add explicit `createCreditRecord()` call in `SaleService::createSale()` after `processSale()`
- [ ] Ensure `createCreditRecord()` still has its early-exit guard (`if ($this->credit()->exists()) return;`) to stay idempotent
- [ ] Test: create a full_credit sale, confirm exactly one `Credit` row exists with correct `amount`, `balance`, `due_date`

---

### 2.3 `credit.amount` is overwritten by closing offer acceptance
**Severity:** P1 | **Effort:** ~1 hour + 1 migration

**Problem:**
`CreditPaymentService::processEarlyClosureWithNegotiatedPrices()` at line 306:
```php
$locked->amount = $totalClosingCost;  // destroys original transaction value
```
`credit.amount` is supposed to be the original purchase total. Setting it to the negotiated
closing cost erases the historical record. Reports comparing original vs negotiated amounts
will read the negotiated value as the original.

**Fix — add `closing_amount` column, stop touching `amount`:**

New migration:
```php
Schema::table('credits', function (Blueprint $table) {
    $table->decimal('closing_amount', 15, 2)->nullable()->after('amount')
          ->comment('Negotiated total from closing offer; null until offer accepted');
});
```

In `CreditPaymentService::processEarlyClosureWithNegotiatedPrices()` — replace the
`$locked->amount = ...` line:
```php
// Before:
$locked->amount     = $totalClosingCost;
$locked->paid_amount = $locked->payments()->sum('amount');
$locked->balance    = max(0, $totalClosingCost - $locked->paid_amount);

// After:
$locked->closing_amount = $totalClosingCost;
$locked->paid_amount    = $locked->payments()->sum('amount');
$locked->balance        = max(0, $locked->amount - $locked->paid_amount + $totalSavings);
```

- [ ] Create migration adding `credits.closing_amount` (decimal 15,2 nullable)
- [ ] Update `CreditPaymentService` to write `closing_amount` instead of mutating `amount`
- [ ] Update `CreditResource` to expose `closing_amount` in the API response
- [ ] Update `fixClosingPaymentCredits()` in the same service to use `closing_amount`
- [ ] Test: accept a closing offer, confirm `credit.amount` = original total, `credit.closing_amount` = negotiated total

---

### 2.4 Duplicate items in a single request double the stock effect
**Severity:** P1 | **Effort:** 30 minutes

**Problem:**
`StoreSaleRequest` and `StorePurchaseRequest` do not validate that `items[].item_id`
values are unique. Submitting the same item twice creates two `SaleItem`/`PurchaseItem`
rows, triggers stock movement twice, and doubles the credit/payment amounts.

**Files:** `app/Http/Requests/Api/V1/Sales/StoreSaleRequest.php`, `app/Http/Requests/Api/V1/Purchases/StorePurchaseRequest.php`

**Fix — add a duplicate-detection rule to `rules()` in both requests:**
```php
'items' => [
    'required', 'array', 'min:1',
    function ($attribute, $value, $fail) {
        $ids = array_filter(array_column((array) $value, 'item_id'));
        if (count($ids) !== count(array_unique($ids))) {
            $fail('Each item may only appear once per transaction.');
        }
    },
],
```

- [ ] Add the closure rule to `StoreSaleRequest`
- [ ] Add the closure rule to `StorePurchaseRequest`
- [ ] Test: submit a sale with the same `item_id` twice, confirm 422 with a readable error

---

### 2.5 `advance_amount` can exceed the sale/purchase total
**Severity:** P1 | **Effort:** 30 minutes

**Problem:**
Both FormRequests validate `advance_amount >= 0.01` but impose no upper limit.
Submitting `advance_amount = 999999` on a 500 ETB sale produces `due_amount = -999499`
and a credit with a negative balance.

**Files:** Same as 2.4

**Fix — add a cross-field max rule:**
```php
'advance_amount' => [
    'required_if:payment_method,credit_advance',
    'nullable', 'numeric', 'min:0.01',
    function ($attribute, $value, $fail) {
        if ($value === null) return;
        $total = collect($this->input('items', []))->sum(
            fn ($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? $i['unit_cost'] ?? 0)
        );
        if ($value >= $total) {
            $fail('Advance amount must be less than the total transaction amount.');
        }
    },
],
```

- [ ] Apply the closure rule to `StoreSaleRequest`
- [ ] Apply the closure rule to `StorePurchaseRequest` (use `unit_cost` key instead of `unit_price`)
- [ ] Test: send `advance_amount` equal to total, confirm 422

---

### 2.6 `TransferService` bypasses the IoC container
**Severity:** P2 | **Effort:** 5 minutes

**Problem:**
`TransferService::__construct()` manually instantiates `StockMovementService`:
```php
$this->stockMovementService = new StockMovementService();
```
This makes `StockMovementService` impossible to mock in tests and bypasses any
IoC bindings.

**File:** `app/Services/TransferService.php:20-25`

**Fix:**
```php
// Before:
public function __construct()
{
    $this->stockMovementService = new StockMovementService();
}

// After:
public function __construct(private readonly StockMovementService $stockMovementService) {}
```

Remove the `private StockMovementService $stockMovementService;` property declaration if one exists.

- [ ] Switch to constructor injection
- [ ] Confirm Laravel resolves it automatically (no `AppServiceProvider` binding needed for concrete classes)

---

## Phase 3 — Business Rule Hardening (P2)

Silent violations that produce wrong business outcomes without errors.

---

### 3.1 Walking customers can be assigned credit payment methods
**Severity:** P2 | **Effort:** 30 minutes

**Problem:**
`StoreSaleRequest` does not reject `full_credit` or `credit_advance` for
`is_walking_customer = true` sales. The only guard is inside `Sale::createCreditRecord()`
which silently returns without creating a credit — leaving a sale with `payment_status = due`
and no mechanism to ever pay the balance.

**File:** `app/Http/Requests/Api/V1/Sales/StoreSaleRequest.php`

**Fix — add a validation rule to `rules()`:**
```php
'payment_method' => [
    'required',
    Rule::in(PaymentMethod::forSalesValues()),
    function ($attribute, $value, $fail) {
        $creditMethods = [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value];
        if ($this->boolean('is_walking_customer') && in_array($value, $creditMethods, true)) {
            $fail('Walking customers cannot purchase on credit.');
        }
    },
],
```

- [ ] Add closure rule to `StoreSaleRequest`
- [ ] Test: submit `is_walking_customer=true` + `payment_method=full_credit`, confirm 422

---

### 3.2 Future-dated sales and purchases are accepted
**Severity:** P2 | **Effort:** 10 minutes

**Problem:**
`sale_date` and `purchase_date` have no future-date restriction. A future-dated sale
deducts stock immediately but appears in future reporting periods, creating a gap
between physical stock counts and the books.

**Files:** `app/Http/Requests/Api/V1/Sales/StoreSaleRequest.php`, `app/Http/Requests/Api/V1/Purchases/StorePurchaseRequest.php`

**Fix:**
```php
// StoreSaleRequest:
'sale_date' => ['nullable', 'date', 'before_or_equal:today'],

// StorePurchaseRequest:
'purchase_date' => ['required', 'date', 'before_or_equal:today'],
```

- [ ] Add `before_or_equal:today` to `sale_date` rule
- [ ] Add `before_or_equal:today` to `purchase_date` rule
- [ ] Test: submit a sale with tomorrow's date, confirm 422

---

### 3.3 Dashboard charts group by `created_at` instead of the business date
**Severity:** P2 | **Effort:** 30 minutes

**Problem:**
`ChartDataService` builds time-series data using `Sale::whereBetween('created_at', ...)`.
The business date fields `sale_date` and `purchase_date` are separate columns.
A sale entered today for yesterday's date appears in today's bar, not yesterday's.

**File:** `app/Services/Dashboard/ChartDataService.php:159-191`

**Fix — change the `whereBetween` column and the Carbon group-by format:**
```php
// In getSalesData():
$query = Sale::whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()]);
// Group by:
->groupBy(fn ($s) => Carbon::parse($s->sale_date)->format($groupFormat))

// In getPurchasesData():
$query = Purchase::whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()]);
->groupBy(fn ($p) => Carbon::parse($p->purchase_date)->format($groupFormat))
```

Note: hourly chart (`today`, `yesterday`) uses `created_at` is fine since same-day grouping.
Only the daily/monthly ranges need to switch to the business date columns.

- [ ] Update `getSalesData()` query and groupBy
- [ ] Update `getPurchasesData()` query and groupBy
- [ ] Test: create a sale with `sale_date = yesterday`, confirm it appears in yesterday's chart bar

---

### 3.4 Over-payments on credits are not rejected
**Severity:** P2 | **Effort:** 30 minutes

**Problem:**
`CreditController::addPayment()` calls `$credit->addPayment(amount: ...)` with no
guard preventing the payment from exceeding `credit.balance`. Over-paying produces
`balance < 0` with no refund pathway or alert.

**File:** `app/Models/Credit.php` (in the `addPayment()` method)

**Fix — add a balance guard at the top of `addPayment()`:**
```php
public function addPayment(float $amount, ...): CreditPayment
{
    if ($amount > $this->balance + 0.005) { // 0.005 float tolerance
        throw new \DomainException(
            "Payment amount ({$amount}) exceeds outstanding balance ({$this->balance})."
        );
    }
    // ... rest of method
}
```

- [ ] Add the guard to `Credit::addPayment()`
- [ ] Catch `DomainException` in `CreditController::addPayment()` and return a 422 response
- [ ] Test: attempt to pay more than the credit balance, confirm 422 with descriptive message

---

### 3.5 Race condition: reservation check is not locked
**Severity:** P2 | **Effort:** ~2 hours

**Problem:**
`StockMovementService::reserveItemStock()` calls `getAvailableStock()` then `getReservedStock()`
as two separate unprotected reads, then inserts the reservation. Between those reads and
the INSERT, a concurrent request can read the same "sufficient stock" and also proceed.
Both reservations succeed, but they overcommit the stock. The failure surfaces only when
the second transfer is approved and `removeStockFromWarehouse` throws a `TransferException`.

**File:** `app/Services/StockMovementService.php:146-177`

**Fix — wrap the check + insert in a single locked read:**
```php
private function reserveItemStock(
    int $itemId, float $quantity, string $locationType, int $locationId,
    string $referenceType, int $referenceId, int $userId
): void {
    // Lock the stock row before reading available quantity
    $stockQty = Stock::where('warehouse_id', $locationId)
        ->where('item_id', $itemId)
        ->lockForUpdate()
        ->value('quantity') ?? 0;

    $reservedQty = StockReservation::where('item_id', $itemId)
        ->where('location_type', $locationType)
        ->where('location_id', $locationId)
        ->where('expires_at', '>', now())
        ->sum('quantity');

    $actuallyAvailable = $stockQty - $reservedQty;

    if ($actuallyAvailable < $quantity) {
        $item = Item::find($itemId);
        throw new TransferException(
            "Insufficient available stock for {$item->name}. " .
            "Available: {$actuallyAvailable}, Required: {$quantity}"
        );
    }

    StockReservation::create([
        'item_id'        => $itemId,
        'location_type'  => $locationType,
        'location_id'    => $locationId,
        'quantity'       => $quantity,
        'reference_type' => $referenceType,
        'reference_id'   => $referenceId,
        'expires_at'     => now()->addHours(24),
        'created_by'     => $userId,
    ]);
}
```

This method is already called inside a `DB::transaction()` in `reserveStock()`, so the
`lockForUpdate()` is correctly scoped to that transaction.

- [ ] Rewrite `reserveItemStock()` with `lockForUpdate()` on stock row
- [ ] Ensure the outer `DB::transaction()` in `reserveStock()` is intact
- [ ] Note: for branch-type source, the lock needs to be applied to all warehouse stock rows
      in the branch — see `removeStockFromBranch()` for the pattern

---

## Phase 4 — Structural Cleanup (P3)

Technical debt that doesn't cause bugs today but will cause them eventually.

---

### 4.1 Delete dead code: `SaleFormService` and `Stock::updateStock()`
**Severity:** P3 | **Effort:** 15 minutes

**Problem:**
- `app/Services/Sales/SaleFormService.php` has a duplicate `createSale()` method
  that is not referenced by any controller or service. It creates confusion about
  which path is canonical.
- `Stock::updateStock()` (legacy method, line 244) only updates `quantity` but not
  `piece_count`. Any call would silently break the `quantity === piece_count` invariant.

**Fix:**
```bash
rm app/Services/Sales/SaleFormService.php
# Remove updateStock() from app/Models/Stock.php
```

Verify with:
```bash
grep -rn "SaleFormService\|updateStock" app/ --include="*.php"
```

- [ ] Delete `app/Services/Sales/SaleFormService.php`
- [ ] Remove `Stock::updateStock()` (lines ~244-267 in `app/Models/Stock.php`)
- [ ] Confirm no remaining references with grep

---

### 4.2 Canonicalize `stock_histories` write path
**Severity:** P3 | **Effort:** ~4 hours

**Problem:**
Three independent paths write to `stock_histories` with different field sets:
1. `StockMovementService::recordStockHistory()` — uses `description`, `user_id`
2. `Stock::createStockHistory()` — uses `description`, `user_id`, `units_*`
3. `Sale::processWarehouseSaleItem()` — used `movement_type`, `notes`, `created_by` (fixed in 1.4)

There is no single source of truth. Any schema addition requires three updates.

**Fix — after Phase 1 item 1.4 is done, `Stock::createStockHistory()` becomes the
single writer (it handles `units_*` correctly). Refactor `StockMovementService::recordStockHistory()`
to delegate to `Stock::createStockHistory()` or extract a standalone `StockHistoryWriter` service:**

```php
// app/Services/StockHistoryWriter.php
final class StockHistoryWriter
{
    public static function write(
        int $warehouseId, int $itemId,
        float $quantityBefore, float $quantityChange, float $quantityAfter,
        ?float $unitsBefore, ?float $unitsChange, ?float $unitsAfter,
        string $referenceType, ?int $referenceId,
        string $description, ?int $userId
    ): void {
        StockHistory::create([
            'warehouse_id'    => $warehouseId,
            'item_id'         => $itemId,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after'  => $quantityAfter,
            'units_before'    => $unitsBefore,
            'units_change'    => $unitsChange,
            'units_after'     => $unitsAfter,
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'description'     => $description,
            'user_id'         => $userId,
        ]);
    }
}
```

- [ ] Create `app/Services/StockHistoryWriter.php`
- [ ] Replace `StockMovementService::recordStockHistory()` to call `StockHistoryWriter::write()`
- [ ] Replace `Stock::createStockHistory()` to call `StockHistoryWriter::write()`
- [ ] Remove the now-redundant private methods in both files

---

### 4.3 `PurchaseService` must write to `price_histories`
**Severity:** P3 | **Effort:** 1 hour

**Problem:**
`PurchaseService::updateItemCostPrice()` overwrites `item.cost_price` on every purchase
with no audit trail. A second purchase at a different price destroys the previous cost record.
The `price_histories` table exists for exactly this purpose but is never written by `PurchaseService`.

**Files:** `app/Services/PurchaseService.php:165-172`, `app/Models/PriceHistory.php`

**Fix — write to `price_histories` before updating `items`:**
```php
private function updateItemCostPrice(Item $item, float $cost): void
{
    if ($cost <= 0 || $cost === (float) $item->cost_price) {
        return;
    }

    PriceHistory::create([
        'item_id'     => $item->id,
        'cost_price'  => $cost,
        'old_price'   => $item->cost_price,
        'user_id'     => auth()->id(),
        'reason'      => 'Updated from purchase',
    ]);

    $item->cost_price          = $cost;
    $item->cost_price_per_unit = $cost / ($item->unit_quantity ?? 1);
    $item->save();
}
```

- [ ] Check `PriceHistory` model fillable and migration columns match the above
- [ ] Update `updateItemCostPrice()` to write a `PriceHistory` row before updating the item
- [ ] Test: create two purchases for the same item at different prices, confirm two `price_histories` rows exist

---

## Phase 5 — Database Migrations

Each migration is independent and can be run in any order within this phase.

---

### 5.1 Add missing performance indexes
**Severity:** P3 | **Effort:** 30 minutes

**Note:** `2026_05_17_000001_add_performance_indexes.php` may already exist — check its contents
first and only add what's missing.

```php
Schema::table('stock_histories', function (Blueprint $table) {
    $table->index('user_id', 'sh_user_id_idx');
    $table->index(['warehouse_id', 'created_at'], 'sh_warehouse_created_idx');
});
Schema::table('credits', function (Blueprint $table) {
    $table->index(['reference_type', 'reference_id', 'status'], 'credits_ref_status_idx');
    $table->index(['customer_id', 'status'], 'credits_customer_status_idx');
});
Schema::table('sales', function (Blueprint $table) {
    $table->index('sale_date', 'sales_sale_date_idx');
    $table->index(['branch_id', 'sale_date'], 'sales_branch_date_idx');
});
Schema::table('purchases', function (Blueprint $table) {
    $table->index('purchase_date', 'purchases_purchase_date_idx');
    $table->index(['branch_id', 'purchase_date'], 'purchases_branch_date_idx');
});
```

- [ ] Check existing performance indexes migration content
- [ ] Add missing indexes in a new migration
- [ ] Run `php artisan migrate` and verify with `SHOW INDEX FROM table_name`

---

### 5.2 Add `credits.closing_amount` column
**Severity:** P1 | **Effort:** 15 minutes (dependent on item 2.3)

Already described in item 2.3. Track separately here so the migration is not forgotten.

```php
Schema::table('credits', function (Blueprint $table) {
    $table->decimal('closing_amount', 15, 2)->nullable()->after('amount')
          ->comment('Negotiated closing total from early closure; null until offer is accepted');
});
```

- [ ] Create migration
- [ ] Run migration
- [ ] Update `CreditResource` to expose the new column

---

### 5.3 Add DB-level constraints on `credits`
**Severity:** P3 | **Effort:** 30 minutes

MySQL check constraints (available in MySQL 8.0.16+).

```php
DB::statement('ALTER TABLE credits ADD CONSTRAINT chk_balance_non_negative CHECK (balance >= 0)');
DB::statement('ALTER TABLE credits ADD CONSTRAINT chk_paid_lte_amount CHECK (paid_amount <= amount + 0.01)');
```

- [ ] Verify MySQL version supports CHECK constraints (`SELECT VERSION()`)
- [ ] Create migration with the two constraints
- [ ] Test: attempt a raw DB insert with `balance = -1`, confirm it is rejected

---

### 5.4 Replace `stocks.piece_count` with a virtual column (or remove it)
**Severity:** P3 | **Effort:** 1 hour

**Problem:** `stocks.quantity` and `stocks.piece_count` always represent the same value.
Maintaining two columns creates a sync risk (already demonstrated by `Stock::updateStock()`).

**Option A (preferred — no app code changes):**
```sql
ALTER TABLE stocks DROP COLUMN piece_count;
ALTER TABLE stocks ADD COLUMN piece_count INT GENERATED ALWAYS AS (CAST(quantity AS SIGNED)) STORED;
```

**Option B (pure PHP, simpler to revert):**
Remove `piece_count` from `$fillable` and `$casts` in `Stock.php`. Redirect all reads
of `$stock->piece_count` to `$stock->quantity` via an accessor.

- [ ] Choose Option A or B and document the decision here
- [ ] Create and run migration
- [ ] Audit all code that writes to `piece_count` and confirm none break

---

## Phase 6 — Test Suite

No code in this project is currently covered by automated tests.
Build the suite module by module as each phase above is completed.

---

### 6.1 Set up test infrastructure
**Effort:** 2 hours

- [ ] Add `pestphp/pest` to dev dependencies: `composer require pestphp/pest --dev`
- [ ] Run `php artisan pest:install`
- [ ] Create model factories for: `User`, `Branch`, `Warehouse`, `Item`, `Category`, `Customer`, `Supplier`, `Sale`, `Purchase`, `Transfer`, `Credit`, `Stock`
- [ ] Create a `TestCase` base class that logs in a user with a given role using Sanctum:
  ```php
  protected function actingAsRole(string $role): static
  {
      $user = User::factory()->create();
      $user->assignRole($role);
      return $this->actingAs($user, 'sanctum');
  }
  ```
- [ ] Confirm `phpunit.xml` is configured to use a separate test database

---

### 6.2 Unit tests (no database, pure logic)
**Effort:** 3 hours

File: `tests/Unit/`

| Test class | What it covers |
|---|---|
| `UserAccessTest` | `hasFullAccess()` = true for SuperAdmin and Manager; false for BranchManager |
| `SaleServiceAmountsTest` | `resolveAmounts()` returns correct paid/due/status for all 5 payment methods |
| `CreditPaymentServiceTest` | `calculateProfitLossFromNegotiatedPrices()` with keyed format; savings correctly computed |
| `PaymentMethodEnumTest` | `forSalesValues()` and `forPurchasesValues()` return expected values |
| `StoreSaleRequestValidationTest` | Walking customer + credit → 422; advance > total → 422; duplicate items → 422 |

- [ ] `UserAccessTest`
- [ ] `SaleServiceAmountsTest`
- [ ] `CreditPaymentServiceTest`
- [ ] `PaymentMethodEnumTest`
- [ ] `StoreSaleRequestValidationTest`

---

### 6.3 Feature tests — Sales
**Effort:** 4 hours

File: `tests/Feature/SaleCreationTest.php`

- [ ] Cash sale deducts stock and creates no credit record
- [ ] `full_credit` sale creates one `Credit` row with `amount = total`, `balance = total`, `status = active`
- [ ] `credit_advance` sale creates credit with `balance = total - advance`, `status = partial`
- [ ] `sale_date` is preserved correctly after `createCreditRecord()` runs (Carbon mutation test)
- [ ] Walking customer + `full_credit` → 422 from FormRequest
- [ ] Duplicate `item_id` in items → 422
- [ ] `advance_amount >= total` → 422
- [ ] Future `sale_date` → 422
- [ ] Sale with insufficient stock still succeeds (negative stock allowed)
- [ ] Deleting a sale with `full_credit` removes the associated credit record

---

### 6.4 Feature tests — Purchases
**Effort:** 3 hours

File: `tests/Feature/PurchaseCreationTest.php`

- [ ] Purchase increments `stocks.quantity` for the correct warehouse
- [ ] Purchase creates a `PriceHistory` row (after Phase 4 item 4.3)
- [ ] `full_credit` purchase creates a payable `Credit` row
- [ ] Deleting a received purchase decrements stock and removes credit
- [ ] Duplicate `item_id` in items → 422
- [ ] Future `purchase_date` → 422
- [ ] `BranchManager` can only create purchases for their own branch → 403 for other branch

---

### 6.5 Feature tests — Transfers
**Effort:** 4 hours

File: `tests/Feature/TransferWorkflowTest.php`

- [ ] Branch-to-branch transfer creates stock reservation
- [ ] Warehouse-to-warehouse transfer creates stock reservation (regression for item 1.3)
- [ ] Approve releases reservation and moves stock between warehouses
- [ ] Reject releases reservation, stock unchanged
- [ ] Cancel releases reservation, stock unchanged
- [ ] Transfer exceeding available stock → `TransferException` with descriptive message
- [ ] Two concurrent transfers for the same stock: second reservation is blocked by lock (test with two separate DB connections or by directly testing the service with simulated reservation pre-existing)
- [ ] `BranchManager` can only approve transfers destined for their branch

---

### 6.6 Feature tests — Credits
**Effort:** 3 hours

File: `tests/Feature/CreditPaymentTest.php`

- [ ] `addPayment()` increments `paid_amount`, decrements `balance`, updates `status`
- [ ] Over-payment → `DomainException` → 422 from controller (after item 3.4)
- [ ] Closing offer requires ≥ 50% payment on a payable credit
- [ ] `acceptClosingOffer()` preserves `credit.amount` = original total; sets `closing_amount` = negotiated total (after items 2.3 + 5.2)
- [ ] Closing offer on a receivable credit → 403
- [ ] `negotiated_prices` sent as list of objects → correctly mapped to keyed array (regression for item 1.2)

---

## Progress tracker

| Phase | Items | Done | Remaining |
|-------|-------|------|-----------|
| 1 — P0 Crashes | 4 | 0 | 4 |
| 2 — P1 Data corruption | 6 | 0 | 6 |
| 3 — P2 Business rules | 5 | 0 | 5 |
| 4 — P3 Structural | 3 | 0 | 3 |
| 5 — DB migrations | 4 | 0 | 4 |
| 6 — Tests | 6 | 0 | 6 |
| **Total** | **28** | **0** | **28** |

Update this table as you complete items.
