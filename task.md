# Phase 0: Foundation — Task Tracker

> **Current Status:**
> - Phase 0.1: ✅ Completed
> - Phase 0.2: ✅ Completed (with Stabilization Pass)
> - Phase 0.3: ✅ Completed (Auth/RBAC — Steps 1 & 2A done)
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
- [x] Compliance storage: verified Laravel 13 does NOT support native local encryption; tracked requirement for `spatie/laravel-encrypted-filesystem` package before document upload features

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
- [x] Install spatie/laravel-encrypted-filesystem + configure compliance disk
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

## 0.8 — Auth Module (Frontend)
- [ ] Create AuthContext provider
- [ ] Create LoginPage
- [ ] Create ForgotPasswordPage
- [ ] Create ProtectedRoute component
- [ ] Create useAuth hook
- [ ] Create usePermission hook

## 0.9 — Documentation Updates
- [ ] Update AGENTS.md with real commands
- [ ] Update context.md with implementation state
- [ ] Create ADR-0003 for TypeScript + SPA decision
