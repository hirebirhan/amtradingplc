# AGENTS.md — AM Trading PLC API

> Context document for AI agents working on bug fixes or feature implementations.
> Read this before touching any file. The README.md is outdated and describes a UI that no longer exists.

---

## 1. What This Project Is

**AM Trading PLC** is a multi-branch inventory management system for an Ethiopian trading company. It is a **pure Laravel 12 JSON API** — there are no Blade templates, no Livewire components, no NPM assets, and no web routes beyond error pages and the Swagger UI. Every feature is consumed via REST endpoints at `/api/v1/`.

The system manages: purchasing goods from suppliers → storing them across warehouses → selling them through branches → tracking all payments and credit obligations.

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12, PHP 8.2 |
| Auth | Laravel Sanctum 4 (token-based) |
| Authorization | Spatie Permission 6 (`role/permission` model) |
| API Docs | `darkaonline/l5-swagger` (OpenAPI 3, at `/api/documentation`) |
| Database | MySQL 8 |
| Containerization | Docker + docker-compose |

**No frontend assets.** `package.json` and `vite.config.js` exist but are not used. Do not add NPM dependencies or `npm run` steps to any workflow.

---

## 3. Project Layout (key paths)

```
app/
├── Console/Commands/       # Artisan commands (SystemCleanupCommand, etc.)
├── Enums/                  # PHP 8.1 backed enums (PaymentMethod, SaleStatus, etc.)
├── Http/
│   ├── Controllers/Api/V1/ # One controller per resource
│   ├── Requests/Api/V1/    # FormRequests, grouped by resource
│   │   ├── Sales/          # StoreSaleRequest, etc.
│   │   ├── Purchases/
│   │   ├── Transfers/
│   │   ├── Credits/        # ClosingOfferRequest, etc.
│   │   └── ...
│   └── Resources/Api/V1/   # API Resources for response shaping
├── Models/                 # Eloquent models
├── Policies/               # Laravel Gate policies (one per model)
├── Services/               # Business logic layer
│   ├── SaleService.php
│   ├── PurchaseService.php
│   ├── TransferService.php
│   ├── StockMovementService.php
│   ├── CreditPaymentService.php
│   ├── PurchaseValidationService.php
│   └── Dashboard/          # ChartDataService, DashboardService, StatsService, etc.
└── Support/
    └── Access/
        ├── UserAccess.php         # Query scoping by branch/warehouse
        └── PermissionCatalog.php  # Centralised permission name constants
routes/
└── api.php                 # All routes — versioned under /api/v1/
database/
├── migrations/
└── seeders/
```

---

## 4. Authorization Model

### Roles (Spatie Permission)

| Role | Typical scope |
|------|--------------|
| `SystemAdmin` | All permissions |
| `Manager` | Full commercial operations, all locations |
| `BranchManager` | Branch-scoped sales, purchases, transfers, customers |
| `WarehouseUser` | Warehouse-scoped stock and transfers |
| `Sales` | Sales creation and customer management only |

### Permission naming convention

`resource.action` — e.g., `sales.create`, `stock.adjust`, `reports.view`.
The full list lives in `app/Support/Access/PermissionCatalog.php`.

### Two-layer authorization

1. **Route/action level** — Spatie `can:` middleware or `$this->authorize('sales.create')` inside the controller. Checks whether the user's role grants the named permission.
2. **Record level** — Laravel Gate policies in `app/Policies/`. Check ownership (does this sale belong to the user's branch?). Called via `Gate::authorize('update', $sale)` or `$this->authorize('update', $sale)`.

### Query scoping — `UserAccess`

`app/Support/Access/UserAccess.php` is a static utility. Every index/list query must apply its scope:

```php
UserAccess::scopeQuery($query, $user);
// or for specific tables:
UserAccess::applySaleScope($query, $user);
UserAccess::applyPurchaseScope($query, $user);
```

A `SystemAdmin` or `Manager` sees all records. `BranchManager` sees their branch's records. `WarehouseUser` sees their warehouse's records. `Sales` sees only their own records (for sales) or their branch's.

**Never skip this scoping on index endpoints.** Leaking cross-branch data is a security issue.

---

## 5. Core Modules

### 5.1 Catalog

**Models**: `Category`, `Item`
**Controllers**: `CategoryController`, `ItemController`, `ItemImportController`
**Key fields on Item**: `sku`, `name`, `category_id`, `cost_price`, `selling_price`, `reorder_level`, `unit_type`, `units_per_piece`, `is_active`
**Imports**: Bulk import via `ItemImportService` (preview → confirm).

### 5.2 Organization

**Models**: `Branch`, `Warehouse`, `User`
- A user has either `branch_id` OR `warehouse_id`, never both.
- Branches are retail/sales points. Warehouses are storage/fulfillment points.
- Users are linked to Spatie roles via the polymorphic `model_has_roles` table.

### 5.3 Stock

**Models**: `Stock`, `StockHistory`, `StockReservation`

**`stocks` table** — current on-hand per `(warehouse_id, item_id)`:
- `quantity` — primary count. Always equals `piece_count` (legacy duplicate).
- `piece_count` — kept in sync with `quantity`.
- `total_units` — `quantity × units_per_piece`.
- `current_piece_units` — partial units from last opened piece.
- **Negative stock is allowed** (backorder by design).

**`stock_histories` table** — immutable audit log of every movement:
- `reference_type`: `purchase | sale | transfer | adjustment | purchase_deleted`
- `quantity_before`, `quantity_change`, `quantity_after`

**`stock_reservations` table** — temporary holds during transfer approval:
- Created on transfer submission, released on completion/rejection/cancellation.
- Auto-expire after 24 hours (`expires_at`).

**`StockMovementService`** is the **single entry point for all stock mutations**. Never mutate `stocks` directly in a controller or model. Call:
- `StockMovementService::addPurchaseStock($purchase)` — increments stock
- `StockMovementService::deductSaleStock($sale, $item, $saleItem)` — decrements stock
- `StockMovementService::transferStock($transfer)` — moves stock between warehouses
- `StockMovementService::adjustStock(...)` — manual adjustment

All mutations happen inside `DB::transaction()` with `lockForUpdate()`.

### 5.4 Purchases

**Model**: `Purchase`, `PurchaseItem`, `PurchasePayment`
**Controller**: `PurchaseController`
**Service**: `PurchaseService`, `PurchaseValidationService`
**FormRequests**: `app/Http/Requests/Api/V1/Purchases/`

**Lifecycle**:
1. `POST /api/v1/purchases` → `PurchaseService::createPurchase()` → creates `Purchase` (status: `confirmed`), creates `PurchaseItem` records, calls `StockMovementService::addPurchaseStock()` → status moves to `received`.
2. For credit purchases: a `Credit` (type: `payable`) is created.
3. Subsequent payments: `Purchase::addPayment()` → `PurchasePayment` row, syncs `Credit` status.

**Status enum**: `PurchaseStatus` — `pending | confirmed | received | cancelled`
**Payment status enum**: `PaymentStatus` — `due | partial | paid`
**Payment method enum**: `PaymentMethod` — `cash | bank_transfer | telebirr | full_credit | credit_advance`

**Note**: `PUT/PATCH /purchases/{id}` is **excluded** from the resource route. Purchases are immutable after creation. Delete re-reverses stock via the `Purchase::deleting` Eloquent event.

### 5.5 Sales

**Model**: `Sale`, `SaleItem`, `SalePayment`
**Controller**: `SaleController`
**Service**: `SaleService`
**FormRequests**: `app/Http/Requests/Api/V1/Sales/`

**Lifecycle**:
1. `POST /api/v1/sales` → `SaleService::createSale()` → creates `Sale` (status: `pending`), creates `SaleItem` records, calls `processStockForSale()` → `StockMovementService` deducts per item → status moves to `completed`.
2. `Sale::saved` Eloquent observer fires after every save. If `due_amount > 0` and no credit exists, calls `Sale::createCreditRecord()` which creates a `Credit` (type: `receivable`).
3. Walking customers (`is_walking_customer = true`) cannot have credits.
4. Subsequent payments: `Sale::addPayment()` → `SalePayment` row, syncs `Credit` status.

**Note**: `PUT/PATCH /sales/{id}` is **excluded**. Sales are immutable after creation.

### 5.6 Transfers

**Model**: `Transfer`, `TransferItem`
**Controller**: `TransferController`
**Service**: `TransferService`

**Status flow**: `draft → pending_approval → approved → in_transit → completed`
(also: `rejected`, `cancelled` from `pending_approval` or `approved`)

**Key rules**:
- On `submit`: `StockReservation` created per item at source warehouse (24h window).
- On `approve`: stock moves immediately via `StockMovementService::transferStock()`, reservations released.
- On `reject` or `cancel`: reservations released, stock unchanged.
- `TransferStatus` enum: `draft | pending_approval | approved | in_transit | completed | rejected | cancelled`

### 5.7 Credits

**Model**: `Credit`, `CreditPayment`
**Controller**: `CreditController`
**Service**: `CreditPaymentService`

Credits are **polymorphic**:
- `reference_type = 'sale'`, `credit_type = 'receivable'` — customer owes us
- `reference_type = 'purchase'`, `credit_type = 'payable'` — we owe supplier

**Status flow**: `active → partial → paid`

**Closing offer** feature: When ≥50% of a purchase credit is paid, the supplier can negotiate lower item prices to close the balance.
- `GET /credits/{credit}/closing-offer` — eligibility check
- `POST /credits/{credit}/closing-offer/calculate` — compute savings at given prices
- `POST /credits/{credit}/closing-offer/accept` — apply negotiated prices, close credit

### 5.8 Supporting Modules

| Module | Models | Notes |
|--------|--------|-------|
| Expenses | `Expense`, `ExpenseCategory` | Simple CRUD; no integration with P&L |
| Employees | `Employee` | Separate from `User`; no auth link |
| Bank Accounts | `BankAccount` | Company bank accounts; referenced in payments |
| Returns | `ReturnModel` | Model exists; **no routes or controller — non-functional** |
| Price History | `PriceHistory` | Tracks item cost price changes over time |

### 5.9 Dashboard & Reports

**Services**: `ChartDataService`, `DashboardService`, `StatsService`, `InventoryService`

- `GET /api/v1/dashboard` — summary metrics
- `GET /api/v1/dashboard/charts/{range}` — sales vs purchases time-series (`today | yesterday | week | month | this_month | year`)
- `GET /api/v1/reports/summary|inventory|sales|purchases|financial`

All dashboard/report data is scoped through `UserAccess` — branch/warehouse users only see their own data.

---

## 6. All API Endpoints

All routes require `auth:sanctum` except `POST /api/v1/auth/login`.
The `api.active` middleware rejects deactivated user accounts.

```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/me
PATCH  /api/v1/me/profile

GET|POST|GET|PATCH|DELETE  /api/v1/branches/{branch?}
GET|POST|GET|PATCH|DELETE  /api/v1/warehouses/{warehouse?}
GET|POST|GET|PATCH|DELETE  /api/v1/categories/{category?}

GET    /api/v1/items/search
GET|POST|GET|PATCH|DELETE  /api/v1/items/{item?}
POST   /api/v1/items/imports/preview
POST   /api/v1/items/imports
GET    /api/v1/items/import-template
GET    /api/v1/items/{item}/price-histories

GET|POST|GET|PATCH|DELETE  /api/v1/customers/{customer?}
GET|POST|GET|PATCH|DELETE  /api/v1/suppliers/{supplier?}
GET|POST|GET|PATCH|DELETE  /api/v1/users/{user?}
GET    /api/v1/users/{user}/roles
POST   /api/v1/users/{user}/roles
GET|POST|GET|PATCH|DELETE  /api/v1/roles/{role?}
GET    /api/v1/permissions
GET|POST|GET|PATCH|DELETE  /api/v1/employees/{employee?}
GET|POST|GET|PATCH|DELETE  /api/v1/bank-accounts/{bankAccount?}

# Purchases (no update route)
GET    /api/v1/purchases
POST   /api/v1/purchases
GET    /api/v1/purchases/{purchase}
DELETE /api/v1/purchases/{purchase}

# Sales (no update route)
GET    /api/v1/sales
POST   /api/v1/sales
GET    /api/v1/sales/{sale}
DELETE /api/v1/sales/{sale}

# Credits
GET    /api/v1/credits
GET    /api/v1/credits/{credit}
GET    /api/v1/credits/{credit}/payments
POST   /api/v1/credits/{credit}/payments
GET    /api/v1/credits/{credit}/closing-offer
POST   /api/v1/credits/{credit}/closing-offer/calculate
POST   /api/v1/credits/{credit}/closing-offer/accept

# Transfers (no update route)
GET    /api/v1/transfers
POST   /api/v1/transfers
GET    /api/v1/transfers/{transfer}
DELETE /api/v1/transfers/{transfer}
POST   /api/v1/transfers/{transfer}/approve
POST   /api/v1/transfers/{transfer}/reject
POST   /api/v1/transfers/{transfer}/cancel
POST   /api/v1/transfers/{transfer}/mark-in-transit
POST   /api/v1/transfers/{transfer}/complete

# Stock (read-only)
GET    /api/v1/stocks
GET    /api/v1/stocks/{stock}
GET    /api/v1/stock-histories
GET    /api/v1/stock-card

# Stock Reservations
GET    /api/v1/stock-reservations
GET    /api/v1/stock-reservations/{stockReservation}
POST   /api/v1/stock-reservations/{stockReservation}/release
PATCH  /api/v1/stock-reservations/{stockReservation}/extend
POST   /api/v1/stock-reservations/cleanup

# Stock Reports
GET    /api/v1/stock-reports
GET    /api/v1/stock-reports/export

GET|POST|GET|PATCH|DELETE  /api/v1/expenses/{expense?}
GET    /api/v1/price-histories

GET    /api/v1/dashboard
GET    /api/v1/dashboard/charts/{range}
GET    /api/v1/reports/summary
GET    /api/v1/reports/inventory
GET    /api/v1/reports/sales
GET    /api/v1/reports/purchases
GET    /api/v1/reports/financial
```

---

## 7. Database Tables (key relationships)

```
branches ──< users >── warehouses
             │
categories ──< items >── stocks >── warehouses
                          └──< stock_histories
                          └──< stock_reservations

suppliers ──< purchases >── purchase_items >── items
                │            └── warehouse_id
                └──< credits (reference_type='purchase')
                └──< purchase_payments

customers ──< sales >── sale_items >── items
               │         └── warehouse_id / branch_id
               └──< credits (reference_type='sale')
               └──< sale_payments

credits ──< credit_payments
transfers ──< transfer_items
roles ──< permissions (via model_has_permissions, role_has_permissions)
users ──< roles (via model_has_roles)

purchase_items: closing_unit_price, total_closing_cost, profit_loss_per_item
               (populated by closing offer acceptance)
```

---

## 8. Enums (app/Enums/)

All enums are PHP 8.1 backed enums. Always use `->value` to get the string:

```php
PaymentMethod::CASH->value         // 'cash'
PaymentStatus::PAID->value         // 'paid'
SaleStatus::COMPLETED->value       // 'completed'
PurchaseStatus::RECEIVED->value    // 'received'
TransferStatus::APPROVED->value    // 'approved'
CreditStatus::ACTIVE->value        // 'active'
CreditType::RECEIVABLE->value      // 'receivable'
CreditType::PAYABLE->value         // 'payable'
UserRole::SYSTEM_ADMIN->value      // 'SystemAdmin'
```

Key enums: `PaymentMethod`, `PaymentStatus`, `SaleStatus`, `PurchaseStatus`, `TransferStatus`, `CreditStatus`, `CreditType`, `UserRole`, `ItemUnit`, `UnitType`, `Status`.

---

## 9. Patterns to Follow

### Adding a new endpoint

1. Create or update a **FormRequest** in `app/Http/Requests/Api/V1/{Resource}/`.
2. Create or update the **Controller** in `app/Http/Controllers/Api/V1/`. Controllers are thin: validate → authorize → delegate to service → return resource.
3. Put business logic in a **Service** in `app/Services/`. Services receive typed model arguments, not raw request data.
4. Shape responses with an **API Resource** in `app/Http/Resources/Api/V1/`.
5. Register the route in `routes/api.php` inside the auth middleware group.
6. Add a **Policy** method if per-record authorization is needed.

### Controller template pattern

```php
public function store(StoreItemRequest $request): JsonResponse
{
    $this->authorize('items.create');           // permission check
    $item = $this->itemService->create($request->validated());
    return (new ItemResource($item))
        ->response()
        ->setStatusCode(201);
}

public function update(UpdateItemRequest $request, Item $item): JsonResponse
{
    Gate::authorize('update', $item);          // policy check (ownership)
    $item = $this->itemService->update($item, $request->validated());
    return new ItemResource($item);
}
```

### Scoping index queries

```php
public function index(Request $request): AnonymousResourceCollection
{
    $query = Sale::query();
    UserAccess::applySaleScope($query, $request->user());
    // ... filters, pagination
    return SaleResource::collection($query->paginate());
}
```

### All stock mutations go through StockMovementService

```php
// DO:
$this->stockMovementService->addPurchaseStock($purchase);

// DO NOT:
Stock::where(...)->increment('quantity', $qty);
```

### Database transactions for multi-step writes

```php
DB::transaction(function () use ($data) {
    $sale = Sale::create($data['sale']);
    foreach ($data['items'] as $item) {
        SaleItem::create([..., 'sale_id' => $sale->id]);
    }
    $this->stockMovementService->deductSaleStock($sale, ...);
});
```

### Use `lockForUpdate()` when reading stock before writing

```php
$stock = Stock::where('warehouse_id', $wh)->where('item_id', $id)
              ->lockForUpdate()
              ->firstOrFail();
```

---

## 10. Business Rules (do not violate)

1. **Sales and purchases are immutable after creation.** There are intentionally no `PUT /sales` or `PUT /purchases` routes.
2. **Walking customers cannot have credit.** `is_walking_customer = true` blocks credit creation in `Sale::createCreditRecord()`.
3. **Negative stock is allowed by design.** Do not add guards that prevent stock from going below zero.
4. **Stock mutations must always produce a `StockHistory` row.** Every call to `StockMovementService` writes audit history — never skip this.
5. **`stock.quantity` and `stock.piece_count` must stay in sync.** Both fields represent the same value. When writing directly (e.g., seed or adjustment), update both.
6. **The closing offer is only available on purchase credits (payable), not sale credits (receivable).** The controller already guards this, but don't break that guard.
7. **Transfer reservations block concurrent transfers.** When computing available stock for a transfer, subtract active unexpired reservations. `StockMovementService` does this already.
8. **Permissions are checked at two levels.** Always apply both: the action permission (`can:sales.create`) and the ownership policy (`Gate::authorize('update', $sale)`). Never remove either layer.
9. **Credit auto-creation via `Sale::saved` observer.** Do not call `Sale::createCreditRecord()` manually from a service — the observer handles it. If you add a new pathway to create sales, ensure the observer fires (it fires on `save()`, not `insert()`).
10. **`Returns` module is non-functional.** `ReturnModel` exists but has no routes or controller. Do not build on top of it without implementing the full module.

---

## 11. Common Artisan Commands

```bash
# System setup / role-permission sync
php artisan system:cleanup [--dry-run] [--force]

# Clear caches after config changes
php artisan optimize:clear

# Run migrations
php artisan migrate

# Run with seed
php artisan migrate --seed

# Clean expired stock reservations
php artisan stock:cleanup-reservations [--dry-run]

# Swagger docs regeneration
php artisan l5-swagger:generate
```

---

## 12. Environment & Docker

The project ships with a `docker-compose.yml` and `Dockerfile`. Services: `app` (PHP-FPM), `webserver` (Nginx), `db` (MySQL 8).

```bash
docker-compose up -d --build
docker-compose exec app php artisan migrate --seed
```

Default superadmin credential (seeded): `superadmin@stock360.com` / `password`

API base URL: `http://localhost:8000/api/v1/`
Swagger UI: `http://localhost:8000/api/documentation`

---

## 13. What the README.md Got Wrong

The existing `README.md` describes a UI-based system (Livewire, Bootstrap, `/admin/` routes). That UI was removed. Ignore anything in `README.md` that references:
- `/admin/` URLs
- Livewire, Blade, or Bootstrap
- `npm run dev` / `npm run build`
- `stock_movements` table (the real table is `stock_histories`)
- `GeneralManager` or `Clerk` roles (renamed to `Manager` and `Sales`)
- SuperAdmin auto-approve on transfers (not implemented in current code)
