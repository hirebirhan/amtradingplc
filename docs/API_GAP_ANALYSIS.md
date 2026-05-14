# API-First Gap Analysis

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
