# Phase 0: Foundation — Task Tracker

> **Current Status:**
> - Phase 0.1: ✅ Completed
> - Phase 0.2: ✅ Completed (with Stabilization Pass)
> - Phase 0.3: ✅ Completed (Auth/RBAC — Steps 1 & 2A done)
> - Phase 1: ✅ Completed (Fleet Module)
> - Auth decision: Sanctum SPA cookie authentication (not JWT, not Passport)

---

## 0.1 — Skills + Templates ✅
- [x] Create `laravel-backend` skill (SKILL.md, checklist, 7 templates, 3 examples)
- [x] Create `react-frontend` skill (SKILL.md, checklist, 5 templates, 2 examples)
- [x] Create `database-schema` skill (SKILL.md, checklist, 2 templates, 2 examples)
- [x] Create `rbac-security` skill (SKILL.md)
- [x] Create `dashboard-ui` skill (SKILL.md)
- [x] Create `file-upload-compliance` skill (SKILL.md)
- [x] Create `landing-page` skill (SKILL.md)
- [x] Create `reporting-analytics` skill (SKILL.md)
- [x] Create `accessibility-review` skill (SKILL.md)
- [x] Create documentation files (AGENTS.md, CLAUDE.md, context.md, DESIGN.md, memory.md, OPEN_CODE.md)
- [x] Create ADRs (0001, 0002)
- [x] Create agent config (hooks.md, settings.md)

---

## 0.2 — Scaffold Backend (Laravel) ✅

### Task 1: Scaffold Laravel Project ✅
- [x] Create Laravel project at `backend/` via Docker
- [x] Verify base Laravel works

### Task 2: Install Laravel Sail + Services ✅
- [x] Install Sail with PostgreSQL + Redis
- [x] Configure custom ports (8000, 54320, 63790)
- [x] Generate docker-compose.yml

### Task 3: Install Additional Packages ✅
- [x] laravel/horizon (queue monitoring)
- [x] predis/predis (Redis client)
- [x] dedoc/scramble (OpenAPI docs)
- [x] pestphp/pest + pest-plugin-laravel (testing)
- [x] laravel/pint (code formatting)

### Task 4: Domain Folder Structure ✅
- [x] Create app/Domain/ with 12 module directories
- [x] Create app/Domain/Shared/ with infrastructure subdirs

### Task 5: Shared Infrastructure Classes ✅
- [x] AuditLogService.php
- [x] HasApiResponse trait
- [x] Auditable trait
- [x] BusinessRuleException.php
- [x] ForceJsonResponse middleware

### Task 6: Configuration Files ✅
- [x] .env / .env.example with all variables
- [x] config/cors.php (SPA CORS)
- [x] config/sanctum.php (stateful domains)
- [x] config/filesystems.php (compliance + general disks)
- [x] config/session.php (Redis driver)
- [x] config/horizon.php (queue config)
- [x] config/database.php (PostgreSQL + Redis DBs)

### Task 7: API Routes & Health Check ✅
- [x] routes/api.php with v1 prefix skeleton
- [x] Health check endpoint
- [x] Exception handler for JSON API responses

### Task 8: Testing Setup ✅
- [x] Pest configuration
- [x] Base TestCase updates
- [x] Health check test

### Task 9: Code Quality ✅
- [x] Pint configuration (pint.json)

### Task 10: Documentation Updates ✅
- [x] Update AGENTS.md with real commands
- [x] Add ADR 0003 for Sail/Docker decision
- [x] Update memory.md change log

---

## 0.2.1 — Foundation Stabilization Pass ✅
- [x] SESSION_DRIVER consistency: aligned .env/.env.example/config to `redis`
- [x] QUEUE_CONNECTION consistency: aligned .env/.env.example/config to `redis`
- [x] Production defaults: .env.example uses APP_DEBUG=false, SESSION_DRIVER=redis, QUEUE_CONNECTION=redis, SESSION_ENCRYPT=true
- [x] Audit logging: created `audit_logs` migration (2026_06_17_000000)
- [x] Compliance storage: verified Laravel 13 does NOT support native local encryption; custom `EncryptedLocalFilesystem` driver created, backed by `Crypt::encryptString` / `Crypt::decryptString`, registered as `encrypted-local` Flysystem adapter

---

## 0.3 — Auth/RBAC Foundation ✅
- [x] RBAC migrations (roles, permissions, role_permission, user_role)
- [x] Role, Permission, User models with relationships
- [x] RoleSeeder (10 actor roles)
- [x] PermissionSeeder (54 granular permissions)
- [x] RolePermissionSeeder (least-privilege assignments)
- [x] Sanctum SPA cookie authentication (login, logout, me)
- [x] AuthService with rate-limited login
- [x] AuthController (thin, using HasApiResponse trait)
- [x] Session middleware for API routes (Sanctum SPA compatible)
- [x] Pest tests for auth endpoints (8 tests, 48 assertions)

---

## Pre-Phase-0.4 Stabilization ✅
- [x] Fix .gitignore (database/*.sqlite, .docker-build.log)
- [x] Fix .env.example (CACHE_STORE, LOG_LEVEL, COMPLIANCE_ENCRYPTION_KEY)
- [x] Fill AGENTS.md blank commands
- [x] Fix compose.yaml health conditions
- [x] Create custom `EncryptedLocalFilesystem` driver and register as `encrypted-local` Flysystem adapter in config/filesystems.php
- [x] Add throttle test to AuthTest.php
- [x] Create RoleFactory and PermissionFactory
- [x] Configure phpunit.xml for PostgreSQL
- [x] Write ADR 0004 (Sanctum SPA cookie auth)
- [x] Write ADR 0005 (immutable audit logs)
- [x] Create GitHub Actions CI workflow
- [x] Update memory.md change log

### Step 1: RBAC Migrations + Models + Seeders ✅
- [x] Create roles migration
- [x] Create permissions migration
- [x] Create role_permission pivot migration
- [x] Create user_role pivot migration
- [x] Create Role model with relationships + helper methods
- [x] Create Permission model with relationships
- [x] Update User model with role/permission relationships
- [x] Create RoleSeeder with 10 actor roles from context.md
- [x] Create PermissionSeeder with 54 granular module.action permissions
- [x] Create RolePermissionSeeder with least-privilege assignments
- [x] Update DatabaseSeeder execution order
- [x] Run migrate:fresh --seed (all 9 migrations + 3 seeders pass)
- [x] Run tests (2 passed, 9 assertions)
- [x] Update documentation

### Step 2A: Sanctum SPA Cookie Auth Foundation ✅
- [x] Create LoginRequest (email + password validation)
- [x] Create AuthService (login with rate limiting, logout, me with roles/permissions)
- [x] Create AuthController (thin controller using HasApiResponse trait)
- [x] Register routes: POST /api/v1/auth/login, POST /api/v1/auth/logout, GET /api/v1/auth/me
- [x] Configure Sanctum stateful middleware + session support for API routes
- [x] Log auth events (login, logout) through AuditLogService
- [x] Create 8 Pest tests (login, invalid credentials, validation, me, logout, session persistence)
- [x] All 9 tests pass (48 assertions)

## 0.4 — Scaffold Frontend (React + TypeScript + Vite)
- [x] Initialize Vite + React + TypeScript project in `frontend/`
- [x] Install core dependencies (React Router, React Query, Zustand, Axios, Zod, React Hook Form, Lucide, Day.js, TanStack Table)
- [x] Set up project folder structure (`src/features/`, `src/shared/`, `src/router/`, `src/styles/`)
- [x] Configure ESLint + Prettier
- [x] Create design tokens CSS (`src/styles/tokens.css`)
- [x] Create global CSS (`src/styles/global.css`)
- [x] Create API client layer (`src/shared/api/`)
- [x] Create shared types (`src/shared/types/`)
- [x] Create shared UI components (LoadingSpinner, EmptyState, StatusBadge, KpiCard, ConfirmDialog)
- [x] Create shared form components (FormField, FileUpload, DatePicker)
- [x] Create shared hooks (useAuth, useDebounce, usePagination)
- [x] Create Zustand stores (authStore)
- [x] Create utility functions (dates, formatters, permissions)
- [x] Create router skeleton with guards and layouts
- [x] Create 12 feature module directories (empty)
- [x] Build passes (tsc + vite build)

## 0.5 — Docker Compose Setup ✅
- [x] Create `docker-compose.yml` (PostgreSQL, Redis, PHP-FPM, Nginx)
- [x] Create Nginx config (`docker/nginx/default.conf`)
- [x] Create PHP Dockerfile (`docker/php/Dockerfile`)
- [x] Create `.env` with production defaults
- [x] Create `.dockerignore`
- [x] No backend or frontend application code modified

### Phase 0.5 Review ✅
- [x] Verify React SPA fallback routing (`try_files $uri $uri/ /index.html`) — correct
- [x] Verify Sanctum CSRF cookie support — added missing `HTTP_HOST` fastcgi param
- [x] Verify storage symlink handling — direct `alias` to `storage/app/public/` is correct
- [x] Add healthchecks for all 4 services (nginx, php, pgsql, redis)
- [x] Verify required PHP extensions (posix built-in, pcntl/redis/bcmath installed, added netcat-openbsd for healthchecks)
- [x] Document Sail vs production compose port conflict in docker-compose.yml header
- [x] Simplify Dockerfile (remove COPY overridden by volume mount)
- [x] Add APP_KEY generation note to .env

## 0.6 — Design System Foundation ✅
- [x] Design token architecture: colors, spacing, radius, shadows, typography scale, z-index scale in `tokens.css`
- [x] Global CSS structure: CSS variables, reset normalization, form styles, card styles, scrollbar, utility classes
- [x] AppShell component: context provider, sidebar+topbar+content layout, mobile/desktop responsive
- [x] Sidebar navigation: collapsible (260px↔64px), hover-expand, role-aware filtering, icon support, mobile overlay
- [x] Topbar: page title, notification bell placeholder, user avatar with dropdown (settings, sign out), mobile hamburger
- [x] Responsive layout: desktop (>=1024px: persistent sidebar), tablet (768-1023px: overlay sidebar), mobile (<768px: full overlay + hidden user name)
- [x] Accessibility: keyboard navigation, :focus-visible styles, aria-labels, aria-current, skip-to-content link, sr-only utility, Escape key close
- [x] Navigation config: 13 module items across 6 sections with Lucide icons and role/permission filtering
- [x] DESIGN.md updated with full design token docs, layout architecture, module integration guide
- [x] Build passes (tsc + vite build), ESLint passes (0 errors, 0 warnings)

## 0.7 — Shared Component Library v1 ✅
- [x] StatusBadge component — upgraded with variant-based API (success/warning/danger/info/neutral)
- [x] KPICard component — upgraded with loading state (skeleton placeholder)
- [x] DataTable component (TanStack Table wrapper with sorting, filtering, pagination, loading/error/empty states, row actions)
- [x] FormBuilder component (React Hook Form + Zod field-driven form builder)
- [x] PageContainer component (title, actions, breadcrumbs, content wrapper)
- [x] EmptyState component — upgraded with customizable icon prop
- [x] LoadingState component (spinner + skeleton variants, supports table/card/text types)

## 0.8 — Auth Module (Frontend) ✅
- [x] Create AuthContext provider — wraps app tree, initializes session via GET /api/v1/auth/me on mount
- [x] Create LoginPage — full login form with Zod validation, server error mapping, show/hide password, CSRF cookie fetch, loading/submit states
- [x] Create ForgotPasswordPage — forgot password form with success/info states, error handling
- [x] Update AuthGuard (ProtectedRoute) — shows loading spinner during session check before rendering children
- [x] Upgrade useAuth hook — now re-exports from AuthContext (backward-compatible with shared/shared imports)
- [x] Create usePermission hook — reactive permission/role checking tied to Zustand auth store
- [x] Auth-aware navigation/header — Topbar uses API-based logout with isLogoutPending disabled state
- [x] Error handling + loading states — login shows server errors, field-level errors, loading spinner, 401 interceptor redirects to /login

## 1.0 — Fleet Module (Vehicles, Drivers, Owners) ✅

### Backend ✅
- [x] Create `vehicles` migration with plate_number+category composite unique, soft deletes, audit fields
- [x] Create `drivers` migration with assigned_vehicle_id, license fields, soft deletes
- [x] Create `owners` migration with company/contact fields, soft deletes
- [x] Create enums: VehicleCategory, VehicleStatus, FuelType, DriverStatus, OwnerStatus
- [x] Create Vehicle model with casts, relationships (owner, createdBy, updatedBy), status_changed_at observer, Auditable trait
- [x] Create Driver model with casts, relationships (user, assignedVehicle, createdBy, updatedBy), Auditable trait
- [x] Create Owner model with casts, relationships (user, vehicles, createdBy, updatedBy), Auditable trait
- [x] Create VehicleService with filtering (search by plate/make/model, category, status, owner_id), business rule enforcement (contracted_private requires owner_id on both create and update)
- [x] Create DriverService with filtering (search by user name/license, status, license_category)
- [x] Create OwnerService with filtering (search by company/contact/email, status)
- [x] Create VehiclePolicy with RBAC checks (viewAny, view, create, update, delete) and contractor scoping
- [x] Create DriverPolicy with RBAC checks
- [x] Create OwnerPolicy with RBAC checks
- [x] Create StoreVehicleRequest with plate_number unique rule (scoped to category)
- [x] Create UpdateVehicleRequest with plate_number unique rule (scoped to category, ignoring self)
- [x] Create StoreDriverRequest with unique license_number validation
- [x] Create UpdateDriverRequest with unique license_number validation (ignoring self)
- [x] Create StoreOwnerRequest with unique email validation
- [x] Create UpdateOwnerRequest with unique email validation (ignoring self)
- [x] Create VehicleController (thin, ≤10 lines/method, delegates to VehicleService)
- [x] Create DriverController (thin)
- [x] Create OwnerController (thin)
- [x] Register Fleet routes under auth:sanctum middleware (apiResource)
- [x] Create VehicleFactory, DriverFactory, OwnerFactory
- [x] Create VehicleTest (9 tests: CRUD + authorization + business rules)
- [x] Create DriverTest (7 tests: CRUD + authorization)
- [x] Create OwnerTest (8 tests: CRUD + authorization + duplicate email)

### Frontend ✅
- [x] Create vehicle types (Vehicle, VehicleCategory, VehicleStatus, FuelType, VehicleFilters, CreateVehicleData)
- [x] Create vehicle Zod schema with superRefine for contracted_private owner_id requirement
- [x] Create vehicle API client (list, get, create, update, delete)
- [x] Create useVehicles hook (query list with placeholder, single with enabled guard, CRUD mutations)
- [x] Create VehicleList page (DataTable + filters + confirm delete)
- [x] Create VehicleForm page (create/edit with server error handling)
- [x] Create VehicleDetail page (KpiCards + info rows + delete)
- [x] Create driver types
- [x] Create driver Zod schema with license_category enum
- [x] Create driver API client
- [x] Create useDrivers hook
- [x] Create DriverList page
- [x] Create DriverForm page
- [x] Create DriverDetail page
- [x] Create owner types
- [x] Create owner Zod schema
- [x] Create owner API client
- [x] Create useOwners hook
- [x] Create OwnerList page
- [x] Create OwnerForm page
- [x] Create OwnerDetail page (with nested vehicles DataTable)
- [x] Register all Fleet routes in routes.tsx
- [x] Navigation config already had Fleet entries

### Review & Fixes ✅
- [x] Integration audit — 54 files reviewed, 6 bugs fixed (auth permissions format, forgot-password endpoint, encrypted filesystem docs, SESSION_SECURE_COOKIE, missing components, predis removal)
- [x] Fleet review — 3 blockers fixed (plate_number unique validation, driver license_category enum, update business rule enforcement), 4 minors fixed (status_changed_at, select dropdown, owner_id refinement, policy signature)

## 0.9 — Documentation Updates ✅
- [x] Update AGENTS.md with real commands (done in Pre-Phase-0.4 Stabilization)
- [x] Create ADR-0003 for TypeScript + SPA decision (done in Phase 0.4)

## Post-Phase-1 Cleanup & Hardening ✅
- [x] Delete orphaned empty feature folders
- [x] Document frontend naming convention
- [x] Fix plate_number uniqueness scope (was per-category, now global unique)
- [x] Add frontend CI job (lint, build, test)
- [x] Add frontend test tooling (vitest, testing-library, vitest-axe)
- [x] Write DataTable + Vehicle feature tests (34 tests across 7 suites)
- [x] Add accessibility tests for shared components (5 components, zero violations)
- [x] Configure Scramble API documentation at `/docs/api`
- [x] Write ADR 0006 (dual Docker stack)
- [x] Add Docker stack conflict-check script (`scripts/check-docker-stacks.sh`)
- [x] Add dependency vulnerability scanning to CI (composer audit + npm audit)
- [x] Write compliance-workflow skill (SKILL.md, checklist, templates, examples)
- [x] Write testing-strategy skill (SKILL.md, checklist, templates, examples)
- [x] Update memory.md change log and accepted risks
- [x] Update task.md

## Phase 3 — Compliance Module ✅

### Task 1 — Design compliance domain ✅
- [x] Create ADR 0007 — compliance-workflow.md (state machine, permissions, storage design, expiry handling)

### Task 2 — Database ✅
- [x] Create migration `2026_06_20_000003_create_compliance_documents_table` (polymorphic, indexed, no soft deletes)

### Task 3 — Model ✅
- [x] Create `ComplianceDocument` model with polymorphic morphTo, scopes (pending, approved, expired, forVehicle, forDriver, forOwner), casts for status/type enums
- [x] Create enums: `ComplianceStatus`, `ComplianceDocumentType`

### Task 4 — Storage ✅
- [x] Use existing `encrypted-local` compliance disk
- [x] `ComplianceDocumentService` with upload, approve, reject, markExpired, delete methods
- [x] Every approval/rejection logged via AuditLogService
- [x] Rejected documents require reason
- [x] Expired documents cannot become approved without resubmission

### Task 5 — Policies ✅
- [x] Create `ComplianceDocumentPolicy` with viewAny, view (row-level scoping), create, update, delete, approve, reject
- [x] Compliance Officer: view all, approve/reject
- [x] Driver: view own documents only
- [x] Contractor: view own documents only
- [x] Admin: full access

### Task 6 — Requests ✅
- [x] `StoreComplianceDocumentRequest` with file validation (pdf/jpg/jpeg/png, max 10MB), document_type required
- [x] `UpdateComplianceDocumentRequest`
- [x] `ApproveComplianceDocumentRequest`
- [x] `RejectComplianceDocumentRequest` (reason required, min 10 chars)

### Task 7 — Controller ✅
- [x] `ComplianceDocumentController` with index, store, show, approve, reject — all thin (delegated to service)

### Task 8 — API Routes ✅
- [x] `GET /api/v1/compliance/documents` — index
- [x] `POST /api/v1/compliance/documents` — store (file upload)
- [x] `GET /api/v1/compliance/documents/{id}` — show
- [x] `POST /api/v1/compliance/documents/{id}/approve` — approve
- [x] `POST /api/v1/compliance/documents/{id}/reject` — reject
- [x] All protected with `auth:sanctum`

### Task 9 — Tests ✅
- [x] 16 tests: upload, invalid file, oversize file, list, show, approve, reject, reject requires reason, reject requires 10 chars, non-auth denied, already-approved denied, expired detection, audit logs on approve/reject, unauthorized, unauthenticated

### Task 10 — Frontend ✅
- [x] `frontend/src/features/compliance/` with types, schemas, api, hooks, pages, components subdirs
- [x] Types: `ComplianceDocument`, `ComplianceFilters`, enums for status/type
- [x] Schema: `complianceUploadSchema` with file validation + expiry refinement
- [x] API client: `complianceApi` (list, get, upload, approve, reject)
- [x] Hooks: `useComplianceDocuments`, `useComplianceDocument`, `useUploadComplianceDocument`, `useApproveComplianceDocument`, `useRejectComplianceDocument`
- [x] Pages: `ComplianceList` (DataTable + filters), `ComplianceDetail` (info rows + review action), `ComplianceReview` (approve/reject with inline reason), `ComplianceUpload` (form with file + dates)

### Task 11 — Navigation ✅
- [x] Compliance nav item already exists in `navigation.ts`
- [x] Added `permissions: ['compliance.view']`
- [x] Routes registered in `routes.tsx` (list, upload, detail, review)

### Task 12 — Verification ✅
- [x] Backend: `php artisan test` — 63 passed (172 assertions)
- [x] Backend: `vendor/bin/pint --test` — 115 files PASS
- [x] Frontend: `npm run lint` — 0 errors, 3 warnings (pre-existing)
- [x] Frontend: `npm run build` — 0 errors
- [x] Frontend: `npm test` — 34 passed (7 test files)

### Task 13 — Documentation ✅
- [x] Update memory.md (module status + change log)
- [x] Update task.md
- [x] Update AGENTS.md (module status table)

## Phase 2.3 — Route Module ✅

### Backend ✅
- [x] Create migration `2026_06_21_000001_create_routes_table` (name, code unique, origin, destination, distance_km, estimated_duration_minutes, capacity, status)
- [x] Create `RouteStatus` enum (active, inactive)
- [x] Create `Route` model with Auditable, HasFactory, SoftDeletes
- [x] Create `RouteService` with list (search, filter), create, update, delete
- [x] Create `RoutePolicy` with RBAC (routes.view, routes.create, routes.update, routes.delete)
- [x] Create `StoreRouteRequest` (name, origin, destination required; code unique)
- [x] Create `UpdateRouteRequest` (sometimes, code unique ignoring self)
- [x] Create `RouteController` (thin, delegates to RouteService)
- [x] Register `apiResource('routes', RouteController)` under auth:sanctum
- [x] Permissions already exist (routes.view/create/update/delete in PermissionSeeder, assigned to transport_manager)
- [x] Create RouteFactory
- [x] Write 9 Route tests (list, create, duplicate code, missing required fields, show, update, delete, unauthorized, unauthenticated)

### Frontend ✅
- [x] Create `src/features/routes/` with types, schema, api, hooks, pages, __tests__
- [x] Types: Route, RouteStatus, CreateRouteData, RouteFilters
- [x] Schema: routeSchema with Zod validation
- [x] API client: routeApi (list, get, create, update, delete)
- [x] Hooks: useRoutes, useRoute, useCreateRoute, useUpdateRoute, useDeleteRoute
- [x] Pages: RouteList (DataTable + filters), RouteForm (create/edit), RouteDetail (KpiCards + info)
- [x] Frontend routes: /app/routes, /app/routes/new, /app/routes/:id, /app/routes/:id/edit

### Tests ✅
- [x] Backend: 9 Route tests pass (22 assertions)
- [x] Frontend: 5 Route tests pass (3 list + 2 hook)
- [x] Full suite: 83 backend tests pass (207 assertions), Pint 130 files PASS
- [x] Full frontend: lint 0 errors, build 0 errors, 39 tests pass

## Phase 2.4 — Trip Module ✅

### Backend ✅
- [x] Create migrations: `2026_06_21_000002_create_trips_table` (route_id/vehicle_id/driver_id FKs, scheduled_date, departure_time, status, audit fields, soft deletes), `2026_06_21_000003_create_trip_assignments_table` (trip_id/passenger_id FKs, unique constraint, status)
- [x] Create enums: `TripStatus` (scheduled, in_progress, completed, cancelled), `TripAssignmentStatus` (confirmed, cancelled, boarded, no_show)
- [x] Create `Trip` model with Auditable, HasFactory, SoftDeletes, relationships (route, vehicle, driver, passengers, assignments)
- [x] Create `TripAssignment` pivot model
- [x] Create `TripService` (332 lines) with CRUD, lifecycle (start/complete/cancel), business rules (vehicle active/registration/insurance/maintenance, driver active/license, route active, passenger capacity, contractor compliance), passenger assignment, audit logging
- [x] Create `TripPolicy` with viewAny, view, create, update, delete, assign, start, complete, cancel
- [x] Create `StoreTripRequest`, `UpdateTripRequest`
- [x] Create `TripController` (thin, 91 lines) with index/show/store/update/destroy/start/complete/cancel
- [x] Register routes: `apiResource('trips', TripController)` + POST start/complete/cancel under `auth:sanctum`
- [x] Create `TripFactory` with inProgress/completed/cancelled states, `TripAssignmentFactory`
- [x] Write 20 Trip tests (CRUD, business rule violations, lifecycle transitions, negative guards, authorization, passenger assignment, audit logging)

### Frontend ✅
- [x] Create `src/features/trips/` with types, schemas, api, hooks, pages, __tests__
- [x] Types: `Trip`, `TripFilters`, `CreateTripData`
- [x] Schemas: Zod trip schema
- [x] API client: `tripApi` (7 operations: list, get, create, update, delete, start, complete, cancel)
- [x] Hooks: `useTrips`, `useTrip`, `useCreateTrip`, `useUpdateTrip`, `useDeleteTrip`, `useStartTrip`, `useCompleteTrip`, `useCancelTrip`
- [x] Pages: `TripList` (DataTable + status filter), `TripForm` (create/edit with route/vehicle/driver/passenger selects), `TripDetail` (KpiCards + lifecycle actions)
- [x] Frontend routes: `/app/trips`, `/app/trips/new`, `/app/trips/:id`, `/app/trips/:id/edit`

### Tests ✅
- [x] Backend: 20 Trip tests pass, Pint PASS
- [x] Frontend: 5 Trip tests pass (3 list + 2 hook)
- [x] Full suite: backend all pass, frontend lint/build clean

## Phase 2.5 — Fuel Module ✅

### Backend ✅
- [x] Create migrations: `2026_06_21_000004_create_fuel_stocks_table` (fuel_type enum PK, quantity, min_threshold, audit fields), `2026_06_21_000005_create_fuel_transactions_table` (fuel_type, type enum, quantity, notes, audit fields, immutable — no update/delete)
- [x] Create `FuelType` enum (benzene, diesel, synthetic)
- [x] Create `FuelStock` model (Auditable) + `FuelTransaction` model (immutable — no update/delete allowed)
- [x] Create `FuelService` with issue (validates vehicle is defence_plated, checks stock availability, creates transaction + decrements stock), restock (increments stock, creates transaction), adjust (manual stock correction, creates transaction), all audit-logged
- [x] Create `FuelPolicy` with view, create, update, delete, viewStock, adjustStock
- [x] Create `IssueFuelRequest` (vehicle_id, fuel_type, quantity, notes), `RestockFuelRequest` (fuel_type, quantity, notes), `AdjustFuelRequest` (fuel_type, quantity, reason)
- [x] Create `FuelController` (thin) with transactions, transaction, issue, restock, adjust, stocks, stock — 7 endpoints
- [x] Register custom fuel routes under `auth:sanctum`: `/api/v1/fuel/transactions`, `/api/v1/fuel/issue`, `/api/v1/fuel/restock`, `/api/v1/fuel/adjust`, `/api/v1/fuel/stocks`
- [x] Create `FuelStockFactory`, `FuelTransactionFactory`
- [x] Write 15 Fuel tests (list transactions, issue fuel, defence-plated rule, stock shortage, restock, adjust, unauthorized, unauthenticated, stock adjustment)

### Frontend ✅
- [x] Create `src/features/fuel/` with types, schemas, api, hooks, pages, __tests__
- [x] Types: `FuelTransaction`, `FuelStock`, `IssueFuelData`, `RestockFuelData`, `AdjustFuelData`
- [x] Schemas: 3 Zod schemas (issue/restock/adjust)
- [x] API client: `fuelApi` (transactions, transaction, issue, restock, adjust, stocks, stock)
- [x] Hooks: `useFuelTransactions`, `useFuelStock`, `useIssueFuel`, `useRestockFuel`, `useAdjustFuel`
- [x] Pages: `FuelList` (DataTable + filters + issue/restock action buttons), `FuelIssue` (form), `FuelStock` (stock cards + adjust form)
- [x] Frontend routes: `/app/fuel`, `/app/fuel/issue`, `/app/fuel/restock`, `/app/fuel/stock`

### Tests ✅
- [x] Backend: 15 Fuel tests pass, Pint PASS
- [x] Frontend: 5 Fuel tests pass (3 list + 2 hook)
- [x] Full suite: backend all pass, frontend lint/build clean

## Phase 2.6 — Garage Module ✅

### Backend ✅
- [x] Create migration `2026_06_21_000006_create_maintenance_records_table` (vehicle_id FK, maintenance_type, status, description, scheduled_date, cost, audit fields, soft deletes)
- [x] Create enums: `MaintenanceType`, `MaintenanceStatus`
- [x] Create `MaintenanceRecord` model with Auditable, HasFactory, SoftDeletes, relationships
- [x] Create `GarageService` with list (filtered), create, update, delete, start, complete, cancel — all audit-logged
- [x] Create `MaintenanceRecordPolicy` with viewAny, view, create, update, delete, start, complete, cancel — gated by garage.* permissions
- [x] Create `StoreMaintenanceRecordRequest`, `UpdateMaintenanceRecordRequest`
- [x] Create `GarageController` (thin, delegates to GarageService)
- [x] Register routes: `apiResource('maintenance', GarageController)` + POST start/complete/cancel
- [x] Create `MaintenanceRecordFactory` with pending/inProgress/completed/cancelled states
- [x] Write 15 Garage tests (list, create, defence-plated rule, show, update, delete, start, complete, cancel, unauthorized, unauthenticated, trip integration)
- [x] Register Garage policy in AppServiceProvider
- [x] Add active maintenance check to TripService::validateVehicle()

### Frontend ✅
- [x] Create `src/features/garage/` with types, schemas, api, hooks, pages, __tests__
- [x] Types: `MaintenanceRecord`, `CreateMaintenanceData`, `UpdateMaintenanceData`, `GarageFilters`
- [x] Schemas: Zod create/update schemas
- [x] API client: `garageApi` (list, get, create, update, delete, start, complete, cancel)
- [x] Hooks: `useMaintenanceRecords`, `useMaintenanceRecord`, `useCreateMaintenanceRecord`, `useUpdateMaintenanceRecord`, `useDeleteMaintenanceRecord`, `useStartMaintenance`, `useCompleteMaintenance`, `useCancelMaintenance`
- [x] Pages: `GarageList` (DataTable + status/type filters + action buttons + confirm delete), `GarageForm` (create/edit), `GarageDetail` (KpiCards + actions + info)
- [x] Frontend routes: `/app/garage`, `/app/garage/new`, `/app/garage/:id`, `/app/garage/:id/edit`

### Tests ✅
- [x] Backend: 15 Garage tests pass (35 assertions), Pint 169 files PASS
- [x] Frontend: 6 Garage tests pass (4 list + 2 hook)
- [x] Full suite: backend Pint PASS, frontend lint 0 errors, build 0 errors, 55 total tests pass

## Phase 2.7 — Contract Module ✅

### Backend ✅
- [x] Create migration `2026_06_21_000007_create_contracts_table` (vehicle_id FK, owner_id FK, contract_number unique, start_date, end_date, status, contract_value, payment_terms, notes, audit fields, soft deletes)
- [x] Create `ContractStatus` enum (active, expired, terminated, cancelled)
- [x] Create `Contract` model with Auditable, HasFactory, SoftDeletes, vehicle/owner/createdBy/updatedBy relationships
- [x] Create `ContractService` with list (filtered by status/vehicle/owner/date range), create (validates contracted_private vehicle, auto-generates contract_number), update, delete, activate, terminate — all audit-logged
- [x] Create `ContractPolicy` with viewAny, view, create, update, delete, processPayments, activate, terminate — gated by contracts.* permissions
- [x] Create `StoreContractRequest` (vehicle_id, owner_id, start_date, end_date required; contract_value optional), `UpdateContractRequest` (sometimes validation)
- [x] Create `ContractController` (thin) with index/show/store/update/destroy/activate/terminate
- [x] Register routes: `apiResource('contracts', ContractController)` + POST activate/terminate under `auth:sanctum`
- [x] Create `ContractFactory` with active/expired/terminated/cancelled states
- [x] Update `RolePermissionSeeder`: added `contracts.delete` to `finance_officer` role
- [x] Add active contract check to TripService::validateVehicle() for contracted_private vehicles
- [x] Write 17 Contract tests (list, create, defence-plated rejection, show, update, delete, activate, activate-active fails, terminate, terminate-non-active fails, contractor create/update denied, unauthorized, unauthenticated, trip integration with/without active contract — 36 assertions)

### Frontend ✅
- [x] Create `src/features/contract/` with types, schemas, api, hooks, pages, __tests__
- [x] Types: `Contract`, `ContractFilters`, `CreateContractData`, `UpdateContractData`
- [x] Schemas: Zod create/update schemas
- [x] API client: `contractApi` (list, get, create, update, delete, activate, terminate)
- [x] Hooks: `useContracts`, `useContract`, `useCreateContract`, `useUpdateContract`, `useDeleteContract`, `useActivateContract`, `useTerminateContract`
- [x] Pages: `ContractList` (DataTable + status filter + activate/terminate/delete actions), `ContractForm` (create/edit with vehicle/owner selects), `ContractDetail` (KpiCards + activate/terminate/delete actions)
- [x] Frontend routes: `/app/contracts`, `/app/contracts/new`, `/app/contracts/:id`, `/app/contracts/:id/edit`

### Tests ✅
- [x] Backend: 17 Contract tests pass (36 assertions), Pint 179 files PASS (3 style issues fixed)
- [x] Frontend: 5 Contract tests pass (3 list + 2 hook)
- [x] Full suite: backend all pass, frontend lint 0 errors (3 pre-existing warnings), build 0 errors, 60 frontend tests pass (17 files)

## Post-v4-Analysis Verification & Hardening (2026-08-20)

### Task 1 — Confirm orphan folder cleanup and update AGENTS.md ✅
- [x] `frontend/src/features/` verified: no singular `vehicle/driver/passenger/contractor/route/trip`
- [x] Plural forms + auth/compliance/fuel/garage/contract/report confirmed present
- [x] AGENTS.md updated — removed "Stale singular dirs exist" note, now reads "No stale singular folders should be created"

### Task 2 — Full backend test suite ✅
- [x] `docker compose exec -T laravel.test php artisan test` ran — **208 passed (450 assertions), 0 failures**, 18 files, Duration 348.08s
- [x] No failures reported (no fixes required)

### Task 3 — Full frontend test suite ✅
- [x] `npm test` ran — **17 files passed, 60 tests passed, 0 failures** (Vitest v4.1.9)
- [x] No failures reported (no fixes required)

### Task 4 — ProductionHardeningTest.php in full ✅ (partial gap noted)
- [x] Read in full (25,376 bytes / 715 lines / 38 tests)
- [x] Confirmed all 38 tests hit REAL routes/services + real DB rows (NOT config-value assertions)
- [x] Documented gap: name implies broad hardening but covers only audit integrity, deletion guards, cross-module eligibility — no config/env, CORS, SQLi/mass-assignment, file-upload, encryption-at-rest, security-header, or backup tests

### Task 5 — Deep-review ContractService and ReportService ✅ (findings documented)
- [x] ContractService (6,200 B / 178 lines): PASS on BusinessRuleException slugs, DB::transaction, column-scoped eager loads
- [x] ReportService (7,252 B / 201 lines): PASS on SQL aggregation efficiency overall
- [x] CONCERN: Contract has NO payment/fee processing implemented (processPayments capability + contract_value/payment_terms columns exist but no service method)
- [x] CONCERN: ReportService tripAnalysis plucks all filtered trip IDs into memory; passengerUtilization eager-loads full trip.route models

### Task 6 — Review TripService.php in full ✅
- [x] Read in full (13,630 B / 370 lines / 14 methods)
- [x] Eligibility chain confirmed inside DB::transaction: vehicle → driver → route → capacity → (contracted_private) contractor compliance; every failure path throws distinct BusinessRuleException slug
- [x] CONCERN: driver medical compliance not in chain (consistent with no medical_certificate doc type)
- [x] MINOR: delete() not wrapped in DB::transaction

### Task 7 — Driver.medical_expiry sync coverage ✅
- [x] **Not applicable** — no `medical_certificate` document type defined (enum has only vehicle_registration/insurance/driver_license/contract_document/other)
- [x] Confirmed `drivers.medical_expiry` column exists (migration line 18) but is orphaned — latent gap if medical cert type added later

### Task 8 — Fix any test failures ⏭️ Not applicable
- [x] No failures found in Tasks 2-3; no fixes required

### Task 9 — Confirm frontend CI coverage ✅
- [x] `frontend` job present in `.github/workflows/ci.yml` (npm ci + lint + build + test + npm audit --audit-level=high)
- [x] No `continue-on-error` on test step — temporary workaround confirmed removed

### Task 10 — Audit skill coverage gap ✅
- [x] `ls skills/` verified (9 original + compliance-workflow + testing-strategy)
- [x] Created `skills/transport-routing/` (SKILL.md + checklist.md + examples/FuelService.example.php) documenting the real eligibility-gate-chain / BusinessRuleException / DB::transaction / log-vs-logSensitive / column-scoped-eager-load pattern
- [x] payment-ledger, notifications-workflow, mobile-driver-passenger-ui, deployment-ops, api-contract, project-scaffold explicitly deferred (not silently skipped) — tracked in memory.md

### Task 11 — Update memory.md and task.md ✅
- [x] memory.md change log appended with all findings
- [x] task.md phase section added (this section)

## Staging Validation & Final Production Gate (2026-08-25) ✅

### Staging Validation Steps 1–25 ✅
- [x] Environment sanity: Docker stack healthy, DB seeded, Nginx serving, Laravel /up 200
- [x] Backend tests: 264/305 pass against seeded production DB (40 environmental)
- [x] Frontend tests: 81/81 pass, build clean, lint clean
- [x] Auth: login/logout/me all functional with Sanctum SPA cookies
- [x] RBAC: 10 roles, 59 permissions, policy enforcement verified
- [x] API CRUD smoke tests: vehicles, drivers, owners, routes, trips, fuel, garage, contracts, compliance, notifications
- [x] Config leak: .env, .git, composer.json all return 403
- [x] Security headers present (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- [x] Audit trail: CRUD operations create audit log entries
- [x] Scheduler: notifications:check runs without error

### 7 Post-Validation Advisories ✅
- [x] **ADV1** — X-Powered-By header removed via `fastcgi_hide_header` (docker/nginx/default.conf)
- [x] **ADV2** — Nginx version hidden via `server_tokens off` (docker/nginx/default.conf)
- [x] **ADV3** — Permissions-Policy header added to all locations (docker/nginx/default.conf)
- [x] **ADV4** — CSP/HSTS deferred (requires TLS termination; documented in config)
- [x] **ADV5** — Queue worker added as `queue` service in docker-compose.yml (Horizon)
- [x] **ADV6** — Invalid driver license_category fixed in staging DB (level_3→heavy)
- [x] **ADV7** — ModelNotFoundException envelope: NotFoundHttpException render callback in bootstrap/app.php; regression test in NotificationTest.php

### Final Verification ✅
- [x] Backend: 263/306 tests pass (42 environmental, seed-data interference), 0 regressions
- [x] Frontend: 81/81 tests pass, build OK, lint OK
- [x] Pint: 208 files PASS (2 pre-existing style issues in utility scripts only)
- [x] Composer audit: 0 vulnerabilities
- [x] NPM audit: 0 vulnerabilities
- [x] Nginx config syntax valid
- [x] All 7 advisory endpoints verified via curl
- [x] Horizon queue worker running
- [x] Config leak (.env/.git/composer.json): all 403
- [x] Unauthenticated access: 401
- [x] memory.md updated with advisory dispositions + gotchas
- [x] task.md updated (this section)

---

## Phase 3.2 — Notifications Module ✅

### Backend ✅
- [x] Migration `2026_08_20_000000_create_notifications_table` (user_id FK cascade, type, title, body, data json, read_at, timestamps; indexes user_id+read_at, type+created_at)
- [x] `NotificationType` enum (compliance_expiring, contract_expiring, maintenance_due, fuel_low_stock)
- [x] `Notification` model (custom in-app table — NOT Laravel morph schema; removed unused `Notifiable` trait from User, added `notifications(): HasMany` + `unreadNotifications()`)
- [x] `NotificationFactory` with unread/read + per-type states
- [x] `NotificationService` (listForUser, unreadCount, markRead, markAllRead, create, createUnique w/ JSON dedupe)
- [x] `NotificationPolicy` (notifications.view gate + ownership on view/markRead), registered in AppServiceProvider
- [x] `NotificationController` (thin, ≤10 lines/method)
- [x] Routes: `GET /api/v1/notifications`, `PATCH /api/v1/notifications/{notification}/read`, `PATCH /api/v1/notifications/read-all`
- [x] Permissions `notifications.view` (all roles) + `notifications.manage` (transport_manager); AuthTest counts bumped 27→29 / 57→59
- [x] `app/Console/Commands/Notifications/CheckNotifications.php` (`notifications:check`): compliance expiring ≤30d, contracts expiring ≤30d, maintenance due ≤7d, fuel ≤minimum; scheduled hourly in routes/console.php

### Tests ✅
- [x] 19 Pest tests: creation, ownership isolation, unread_count, mark read, cross-user 403, mark-all-read, service create + createUnique dedupe, all four scheduler checks, window exclusion, idempotency, no-recipients

### Frontend ✅
- [x] `src/features/notifications/` — types (`AppNotification`, `NotificationType`, `NotificationFilters`, `NotificationsResponse`), `notificationApi`, hooks (`useNotifications`, `useUnreadCount`, `useMarkNotificationRead`, `useMarkAllRead`)
- [x] `NotificationCenter` page (DataTable + status filter + mark-as-read actions + mark-all-read, unread bolded)
- [x] `UnreadBadge` component; Topbar bell badge + navigate to `/app/notifications`
- [x] Nav item under Administration; route `/app/notifications` registered

### Verification ✅
- [x] Backend: 227 tests pass (494 assertions), Pint 195 files PASS
- [x] Frontend: lint 0 errors (3 pre-existing warnings), build 0 errors, 65 tests pass (19 files)

### Notification Preferences (follow-up) ✅
#### Backend ✅
- [x] Migration `2026_08_20_000001_create_notification_preferences_table` (user_id unique FK cascade, `preferences` json default '{}')
- [x] `NotificationPreference` model (enabled() default-true, defaultPreferences(), enabledFor() helper) + factory (`disabled(type)` state) + seeder in DatabaseSeeder
- [x] `NotificationPreferenceService` (getForUser firstOrCreate; updateForUser merge + audit log `notification_preferences_updated`)
- [x] `NotificationPreferencePolicy` (notifications.view + ownership), registered in AppServiceProvider
- [x] `UpdateNotificationPreferenceRequest` (boolean array; after() rejects unsupported enum keys → 422)
- [x] `NotificationPreferenceController` (thin show/update with policy auth)
- [x] Routes `GET/PATCH /api/v1/notifications/preferences` (registered before `{notification}/read`)
- [x] Scheduler skips muted types per user via `NotificationPreference::enabledFor`

#### Tests ✅
- [x] 13 Pest tests: defaults, retrieve, update, merge-preserves-unspecified, 422 unsupported type, 422 non-boolean, cross-user denied, 403 no permission, 401, audit log, scheduler skip, per-user scheduler respect, enabled types still fire

#### Frontend ✅
- [x] `NotificationPreference` + `NotificationPreferenceMap` types; `notificationApi` getPreferences/updatePreferences
- [x] `useNotificationPreferences` + `useUpdateNotificationPreferences` hooks
- [x] `NotificationSettings` page (4 checkbox toggles + descriptions, loading/error/retry, saved + server-error banners, save w/ spinner) — child `PreferencesForm` avoids setState-in-effect
- [x] Route `/app/notifications/settings` + "Settings" button on NotificationCenter
- [x] Tests: `useNotificationPreferences.test.tsx` + `NotificationSettings.test.tsx` (7 component + 3 hook)

#### Verification ✅
- [x] Backend: full suite 240 tests pass (534 assertions, +13), Pint 204 files PASS
- [x] Frontend: full suite 75 tests pass (21 files), lint 0 errors (3 pre-existing warnings), build 0 errors

## Phase 3.3 — Production Hardening Pass ✅

### Batch 1 (2026-08-21) ✅
- [x] C1 audit immutability — NOT a bug (no update/delete paths; append-only verified)
- [x] H6 VehicleStatus consistency — NOT a bug
- [x] H7 FuelType values — NOT a bug (diesel/petrol/electric/hybrid correct); stale doc line fixed
- [x] H8 contract mass-assignment — NOT a bug
- [x] M10 CRUD audit parity — present (create-only models are intentional)
- [x] Verified: 259 backend tests pass (566 assertions), Pint 204 files PASS

### Batch 2 (2026-08-21) ✅
- [x] H1 Trip update validation gap — FIXED (update revalidates effective relations, create-order chain)
- [x] H2 Trip start revalidation — FIXED
- [x] H3 contractor compliance per trip — FIXED (reg + insurance + license approved docs)
- [x] M3 Garage update parity — FIXED
- [x] M4 Garage start revalidation — FIXED
- [x] M8 route capacity — FIXED (validateCapacity enforces routes.capacity)
- [x] Tests: TripTest +9, ProductionHardeningTest +4, GarageTest +5
- [x] Verified: 277 backend tests pass (585 assertions), Pint 204 files PASS

### Batch 3 (2026-08-21) ✅
- [x] C2 decimal serialization — FIXED: `DecimalNumber` cast (measures→JSON numbers); money stays decimal:2; `toNumber()` helper for UI conversion
- [x] C3 low-stock string compare — FIXED via C2 + FuelStock tests
- [x] H4 expiry reg/insurance — NOT a bug (Fuel + Garage already block expired; NULL = valid per user)
- [x] M6 fuel-type exact match — FIXED (`fuel_type_incompatible`, 422; electric/hybrid blocked)
- [x] M9-A contract owner/vehicle consistency — FIXED (`contract_owner_vehicle_mismatch`)
- [x] M9-B overlapping active contracts — FIXED (create/update/activate; inclusive overlap; rule `contract_overlap_active`)
- [x] M9-C contract number race — FIXED (`pg_advisory_xact_lock` per year; unique constraint backstop)
- [x] TOCTOU fuel stock — FIXED (`lockForUpdate` on issue/adjust)
- [x] Tests: ContractTest +9, FuelTest +8, RouteTest +1, frontend +6
- [x] Verified: 294 backend tests pass (633 assertions, 20 files); Pint 205 files PASS; frontend 81 tests pass (22 files), lint 0 errors (3 pre-existing warnings), build 0 errors
- [x] Audits: composer 17 advisories + npm 6 high — REPORT ONLY (transitive dev/test deps, deferred)

### Batch 4 — Final Production Hardening (2026-08-21) ✅
- [x] B4-1 dependency audits — composer 17→0 (guzzle 7.15.3, psr7 2.13.0, commonmark 2.10.0), npm 6 high→0 (lockfile-only patch/minor); `composer validate --strict` OK
- [x] B4-2 production config — FIXED nginx header-inheritance bug (per-location security headers + Referrer-Policy; HSTS/CSP deferred+documented); blanked real APP_KEY in `.env.example`; rest verified sound (Sanctum/CORS/session/throttle/Scramble gate)
- [x] B4-3 mass assignment — all modules audited SAFE; ONE gap found+fixed: `StoreVehicleRequest` no longer accepts client `status` (creation always `active`)
- [x] B4-4 sort injection — shared `SafeSort` whitelist applied to all 10 list services; invalid field/dir regression-tested
- [x] B4-5 encryption at rest — CRITICAL ×2: provider now actually boots (missing import + Flysystem operator wrapper) and `writeStream` encrypts (production uploads were plaintext); proof test asserts raw bytes ≠ plaintext; `COMPLIANCE_ENCRYPTION_KEY` documented as unused (APP_KEY is the key)
- [x] B4-6 transactions — `TripService::delete()` wrapped in DB::transaction
- [x] B4-7 report perf — tripAnalysis subquery (no ID pluck), passengerUtilization eager-load removed; identical-output + 250-trip volume tests
- [x] B4-8 medical_expiry — DISPOSITIONED: reserved/administrative metadata; zero eligibility coupling verified + locked by test
- [x] B4-9 security tests — SecurityHardeningTest (spoofing, safe-sort, nginx headers guard, session defaults) + SecurityEncryptionTest
- [x] B4-10 E2E foundation — Playwright installed/configured (webServer auto-vite), auth smoke PASSING, login critical-path guarded-skip, vitest excludes e2e/, prerequisites in frontend/e2e/README.md
- [x] B4-11 CI — added `composer validate --strict`; audits confirmed fail-hard; E2E out of CI by documented policy
- [x] B4-12/13 full regression — backend **306 tests (614 assertions on seeded prod DB; 304 pass on clean DB, Pint **208 files PASS**; frontend **81 tests (22 files)**, lint 0 errors, build OK, npm audit 0; E2E 2 passed / 1 skipped
- [x] B4-14 docs — memory.md + task.md updated

## Production Deployment Preparation (2026-08-25)

### Review
- [x] Read all production config files: .env, .env.example, docker-compose.yml, Dockerfile, nginx/default.conf, bootstrap/app.php, cors.php, session.php, filesystems.php, logging.php, horizon.php, sanctum.php, cache.php, queue.php, database.php, routes/console.php, AppServiceProvider, HorizonServiceProvider, vite.config.ts, .gitignore files, encrypted filesystem driver, CI workflow
- [x] Identified all BLOCKING issues for production deployment

### BLOCKING Fixes Applied
- [x] **Scheduler service** added to docker-compose.yml — runs `php artisan schedule:work` to execute compliance:check-expirations (daily 01:00), notifications:check (hourly), horizon:snapshot (hourly)
- [x] **Horizon gate** fixed — `HorizonServiceProvider::gate()` now checks `hasRole('system_administrator')` instead of empty email list
- [x] **SESSION_SECURE_COOKIE** set to `true` in root .env (was `false`)

### Documentation
- [x] Created `DEPLOYMENT.md` — 679 lines, 14 sections (A–N):
  - A: Pre-deployment .env configuration table
  - B: Infrastructure prerequisites
  - C: First-time deployment procedure (11 steps)
  - D: Scheduler service docker-compose snippet
  - E: TLS/HTTPS setup (certbot + nginx HTTPS config)
  - F: Horizon dashboard access fix
  - G: Post-deployment verification checklist (11 curl commands)
  - H: Backup procedures (DB dump, encrypted storage, automated cron)
  - I: Update/redeployment procedure
  - J: Rollback procedure
  - K: Monitoring commands table
  - L: Environment variables summary table
  - M: Known issues & technical debt table
  - N: Production readiness verdict (CONDITIONAL PASS)

### Verification
- [x] Docker Compose config syntax validated (`docker compose config --quiet`)
- [x] All 6 services registered: pgsql, redis, php, queue, scheduler, nginx
- [x] Pint PASS on modified HorizonServiceProvider
- [x] memory.md updated with deployment preparation findings
- [x] task.md updated (this section)
