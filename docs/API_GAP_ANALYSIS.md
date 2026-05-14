# API Migration Plan — Blade/Livewire → API-Only

Source: `docs/API_FIRST_BACKEND_ARCHITECTURE_ANALYSIS.md`  
Strategy: build + test one module at a time, then delete the matching Livewire code.  
Every task is independently completable. Do not start Phase N+1 until Phase N tests pass.

---

## Phase 0 — Stabilize the Base

These are bugs and inconsistencies that will silently break API work if left unfixed.

### Task 0.1 — Fix phpunit bootstrap
- Create `phpunit.xml` (from `phpunit.xml.dist` template)
- Add `.env.testing` with `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
- **Acceptance**: `php artisan test` exits 0 with existing two unit tests passing

### Task 0.2 — Fix hybrid route bootstrapping
**Problem**: `bootstrap/app.php` does NOT register `routes/api.php`. `RouteServiceProvider` does but is not in `bootstrap/providers.php`.
- `bootstrap/app.php` — add `api: __DIR__.'/../routes/api.php'` to `withRouting()`; add `withMiddleware()` alias block; add `withExceptions()` JSON rule
- `app/Providers/RouteServiceProvider.php` — remove API route loading block (keep web if still needed or delete the file)
- **Acceptance**: `php artisan route:list` shows `/api/user`; `GET /api/user` returns JSON

### Task 0.3 — Fix PaymentMethod enum inconsistency
**Problem**: `PaymentMethod::FULL_CREDIT` value is `full_credit`; sale code uses `credit_full`; `CreditPaymentController` allows `mobile_money` while enum uses `telebirr`.
- `app/Enums/PaymentMethod.php` — audit all case values
- Grep `credit_full` and `mobile_money` across `app/` — standardize to canonical enum values
- Write a data migration if `sales.payment_method` column stores the wrong string
- **Acceptance**: one canonical value; no divergent string literals in services or Livewire

### Task 0.4 — Fix stock reservation schema mismatch
**Problem**: `Admin/StockReservationController` filters by `warehouse_id`, but the table uses `location_type`/`location_id`. Calls missing `UserHelper::getAccessibleWarehouseIds()`.
- `app/Http/Controllers/Admin/StockReservationController.php` — replace `warehouse_id` filter with morph query on `location_type`/`location_id`
- `app/Helpers/UserHelper.php` — add `getAccessibleWarehouseIds(User $user): array`
- Add `StockReservation::forLocation(Model $location)` scope if missing
- **Acceptance**: web stock-reservation page loads without 500

### Task 0.5 — Fix transfer print relation aliases
**Problem**: `TransferController@print` loads `transferItems` + `createdBy`; model exposes `items` + `creator`.
- `app/Http/Controllers/TransferController.php` — change to `with(['items.item', 'creator'])`
- **Acceptance**: transfer print view loads without "relation not found" error

### Task 0.6 — Add missing policies
Required before any write API can use `$this->authorize()`:

| Policy | Model | Key rule |
|---|---|---|
| `SalePolicy` | `Sale` | owner branch; manager/sales role |
| `CreditPolicy` | `Credit` | linked sale/purchase actor checks |
| `WarehousePolicy` | `Warehouse` | manager restricted to own-branch warehouses |
| `BankAccountPolicy` | `BankAccount` | branch-bound access |
| `ExpensePolicy` | `Expense` | branch-bound; accountant role |
| `EmployeePolicy` | `Employee` | HR or manager role |

- Create `app/Policies/{Sale,Credit,Warehouse,BankAccount,Expense,Employee}Policy.php`
- Register in `app/Providers/AuthServiceProvider.php`
- **Acceptance**: `Gate::inspect()` unit test per policy passes

### Task 0.7 — Secure unauthenticated stock-card routes
**Problem**: `/stock-card` and `/stock-card/print` are outside the `auth` middleware group.
- `routes/web.php` — move both routes inside `auth` + `permission:stock-card.view` group
- **Acceptance**: anonymous `GET /stock-card` returns redirect; authenticated returns 200

---

## Phase 1 — API Foundation

One-time infrastructure all other phases depend on. Complete entirely before starting Phase 2.

### Task 1.1 — Normalize bootstrap/app.php (builds on Task 0.2)

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.active' => \App\Http\Middleware\EnsureApiUserIsActive::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn ($request, \Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
```

Also update `config/cors.php` — restrict `allowed_origins` to known frontend origins.

### Task 1.2 — Auth controller + routes + UserResource

Create:
- `app/Http/Controllers/Api/V1/AuthController.php` — `login`, `logout`, `me`, `updateProfile`
- `app/Http/Requests/Api/V1/Auth/LoginRequest.php` — validates `email`, `password`
- `app/Http/Requests/Api/V1/Auth/UpdateProfileRequest.php`
- `app/Http/Resources/Api/V1/UserResource.php` — exposes `id`, `name`, `email`, `is_active`, `roles`, `branch`, `warehouse`; never exposes `password`

`routes/api.php`:
```php
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me/profile', [AuthController::class, 'updateProfile']);
    });
});
```

### Task 1.3 — EnsureApiUserIsActive middleware

Create `app/Http/Middleware/EnsureApiUserIsActive.php`:
- `$request->user()->is_active === false` → `response()->json(['message' => 'Account is inactive.'], 403)`
- No session invalidation, no redirect

### Task 1.4 — Centralized JSON error contract

Add to `withExceptions()`:
- `ValidationException` → 422 `{ errors: { field: [...] } }`
- `AuthorizationException` → 403 `{ message }`
- `AuthenticationException` → 401 `{ message: 'Unauthenticated.' }`
- `ModelNotFoundException` → 404 `{ message: 'Resource not found.' }`
- `\DomainException` → 422 `{ message }` (business rule violations from services)

### Task 1.5 — Auth feature test

Create `tests/Feature/Api/V1/AuthTest.php`:
- Login success → token returned
- Wrong password → 401
- Inactive user → 403
- Logout → token revoked
- `GET /me` → UserResource returned

---

## Phase 2 — Reference Data APIs

These have no financial side effects. Ship and verify before touching transactions.

### Task 2.1 — Branches API

Create:
- `app/Http/Controllers/Api/V1/BranchController.php` — full `apiResource`
- `app/Http/Requests/Api/V1/Branches/StoreBranchRequest.php`
- `app/Http/Requests/Api/V1/Branches/UpdateBranchRequest.php`
- `app/Http/Resources/Api/V1/BranchResource.php`

Use existing `BranchPolicy`. Add `Route::apiResource('branches', BranchController::class)`.

Test `tests/Feature/Api/V1/BranchApiTest.php`: SuperAdmin creates; BranchManager cannot create → 403.

**Delete after tests pass**: `app/Livewire/Branches/` + matching web routes (only after frontend confirms).

### Task 2.2 — Warehouses API

Same pattern as Task 2.1.

Create `WarehouseController`, requests, `WarehouseResource`. Use `WarehousePolicy` (Task 0.6). Include `branch` relation.

Test: BranchManager can only see warehouses in their own branch.

**Delete after**: `app/Livewire/Warehouses/`.

### Task 2.3 — Categories API

Create `CategoryController`, `StoreCategoryRequest`, `CategoryResource`. Use existing `CategoryPolicy`.

**Delete after**: `app/Livewire/Categories/`.

### Task 2.4 — Items API

Create:
- `app/Http/Controllers/Api/V1/ItemController.php` — `index`, `store`, `show`, `update`, `destroy`, `search`
- `app/Http/Requests/Api/V1/Items/StoreItemRequest.php` — SKU, barcode, unit_quantity, prices, reorder_level
- `app/Http/Requests/Api/V1/Items/UpdateItemRequest.php`
- `app/Http/Resources/Api/V1/ItemResource.php` — conditional `stock` when loaded

Routes:
```php
Route::get('items/search', [ItemController::class, 'search']);
Route::apiResource('items', ItemController::class);
```

Reuse `ItemSearchService`. Use existing `ItemPolicy`.

Test: search returns only items with stock for user's branch; `items.create` permission gated.

**Delete after**: `app/Livewire/Items/`.

### Task 2.5 — Item Import API

Create:
- `app/Http/Controllers/Api/V1/ItemImportController.php` — `preview` (POST), `import` (POST), `downloadTemplate` (GET)
- `app/Http/Requests/Api/V1/Items/ImportItemsRequest.php`

Reuse `ItemImportService` and `ItemsImport` unchanged.

**Delete after**: `app/Livewire/Admin/Items/ImportItems.php` + web import routes.

### Task 2.6 — Customers & Suppliers APIs

Create `CustomerController`, `SupplierController`, requests, resources.  
Use existing `CustomerPolicy`, `SupplierPolicy`.

**Delete after**: `app/Livewire/Customers/`, `app/Livewire/Suppliers/`, old `CustomerController`, `SupplierController`.

### Task 2.7 — Users, Roles, Permissions APIs

Create:
- `app/Http/Controllers/Api/V1/UserController.php` — apiResource + `GET/POST /users/{user}/roles`
- `app/Http/Controllers/Api/V1/RoleController.php` — apiResource
- `app/Http/Controllers/Api/V1/PermissionController.php` — `index` only

Use existing `UserPolicy`.

**Delete after**: `app/Livewire/Users/`, `app/Livewire/Roles/`.

### Task 2.8 — Employees, Bank Accounts, Expense Types APIs

Create new clean API controllers (do NOT reuse old `BankAccountController` — it hard-codes location names):
- `app/Http/Controllers/Api/V1/EmployeeController.php`
- `app/Http/Controllers/Api/V1/BankAccountController.php`
- `app/Http/Controllers/Api/V1/ExpenseTypeController.php`

**Delete after**: old `app/Http/Controllers/BankAccountController.php`, `app/Livewire/Employees/`, `app/Livewire/BankAccounts/`, `app/Livewire/Admin/Settings/`.

---

## Phase 3 — Inventory & Stock APIs

### Task 3.1 — Stock read API

Create:
- `app/Http/Controllers/Api/V1/StockController.php` — `index` (filter by `branch_id`, `warehouse_id`, `item_id`, `below_reorder`), `show`
- `app/Http/Resources/Api/V1/StockResource.php` — `quantity`, `piece_count`, `total_units`, `current_piece_units`, `item`, `branch`, `warehouse`

Test: BranchManager only sees their branch's stock.

### Task 3.2 — Stock history API

Create:
- `app/Http/Controllers/Api/V1/StockHistoryController.php` — `index`, filter by `item_id`, `branch_id`, `date_from`, `date_to`
- `app/Http/Resources/Api/V1/StockHistoryResource.php`

### Task 3.3 — Stock card API

Create:
- `app/Http/Controllers/Api/V1/StockCardController.php` — requires `auth:sanctum` + `permission:stock-card.view`
- `app/Http/Resources/Api/V1/StockCardResource.php`

**Delete after**: old `app/Http/Controllers/StockCardController.php`, `app/Livewire/StockCard/`.

### Task 3.4 — Stock reservations API (requires Task 0.4 done first)

Create:
- `app/Http/Controllers/Api/V1/StockReservationController.php` — `index`, `show`, `release` (POST), `extend` (PATCH), `cleanup` (POST)
- `app/Http/Resources/Api/V1/StockReservationResource.php`

**Delete after**: old `app/Http/Controllers/Admin/StockReservationController.php`.

### Task 3.5 — Stock reports & export API

Create:
- `app/Http/Controllers/Api/V1/StockReportController.php` — `index` (JSON), `export` (CSV/JSON file download)

Reuse export logic from old `Admin/StockReportController.php`.

**Delete after**: old `app/Http/Controllers/Admin/StockReportController.php`.

---

## Phase 4 — Purchases API

Each task here must have its own test before the next starts.

### Task 4.1 — Refactor PurchaseService to accept explicit actor

**Problem**: `PurchaseService::createPurchase()` calls `Auth::user()` internally — breaks tests and API calls.

- `app/Services/PurchaseService.php` — add `User $actor` parameter; replace all `Auth::user()` / `auth()` calls with `$actor`
- `app/Livewire/Purchases/Create.php` — pass `auth()->user()` explicitly
- **Acceptance**: service has no `Auth::` calls; existing Livewire flow still works

### Task 4.2 — StorePurchaseRequest

Create `app/Http/Requests/Api/V1/Purchases/StorePurchaseRequest.php`:
- `supplier_id`, `branch_id`, `warehouse_id`, `purchase_date`, `payment_method` (enum), `transaction_number`, `bank_account_id`, `advance_amount`
- `items[]` with `item_id`, `quantity`, `unit_cost`, `subtotal`
- Authorization: `purchases.create`

### Task 4.3 — PurchaseResource + PurchaseItemResource

Create:
- `app/Http/Resources/Api/V1/PurchaseResource.php`
- `app/Http/Resources/Api/V1/PurchaseItemResource.php`

Never leak `deleted_by` or raw pivot data.

### Task 4.4 — Purchase API controller

Create `app/Http/Controllers/Api/V1/PurchaseController.php`:
- `index` — filter by `supplier_id`, `branch_id`, `status`, `payment_status`, `date_from`, `date_to`; paginated
- `store` — calls `PurchaseService::createPurchase($actor, $data)` inside `DB::transaction()`; returns 201
- `show` — loads `supplier`, `branch`, `warehouse`, `items.item`, `credit`, `payments`
- `payments` — `index` + `store` sub-resource

Routes:
```php
Route::apiResource('purchases', PurchaseController::class);
Route::get('purchases/{purchase}/payments', [PurchaseController::class, 'payments']);
Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'addPayment']);
```

Use `PurchasePolicy`.

### Task 4.5 — Purchase API tests

Create `tests/Feature/Api/V1/PurchaseApiTest.php`:
- Full credit purchase creates credit record
- Cash purchase succeeds; bank transfer requires `bank_account_id`
- Telebirr purchase creates stock movement
- BranchManager cannot create for other branch → 403
- Duplicate `transaction_number` rejected → 422
- PurchaseOfficer role can create; Sales role cannot → 403

---

## Phase 5 — Sales API

### Task 5.1 — Refactor SaleFormService → SaleService

**Problem**: `SaleFormService` uses Livewire `$this->` state and session-based `UserContextService`.

Create `app/Services/SaleService.php`:
- Accepts `User $actor` and validated `array $data`
- Resolves branch/warehouse from `$data` with fallback to actor's assigned branch
- Calls `Sale::processSale()` inside `DB::transaction()`
- Throws `\DomainException` for business rule violations (below-cost without permission, insufficient stock, missing customer)

Keep `SaleFormService` intact for Livewire until Phase 10.

### Task 5.2 — StoreSaleRequest

Create `app/Http/Requests/Api/V1/Sales/StoreSaleRequest.php`:
- `payment_method` using `Rule::enum(PaymentMethod::class)`
- `customer_id` required unless `is_walking_customer` is true
- `items[].item_id`, `items[].quantity`, `items[].sale_method` (piece/unit), `items[].unit_price`

### Task 5.3 — SaleResource + SaleItemResource

Create:
- `app/Http/Resources/Api/V1/SaleResource.php` — `reference_no`, `status`, `payment_status`, `payment_method`, `amounts` object (total/paid/due/tax/shipping), `customer`, `branch`, `warehouse`, `items`, `credit`
- `app/Http/Resources/Api/V1/SaleItemResource.php`

### Task 5.4 — Sale API controller

Create `app/Http/Controllers/Api/V1/SaleController.php`:
- `index` — filter by `customer_id`, `branch_id`, `warehouse_id`, `status`, `payment_status`, `date_from`, `date_to`
- `store` — calls `SaleService::create($actor, $data)`; returns 201
- `show` — loads `customer`, `branch`, `warehouse`, `items.item`, `credit`
- `payments` sub-resource

Use `SalePolicy` (Task 0.6).

### Task 5.5 — Sales API tests

Create `tests/Feature/Api/V1/SaleApiTest.php`:
- Walking customer sale (no `customer_id`) succeeds
- Credit sale creates `Credit` record
- Sale by piece deducts correct stock units
- Sale by unit deducts correct stock units
- Below-cost sale rejected for role without permission → 422
- BranchManager cannot create for other branch → 403
- Insufficient stock → 422

---

## Phase 6 — Transfers API

### Task 6.1 — StoreTransferRequest

Create `app/Http/Requests/Api/V1/Transfers/StoreTransferRequest.php`:
- `from_branch_id`, `to_branch_id`, `items[]` with `item_id`, `quantity`, `sale_method`
- Authorization: `transfers.create`; BranchManager: `from_branch_id` must equal own branch

### Task 6.2 — TransferResource + TransferItemResource

Create (using correct relation names `items` and `creator`, not `transferItems`/`createdBy`):
- `app/Http/Resources/Api/V1/TransferResource.php`
- `app/Http/Resources/Api/V1/TransferItemResource.php`

### Task 6.3 — Transfer API controller

Create `app/Http/Controllers/Api/V1/TransferController.php`:
- `index`, `store`, `show`, `destroy`
- `approve`, `reject`, `cancel`, `markInTransit`, `complete` (all POST)

Reuse `TransferService` entirely — do not duplicate workflow logic.

Routes:
```php
Route::apiResource('transfers', TransferController::class);
Route::post('transfers/{transfer}/approve',        [TransferController::class, 'approve']);
Route::post('transfers/{transfer}/reject',         [TransferController::class, 'reject']);
Route::post('transfers/{transfer}/cancel',         [TransferController::class, 'cancel']);
Route::post('transfers/{transfer}/mark-in-transit',[TransferController::class, 'markInTransit']);
Route::post('transfers/{transfer}/complete',       [TransferController::class, 'complete']);
```

Use `TransferPolicy`.

### Task 6.4 — Transfer API tests

Create `tests/Feature/Api/V1/TransferApiTest.php`:
- BranchManager creates transfer from own branch
- BranchManager cannot create from other branch → 403
- Only destination BranchManager can approve
- Approve releases reservation and updates stock
- Cancel releases reservation
- Duplicate concurrent approval handled safely by DB lock

**Delete after Phase 6 tests pass**: `app/Livewire/Transfers/`, old `app/Http/Controllers/TransferController.php`.

---

## Phase 7 — Credits & Credit Closing API

### Task 7.1 — Credit read API

Create:
- `app/Http/Controllers/Api/V1/CreditController.php` — `index`, `show`
- `app/Http/Resources/Api/V1/CreditResource.php`

Filter by `creditable_type` (sale/purchase), `status`, `branch_id`.

Remove `Credit::getReferenceUrlAttribute()` web route dependency — move URL building into `CreditResource`.

### Task 7.2 — Credit payment API

Create:
- `app/Http/Controllers/Api/V1/CreditPaymentController.php` — `index` and `store` for `/credits/{credit}/payments`
- `app/Http/Requests/Api/V1/Credits/StoreCreditPaymentRequest.php` — validates `amount`, `payment_method` using `PaymentMethod` enum (fixed in Task 0.3), `transaction_number`

Reuse `CreditPaymentService` for processing.

**Delete after**: old `app/Http/Controllers/CreditPaymentController.php`, `app/Livewire/CreditPayment/`, `app/Livewire/SalesCreditPayment/`, `app/Livewire/Credits/`.

### Task 7.3 — Credit closing offer API

Add three actions to `CreditController`:

| Method | URI | Action |
|---|---|---|
| GET | `/api/v1/credits/{credit}/closing-offer` | Show current offer details |
| POST | `/api/v1/credits/{credit}/closing-offer/calculate` | Calculate offer for proposed item prices |
| POST | `/api/v1/credits/{credit}/closing-offer/accept` | Accept offer, close credit early |

Available only when `credit->is_payable === true` and balance ≥ 50%. Authorization: `credits.edit`.  
Reuse `CreditPaymentService::calculateClosingOffer()` and `::acceptClosingOffer()`.

### Task 7.4 — Credit API tests

Create `tests/Feature/Api/V1/CreditApiTest.php`:
- Payment reduces balance; marks paid when fully settled
- Cannot overpay → 422
- Closing offer unavailable when < 50% paid → 403
- Accepted closing offer closes credit and persists negotiated prices
- Payment `payment_method` aligns with `PaymentMethod` enum values

---

## Phase 8 — Expenses, Price History APIs

### Task 8.1 — Expenses API

Create:
- `app/Http/Controllers/Api/V1/ExpenseController.php` — full CRUD
- `app/Http/Requests/Api/V1/Expenses/StoreExpenseRequest.php`
- `app/Http/Resources/Api/V1/ExpenseResource.php`

Use `ExpensePolicy` (Task 0.6). Filter by `branch_id`, `expense_type_id`, `date_from`, `date_to`.

**Delete after**: `app/Livewire/Expenses/`.

### Task 8.2 — Price History API

Create:
- `app/Http/Controllers/Api/V1/PriceHistoryController.php` — `index` (global) + nested under items
- `app/Http/Resources/Api/V1/PriceHistoryResource.php`

Routes:
```php
Route::get('price-histories', [PriceHistoryController::class, 'index']);
Route::get('items/{item}/price-histories', [PriceHistoryController::class, 'forItem']);
```

**Delete after**: old `app/Http/Controllers/PriceHistoryController.php`.

---

## Phase 9 — Dashboard & Reports APIs

### Task 9.1 — Dashboard API

Create:
- `app/Http/Controllers/Api/V1/DashboardController.php` — `index` (summary stats), `charts` (`/dashboard/charts/{range}`)
- `app/Http/Resources/Api/V1/DashboardResource.php`

Reuse `DashboardService`, `StatsService`, `ChartDataService`, `InventoryService` unchanged.

**Delete after**: `app/Http/Controllers/Admin/DashboardController.php`.

### Task 9.2 — Reports API

Create:
- `app/Http/Controllers/Api/V1/ReportController.php` — `summary`, `inventory`, `sales`, `purchases`, `financial`, `activity`

All reports filterable by `branch_id`, `warehouse_id`, `date_from`, `date_to`.  
`financial` additionally requires `GeneralManager` or `Accountant` role.

**Delete after**: `app/Http/Controllers/ReportsController.php`.

---

## Phase 10 — Remove Blade and Livewire

Execute only after all Phases 2–9 API endpoints have passing feature tests and frontend confirms no web routes needed.

### Task 10.1 — Delete Livewire components (directory by directory, run tests after each)

```
app/Livewire/Branches/         ← after Task 2.1
app/Livewire/Warehouses/       ← after Task 2.2
app/Livewire/Categories/       ← after Task 2.3
app/Livewire/Items/            ← after Task 2.4
app/Livewire/Admin/Items/      ← after Task 2.5
app/Livewire/Customers/        ← after Task 2.6
app/Livewire/Suppliers/        ← after Task 2.6
app/Livewire/Users/            ← after Task 2.7
app/Livewire/Roles/            ← after Task 2.7
app/Livewire/Employees/        ← after Task 2.8
app/Livewire/BankAccounts/     ← after Task 2.8
app/Livewire/Admin/Settings/   ← after Task 2.8
app/Livewire/Purchases/        ← after Task 4.5
app/Livewire/Sales/            ← after Task 5.5
app/Livewire/Transfers/        ← after Task 6.4
app/Livewire/Credits/          ← after Task 7.4
app/Livewire/CreditPayment/    ← after Task 7.4
app/Livewire/SalesCreditPayment/ ← after Task 7.4
app/Livewire/StockCard/        ← after Task 3.3
app/Livewire/Activities/       ← after Task 9.2
app/Livewire/Profile/          ← after Task 1.2
app/Livewire/Components/       ← last (shared dropdowns no longer needed)
```

### Task 10.2 — Delete old web controllers

After their API replacements are verified:
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/BankAccountController.php`
- `app/Http/Controllers/CreditPaymentController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/ItemImportController.php`
- `app/Http/Controllers/PriceHistoryController.php`
- `app/Http/Controllers/PurchasesController.php`
- `app/Http/Controllers/ReportsController.php`
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/StockCardController.php`
- `app/Http/Controllers/SupplierController.php`
- `app/Http/Controllers/TransferController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/StockReportController.php`
- `app/Http/Controllers/Admin/StockReservationController.php`

### Task 10.3 — Gut routes/web.php

- Remove all `/admin/*` routes
- If login/logout are not needed via web: remove entirely
- Consider serving the SPA from a catch-all

### Task 10.4 — Remove Livewire and Blade assets

```bash
composer remove livewire/livewire
```

Delete:
- `resources/views/` (all Blade templates)
- `app/Http/View/` (View Composers)
- `app/View/` (View Components)

### Task 10.5 — Remove session dependencies from API

- Verify `config/session.php` is no longer needed for any API path
- Remove `StartSession` and related web middleware from anything touching `api/*`

---

## Task Sequence Summary

| Task | Phase | Blocks |
|---|---|---|
| 0.1 phpunit | 0 | All tests |
| 0.2 route bootstrap | 0 | All API |
| 0.3 PaymentMethod enum | 0 | Sales, Purchases, Credits |
| 0.4 stock reservation schema | 0 | Task 3.4 |
| 0.5 transfer relation aliases | 0 | Task 6.3 |
| 0.6 missing policies | 0 | Tasks 5.x, 6.x, 7.x, 8.x |
| 0.7 stock-card auth | 0 | Security |
| 1.1–1.5 API foundation | 1 | All Phase 2+ |
| 2.1–2.3 Branches, Warehouses, Categories | 2 | Phase 4+ FK validation |
| 2.4–2.5 Items + Import | 2 | Phase 4+ item validation |
| 2.6–2.8 Customers, Suppliers, Users, Employees, BankAccounts | 2 | Phase 4+ FK validation |
| 3.1–3.5 Stock APIs | 3 | Phase 4 stock deduction |
| 4.1–4.5 Purchases | 4 | Phase 7 credit link |
| 5.1–5.5 Sales | 5 | Phase 7 credit link |
| 6.1–6.4 Transfers | 6 | Independent |
| 7.1–7.4 Credits + Closing | 7 | Needs Phase 4 + 5 done |
| 8.1–8.2 Expenses, PriceHistory | 8 | Independent |
| 9.1–9.2 Dashboard, Reports | 9 | Needs Phase 2–7 data |
| 10.1–10.5 Remove Blade/Livewire | 10 | All 2–9 + frontend confirmed |

---

## Original Gap Analysis (reference)

This gap analysis compares the current Laravel/Livewire web application against the target API-first Laravel backend described in `docs/API_FIRST_BACKEND_ARCHITECTURE_ANALYSIS.md`.

Priority scale:

- P0: Blocks secure API launch or can expose data/behavior incorrectly.
- P1: Blocks a reliable migration of core modules.
- P2: Needed for maintainability, observability, or long-term API quality.
- P3: Nice to have after the core API is stable.

## Executive Summary

The application has a strong domain model and several useful services, but its delivery boundary is still web-first. The largest gaps are not "missing CRUD endpoints"; they are the coupling between business workflows and Livewire/session/Blade behavior, the lack of API-specific authorization and response contracts, and several consistency issues in stock, transfers, payments, and route protection.

Before building large endpoint sets, fix the P0 gaps: API bootstrapping, JSON auth, active-user middleware, public stock-card routes, test bootstrap, stock reservation access mismatch, payment method naming, and API-safe branch/warehouse context.

## Current vs Target Snapshot

| Area | Current state | Target API state | Gap level |
|---|---|---|---|
| Routing | Business routes are under `routes/web.php`; `routes/api.php` only has `/api/user`. | Versioned `/api/v1` JSON routes with controllers and middleware. | P0 |
| Auth | Session login via `LoginController`; Sanctum installed but no token login endpoint. | Sanctum token or SPA auth with JSON login/logout/me endpoints. | P0 |
| Responses | Views, redirects, flash messages, mixed JSON in web routes. | JSON-only API responses and centralized JSON exceptions. | P0 |
| Validation | Inline in Livewire/controllers. | `FormRequest` classes per endpoint. | P1 |
| Serialization | Raw models/views. | `JsonResource` classes with stable schemas. | P1 |
| Authorization | Spatie permissions plus partial policies; several modules lack policies. | Permission middleware plus per-record policies and query scopes. | P0/P1 |
| Branch/warehouse context | Session-based `UserContextService`. | Stateless API context from user assignment and validated headers/query. | P0 |
| Business logic | Split between Livewire, services, and model events. | Explicit services/actions with actor user and transactions. | P1 |
| Testing | `php artisan test` cannot run because `phpunit.xml.dist` is missing. | Runnable unit and API feature tests. | P0 |
| Documentation | No OpenAPI. | Generated OpenAPI for all `/api/v1` endpoints. | P2 |

## P0 Gaps

| ID | Gap | Evidence | Impact | Required fix | Acceptance criteria |
|---|---|---|---|---|---|
| G-001 | API route surface is effectively absent | `routes/api.php` only defines `GET /api/user`. | No API-first backend exists for modules. | Add `/api/v1` route group and first controllers for auth, me, items, purchases or another first slice. | `php artisan route:list` shows versioned API endpoints; all return JSON. |
| G-002 | API route bootstrapping is ambiguous | `bootstrap/app.php` registers web/console/health but not `api`; `RouteServiceProvider` contains API route loading but is not listed in `bootstrap/providers.php`. | Route loading may depend on cache or legacy behavior. | Normalize Laravel 12 routing in `bootstrap/app.php`, including `api: routes/api.php`, and keep only one route registration path. | Fresh install without route cache exposes `/api/v1/*` routes. |
| G-003 | No API login/token flow | `LoginController` returns Blade/redirects; Sanctum is installed and `User` uses `HasApiTokens`, but no token issue/revoke endpoints exist. | API clients cannot authenticate cleanly. | Create `Api\V1\AuthController` with `login`, `logout`, `me`, Sanctum token creation, inactive account check. | Login returns bearer token and `UserResource`; logout revokes current token. |
| G-004 | Active-user middleware is web-only | `EnsureUserIsActive` logs out, invalidates session, redirects to `/login`. | Inactive API users get redirects or session behavior instead of JSON. | Add `EnsureApiUserIsActive` returning JSON `403`; use on API group. | Inactive token request returns `403 {"message":"Account is inactive."}`. |
| G-005 | Public stock-card routes | `/stock-card` and `/stock-card/print` are outside the authenticated `/admin` group. | Unauthenticated inventory/sales/purchase information exposure risk. | Move routes into authenticated/permission group or convert to `/api/v1/stock-cards` with `stock-card.view`. | Anonymous requests return `401`/redirect; API requests require auth and permission. |
| G-006 | Test suite cannot run | `php artisan test` fails with missing `phpunit.xml.dist`. | No safety net for migration. | Add `phpunit.xml`, test environment DB config, and verify factories. | `php artisan test` runs and reports actual test results. |
| G-007 | Stock reservation controller filters nonexistent columns | `StockReservationController` filters `warehouse_id`; migration/model use `location_type` and `location_id`. It also calls missing `UserHelper::getAccessibleWarehouseIds()`. | Reservation list/security can break or leak data. | Replace with `forLocation()` filtering; add access service for allowed locations; fix missing helper or remove usage. | Reservation endpoints list only accessible reservations and do not query nonexistent columns. |
| G-008 | API branch/warehouse context is session-based | `UserContextService` reads/writes `Session::get/put`. | Stateless clients cannot select context safely; session dependency leaks into API. | Add API context resolver using user assignment plus `X-Branch-Id`/`X-Warehouse-Id` or explicit filters validated by `hasAccessToBranch/Warehouse`. | API requests can select allowed context and receive `403` for unauthorized context. |
| G-009 | Payment method naming is inconsistent | `PaymentMethod::FULL_CREDIT` is `full_credit`; sales migrations/services use `credit_full`; `CreditPaymentController` allows `mobile_money`, while current enum uses `telebirr`. | Invalid state, failed validation, broken clients, wrong credit behavior. | Standardize canonical values, migrate data, update FormRequests/services/resources. | Same enum values are used in DB constraints, validation, services, OpenAPI, and frontend clients. |
| G-010 | Missing API-safe exception contract | `Handler` returns early in debug mode and still renders Blade error views for web. | API errors can be HTML or inconsistent JSON. | Configure Laravel 12 exceptions in `bootstrap/app.php` with API JSON rendering for validation/auth/not found/domain exceptions. | API tests assert JSON for `401`, `403`, `404`, `422`, `409`, `500`. |

## P1 Gaps

| ID | Gap | Evidence | Impact | Required fix | Acceptance criteria |
|---|---|---|---|---|---|
| G-011 | Business workflows are coupled to Livewire state | `Purchases\Create`, `Sales\Create`, `Transfers\Create`, `CreditPayment\Create` contain validation, workflow orchestration, UI warnings, redirects. | API controllers would duplicate or bypass rules. | Extract reusable command services/actions and FormRequests; keep Livewire as a client of the same services. | API and Livewire create the same records through same service/action path. |
| G-012 | Services use `Auth` internally | `PurchaseService`, `SaleFormService`, `ItemImportService`, `Credit` boot logic use `Auth`/`auth()`. | Background jobs/tests/API requests can have wrong or missing actor context. | Pass `User $actor` into services/actions; keep model events actor-free or explicit. | Services can be unit tested by passing actor user without global auth. |
| G-013 | Important side effects live in model events | `Sale::saved` creates credits and recalculates payment status; `Purchase::created` auto-processes paid purchases; `Purchase::deleting` reverses stock and deletes related records. | Hard to reason about idempotency, rollback, retries, and API command outcomes. | Move side effects into explicit transaction services for create/process/delete commands. | API command tests can assert side effects happen exactly once. |
| G-014 | Policies are incomplete | Existing policies cover Branch, Category, Customer, Item, Purchase, Supplier, Transfer, User only. | Sales, credits, warehouses, bank accounts, expenses, employees and roles lack per-record API authorization. | Add `SalePolicy`, `CreditPolicy`, `WarehousePolicy`, `BankAccountPolicy`, `ExpensePolicy`, `EmployeePolicy`, `RolePolicy`. | Every API resource route either has policy authorization or explicit permission middleware plus scoped query. |
| G-015 | Permission seed/migration mismatch | `RoleAndPermissionSeeder` omits some permissions later used by web routes; permission migrations call roles such as `WarehouseUser` and `Clerk` that may not exist. | Fresh migrations/seeds can fail or produce inconsistent access. | Keep migrations schema-only; consolidate permission creation/assignment in idempotent seeders. | Fresh database can migrate and seed without missing role exceptions. |
| G-016 | Branch/warehouse authorization has legacy assumptions | `ItemPolicy` checks `$item->stocks()->whereHas('warehouse', fn($q) => $q->where('branch_id', ...))`, but `Warehouse` uses `branch_warehouse` pivot. | Branch managers may be incorrectly denied/allowed. | Replace direct `warehouses.branch_id` assumptions with `whereHas('warehouse.branches')`. | Policy tests cover branch manager access through pivot-based warehouse assignment. |
| G-017 | Dashboard role helper excludes GeneralManager | `UserHelper::isAdminOrManager()` only checks SuperAdmin and BranchManager. | GeneralManager may not see admin filters despite `Gate::before` full access. | Update helper and dashboard service role constants to include `GeneralManager` where intended. | GeneralManager dashboard API receives filter options and authorized aggregate data. |
| G-018 | Transfer document code uses missing relation aliases | `TransferController@print` loads `transferItems` and `createdBy`, while `Transfer` exposes `items` and `creator`. | Print/export endpoints can fail. | Add aliases or update controller/resource to use existing relations. | Transfer document/export test loads a transfer without relation errors. |
| G-019 | Transfer service supports branch-only but model/migrations support more | `TransferService::validateTransferData()` rejects non-branch source/destination. | API clients may expect warehouse transfers from schema/routes. | Document branch-only API or implement warehouse/branch variants consistently. | OpenAPI and validation clearly match supported transfer modes. |
| G-020 | Stock unit fields are inconsistently updated | `Stock` has `piece_count`, `total_units`, `current_piece_units`; `StockMovementService` transfer add/remove mostly updates `quantity` and `piece_count`, not `total_units`. | Unit-based stock can drift after transfers. | Make stock movement use `Stock` methods or update all quantity/unit fields atomically. | Tests verify piece and unit totals after purchase, sale-by-unit, and transfer. |
| G-021 | Legacy controllers are not API-safe | `BankAccountController` hard-codes "Main Branch"/"Secondary Branch"; `CustomerController` and `SupplierController` redirect; `PurchasesController` print/PDF are placeholders. | Reusing them for API would create bad contracts. | Create new API controllers; leave legacy controllers web-only until removed. | API controllers contain no `view()`, `redirect()`, or flash messages. |
| G-022 | `Credit::getReferenceUrlAttribute` depends on web routes | Model builds `route('admin.purchases.show')` and `route('admin.sales.show')`. | API serialization can emit web UI links or fail if web routes change. | Move links into `CreditResource` and provide API links where needed. | Raw model no longer owns web URL formatting for API data. |
| G-023 | Returns module is incomplete for API | Web routes reference returns; `ReturnModel` references `ReturnItem`, but no `ReturnItem` model was found in current file list. | Return APIs cannot be safely exposed. | Audit return schema/models/workflows before adding endpoints. | Return feature has model, migration, service, policy, tests, and API resource or is explicitly deferred. |
| G-024 | Reports are view-first and query-heavy in controllers | `ReportsController` builds large queries then returns views. | Hard to test or expose consistent JSON. | Extract report query services and API resources/DTOs. | Report APIs return scoped JSON and are covered by role-specific tests. |

## P2 Gaps

| ID | Gap | Evidence | Impact | Required fix | Acceptance criteria |
|---|---|---|---|---|---|
| G-025 | No API Resources | No `app/Http/Resources` files found. | Raw models may leak fields and unstable relations. | Add resources per API module. | All API endpoints return resources or resource collections. |
| G-026 | No FormRequest layer | Validation is embedded in controllers/Livewire. | Validation duplication and inconsistent errors. | Add `app/Http/Requests/Api/V1/*`. | Store/update endpoints use FormRequests and return JSON `422`. |
| G-027 | No OpenAPI documentation | No OpenAPI/Scramble/L5 Swagger config found. | Frontend/client integration has no contract. | Add Scramble or L5 Swagger and document auth/errors/pagination. | `/docs/api` or generated OpenAPI JSON exists and matches routes. |
| G-028 | No idempotency for financial writes | Sale/purchase/payment creation uses normal POST semantics only. | Retry can duplicate transactions/payments. | Add idempotency key middleware/table for sale, purchase, transfer, payment endpoints. | Repeated POST with same key returns original response without duplicate rows. |
| G-029 | API CORS config is missing from file list | No `config/cors.php` in current file list. | Browser clients may fail or be too permissive if defaults are unknown. | Add explicit CORS config with environment-based allowed origins. | Preflight from allowed frontend succeeds; disallowed origin fails. |
| G-030 | Rate limiting is generic | `RouteServiceProvider` defines `api` as 60/min only. | Login and write endpoints need different limits. | Add named limiters: `login`, `api-read`, `api-write`, `exports`. | Route tests or manual checks show correct throttling headers/status. |
| G-031 | Import flow is Livewire/file-path oriented | `ItemImportService::getPreviewData(string $filePath)` and Livewire upload flow. | API uploads need multipart validation and safe temp file handling. | Add upload FormRequest and API import controller; stream/process file safely. | API can preview/apply import with validation errors in JSON. |
| G-032 | Audit logging is shallow | `AuditObserver` sets actor and attempts Spatie activity if installed; package is not in `composer.json`. | API audit trail may be incomplete. | Decide on audit package or database audit table; log actor, IP, user agent, token id. | Sensitive API writes create queryable audit records. |
| G-033 | Sensitive credentials/docs in repo | `LOGIN_CREDENTIALS.md`, `.env`, production config files are present locally. | Secret exposure risk if committed/shared. | Remove secrets from VCS, rotate known credentials, document safe local setup. | `git status`/repo history contains no live secrets; production uses env management. |
| G-034 | Error logging can include too much detail | `Handler` logs URL, stack trace, user id for every exception. | Tokens/query params may leak to logs. | Redact Authorization, cookies, password/token fields, sensitive query strings. | Error logs exclude secrets and retain correlation/request id. |

## P3 Gaps

| ID | Gap | Evidence | Impact | Required fix | Acceptance criteria |
|---|---|---|---|---|---|
| G-035 | No consistent API envelope decision | Existing JSON endpoints use `success`, `data`, `message`; Laravel resources usually use `data`. | Client integration inconsistency. | Pick a contract: Laravel resource default plus standard error object, or explicit envelope everywhere. | OpenAPI and tests assert one response style. |
| G-036 | Export endpoints mix file downloads and JSON | `StockReportController` can export CSV/JSON from web route. | API clients need predictable media types. | Use `/exports` endpoints with content negotiation or explicit format. | Export endpoints return correct `Content-Type` and auth checks. |
| G-037 | Pagination/filter conventions are not standardized | Livewire components use local search/per-page state; controllers use ad hoc params. | Clients learn different query formats per endpoint. | Adopt `filter[*]`, `sort`, `include`, `per_page`, `page`. | API docs and tests cover common filter/sort behavior. |

## Recommended Fix Order

1. Add `phpunit.xml` and make tests runnable.
2. Lock down `/stock-card` routes.
3. Normalize Laravel 12 API route registration.
4. Add API auth, active-user middleware, and JSON exception handling.
5. Fix stock reservation filtering/helper issues.
6. Standardize payment method enum values and data.
7. Add missing policies and branch/warehouse context resolver.
8. Implement first vertical slice: `auth + me + items read/write`.
9. Implement transaction slice: `purchases` with tests.
10. Implement `sales`, `transfers`, then `credits/payments`.

## Definition Of Done For The Gap Phase

The gap phase is complete when:

- Every P0 item has an owner and implementation decision.
- P0 fixes have tests or at least executable verification steps.
- The first `/api/v1` slice has a working auth flow, resources, FormRequests, policies, and feature tests.
- OpenAPI tooling is selected, even if only the first slice is documented.
- Web/Livewire routes remain isolated and do not block API clients.
