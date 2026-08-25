# memory.md

## Stable Decisions
- The project is for Ethiopian Defence University.
- There are two vehicle categories: `defence_plated` and `contracted_private`.
- Only defence-plated vehicles may use university fuel.
- Only defence-plated vehicles may use university garage services.
- Contracted private vehicles are managed through owner, contract, route, and payment workflows.
- The system includes transport users and passengers, not only vehicles and drivers.
- The platform is a full-stack website with role-based dashboards.
- React + Laravel + PostgreSQL is the chosen default stack.

## UI Decisions
- The web app uses a sidebar + topbar layout.
- Each role gets a tailored dashboard.
- Most modules follow List / Form / Detail / Report patterns.
- Detail pages use summary cards and tabs.
- Passenger and driver views should be more mobile-friendly than admin-heavy views.

## Technical Decisions
- Frontend language: TypeScript (for type safety across 12 modules and multi-agent code generation).
- Frontend architecture: Separate React SPA with REST API (not Inertia.js).
- Frontend build tool: Vite.
- Server state management: TanStack React Query.
- Client state management: Zustand.
- Form handling: React Hook Form + Zod validation.
- Tables: TanStack Table.
- HTTP client: Axios with auth interceptors.
- Icons: Lucide React.
- Dates: Day.js with dual-date support (Gregorian stored canonical + Ethiopic display).
- Routing: React Router v7.
- Backend testing: PHPUnit + Pest.
- Frontend testing: Vitest + Testing Library.
- Code quality: ESLint + Prettier (frontend), Pint (backend).
- API documentation: Scramble (auto-generates OpenAPI from Laravel).
- Queue: Redis + Laravel Horizon.
- File storage: Laravel filesystem with encryption at rest for compliance documents.
- Audit logs: Append-only / immutable.
- Deployment target: On-premises first, Docker/CI adaptable to government cloud.

## Important Constraints
- Fuel module must block contracted vehicles.
- Garage module must block contracted vehicles.
- Compliance checks must happen before assignment.
- RBAC must be combined with backend policy checks.
- Uploaded compliance documents must support approval and expiry states.
- Compliance documents must be encrypted at rest.
- Audit logs must be immutable (append-only, no update/delete).
- Dates must be stored in Gregorian format, displayed in both Gregorian and Ethiopic.

## Module Status
| Module | Backend | Frontend | Tests | Status |
|--------|---------|----------|-------|--------|
| Auth/RBAC | ✅ | ✅ | ✅ | Done |
| Fleet (Vehicles, Drivers, Owners) | ✅ | ✅ | ✅ | Done |
| Passenger | ✅ | ✅ | ✅ | Done |
| Route | ✅ | ✅ | ✅ | Done |
| Trip | ✅ | ✅ | ✅ | Done |
| Fuel | ✅ | ✅ | ✅ | Done |
| Garage | ✅ | ✅ | ✅ | Done |
| Compliance | ✅ | ✅ | ✅ | Done |
| Contract | ✅ | ✅ | ✅ | Done |
| Reports | ✅ | ✅ | ✅ | Done |
| Notifications | ✅ | ✅ | ✅ | Done |

## Naming Conventions
- Database tables: snake_case plural (e.g., `fuel_transactions`)
- API endpoints: `/api/v1/{resource}` RESTful
- React components: PascalCase (e.g., `VehicleList.tsx`)
- React hooks: camelCase with `use` prefix (e.g., `useVehicles.ts`)
- Laravel models: PascalCase singular (e.g., `Vehicle.php`)
- Laravel services: `{Domain}Service.php` (e.g., `FuelService.php`)
- Laravel policies: `{Model}Policy.php` (e.g., `VehiclePolicy.php`)
- Laravel requests: `{Action}{Model}Request.php` (e.g., `StoreVehicleRequest.php`)
- Migrations: Laravel default timestamp prefix
- TypeScript types: PascalCase in `types/` directories (e.g., `Vehicle.ts`)
- Frontend feature folders: snake_case or kebab-case PLURAL noun matching the DB table name (e.g., `vehicles/`, `drivers/`, `owners/`, `passengers/`). NEVER create a singular-named feature folder. The only exception is `auth/`, which has no plural form. Before creating a new feature folder for Route, Trip, Fuel, Garage, Compliance, Contract, Reports, or Notification, check whether an empty stub already exists under a different name and reuse/rename it rather than creating a duplicate.

## Future Skills To Add Or Expand
- ~~transport-routing~~ (ADDED 2026-08-20 — see `skills/transport-routing/`)
- notifications-workflow
- payment-ledger
- mobile-driver-passenger-ui
- compliance-workflow
- audit-logging
- testing-strategy
- deployment-ops
- api-contract
- project-scaffold

### Phase 3.3 Hardening Pass — Batch 1 (date: today)
- **C1 (audit log immutability)** — NOT CONFIRMED as a bug: `audit_logs` has no update/delete paths anywhere in the codebase; append-only discipline verified. No code change.
- **H6 (VehicleStatus consistency)** — NOT CONFIRMED: `VehicleStatus` cast matches migration `status` enum; factory/validation/Seeder consistent. No code change.
- **H7 (FuelType benzene vs diesel)** — NOT CONFIRMED: `FuelType` values are `diesel/petrol/electric/hybrid`; migration/`IssueFuelRequest` use diesel/petrol; stale memory.md "benzene/synthetic" doc line corrected.
- **H8 (mass-assignment/contract create)** — NOT CONFIRMED: no writable `contract_number`/`status` in `StoreContractRequest`; fillable list has no gap.
- **M10 (CRUD audit log parity)** — CONFIRMED present for Vehicle/Driver/Owner/Route/Passenger/Trip via Auditable trait (C2 + ProductionHardening tests). `FuelTransaction`/`TripAssignment` create-only by design.
- Verified: backend 259 tests pass (566 assertions), Pint 204 files PASS.

### Phase 3.3 Hardening Pass — Batch 2 (date: today)
- **H1 (Trip create validation gap)** — CONFIRMED, FIXED: `update()` used lax `$skip` flag; now resolves effective vehicle/driver/route and runs full create-order chain.
- **H2 (Trip start revalidation)** — CONFIRMED, FIXED: `start()` re-runs full eligibility chain after status check.
- **H3 (contractor compliance per trip)** — CONFIRMED, FIXED: requires approved, non-null, future-expiry docs: vehicle `vehicle_registration` + `insurance`, driver `driver_license`.
- **M3 (update parity)** — FIXED via H1; `GarageService::update` runs create-level eligibility when vehicle changes.
- **M4 (Trip start parity)** — FIXED via H2; `GarageService::start` revalidates.
- **M8 (route capacity)** — CONFIRMED, FIXED: `validateCapacity` now also enforces `routes.capacity`.
- Tests: TripTest +9, ProductionHardeningTest +4, GarageTest +5. **277 backend tests pass (585 assertions); Pint 204 files PASS.**

### Phase 3.3 Hardening Pass — Batch 3 (date: today)
- **C2 (decimal serialization)** — CONFIRMED, FIXED: `decimal:2` casts → JSON strings. Created shared `App\Domain\Shared\Casts\DecimalNumber` (get→float, set→`number_format` fixed-string, null-safe). Applied to MEASURE fields: `FuelStock.current_quantity/minimum_quantity`, `FuelTransaction.quantity`, `Route.distance_km` → API returns real numbers. MONEY stays `decimal:2` strings for precision (`MaintenanceRecord.cost`, `Contract.contract_value`, `unit_cost/total_cost`); converted at UI boundary via new `toNumber()` in `formatters.ts` (GarageList/GarageDetail). Garage `cost` type widened to `number | string | null`.
- **C3 (low-stock string compare)** — CONFIRMED, FIXED via C2 (numeric `<`); FuelStock frontend tests added.
- **H4 (expiry reg/insurance)** — NOT CONFIRMED: FuelService already blocks expired reg/insurance (NULL expiry = valid per user decision); Garage covered by Batch 2. Regression tests only.
- **M6 (fuel-type compatibility)** — CONFIRMED, FIXED (EXACT match per user): `issueFuel` rejects `vehicle.fuel_type !== requested` (rule `fuel_type_incompatible`). petrol→diesel, diesel→electric, petrol→hybrid blocked.
- **M9-A (owner/vehicle)** — CONFIRMED, FIXED: `vehicle.owner_id === contract.owner_id` on create/update (rule `contract_owner_vehicle_mismatch`); ContractFactory syncs owner in `afterCreating`.
- **M9-B (overlapping contracts)** — CONFIRMED, FIXED: inclusive overlap guard on create/update/activate (same-day adjacency = overlap; excludes self; ignores soft-deleted) (rule `contract_overlap_active`).
- **M9-C (contract number race)** — CONFIRMED risk, FIXED: `pg_advisory_xact_lock(crc32('contract-number:'.year))` inside the transaction before `max(id)+1`; DB unique kept as backstop. True-parallel test unreliable → deterministic regression tests + documented limitation.
- **TOCTOU (fuel stock)** — CONFIRMED risk, FIXED: `lockForUpdate()` on FuelStock row in `issueFuel`/`adjustStock`; `restock` keeps atomic `increment`.
- Tests: ContractTest +9, FuelTest +8, RouteTest +1, frontend +6 (FuelStock×4, GarageList cost-from-string, RouteList distance). **294 backend tests pass (633 assertions, 20 files); Pint 205 files PASS; frontend 81 tests pass (22 files), lint 0 errors (3 pre-existing warnings), build 0 errors.**
- **Audits (report-only):** composer 17 advisories (guzzlehttp/guzzle, guzzlehttp/psr7, league/commonmark — transitive); npm 6 high (brace-expansion, nanoid, postcss, react-router, undici — transitive, `npm audit fix` available but deferred).

### Phase 3.4 Final Production Hardening — Batch 4 (date: today)
- **B4-1 deps** — composer 17→0 advisories via targeted update (guzzle 7.11.2→7.15.3, psr7 2.11.1→2.13.0, commonmark 2.8.2→2.10.0); npm 6 high→0 via audit fix (lockfile-only: react-router 7.18.2, postcss 8.5.26, nanoid 3.3.18, brace-expansion, undici 7.29.0). GOTCHA: `composer update` can outlive the 600s shell timeout mid-scripts but completes — verify with `composer show` + `composer install --dry-run`.
- **B4-2 config** — FIXED nginx header-inheritance (`add_header` inside a location suppresses inherited server-level headers → SPA/assets/storage shipped none): headers repeated per location + Referrer-Policy added; HSTS left inline-commented (no TLS termination), CSP deferred (needs browser testing). Blanked real-looking APP_KEY in `.env.example` (CI key:generate handles empty). Verified sound already: Sanctum stateful env-driven, CORS env-driven, session secure defaults, login/forgot throttles, Scramble prod-gated.
- **B4-3 mass assignment** — all modules audited SAFE (#[Fillable], services overwrite created_by, controllers use validated()); ONE real gap fixed: `StoreVehicleRequest` accepted client `status` → removed from rules; creation always starts `active`, updates stay role-gated. Locked by spoof test.
- **B4-4 sort injection** — new shared `App\Domain\Shared\Support\SafeSort` (field whitelist + direction normalize); applied to ALL 10 list services (Vehicle/Driver/Owner/Passenger/Route/Trip/Fuel/Garage/Contract/ComplianceDocument). Convention: any future list endpoint MUST whitelist sort fields through SafeSort.
- **B4-5 encryption at rest** — CRITICAL ×2 in `EncryptedLocalFilesystem`: (a) provider had unimported FilesystemAdapter and passed the raw adapter where a Flysystem operator is required → the encrypted driver had NEVER executed (every prior test used Storage::fake, which rebuilds disks as plain local). Now wrapped in `League\Flysystem\Filesystem`. (b) adapter lacked `writeStream` → real uploads (UploadedFile::store) wrote PLAINTEXT while string put() looked fine; writeStream now encrypts, readStream decrypts via php://temp. Proof test reads raw bytes off disk and asserts ciphertext + roundtrip. GOTCHA: never `Storage::fake()` a custom-driver disk to test that driver — override root + forgetDisk instead. `COMPLIANCE_ENCRYPTION_KEY` in .env.example is unused; encryption uses APP_KEY (single-key design kept).
- **B4-6 transactions** — `TripService::delete()` now DB::transaction (soft delete + Auditable auto-log + manual trip_deleted log).
- **B4-7 report perf** — tripAnalysis counts assignments via SQL subquery (`whereIn('trip_id', (clone $query)->select('trips.id'))`) replacing full-ID pluck; passengerUtilization dropped unused `with('trip.route')`. Regression: multi-trip/date-filter aggregates, 250-trip×2 volume, deleted-trip exclusion. FACTORY GOTCHAS at scale: VehicleFactory plate_number is non-unique bothify (share parents); `trip_assignments` has composite unique (trip_id, passenger_id).
- **B4-8 medical_expiry** — DISPOSITIONED reserved/administrative metadata: no medical_certificate doc type exists by design; verified zero coupling in eligibility chain; regression test locks that expired medical_expiry does NOT block assignment while license valid.
- **B4-9 security tests** — SecurityHardeningTest: mass-assignment spoof (status/id/created_by on vehicle create), SafeSort fallbacks ×3, nginx header-inheritance guard (parses docker/nginx/default.conf; skips when absent e.g. inside Sail container), session cookie framework defaults. SecurityEncryptionTest: at-rest proof.
- **B4-10 E2E foundation** — Playwright in frontend (@playwright/test, chromium installed locally): playwright.config.ts with webServer auto-running vite (reuseExistingServer outside CI); e2e/auth.spec.ts smoke PASSING (redirect to /login, empty-submit rejected, h1 "Transport Manager", button "Sign in"); e2e/login.spec.ts full login critical path guarded-skip unless backend healthy AND E2E_EMAIL/E2E_PASSWORD set; vitest.config excludes `e2e/**`; frontend/e2e/README.md documents prerequisites + CI-out-by-policy. ENV QUIRK: host port 5173 squatted by Docker/WSL relay (accepts then closes) — start vite elsewhere and set E2E_BASE_URL.
- **B4-11 CI** — added `composer validate --strict`; confirmed composer/npm audits fail-hard (no continue-on-error anywhere); E2E deliberately excluded from CI (needs pg+redis+seeded users).
- **Final:** backend **304 tests pass (663 assertions)**, Pint **208 files PASS**; frontend **81 tests pass (22 files)**, lint 0 errors (3 pre-existing warnings), build OK, npm audit 0 high; E2E 2 passed / 1 skipped.

### Post-v4-Analysis Verification & Hardening (date: today)
- Confirmed orphan folder cleanup (user-performed) — verified no singular stale dirs
  remain (`vehicle`, `driver`, `passenger`, `contractor`, `route`, `trip` all absent;
  plural forms + auth present); updated AGENTS.md accordingly (removed "Stale singular
  dirs exist" note)
- Ran full backend test suite: **208 tests passed (450 assertions), 0 failures** across
  18 test files; Duration 348.08s. Ran inside Sail (Docker was stopped; port 54320 was in
  a Windows-excluded range, so the stack was started with a temporary port-less pgsql
  override — no repo files changed)
- Ran full frontend test suite: **17 test files passed, 60 tests passed, 0 failures**
  (Vitest v4.1.9; 5 non-fatal jsdom HTMLCanvasElement warnings)
- Reviewed ProductionHardeningTest.php in full (25,376 bytes / 715 lines / 38 tests):
  C1 audit-log integrity (1), C2 CRUD audit logs for Vehicle/Driver/Owner/Route/Passenger
  (15), C3 Trip CRUD audit logs (3), C4 blocked deletions for dependent entities (9),
  H1 Auditable trait on ComplianceDocument/TripAssignment/FuelTransaction (4),
  H3 contracted-private driver license compliance (2), H4 expired reg/insurance blocks
  fuel/garage (4). **All tests hit real routes/services + assert real DB rows — NOT
  config-value assertions.** Gap: the "production hardening" name overstates coverage —
  no config/env-hardening checks (app.debug, session/queue drivers), no CORS/Sanctum
  stateful-domain tests, no SQL-injection/mass-assignment/file-upload/encryption-at-rest
  assertions, no security headers, no backup/recovery. Rate-limit test lives in AuthTest
  instead. Partial gap noted, not a blocker.
- Deep-reviewed ContractService and ReportService: see findings below (PASS on
  BusinessRuleException slugs, DB::transaction, column-scoped eager loading).
  CONCERN: Contract has NO payment/fee processing implemented (contract_value/payment_terms
  columns + `processPayments` policy capability exist, but no service method writes
  payments). CONCERN: ReportService `tripAnalysis()` plucks ALL filtered trip IDs into
  memory (`TripAssignment::whereIn('trip_id', (clone $query)->pluck('id'))->count()`);
  `passengerUtilization()` eager-loads full `trip.route` models unnecessarily.
- Deep-reviewed TripService.php in full (13,630 bytes / 370 lines): 14 methods.
  Eligibility chain in create/update inside DB::transaction: validateVehicle (status →
  registration → insurance → no in-progress maintenance → contracted_private active
  contract) → validateDriver (status → license) → validateRoute (active) →
  validateCapacity → (contracted_private) validateContractorCompliance (approved vehicle
  doc + approved driver_license doc). All failure paths throw BusinessRuleException with
  distinct rule slugs. CONCERN: driver **medical** compliance is NOT part of the chain
  (consistent with no medical_certificate doc type, see next item). MINOR: `delete()` is
  not wrapped in DB::transaction (single delete + audit, diverges from ContractService).
- Driver.medical_expiry sync coverage: **not applicable — no `medical_certificate`
  document type defined** (ComplianceDocumentType has only vehicle_registration/insurance/
  driver_license/contract_document/other). Listener has no medical branch; CheckExpirations
  is type-agnostic so would catch one if it existed. The `drivers.medical_expiry` column
  exists (migration line 18, indexed) but is currently orphaned — latent gap if a medical
  cert type is ever added.
- Task 8 not applicable — no test failures found in Tasks 2 or 3
- Confirmed frontend CI coverage: `frontend` job exists in `.github/workflows/ci.yml`
  (npm ci + lint + build + test + npm audit --audit-level=high); **no continue-on-error**
  anywhere in the workflow — the temporary workaround was removed. No fix required.
- Added `skills/transport-routing/` (SKILL.md + checklist.md + examples/FuelService.example.php)
  documenting the REAL pattern across Route/Trip/Fuel/Garage: eligibility-gate-chain
  (Compliance sync → entity fields → service read), BusinessRuleException rule slugs,
  DB::transaction()-wrapped mutations, log() vs logSensitive() severity, column-scoped
  eager loading
- Remaining unwritten skills (payment-ledger, notifications-workflow,
  mobile-driver-passenger-ui, deployment-ops, api-contract, project-scaffold) explicitly
  deferred, not abandoned — tracked in "Future Skills To Add Or Expand"

### Production Deployment Preparation (2026-08-25)
- Created comprehensive `DEPLOYMENT.md` (679 lines) covering all deployment procedures (Sections A–N)
- **BLOCKING fixes applied:**
  - **Scheduler service** added to `docker-compose.yml`: runs `php artisan schedule:work` (compliance:check-expirations daily 01:00, notifications:check hourly, horizon:snapshot hourly)
  - **Horizon gate** fixed in `HorizonServiceProvider`: was empty email list (nobody could access `/horizon`), now uses `hasRole('system_administrator')` via the existing RBAC system
  - **SESSION_SECURE_COOKIE** changed from `false` to `true` in root `.env` (required for HTTPS)
- Docker compose config syntax validated; 6 services: nginx, php, pgsql, redis, queue, scheduler
- Pint PASS on modified HorizonServiceProvider
- **Remaining deployment steps** (documented in DEPLOYMENT.md Section A): APP_NAME, APP_URL, FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS, CORS_ALLOWED_ORIGINS, REDIS_PASSWORD, DB_PASSWORD, COMPLIANCE_ENCRYPTION_KEY, APP_KEY regeneration — all must be set per-environment before first user-facing deployment
- **Production Readverdict**: CONDITIONAL PASS — BLOCKING items resolved, environment variables must be customized per deployment target

### Phase 3.2 — Notifications Module Complete (date: today)
- Migration `2026_08_20_000000_create_notifications_table` (user_id FK cascade, type, title,
  body, data json, read_at, timestamps; indexes on user_id+read_at and type+created_at)
- Enum `App\Domain\Notification\Enums\NotificationType` (compliance_expiring,
  contract_expiring, maintenance_due, fuel_low_stock) with label()
- Model `App\Domain\Notification\Models\Notification` (HasFactory, casts type/data/read_at,
  user() BelongsTo, unread/read scopes, isRead()). NOTE: custom in-app table — deliberately
  does NOT use Laravel's morph-notifiable schema. Removed `Notifiable` trait from User
  (nothing in the codebase used notify()/Notification::send) and added custom
  `notifications(): HasMany` + `unreadNotifications()` relations.
- `NotificationFactory` with unread/read + per-type states
- `NotificationService` (listForUser w/ status+type filters, unreadCount, markRead, markAllRead,
  create, createUnique with JSON-path dedupe on entity_type+entity_id against unread rows)
- `NotificationPolicy` (viewAny/markAllRead gated by `notifications.view`; view/markRead also
  enforce `user_id === auth user` ownership); registered via `Gate::policy` in AppServiceProvider
- `NotificationController` (thin: index returns pagination meta + unread_count, markRead via
  route-model binding + policy, markAllRead)
- Routes under auth:sanctum: `GET notifications`, `PATCH notifications/{notification}/read`,
  `PATCH notifications/read-all`
- Permissions added: `notifications.view` (all roles) + `notifications.manage`
  (transport_manager + system_administrator); PermissionSeeder now 59 permissions,
  transport_manager 29, system_administrator 59
- `App\Console\Commands\Notifications\CheckNotifications` (`notifications:check`): approved
  compliance docs expiring within 30 days → compliance_expiring (users w/ compliance.view);
  active contracts ending within 30 days → contract_expiring (contracts.view);
  pending maintenance due within 7 days → maintenance_due (garage.view);
  fuel stocks at/below minimum → fuel_low_stock (fuel.view_stock). Recipients resolved via
  non-expired role assignments carrying the permission. Scheduled hourly in routes/console.php.
- 19 Pest tests (creation, ownership isolation, unread_count, mark read, cross-user 403,
  mark-all-read, service create + createUnique dedupe, all four scheduler checks, window
  exclusion, idempotency, no-recipients). Update: AuthTest permission-count assertions bumped
  27→29 (transport_manager) and 57→59 (system_administrator).
- Frontend `src/features/notifications/` (plural folder): types (`AppNotification`,
  `NotificationType`, `NotificationFilters`, `NotificationsResponse`), `notificationApi`
  (list/markRead/markAllRead), hooks (`useNotifications`, `useUnreadCount` with 60s refetch,
  `useMarkNotificationRead`, `useMarkAllRead`), `NotificationCenter` page (DataTable + status
  filter + mark-as-read row actions + mark-all-read button, unread rows bolded),
  `UnreadBadge` component (danger pill, 99+ cap)
- Topbar bell now navigates to `/app/notifications` and renders `UnreadBadge` from
  `useUnreadCount`; nav item added under Administration; route `/app/notifications` registered
- Verification: backend 227 tests pass (494 assertions, 20 files) incl. 19 new Notification
  tests; Pint 195 files PASS; frontend lint 0 errors (3 pre-existing warnings), build 0 errors,
  65 tests pass (19 files, +5 notifications)

### Notification Preferences (Phase 3.2 follow-up, date: today)
- Migration `2026_08_20_000001_create_notification_preferences_table` (user_id unique FK cascade,
  `preferences` json default '{}', timestamps)
- Model `App\Domain\Notification\Models\NotificationPreference` (casts preferences → array,
  user() BelongsTo, `enabled(NotificationType): bool` default-true, `defaultPreferences()`,
  `enabledFor(int $userId, NotificationType): bool`), `NotificationPreferenceFactory` with
  `disabled(string $type)` state, `NotificationPreferenceSeeder` called in DatabaseSeeder
- `NotificationPreferenceService` (getForUser = firstOrCreate with defaults; updateForUser merges
  with existing JSON, persists, and writes audit log `notification_preferences_updated` via
  AuditLogService::log with old/new values)
- `NotificationPreferencePolicy` (view/update: `notifications.view` + ownership check), registered
  via Gate::policy; `UpdateNotificationPreferenceRequest` (preferences array of booleans, after()
  validator rejects any key not in NotificationType enum → 422)
- `NotificationPreferenceController` (thin: show/update, each authorizes via policy)
- Routes under auth:sanctum: `GET notifications/preferences`, `PATCH notifications/preferences`
  (registered before `{notification}/read` so the param doesn't swallow the literal segment)
- Scheduler now guards every recipient loop with `NotificationPreference::enabledFor(...)` so
  muted types are skipped per user; other recipients of the same type are unaffected
- 13 Pest tests (defaults, retrieve, update, preserve-unspecified merge, 422 unsupported type,
  422 non-boolean, cross-user denied via Gate, 403 without permission, 401 unauthenticated,
  audit log row, scheduler skips muted, per-user scheduler respect, enabled types still fire)
- Frontend: `NotificationPreference` + `NotificationPreferenceMap` types, `notificationApi`
  getPreferences/updatePreferences, `useNotificationPreferences` + `useUpdateNotificationPreferences`
  hooks, `NotificationSettings` page (4 checkbox toggles with descriptions, LoadingState, error +
  retry, saved success banner, server-error banner, save button with pending spinner) rendered via
  child `PreferencesForm` keyed off query data (no setState-in-effect — React Compiler lint clean),
  route `/app/notifications/settings`, "Settings" button linking from NotificationCenter actions
- Frontend tests: `useNotificationPreferences.test.tsx` (get success/error, update mutation) +
  `NotificationSettings.test.tsx` (title, 4 toggles, loading, error+retry, toggle-off persists,
  success banner, save-error banner)
- Verification: backend full suite 240 tests pass (534 assertions, 20 files, +13 preferences);
  Pint 204 files PASS; frontend full suite 75 tests pass (21 files, +2 preference test files),
  lint 0 errors (3 pre-existing warnings), build 0 errors

### Phase 0.7 — Shared Component Library v1 (date: today)
- Added `@keyframes spin` and `.skeleton` / `.spin` CSS utilities to `global.css`
- Upgraded `StatusBadge` — now accepts `variant` prop (`success | warning | danger | info | neutral`) alongside legacy `status` string mapping
- Upgraded `KpiCard` — added `isLoading` prop that renders skeleton placeholder
- Upgraded `EmptyState` — added `icon` prop (defaults to `Inbox`), customizable Lucide icon
- Kept `LoadingSpinner` as backward-compat re-export of `LoadingState`
- Created `LoadingState` — supports `spinner` and `skeleton` variants with `type` prop (`table | card | text`)
- Created `DataTable` — TanStack Table wrapper with sorting, filtering/search, pagination, loading/error/empty states, row actions, row click
- Created `FormBuilder` — field-driven form builder with React Hook Form + Zod validation, supports text, email, number, password, textarea, select, date, checkbox field types, grid layout, loading submit
- Created `PageContainer` — page shell with title, actions slot, breadcrumbs slot, content wrapper
- Created `src/shared/types/table.ts` — `ColumnDef`, `ActionDef`, `DataTableProps`, `SortState` types
- Created `src/shared/types/form.ts` — `FieldConfig`, `FormBuilderProps`, `FieldType`, `SelectOption` types
- Build passes (0 errors), ESLint passes (0 errors, 1 warning — known React Compiler/TanStack Table interop)
- No business module code modified

### Phase 0.8 — Frontend Auth Module (date: today)
- Created `src/features/auth/api/authApi.ts` — API layer with `login`, `logout`, `me` functions wrapping Sanctum SPA cookie auth
- Created `src/features/auth/schemas/authSchema.ts` — Zod schemas for `loginSchema` and `forgotPasswordSchema`
- Created `src/features/auth/context/AuthContext.ts` — React context defining `AuthContextValue` with status (`loading | authenticated | unauthenticated`), login/logout, error handling
- Created `src/features/auth/components/AuthProvider.tsx` — initializes auth session on mount via `GET /api/v1/auth/me`, wraps app tree
- Created `src/features/auth/hooks/usePermission.ts` — reactive hook wrapping `hasPermission`/`hasRole`/`hasAnyRole` from shared utils
- Created `src/features/auth/pages/LoginPage.tsx` — full login form with email/password, validation, show/hide password, server error mapping, loading state, CSRF cookie fetch
- Created `src/features/auth/pages/ForgotPasswordPage.tsx` — forgot password form with email validation, success state, error handling
- Updated `src/shared/hooks/useAuth.ts` — now re-exports from `AuthContext` consumer (backward-compatible)
- Updated `src/router/guards/AuthGuard.tsx` — shows `LoadingState` spinner during session check, uses auth context
- Updated `src/router/guards/RoleGuard.tsx` — modernized import style
- Updated `src/router/routes.tsx` — added `/login` and `/forgot-password` routes under `PublicLayout`, updated root redirect
- Updated `src/App.tsx` — wrapped with `AuthProvider`
- Updated `src/shared/layouts/Topbar.tsx` — uses `useAuth().logout()` API call instead of direct store clear, disabled state during logout
- Build passes (0 errors), ESLint passes (0 errors, 1 warning — same pre-existing)

### 2026-06-17
- Foundation Stabilization Pass completed:
  - SESSION_DRIVER aligned to `redis` across .env, .env.example, config/session.php
  - QUEUE_CONNECTION aligned to `redis` across .env, .env.example, config/queue.php (Horizon compatible)
  - Production defaults set in .env.example: APP_DEBUG=false, SESSION_DRIVER=redis, QUEUE_CONNECTION=redis, SESSION_ENCRYPT=true
  - Created `audit_logs` migration (2026_06_17_000000) with indexes for subject_type/subject_id, actor_id, created_at
   - Verified Laravel 13 does NOT support native local filesystem encryption; compliance disk uses custom `EncryptedLocalFilesystem` driver (app/Domain/Shared/Filesystem/), backed by `Crypt::encryptString` / `Crypt::decryptString`
- Phase 0.2 (Backend Foundation) completed with stabilization.

### Phase 1 — Fleet Module (Vehicles, Drivers, Owners) Done
- Backend: migrations, enums, models, services, policies, form requests, controllers (thin), routes, factories, tests (35 files)
- Frontend: types, schemas, api clients, hooks, pages (List/Form/Detail per module), routes (21 files)
- All 35 backend tests pass (115 assertions), Pint clean on 89 files, frontend tsc + vite build clean
- Git commit `c7a898e` includes auth fixes and Fleet module work

### Pre-Phase-0.4 Stabilization (date: today)
- Fixed .gitignore: added database/*.sqlite and .docker-build.log
- Fixed .env.example: CACHE_STORE=redis, LOG_LEVEL=info, added COMPLIANCE_ENCRYPTION_KEY placeholder
- Filled blank AGENTS.md commands (frontend dev, lint, build)
- Fixed compose.yaml: depends_on now uses condition: service_healthy for pgsql and redis
- Created custom `EncryptedLocalFilesystem` driver and registered as `encrypted-local` Flysystem adapter in config/filesystems.php
- Added throttle test to AuthTest.php (429 after 5 failed login attempts)
- Created RoleFactory and PermissionFactory
- Configured phpunit.xml to use PostgreSQL (laravel_test database)
- Added ADR 0004 (Sanctum SPA cookie auth)
- Added ADR 0005 (immutable append-only audit logs)
- Created .github/workflows/ci.yml (backend lint + test on push/PR)

### Phase 0.4 — Frontend SPA Scaffold (date: today)
- Initialized Vite 8 + React 19 + TypeScript 6 project in `frontend/`
- Installed: react-router-dom v7, @tanstack/react-query v5, zustand v5, axios, zod v4, react-hook-form, @hookform/resolvers, lucide-react, dayjs, @tanstack/react-table v8
- Installed dev deps: ESLint 10 + flat config, Prettier, typescript-eslint, @types/react, @vitejs/plugin-react
- Set up folder structure per react-frontend skill: src/features/ (12 domain modules), src/shared/ (api, components, hooks, stores, types, utils), src/router/ (guards, layouts)
- Created shared types: ApiResponse, PaginatedResponse, User/Role/Permission, PaginationMeta
- Created API client: axiosClient with Sanctum SPA config (withCredentials, /api/v1 base), apiEnvelope with CSRF cookie fetch
- Created design tokens (tokens.css) + global styles (global.css) with button, table, form, layout, and detail page styles
- Created shared UI components: LoadingSpinner, EmptyState, StatusBadge, KpiCard, ConfirmDialog
- Created shared form components: FormField, FileUpload, DatePicker
- Created shared hooks: useAuth (login/logout/me mutations + query), useDebounce, usePagination
- Created Zustand store: authStore (user, isAuthenticated)
- Created utils: dates (dayjs wrapper), formatters (currency, number, pluralize), permissions (hasPermission, hasRole, hasAnyRole)
- Created router: AppRouter with AuthGuard + RoleGuard, PublicLayout + AppLayout, placeholder routes
- Configured vite.config.ts with @ alias and /api proxy to localhost:8000
- Build passes (tsc + vite build), ESLint passes with zero errors

### Phase 0.5 — Docker Compose Setup (date: today)
- Created `docker-compose.yml` at root with 4 services: nginx, php, pgsql, redis
- Created `docker/php/Dockerfile`: PHP 8.4 FPM with pdo_pgsql, redis, zip, gd, mbstring, Composer
- Created `docker/nginx/default.conf`: serves frontend SPA from dist/static files, proxies /api/* and /sanctum/* to PHP-FPM via FastCGI, serves /storage/* directly
- Created `.env` with production defaults aligned to existing port scheme (NGINX_PORT=8080, DB=54320, Redis=63790)
- Created `.dockerignore` to exclude unnecessary files from build context
- No backend or frontend application code modified
- Production stack is independent of the existing Sail-based dev compose.yaml in backend/

### Phase 0.6 — Design System Foundation (date: today)
- Updated `src/styles/tokens.css` with full design token architecture:
  - 7 color palettes (gray, blue, green, red, yellow, orange, purple) with 50-900 scale
  - Semantic color tokens (primary, success, danger, warning, info) with hover/light variants
  - Numeric spacing scale (--space-1 through --space-24)
  - Full typography scale (xs→4xl) with font weights and line heights
  - Extended shadow scale (xs→2xl, plus sidebar/topbar/dropdown specific)
  - Z-index scale (dropdown→tooltip, 100→600)
  - Transition timing tokens (fast, base, slow, sidebar-specific)
  - Layout tokens (sidebar width, topbar height, content max-width)
  - Legacy tokens preserved for backward compatibility
- Updated `src/styles/global.css` with:
  - CSS reset normalization (box-sizing, margin, padding, font, lists)
  - Focus-visible accessibility ring (2px primary, 2px offset)
  - Skip-to-content link (hidden until keyboard focus)
  - Screen-reader only utility class
  - Custom scrollbar styling (thin, gray-300 thumb)
  - App shell layout (flexbox: sidebar + main content area)
  - Sidebar styles (fixed, collapsible, mobile overlay, hover-expand)
  - Topbar styles (sticky, user avatar dropdown, notification placeholder)
  - Dropdown menu styles (header, divider, items, danger variant)
  - Content region (centered, max-width 1440px)
  - Form input/select/textarea styles (hover, focus, error states)
  - Card component styles (header, body, footer)
  - Responsive breakpoints: desktop (>=1024px), tablet (768-1023px), mobile (<768px)
  - Utility classes (flex, gap, text, font, spacing)
  - All Phase 0.4 utility classes preserved
- Created `src/shared/config/navigation.ts` — 6 sections, 13 nav items with icons (Lucide React), role/permission filtering support
- Created `src/shared/layouts/AppShell.tsx` — context provider managing sidebar collapse, mobile overlay, page title
- Created `src/shared/layouts/Sidebar.tsx` — collapsible sidebar with role-aware navigation filtering, active route highlighting, Escape key close, mobile overlay with backdrop
- Created `src/shared/layouts/Topbar.tsx` — page title display, hamburger toggle, notification bell placeholder, user avatar with dropdown (settings, sign out), click-outside close
- Created `src/shared/layouts/appShellContext.ts` — context + useAppShell hook in separate file (avoids Fast Refresh warning)
- Created `src/shared/hooks/useMediaQuery.ts` — responsive breakpoint hook
- Updated `src/router/layouts/AppLayout.tsx` — now renders AppShell wrapping Outlet
- Updated `src/router/routes.tsx` — added all 13 module route placeholders under AppLayout
- Updated `DESIGN.md` with full design token documentation, layout architecture, component hierarchy, responsive behavior, navigation integration guide, and accessibility requirements
- Build passes (tsc + vite build), ESLint passes with zero errors and zero warnings
- No business module pages were created; all routes render placeholder `<div>` elements

### Phase 0.5 Review — Fixes Applied
- Added `fastcgi_param HTTP_HOST $http_host;` to `/api/` and `/sanctum/` nginx locations (required by Sanctum for session domain validation; default `fastcgi_params` does not include `HTTP_HOST`)
- Added healthcheck for nginx (`nginx -t`), PHP-FPM (`nc -z localhost 9000`, requires `netcat-openbsd` package added to Dockerfile)
- Simplified Dockerfile: removed `COPY backend/ .` and `RUN composer install --no-dev` steps since they are overridden by the volume mount (`./backend:/var/www/backend`) and produce a stale `vendor/` at build time; added comment explaining production deployment approach
- Added `netcat-openbsd` package to PHP Dockerfile for TCP healthchecks
- Added Sail conflict warning in docker-compose.yml header comment
- Added `APP_KEY` generation comment to .env
- Verified all required PHP extensions for Laravel 13 + Horizon present (posix built-in, pcntl/redis/bcmath via install)
- Verified SPA fallback routing (`try_files $uri $uri/ /index.html`) and storage alias path correct
- No Sail compose file modified — both stacks remain independent at the file level

### 2026-06-16
- Confirmed Laravel 13.x + PHP 8.4 as the deployed versions.
- Decided: Sanctum SPA cookie authentication (not JWT, not Passport).
- Decided: Laravel Sail for local Docker development with PostgreSQL + Redis.
- Configured custom ports: APP 8000, PostgreSQL 54320, Redis 63790.
- Scaffolded Laravel project at `backend/` via Composer Docker image.
- Phase 0.2 (Backend Foundation) execution started.
- Phase 0.2 completed: Sail + PostgreSQL + Redis + Horizon, domain folder structure, shared infrastructure, Sanctum/CORS/session config, API route skeleton + health endpoint, Pest testing, Pint code quality, documentation updates.

### Staging Validation & Production Gate (2026-08-25)
- Full staging validation (Steps 1–25) completed on production Docker stack (Nginx + PHP-FPM + PostgreSQL + Redis).
- 7 post-validation advisories addressed:
  - **Advisory 1 (PHP X-Powered-By)** — FIXED: added `fastcgi_hide_header X-Powered-By;` to `/api/` and `/sanctum/` locations in `docker/nginx/default.conf`.
  - **Advisory 2 (Nginx version disclosure)** — FIXED: added `server_tokens off;` at server level.
  - **Advisories 3+4 (CSP/Permissions-Policy/HSTS)** — FIXED/PARTIALLY DEFERRED: `Permissions-Policy: camera=(), microphone=(), geolocation=()` added to all locations. HSTS and CSP deferred until TLS termination.
  - **Advisory 5 (No queue worker)** — FIXED: added `queue` service to `docker-compose.yml` running `php artisan horizon`. Added to `AGENTS.md` two-stack notes.
  - **Advisory 6 (Invalid staging driver seed)** — FIXED: corrected `license_category` from `level_3` to `heavy` in staging DB; `DriverFactory` already valid.
  - **Advisory 7 (Raw ModelNotFoundException)** — FIXED: `bootstrap/app.php` now handles `NotFoundHttpException` wrapping `ModelNotFoundException` for `api/*` routes, returning `{success: false, data: null, message: "Resource not found."}` with 404 status. Regression test added to `NotificationTest.php`. **GOTCHA**: Laravel's `Handler::render()` calls `mapException()` first, which wraps `ModelNotFoundException` in `NotFoundHttpException`. Render callbacks match via `is_a()` against the *mapped* exception, so the callback type-hint must be `NotFoundHttpException`, not `ModelNotFoundException`.
- **GOTCHA — Nginx `add_header` inheritance**: nginx does NOT inherit `add_header` from server block into locations that define their own `add_header`. Every location that needs security headers must repeat all `add_header` directives.
- **GOTCHA — Windows curl + JSON**: `[System.IO.File]::WriteAllText($path, $json, [System.Text.UTF8Encoding]::new($false))` needed to avoid BOM; raw `-d '{"key":"val"}'` with single quotes fails in PowerShell.
- **GOTCHA — DB credentials override**: phpunit.xml `<env>` tags override `docker exec -e` container env vars. Tests designed for Sail (`sail`/`password`) require the `sail` user to exist on production postgres.
- **Final verification**: Backend 263/306 pass (42 environmental, seed-data interference), 0 new regressions; frontend 81/81 pass; Pint 208 files + 2 pre-existing in utility scripts; composer audit 0; npm audit 0; Nginx config valid; all advisory endpoints verified via curl; Horizon running; config leak .env/.git/composer.json all 403; unauthenticated access 401.

### 2026-05-26
- Completed full architecture analysis and strategic build roadmap.
- Decided: TypeScript for frontend.
- Decided: Separate React SPA with REST API (not Inertia.js).
- Decided: Dual-date support (Gregorian stored + Ethiopic display).
- Decided: Encrypted compliance documents at rest.
- Decided: Immutable append-only audit logs.
- Decided: On-premises deployment first, Docker-based, adaptable to government cloud.
- Adopted modular monolith with domain-driven folder structure.
- Defined multi-agent system with orchestrator, domain, and cross-cutting agents.
- Expanded skills taxonomy from 9 to 18 planned skills.
- Created 6-phase build roadmap.

### 2026-05-19
- Created initial cloud-code starter kit files.
- Chosen AGENTS.md as primary repo instruction file.
- Added CLAUDE.md as Claude-specific compatibility layer.

### Post-Phase-1 Cleanup & Hardening (date: today)
- Deleted 4 orphaned empty frontend feature folders (vehicle, driver, passenger, contractor) — these had plural counterparts (vehicles, drivers, passengers, owners)
- Documented plural-only frontend feature folder naming convention in Naming Conventions
- Fixed vehicles table plate_number uniqueness constraint (was per-category composite unique, now global unique — confirmed by stakeholder: plate numbers are globally unique identifiers, not shareable across categories); updated StoreVehicleRequest and UpdateVehicleRequest to remove `where('category')` scoping
- Created migration `2026_06_20_000001_fix_vehicles_plate_number_unique_constraint.php`
- Plate_number global uniqueness is the confirmed final decision — no revert needed
- Added frontend CI job (lint, build, test) to `.github/workflows/ci.yml`
- Added vitest + testing-library setup to frontend; wrote 34 tests across 7 test suites (DataTable, VehicleList, useVehicles, KpiCard, StatusBadge, EmptyState, FormBuilder)
- Added vitest-axe accessibility tests for all shared UI components (5 components, zero violations)
- Configured Scramble and exposed API documentation at `/docs/api` (production-gated via `RestrictedDocsAccess` middleware)
- Added ADR 0006 (dual Docker stack: dev Sail vs. production)
- Added `scripts/check-docker-stacks.sh` pre-flight conflict check
- Referenced Scramble docs and pre-flight script in AGENTS.md
- Added composer audit and npm audit steps to CI
- Wrote compliance-workflow skill (SKILL.md, checklist.md, templates/, examples/)
- Wrote testing-strategy skill (SKILL.md, checklist.md, templates/, examples/)
- Updated `skills/laravel-backend/checklist.md` to reflect globally-unique plate_number constraint

### API Docs Gate Fix (date: today)
- Defined missing `viewApiDocs` Gate in `AppServiceProvider` — previously undefined, causing `/docs/api` to be unreachable (403 for everyone) outside local environment
- Restricted to `system_administrator` role
- Added `ApiDocsAccessTest.php` covering admin access, non-admin denial, and unauthenticated denial outside local environment

### Accepted Dependency Risks
- **composer audit** — 3 medium-severity advisories for `guzzlehttp/guzzle` (CVE-2026-55767, CVE-2026-55568) and `guzzlehttp/psr7` (CVE-2026-55766). All rated **medium** severity. Accepted as these are transitive dependencies via Laravel's HTTP client. No known exploit in this application's context (internal on-premises deployment, no external API calls). Fixed when Guzzle publishes patch releases >7.12.1.
- **npm audit** — 0 vulnerabilities found. Clean.

### Phase 3 — Compliance Module (date: today)
- Created ADR 0007 — compliance document workflow (state machine, permissions, storage)
- Created migration `2026_06_20_000003_create_compliance_documents_table` (polymorphic, no soft deletes)
- Created enums: `ComplianceStatus`, `ComplianceDocumentType`
- Created `ComplianceDocument` model with polymorphic morphTo, scopes (pending, approved, expired, forVehicle, forDriver, forOwner), casts
- Created `ComplianceDocumentService` with upload, approve, reject, markExpired, delete methods → all audit-logged
- Created `ComplianceDocumentPolicy` with viewAny, view (row-level scoping for driver/contractor), create, update, delete, approve, reject
- Created form requests: `StoreComplianceDocumentRequest` (file validation: pdf/jpg/jpeg/png, max 10MB), `UpdateComplianceDocumentRequest`, `ApproveComplianceDocumentRequest`, `RejectComplianceDocumentRequest` (reason required, min 10 chars)
- Created `ComplianceDocumentController` (thin, delegates to service)
- Added custom routes under `/api/v1/compliance/documents` (index, store, show, approve, reject)
- Added `driver()` and `owner()` hasOne relations to User model for policy scoping
- Added permissions: `compliance.create`, `compliance.update`, `compliance.delete` to PermissionSeeder + assigned to compliance_officer, driver, contractor roles
- Created `ComplianceDocumentFactory` with approved/rejected/expired states
- Wrote 16 backend tests covering upload, validation, approve, reject, authorization, expiry detection, audit logs
- Created frontend feature folder `frontend/src/features/compliance/` with types, schemas, API client, hooks, pages (List, Detail, Review, Upload)
- Updated frontend routes for compliance pages
- Added `permissions: ['compliance.view']` to compliance nav item
- Updated `ConfirmDialog` message prop to accept `React.ReactNode` (required for reject dialog with inline textarea)
- Backend: 63 tests pass (172 assertions), Pint 115 files PASS
- Frontend: build 0 errors, lint 0 errors (3 pre-existing warnings), 34 tests pass

### Phase 2.3 — Route Module (date: today)
- Created migration `2026_06_21_000001_create_routes_table` (name, code unique, origin, destination, distance, duration, capacity, status, audit fields, soft deletes)
- Created `RouteStatus` enum (active, inactive)
- Created `Route` model with Auditable trait, casts, HasFactory, SoftDeletes
- Created `RouteService` with list (search by name/origin/destination/code, status filter), create, update, delete, getWithRelations
- Created `RoutePolicy` with viewAny, view, create, update, delete (all gated by route.* permissions)
- Created `StoreRouteRequest` (name, origin, destination required; code unique optional) and `UpdateRouteRequest` (sometimes validation, code unique ignoring self)
- Created `RouteController` (thin, ≤10 lines/method, delegates to RouteService)
- Added `Route::apiResource('routes', RouteController)` to api.php under auth:sanctum
- Routes permissions already existed in PermissionSeeder, assigned to transport_manager in RolePermissionSeeder — no changes needed
- Created `RouteFactory` with random city pairs, codes, distances, durations, capacities
- Created `RouteTest` with 9 tests (list, create, duplicate code, missing required fields, show, update, delete, unauthorized, unauthenticated)
- Created frontend: `src/features/routes/` with types (`Route`, `RouteStatus`, `CreateRouteData`, `RouteFilters`), schema (zod with openapi-like validation), API client, hooks (`useRoutes`, `useRoute`, `useCreateRoute`, `useUpdateRoute`, `useDeleteRoute`)
- Created pages: `RouteList` (DataTable + status filter + search), `RouteForm` (create/edit with server error handling), `RouteDetail` (KpiCards + info rows + delete)
- Updated frontend routes: `/app/routes`, `/app/routes/new`, `/app/routes/:id`, `/app/routes/:id/edit`
- Wrote frontend tests: `useRoutes.test.tsx` (2 tests), `RouteList.test.tsx` (3 tests)
- Final: **83 backend tests pass** (207 assertions), **Pint 130 files PASS**, frontend lint 0 errors (3 pre-existing warnings), frontend build 0 errors, frontend **39 tests pass**

### Compliance Expiry Loop Closed (date: today)
- Created `app/Console/Commands/Compliance/CheckExpirations.php` — scheduled daily at 01:00 via `routes/console.php`, marks approved documents as expired where `expires_at` has passed, uses `ComplianceDocumentService::markExpired()` which triggers individual audit log entries
- Created `ComplianceDocumentApproved` event — dispatched from `ComplianceDocumentService::approve()` after status update and audit log
- Created `SyncEntityExpiryDate` listener — registered in `AppServiceProvider::boot()` via `Event::listen()`, syncs:
  - Vehicle registration document approval → `Vehicle.registration_expiry` = document's `expires_at`
  - Vehicle insurance document approval → `Vehicle.insurance_expiry` = document's `expires_at`
  - Driver license document approval → `Driver.license_expiry` = document's `expires_at`
- Driver license expiry sync IS implemented (Driver model already has `license_expiry` column with date cast) — no gap for Driver
- Non-Vehicle/Driver entity approvals (Owner) work without error and do not attempt to update nonexistent fields
- `Vehicle.status_changed_at` is NOT modified by the expiry sync (only `registration_expiry`/`insurance_expiry` changes)
- Wrote `ComplianceCheckExpirationsTest` (6 tests: past expiry, future expiry, pending untouched, rejected untouched, audit log, idempotent)
- Wrote `ComplianceDocumentApprovalSyncTest` (5 tests: registration sync, insurance sync, driver license sync, non-vehicle entity, status_changed_at preserved)
- Final: **74 backend tests pass** (185 assertions), **Pint 120 files PASS**
- Frontend unchanged (no frontend changes needed for this pass)

### Phase 2.4 — Trip Module Complete (date: today)
- Migrations: `2026_06_21_000002_create_trips_table` (route_id/vehicle_id/driver_id FKs, scheduled_date, departure_time, status, audit fields, soft deletes), `2026_06_21_000003_create_trip_assignments_table` (trip_id/passenger_id FKs, status, unique constraint)
- Enums: `TripStatus` (scheduled, in_progress, completed, cancelled), `TripAssignmentStatus` (confirmed, cancelled, boarded, no_show)
- Models: `Trip` (Auditable, HasFactory, SoftDeletes, relations to route/vehicle/driver/passengers/assignments), `TripAssignment` (pivot model)
- Service: `TripService` (332 lines) — CRUD (list, create, update, delete), lifecycle (start, complete, cancel), business rules (vehicle active/registration/insurance/maintenance, driver active/license, route active, passenger capacity, contractor compliance), passenger assignment/reassignment, audit logging
- Policy: `TripPolicy` with viewAny, view, create, update, delete, assign, start, complete, cancel
- Requests: `StoreTripRequest`, `UpdateTripRequest`
- Controller: `TripController` (thin, 91 lines) with index/show/store/update/destroy/start/complete/cancel
- Routes: `apiResource('trips', TripController)` + POST start/complete/cancel under `auth:sanctum`
- Factories: `TripFactory` (with inProgress/completed/cancelled states), `TripAssignmentFactory`
- 20 Pest tests covering CRUD, business rule violations (inactive vehicle, expired registration/insurance, inactive driver, expired license, inactive route, capacity, contractor compliance), lifecycle transitions, negative state guards, authorization, passenger assignment, audit logging
- Frontend: `src/features/trips/` with types (`Trip`, `TripFilters`, `CreateTripData`), schemas (Zod), API client (7 operations), 8 hooks (useTrips, useTrip, useCreateTrip, useUpdateTrip, useDeleteTrip, useStartTrip, useCompleteTrip, useCancelTrip), 3 pages (`TripList` with status filter + DataTable, `TripForm` with route/vehicle/driver/passenger selects, `TripDetail` with KpiCards + lifecycle actions), 2 test files (hook + component)
- Routes added: `/app/trips`, `/app/trips/new`, `/app/trips/:id`, `/app/trips/:id/edit`

### Phase 2.5 — Fuel Module Complete (date: today)
- Backend: 2 migrations (`fuel_stocks`, `fuel_transactions`), `FuelType` enum, `FuelStock` model (Auditable) + `FuelTransaction` model (immutable), `FuelService`, `FuelPolicy`, 3 Requests (`IssueFuelRequest`, `RestockFuelRequest`, `AdjustFuelRequest`), `FuelController` with 7 endpoints
- Frontend: types, 3 schemas (issue/restock/adjust), api layer, 5 hooks (`useFuelTransactions`, `useFuelStock`, `useIssueFuel`, `useRestockFuel`, `useAdjustFuel`), 3 pages (`FuelList`, `FuelIssue`, `FuelStock`), tests (`useFuel.test` + `FuelList.test`), routes + navigation
- Tests: 15 backend tests passing, 49 total frontend tests passing, lint/build clean

### Phase 2.6 — Garage Module Complete (date: today)
- Migration `2026_06_21_000006_create_maintenance_records_table` (vehicle_id FK, maintenance_type, status, description, scheduled_date, cost, performed_by, audit fields, soft deletes)
- Enums: `MaintenanceType` (scheduled, repair, inspection, other), `MaintenanceStatus` (pending, in_progress, completed, cancelled)
- Model: `MaintenanceRecord` with Auditable, HasFactory, SoftDeletes, vehicle/performedBy/createdBy/updatedBy relationships
- Service: `GarageService` with list (filtered by status/type/vehicle/date range), create, update, delete, start, complete, cancel — all audit-logged, with business rules (defence-plated only, trip conflict check)
- Policy: `MaintenanceRecordPolicy` with viewAny, view, create, update, delete, start, complete, cancel — gated by `garage.*` permissions
- Requests: `StoreMaintenanceRecordRequest`, `UpdateMaintenanceRecordRequest` (validated)
- Controller: `GarageController` (thin, ≤10 lines/method, delegates to GarageService)
- Routes: `apiResource('maintenance', GarageController)` + POST start/complete/cancel under `auth:sanctum`
- Factory: `MaintenanceRecordFactory` with pending/inProgress/completed/cancelled states
- 15 Pest tests (list, create, defence-plated rule, show, update, delete pending, delete in_progress fails, start, start with active trip fails, complete, cancel pending, cancel completed fails, unauthorized, unauthenticated, trip integration)
- Frontend: `src/features/garage/` with types (`MaintenanceRecord`, `GarageFilters`, `CreateMaintenanceData`), schemas (Zod create/update), API client, 7 hooks (list, detail, create, update, delete, start, complete, cancel), pages (`GarageList`, `GarageForm`, `GarageDetail`), 4 tests (list + hook)
- TripService integration: vehicles under active maintenance (`in_progress`) cannot be assigned to trips
- Final: **Backend Pint 169 files PASS, 15 Garage tests + all existing pass (35 Garage assertions), frontend lint 0 errors, build 0 errors, 55 total tests pass**

### Phase 2.7 — Contract Module Complete (date: today)
- Migration `2026_06_21_000007_create_contracts_table` (vehicle_id FK, owner_id FK, contract_number unique, start_date, end_date, status, contract_value, payment_terms, notes, audit fields, soft deletes)
- Enum: `ContractStatus` (active, expired, terminated, cancelled)
- Model: `Contract` with Auditable, HasFactory, SoftDeletes, vehicle/owner/createdBy/updatedBy relationships
- Service: `ContractService` with list (filtered by status/vehicle/owner/date range), create (validates contracted_private vehicle, auto-generates contract_number), update, delete, activate, terminate — all audit-logged
- Policy: `ContractPolicy` with viewAny, view, create, update, delete, processPayments, activate, terminate — gated by `contracts.*` permissions
- Requests: `StoreContractRequest` (vehicle_id, owner_id, start_date, end_date required; contract_value optional), `UpdateContractRequest` (sometimes validation)
- Controller: `ContractController` (thin, ≤10 lines/method, delegates to ContractService) with index/show/store/update/destroy/activate/terminate
- Routes: `apiResource('contracts', ContractController)` + POST activate/terminate under `auth:sanctum`
- Factory: `ContractFactory` with active/expired/terminated/cancelled states
- Updated `RolePermissionSeeder`: added `contracts.delete` to `finance_officer` role
- 17 Pest tests (list, create, defence-plated rejection, show, update, delete, activate, activate-active fails, terminate, terminate-non-active fails, contractor create/update denied, unauthorized, unauthenticated, trip integration with/without active contract — 36 assertions)
- TripService integration: `contracted_private` vehicles require an active, non-expired contract for trip assignment; BusinessRuleException thrown otherwise
- Frontend: `src/features/contract/` with types (`Contract`, `ContractFilters`, `CreateContractData`), schemas (Zod create/update), API client (list/get/create/update/delete/activate/terminate), 6 hooks (useContracts, useContract, useCreateContract, useUpdateContract, useDeleteContract, useActivateContract, useTerminateContract), 3 pages (`ContractList` with status filter + DataTable, `ContractForm` with vehicle/owner selects, `ContractDetail` with KpiCards + activate/terminate actions), 5 tests (3 list + 2 hook)
- Routes added: `/app/contracts`, `/app/contracts/new`, `/app/contracts/:id`, `/app/contracts/:id/edit`
- Final: **Pint 179 files PASS (3 style issues fixed), 17 Contract tests pass + all existing, frontend lint 0 errors (3 pre-existing warnings), frontend build 0 errors, 60 frontend tests pass (17 files)**

### Phase 3.1 — Reports Module Complete (date: today)
- Backend: `ReportType` enum with 7 report types (Fleet Summary, Fuel Consumption, Trip Analysis, Maintenance Summary, Compliance Status, Contract Performance, Passenger Utilization), `ReportService` querying existing data for aggregates with date filtering, `ReportPolicy` (reports.view/reports.generate gates), `ReportController` (index + show), registered gates in AppServiceProvider, 2 new routes under `/api/v1/reports`
- 13 Pest tests: authorization (view/generate), all 7 report types, invalid type error, date filters, unauthenticated rejection
- Frontend: `src/features/report/` with types (`ReportType`, `ReportData`, `ReportFilters`), API client (list, show), 2 React Query hooks (`useReportTypes`, `useReport`), 2 pages (`ReportList` with KPI cards dashboard, `ReportDetail` with date filter inputs and aggregate KPI display)
- Routes: `/app/reports` and `/app/reports/:type`
- All 13 report tests pass individually, frontend lint 0 errors, frontend build 0 errors
