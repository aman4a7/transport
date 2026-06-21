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
