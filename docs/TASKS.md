## Engineering Backlog — AM Trading PLC

This backlog captures high-impact improvements and likely issues to address across the codebase. It’s organized by priority and area. Use it to open focused PRs; keep changes small and well-tested.

Notes
- Stack: Laravel 12 (PHP 8.2), Livewire v3, Vite 6, MySQL 8.
- Follow docs/DEVELOPMENT_RULES.md and .github/instructions/development-guide-line.instructions.md.
- Prefer docker compose for all commands.

### P0 — Critical integrity and correctness

- [ ] Wrap stock/money flows in DB transactions
  - Areas: Sales, Purchases, Transfers, Credit Payments, StockMovementService, TransferService
  - Include row-level locks (e.g., SELECT … FOR UPDATE) on stock/stock_reservation rows
  - Acceptance: concurrent operations don’t produce negative stock or double reservations

- [ ] Enforce DB constraints and uniqueness
  - Add FK constraints and cascades where missing
  - Unique indexes: users(email), items(sku), categories(name), warehouses(code)
  - Composite unique: stock(item_id, warehouse_id)
  - Acceptance: migrations are reversible; pre-migration script ensures no duplicates

- [ ] Standardize money and quantity handling
  - Use DECIMAL for all monetary fields (e.g., DECIMAL(12,2)); avoid floats
  - Centralize rounding in a Money helper; align with docs/CLOSING_PRICE_LOGIC.md
  - Acceptance: unit tests confirm totals/rounding across sales/purchases/credits

- [ ] Validate and authorize consistently
  - Controllers: Form Requests; Livewire: typed rules + validate()
  - Ensure Policies cover all critical actions; align with spatie permissions
  - Acceptance: feature tests for forbidden/allowed paths per role

### P1 — Performance, scalability, and developer experience

- [ ] Eliminate N+1 queries in Livewire components
  - Eager-load relations, use withCount, paginate, and debounce searches
  - Acceptance: key pages show stable query counts in Telescope/debug tooling

- [ ] Index hot query paths
  - Examples: stock(item_id, warehouse_id), sales by date range, price_histories(item_id, date)
  - Acceptance: indexes present; slow queries < threshold in local profiling

- [ ] Queue heavy jobs (imports/exports/reports)
  - Use chunked processing; add retries/backoff; progress logs
  - Wire supervisor config and document startup
  - Acceptance: 100k-row import completes without OOM; produces error report for bad rows

- [ ] Enhance auditing and observability
  - Extend AuditObserver to capture before/after diffs, actor, and context
  - Add structured logs with request ID; consider enabling Telescope for local
  - Acceptance: audit records reproducibly explain stock/price changes

- [ ] Test coverage for core business rules
  - Unit: closing price calculations, stock movement invariants
  - Feature: sale/purchase/transfer flows, credit payment transitions
  - Livewire: critical components (happy path + 1–2 edge cases)
  - Acceptance: green suite; tests guard against regressions

### P2 — Correctness hardening and maintainability

- [ ] Concurrency-safe reservations
  - Ensure creation/use/release paths honor StockReservation; clean up stale rows
  - Add a scheduled command or confirm existing one is idempotent

- [ ] Enum casts for statuses and methods
  - Cast PaymentStatus, PaymentMethod, Status, etc., on relevant models

- [ ] Relationship return types and model casts
  - Add explicit return types on relationships; add casts for dates/json/enums

- [ ] Domain services and events
  - Keep controllers/components thin; emit domain events (StockAdjusted, TransferCompleted)
  - Handle side-effects in listeners; improve testability

- [ ] Documentation upgrades
  - READMEs per domain (Inventory, Sales, Transfers): invariants, edge cases, workflows
  - Document all Artisan commands with examples, dry-run flags, and risks

- [ ] Timezone consistency
  - Persist timestamps in UTC; present as Africa/Addis_Ababa
  - Verify model/date casts and Carbon usage are consistent

### Quick wins

- [ ] Add unique index: stock(item_id, warehouse_id)
- [ ] Transaction + row lock in TransferService and StockMovementService
- [ ] Eager-load fixes in 2–3 heaviest Livewire components
- [ ] Add 4–6 focused tests: negative stock prevention, closing price calc, credit payment transitions
- [ ] Money helper + DECIMAL audit on monetary columns

### How to work this backlog

1. Pick one item; open a small PR with concise description and acceptance checks.
2. Add or update tests first; keep changes minimal and localized.
3. Use docker compose for running tests and tools.
4. Update docs where behavior or commands change.

### References

- docs/CLOSING_PRICE_LOGIC.md — business rules for price/closing
- docs/NOTIFICATION_STANDARDS.md — user/system notifications
- docs/DEVELOPMENT_RULES.md — repo standards
- .github/instructions/development-guide-line.instructions.md — environment and coding guidance
