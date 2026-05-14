# API-First Backend Architecture Analysis

Project: AM Trading PLC Laravel backend  
Reviewed state: Laravel 12, PHP 8.2, Livewire 3, Sanctum 4, Spatie Permission 6  
Current API surface: only `GET /api/user`

## Current System Summary

This codebase is currently a session-authenticated Laravel web application with a Livewire admin UI. Most business workflows are implemented inside Livewire page components and Eloquent models, with some reusable service classes for purchases, sales, transfers, stock movement, dashboard data, item import, and credit payment logic.

The domain is an inventory and trading operations system with branch/warehouse-aware stock, purchases, sales, credits, bank accounts, expenses, employees, roles, reports, and transfers. The core data model is already suitable for an API backend, but the HTTP boundary is not: web routes return Blade views, Livewire components own request/response behavior, redirects and flash messages are common, and validation is mostly embedded in components/controllers rather than FormRequests.

Important existing facts:

- `composer.json` uses `laravel/framework:^12.0`, `laravel/sanctum:^4.0`, `livewire/livewire:^3.6`, `spatie/laravel-permission:^6.17`, and `maatwebsite/excel:^3.1`.
- `routes/api.php` only defines `GET /api/user` behind `auth:sanctum`.
- `routes/web.php` owns the business surface under `/admin/*`.
- `app/Models/User.php` already uses `HasApiTokens` and `HasRoles`.
- `config/auth.php` defines only the `web` session guard; Sanctum still works through `auth:sanctum`, but no API auth flow exists.
- `bootstrap/app.php` is Laravel 12 style but does not explicitly register `routes/api.php`; `app/Providers/RouteServiceProvider.php` is old-style and defines API rate limiting/loading. This hybrid bootstrapping should be cleaned up before expanding APIs.
- `app/Exceptions/Handler.php` has partial JSON handling, but in debug mode it returns early and still supports view rendering. API exception rendering should be centralized in Laravel 12's `bootstrap/app.php`.

## Existing Modules And Features

| Module | Current implementation | Important business logic |
|---|---|---|
| Auth/profile | `LoginController`, web session routes, `Profile\Edit` Livewire | Tracks `last_login_at`; deactivated users are redirected to login. |
| Users/roles/permissions | Livewire pages, Spatie Permission, `UserPolicy` | Roles include `SuperAdmin`, `GeneralManager`, `BranchManager`, `WarehouseManager`, `WarehouseUser`, `Sales`, `Accountant`, `CustomerService`, `PurchaseOfficer`. |
| Branches/warehouses | `Branch`, `Warehouse`, `branch_warehouse` pivot, Livewire CRUD | Users can be assigned to branch/warehouse. Branch managers are restricted to their branch. |
| Categories/items | `Category`, `Item`, item import, price history, item search | Latest migrations make items/categories global; stock remains branch/warehouse isolated. Items support SKU, barcode, unit quantity, per-unit prices, images, reorder level, and import from Excel. |
| Stock | `Stock`, `StockHistory`, `StockReservation`, `StockMovementService` | Tracks `quantity`, `piece_count`, `total_units`, `current_piece_units`. Transfer reservations expire after 24 hours. |
| Purchases | `Purchases\Create` Livewire, `PurchaseService`, `Purchase` model | Auto-receives purchases, adds stock, updates item cost price, supports full credit / credit with advance / cash / bank transfer / Telebirr. |
| Sales | `Sales\Create` Livewire, `SaleFormService`, `Sale` model | Supports walking customers, branch/warehouse resolution, sale by piece/unit, below-cost warnings, credit sales, and stock deduction. Some sale paths allow negative stock/backorders. |
| Credits/payments | `Credit`, `CreditPayment`, `CreditPaymentService`, Livewire and controller payment flows | Credits are polymorphic to sale or purchase using morph map aliases `sale` and `purchase`. Payable credits can be closed early after 50 percent paid via negotiated purchase item prices. |
| Transfers | `Transfers\Create/Index/Pending/Show` Livewire, `TransferService`, `StockMovementService` | Current service enforces branch-to-branch transfers only. Branch manager can create from own branch and approve only transfers to own branch. Approval auto-completes movement. |
| Bank accounts | Livewire CRUD, old `BankAccountController` | Linked to branch or warehouse; used by purchase/sale bank transfers. |
| Expenses/settings | Livewire CRUD for expenses and expense types, positions/departments | Expenses are branch-bound and payment-method aware. |
| Reports/dashboard | `DashboardController`, `ReportsController`, `StockReportController`, `StockCardController` | Mostly view-based reporting, with a few JSON endpoints embedded under web routes. |
| Imports/exports | `ItemImportController`, `ItemImportService`, `ItemsImport`, CSV/JSON stock export | Excel import parses branch columns and creates/updates global items plus stock per branch warehouse. |

## Existing Web Routes And Controller Mapping

Current routes are web/session-first:

| Web route group | Current handler | Current output |
|---|---|---|
| `/`, `/login`, `/logout` | `LoginController`, closures | redirects/views |
| `/admin/dashboard`, `/admin/dashboard/chart-data/{range}` | `Admin\DashboardController` | Blade and JSON chart data |
| `/admin/categories/*` | closures returning Blade wrappers, `CategoryManager` Livewire | Blade/Livewire |
| `/admin/items/*` | closures, `ImportItems` Livewire, `ItemImportController@downloadTemplate` | Blade/Livewire/download |
| `/admin/warehouses/*` | `Warehouses/*` Livewire | Blade/Livewire |
| `/admin/branches/*` | closures returning Blade wrappers | Blade/Livewire |
| `/admin/users/*`, `/admin/employees/*`, `/admin/roles/*` | closures returning Blade wrappers | Blade/Livewire |
| `/admin/purchases/*` | closures, `PurchasesController@generatePdf`, `printPurchase` | Blade/redirect placeholders |
| `/admin/sales/*` | `Sales/*` Livewire, `SaleController@print` | Blade/Livewire/print view |
| `/admin/transfers/*` | `Transfers/*` Livewire, `TransferController@print` | Blade/Livewire/print view |
| `/admin/stock-reservations/*` | `Admin\StockReservationController` | mixed Blade and JSON |
| `/admin/stock-reports/*` | `Admin\StockReportController` | mixed Blade, JSON, CSV/JSON download |
| `/admin/returns/*` | closures returning Blade wrappers | Blade only; no strong backend workflow found |
| `/admin/customers/*`, `/admin/suppliers/*` | closures returning Blade wrappers | Blade/Livewire; old controllers also exist |
| `/admin/credits/*` | `Credits/*`, `CreditPayment/*` Livewire, `CreditPaymentController@store` | Blade/Livewire/redirect |
| `/admin/price-history/*` | `PriceHistoryController` | Blade |
| `/admin/bank-accounts/*`, `/admin/expenses/*`, `/admin/settings/*` | Livewire | Blade/Livewire |
| `/admin/reports/*` | `ReportsController` | Blade |
| `/stock-card`, `/stock-card/print` | `StockCardController` | Blade; currently outside authenticated admin group |
| `/api/user` | closure | JSON authenticated user |

## Proposed API Endpoints

Use `/api/v1` and JSON only. Keep Blade and Livewire isolated under `routes/web.php` until retired.

### Auth And Profile

| Method | URI | Permission |
|---|---|---|
| POST | `/api/v1/auth/login` | public, throttle `login` |
| POST | `/api/v1/auth/logout` | `auth:sanctum` |
| GET | `/api/v1/me` | `auth:sanctum` |
| PATCH | `/api/v1/me/profile` | `auth:sanctum` |

### Admin Reference Data

| Method | URI | Permission |
|---|---|---|
| GET | `/api/v1/branches` | `branches.view` |
| POST | `/api/v1/branches` | `branches.create` |
| GET/PATCH/DELETE | `/api/v1/branches/{branch}` | policy |
| GET | `/api/v1/warehouses` | `warehouses.view` |
| POST | `/api/v1/warehouses` | `warehouses.create` |
| GET/PATCH/DELETE | `/api/v1/warehouses/{warehouse}` | policy |
| GET | `/api/v1/categories` | `categories.view` |
| POST | `/api/v1/categories` | `categories.create` |
| GET/PATCH/DELETE | `/api/v1/categories/{category}` | policy |
| GET | `/api/v1/items` | `items.view` |
| POST | `/api/v1/items` | `items.create` |
| GET/PATCH/DELETE | `/api/v1/items/{item}` | `ItemPolicy` |
| GET | `/api/v1/items/search` | `items.view` |
| POST | `/api/v1/items/imports/preview` | `items.create` |
| POST | `/api/v1/items/imports` | `items.create` |
| GET | `/api/v1/items/import-template` | `items.create` |
| GET | `/api/v1/price-histories` | `items.view` or `reports.view` |
| GET | `/api/v1/items/{item}/price-histories` | `items.view` |

### Parties And People

| Method | URI | Permission |
|---|---|---|
| apiResource | `/api/v1/customers` | `customers.*`, `CustomerPolicy` |
| apiResource | `/api/v1/suppliers` | `suppliers.*`, `SupplierPolicy` |
| apiResource | `/api/v1/users` | `users.*`, `UserPolicy` |
| GET/POST/PATCH/DELETE | `/api/v1/users/{user}/roles` | `roles.view` / `roles.edit` |
| apiResource | `/api/v1/employees` | `employees.*` |
| apiResource | `/api/v1/roles` | `roles.*` |
| GET | `/api/v1/permissions` | `roles.view` |

### Inventory And Stock

| Method | URI | Permission |
|---|---|---|
| GET | `/api/v1/stocks` | `stock.view` |
| GET | `/api/v1/stocks/{stock}` | `stock.view` |
| POST | `/api/v1/stocks/adjustments` | `stock.adjust` |
| GET | `/api/v1/stock-histories` | `stock.history` |
| GET | `/api/v1/stock-cards` | `stock-card.view` |
| GET | `/api/v1/stock-reservations` | `transfers.view` |
| GET | `/api/v1/stock-reservations/{reservation}` | `transfers.view` |
| POST | `/api/v1/stock-reservations/cleanup` | `transfers.edit` |
| POST | `/api/v1/stock-reservations/{reservation}/release` | `transfers.edit` |
| PATCH | `/api/v1/stock-reservations/{reservation}/extend` | `transfers.edit` |
| GET | `/api/v1/items/{item}/stock-reservations` | `transfers.view` |

### Transactions

| Method | URI | Permission |
|---|---|---|
| GET/POST | `/api/v1/purchases` | `purchases.view` / `purchases.create` |
| GET/PATCH/DELETE | `/api/v1/purchases/{purchase}` | `PurchasePolicy` |
| POST | `/api/v1/purchases/{purchase}/receive` | `purchases.receive` |
| POST | `/api/v1/purchases/{purchase}/payments` | `payments.create` |
| GET | `/api/v1/purchases/{purchase}/payments` | `payments.view` |
| GET/POST | `/api/v1/sales` | `sales.view` / `sales.create` |
| GET/PATCH/DELETE | `/api/v1/sales/{sale}` | `SalePolicy` needed |
| POST | `/api/v1/sales/{sale}/payments` | `payments.create` |
| GET | `/api/v1/sales/{sale}/payments` | `payments.view` |
| GET/POST | `/api/v1/transfers` | `transfers.view` / `transfers.create` |
| GET/PATCH/DELETE | `/api/v1/transfers/{transfer}` | `TransferPolicy` |
| POST | `/api/v1/transfers/{transfer}/approve` | `transfers.approve`, destination policy |
| POST | `/api/v1/transfers/{transfer}/reject` | `transfers.approve`, destination policy |
| POST | `/api/v1/transfers/{transfer}/cancel` | `TransferPolicy@delete` or `transfers.delete` |
| POST | `/api/v1/transfers/{transfer}/mark-in-transit` | `transfers.send` |
| POST | `/api/v1/transfers/{transfer}/complete` | `transfers.receive` |
| GET/POST | `/api/v1/credits` | `credits.view` / `credits.create` |
| GET/PATCH/DELETE | `/api/v1/credits/{credit}` | `CreditPolicy` needed |
| GET/POST | `/api/v1/credits/{credit}/payments` | `credits.view` / `credits.edit` |
| GET | `/api/v1/credits/{credit}/closing-offer` | `credits.view` |
| POST | `/api/v1/credits/{credit}/closing-offer/calculate` | `credits.edit` |
| POST | `/api/v1/credits/{credit}/closing-offer/accept` | `credits.edit` |
| GET/POST/PATCH/DELETE | `/api/v1/expenses` | `expenses.*` |
| GET/POST/PATCH/DELETE | `/api/v1/bank-accounts` | `bank-accounts.*` |

### Reporting

| Method | URI | Permission |
|---|---|---|
| GET | `/api/v1/dashboard` | authenticated |
| GET | `/api/v1/dashboard/charts/{range}` | authenticated |
| GET | `/api/v1/reports/summary` | `reports.view` |
| GET | `/api/v1/reports/inventory` | `reports.view` |
| GET | `/api/v1/reports/sales` | `reports.view` and revenue role |
| GET | `/api/v1/reports/purchases` | `reports.view`, `purchases.view` |
| GET | `/api/v1/reports/financial` | `reports.view` plus finance role |
| GET | `/api/v1/reports/activity` | `reports.view` |
| GET | `/api/v1/stock-reports` | `items.view` |
| GET | `/api/v1/stock-reports/export` | `reports.export` or `items.view` |

## Recommended Laravel API Architecture

Use a parallel API layer first, then retire Livewire pages module by module. Do not move business logic back into controllers. Reuse and harden the existing services:

- `PurchaseService` remains the purchase command service, but should stop reading `Auth` internally. Pass the actor user explicitly.
- `SaleFormService` should become `SaleService` or `CreateSaleAction`; keep sale creation and `Sale::processSale()` inside a transaction.
- `TransferService` is the right API boundary for transfer workflow actions.
- `StockMovementService` is the right place for lock-based stock movement and reservation cleanup.
- `CreditPaymentService` is the right place for closing offer calculations and early closure.
- Move validation from Livewire components into `app/Http/Requests/Api/V1/*`.
- Move JSON shape into `app/Http/Resources/Api/V1/*`.
- Use policies for per-record access and Spatie permissions for coarse route authorization.
- Use explicit API pagination/filter conventions: `?filter[branch_id]=`, `?filter[warehouse_id]=`, `?filter[status]=`, `?sort=-created_at`, `?per_page=`.
- Prefer Sanctum personal access tokens for non-browser API clients. Use Sanctum SPA cookie mode only if the frontend is first-party and browser-based.

## API Folder Structure

```text
app/
  Actions/
    Sales/CreateSale.php
    Purchases/CreatePurchase.php
    Transfers/ProcessTransferWorkflow.php
  Http/
    Controllers/Api/V1/
      AuthController.php
      DashboardController.php
      ItemController.php
      PurchaseController.php
      SaleController.php
      TransferController.php
      CreditController.php
      CreditPaymentController.php
      StockReservationController.php
      ReportController.php
    Middleware/
      EnsureApiUserIsActive.php
      ResolveApiLocationContext.php
    Requests/Api/V1/
      Auth/LoginRequest.php
      Items/StoreItemRequest.php
      Purchases/StorePurchaseRequest.php
      Sales/StoreSaleRequest.php
      Transfers/StoreTransferRequest.php
      Credits/StoreCreditPaymentRequest.php
    Resources/Api/V1/
      UserResource.php
      ItemResource.php
      StockResource.php
      PurchaseResource.php
      SaleResource.php
      TransferResource.php
      CreditResource.php
      DashboardResource.php
  Policies/
    SalePolicy.php
    CreditPolicy.php
    WarehousePolicy.php
tests/
  Feature/Api/V1/
    AuthTest.php
    PurchaseApiTest.php
    SaleApiTest.php
    TransferApiTest.php
    CreditPaymentApiTest.php
```

## Gap Analysis

| Gap | Priority | Evidence | Recommendation |
|---|---:|---|---|
| No real API layer | P0 | `routes/api.php` only has `/user` | Build `/api/v1` route groups with controllers, resources, FormRequests. |
| API bootstrapping is hybrid | P0 | Laravel 12 `bootstrap/app.php` omits `api`, old `RouteServiceProvider` exists but is not in `bootstrap/providers.php` | Register API routes and rate limiters in `bootstrap/app.php`; remove duplicate/old route loading after confirmation. |
| Livewire owns business workflows | P0 | Purchase/sale/transfer creation happens in Livewire components | Extract request validation to FormRequests and keep commands in services/actions. |
| Redirect/session responses in auth/middleware | P0 | `LoginController`, `EnsureUserIsActive`, `EnforceBranchAuthorization` return redirects/abort web messages | Add API auth controller and API-safe middleware returning JSON 401/403. |
| Stateless branch/warehouse context missing | P0 | `UserContextService` depends on Session | For APIs, resolve context from user assignment plus optional `X-Branch-Id`, `X-Warehouse-Id`, validated by policy. |
| Stock-card routes are unauthenticated | P0 | `/stock-card` routes are outside `/admin` auth group | Move to authenticated API/web route and require `stock-card.view`. |
| Stock reservation controllers have schema mismatches | P0 | Controller filters `warehouse_id`, but table uses `location_type`/`location_id`; calls missing `UserHelper::getAccessibleWarehouseIds()` | Fix before exposing API; use `forLocation()` and add helper/service method. |
| Transfer print relation names are wrong | P1 | `TransferController@print` loads `transferItems` and `createdBy`; model exposes `items` and `creator` | Fix relation names or add aliases before creating document APIs. |
| Payment method naming is inconsistent | P1 | `PaymentMethod::FULL_CREDIT` value is `full_credit`; sale code uses `credit_full` | Standardize enum values and migrate stored data if needed. |
| Policies are incomplete | P1 | No `SalePolicy`, `CreditPolicy`, `WarehousePolicy`, `ExpensePolicy`, `BankAccountPolicy` | Add policies before writing write APIs. |
| Controllers contain stale/placeholder code | P1 | `BankAccountController` hard-codes location names; `PurchasesController` PDF/print are placeholders | Do not reuse old web controllers for API. Create clean API controllers. |
| Model events perform important side effects | P1 | `Purchase::created`, `Sale::saved`, `Purchase::deleting`, `Credit::creating` mutate related records | Move side effects into explicit actions/services for API idempotency and testability. |
| Migrations mutate permissions using app models | P2 | Permission migrations call `Role::findByName('WarehouseUser')`, `Clerk` without checks | Move role assignment to seeders; keep migrations schema-only. |
| API serialization missing | P2 | Eloquent models would leak attributes/relations by default | Add API Resources with stable shape and conditional relationships. |
| OpenAPI missing | P2 | No docs generator/config found | Add `dedoc/scramble` or `l5-swagger`, publish `/docs/api`. |
| No feature tests | P0 | `tests/` has only two unit files | Add authenticated API tests per module and permission matrix tests. |

## Security Gaps

1. `GET /stock-card` and `GET /stock-card/print` are not behind `auth` or permission middleware.
2. `EnsureUserIsActive` logs out and redirects; API clients need `403` JSON without session invalidation.
3. `EnforceBranchAuthorization` checks form input and web route names; API authorization should be in policies and query scopes.
4. API CORS is not explicitly configured in `config/cors.php`; add a strict allowlist for frontend origins.
5. `SuperAdminSeeder` seeds `superadmin@amtradingplc.com` with password `password`; never run that in production.
6. `LOGIN_CREDENTIALS.md` exists in repo root; credentials should not live in source control.
7. `Handler` logs full stack trace and URL for all reportable exceptions; scrub tokens, passwords, transaction references, and uploaded file names.
8. `CreditPaymentController` accepts `cash,bank_transfer,check,mobile_money`, while current enum/services use `telebirr` and not `mobile_money`; align validation to prevent unexpected states.
9. Several flows use `auth()->id()` inside services/models. API jobs/tests/background commands can create records with missing or wrong actor context.
10. Use database locks and unique transaction number validation across sales, purchases, sale payments, and credit payments to avoid duplicate bank/Telebirr references under concurrency.

## Testing Gaps

Current `php artisan test` does not run because `phpunit.xml.dist` is missing. Existing tests are:

- `tests/Unit/CreditManagementTest.php`
- `tests/Unit/PurchaseStatusWorkflowTest.php`

Required API testing roadmap:

| Test area | Priority | Coverage |
|---|---:|---|
| Test bootstrap | P0 | Add `phpunit.xml`, test database config, factories for all active models. |
| Auth | P0 | login success/failure, inactive user, logout token revoke, `/me`. |
| Permissions | P0 | SuperAdmin, GeneralManager, BranchManager, WarehouseManager, Sales, Accountant access matrix. |
| Branch/warehouse isolation | P0 | Branch manager cannot read/write other branch sales, purchases, transfers, stock, customers. |
| Purchases API | P0 | full credit, credit advance, cash/bank/Telebirr, stock increase, item cost update, duplicate transaction number. |
| Sales API | P0 | walking customer, credit_full/full_credit naming, stock deduction by piece/unit, below-cost policy, backorder rule. |
| Transfers API | P0 | reserve stock, reject/cancel release reservation, approve auto-complete, destination approval rule, concurrent stock changes. |
| Credits API | P1 | payment sync to sale/purchase, payable closing offer, negotiated price persistence. |
| Reports API | P1 | role-filtered financial visibility and branch/warehouse filtering. |
| Error contract | P1 | validation `422`, auth `401`, authorization `403`, not found `404`, conflict `409`, server `500`. |

## Migration Plan

### Phase 0: Stabilize The Base

- Add `phpunit.xml` and make current tests runnable.
- Fix public `/stock-card` auth.
- Fix stock reservation schema/controller mismatch.
- Fix transfer relation aliases used by print/document flows.
- Standardize payment method naming, especially `full_credit` vs `credit_full`.
- Add missing policies for sale, credit, warehouse, bank account, expense, employee, role.

### Phase 1: API Foundation

- Register `/api/v1` routes explicitly in `bootstrap/app.php`.
- Add `AuthController` for Sanctum tokens.
- Add `EnsureApiUserIsActive`, API exception rendering, rate limiters, and CORS.
- Add base API response/error conventions and resources for `User`, `Branch`, `Warehouse`, `Category`, `Item`, `Stock`.
- Add OpenAPI generation.

### Phase 2: Read APIs

- Ship read-only endpoints for reference data, dashboard, stock, reports, purchases, sales, transfers, credits.
- Add filters, pagination, sorting, and role-aware query scopes.
- Keep web routes unchanged while frontend migrates.

### Phase 3: Write APIs For Master Data

- Implement CRUD APIs for categories, items, customers, suppliers, branches, warehouses, bank accounts, employees, roles.
- Move Livewire validation rules into FormRequests.
- Add API Resources and policy tests.

### Phase 4: Transaction APIs

- Implement purchase creation through `PurchaseService`.
- Implement sale creation through a renamed API-safe `SaleService`.
- Implement transfer creation and workflow actions through `TransferService`.
- Implement credit payments and closing offer APIs through `CreditPaymentService`.
- Add idempotency keys for sale, purchase, transfer, and payment create endpoints.

### Phase 5: Retire Or Isolate Web UI

- Move Blade/Livewire-only routes behind `web.php`.
- Remove view dependencies from services/controllers used by API.
- Keep document generation as separate export endpoints returning files, not views.

## Code Examples

### API Routes

```php
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('items', ItemController::class);
        Route::apiResource('purchases', PurchaseController::class);
        Route::apiResource('sales', SaleController::class);
        Route::apiResource('transfers', TransferController::class);

        Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve'])
            ->middleware('permission:transfers.approve');
    });
});
```

### Laravel 12 API Bootstrap

```php
// bootstrap/app.php
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn ($request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
```

### Auth Controller

```php
final class AuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (!$user || !Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if (!$user->is_active) {
            abort(403, 'Account is inactive.');
        }

        $user->updateLastLogin();

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $user->createToken('api')->plainTextToken,
            'user' => new UserResource($user->load('roles', 'branch', 'warehouse')),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles', 'branch', 'warehouse'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
```

### FormRequest For Sales

```php
final class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.create') === true;
    }

    public function rules(): array
    {
        return [
            'sale_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id', 'required_without:is_walking_customer'],
            'is_walking_customer' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'telebirr', 'credit_advance', 'full_credit'])],
            'transaction_number' => ['required_if:payment_method,bank_transfer,telebirr', 'nullable', 'string', 'min:5', 'max:255'],
            'bank_account_id' => ['required_if:payment_method,bank_transfer', 'nullable', 'exists:bank_accounts,id'],
            'advance_amount' => ['required_if:payment_method,credit_advance', 'nullable', 'numeric', 'min:0.01'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.sale_method' => ['nullable', Rule::in(['piece', 'unit'])],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
```

### API Resource

```php
final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'sale_date' => optional($this->sale_date)->toDateString(),
            'amounts' => [
                'total' => (float) $this->total_amount,
                'paid' => (float) $this->paid_amount,
                'due' => (float) $this->due_amount,
                'tax' => (float) $this->tax,
                'shipping' => (float) $this->shipping,
            ],
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'credit' => new CreditResource($this->whenLoaded('credit')),
        ];
    }
}
```

### API Controller Using Existing Service

```php
final class PurchaseController extends Controller
{
    public function store(StorePurchaseRequest $request, PurchaseService $service): PurchaseResource
    {
        $data = $request->validated();

        $purchase = $service->createPurchase(
            formData: $data,
            items: $data['items'],
            totalAmount: collect($data['items'])->sum('subtotal'),
            taxAmount: (float) ($data['tax_amount'] ?? 0),
        );

        return new PurchaseResource($purchase->load(['supplier', 'branch', 'warehouse', 'items.item', 'credit']));
    }
}
```

Before implementing this exact controller, refactor `PurchaseService` to accept `User $actor` instead of using `Auth::user()` internally.

### Active User Middleware

```php
final class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            return response()->json([
                'message' => 'Account is inactive.',
            ], 403);
        }

        return $next($request);
    }
}
```

### Feature Test Example

```php
public function test_branch_manager_cannot_create_purchase_for_other_branch(): void
{
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $ownBranch->id]);
    $user->assignRole('BranchManager');
    $user->givePermissionTo('purchases.create');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/purchases', [
        'supplier_id' => Supplier::factory()->create()->id,
        'branch_id' => $otherBranch->id,
        'purchase_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'items' => [
            ['item_id' => Item::factory()->create()->id, 'quantity' => 1, 'unit_cost' => 100],
        ],
    ]);

    $response->assertForbidden();
}
```

## Code-Level Recommendations

- Rename or wrap `SaleFormService` as an API-safe `SaleService`; it should accept the actor and validated DTO/array, not depend on Livewire state.
- Refactor `PurchaseService::createPurchase()` to accept `User $actor` and use the actor for branch resolution, created_by, and authorization.
- Remove side-effect-heavy model events where possible. `Sale::saved` and `Purchase::created` currently create/update credits and stock implicitly. APIs need explicit, testable transaction boundaries.
- Add `SalePolicy`, `CreditPolicy`, `WarehousePolicy`, `BankAccountPolicy`, `ExpensePolicy`, and `EmployeePolicy`.
- Replace direct `abort()` and redirects in reusable flows with domain exceptions (`DomainException`, `TransferException`) that the API layer maps to JSON.
- Fix `UserHelper::setWarehosuseId` typo and either add `getAccessibleWarehouseIds()` or remove callers.
- Fix stock reservation access filtering to use `location_type` and `location_id`, not `warehouse_id`.
- Create API Resources before exposing models to prevent leaking `deleted_by`, `created_by`, internal balances, or password-adjacent fields.
- Add idempotency support for payment, sale, purchase, and transfer creation to avoid duplicate financial transactions.
- Add OpenAPI docs and require every new API controller/request/resource to be represented there.
