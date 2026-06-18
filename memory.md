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
| Auth/RBAC | ✅ | ⚙️ | ✅ | In progress |
| Vehicle | ❌ | ❌ | ❌ | Not started |
| Driver | ❌ | ❌ | ❌ | Not started |
| Owner/Contractor | ❌ | ❌ | ❌ | Not started |
| Passenger | ❌ | ❌ | ❌ | Not started |
| Route | ❌ | ❌ | ❌ | Not started |
| Trip | ❌ | ❌ | ❌ | Not started |
| Fuel | ❌ | ❌ | ❌ | Not started |
| Garage | ❌ | ❌ | ❌ | Not started |
| Compliance | ❌ | ❌ | ❌ | Not started |
| Contract | ❌ | ❌ | ❌ | Not started |
| Reports | ❌ | ❌ | ❌ | Not started |

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

## Future Skills To Add Or Expand
- transport-routing
- notifications-workflow
- payment-ledger
- mobile-driver-passenger-ui
- compliance-workflow
- audit-logging
- testing-strategy
- deployment-ops
- api-contract
- project-scaffold

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
  - Verified Laravel 13 does NOT support native local filesystem encryption; compliance disk requires `spatie/laravel-encrypted-filesystem` package (tracked requirement, not yet installed)
- Phase 0.2 (Backend Foundation) completed with stabilization.

### Pre-Phase-0.4 Stabilization (date: today)
- Fixed .gitignore: added database/*.sqlite and .docker-build.log
- Fixed .env.example: CACHE_STORE=redis, LOG_LEVEL=info, added COMPLIANCE_ENCRYPTION_KEY placeholder
- Filled blank AGENTS.md commands (frontend dev, lint, build)
- Fixed compose.yaml: depends_on now uses condition: service_healthy for pgsql and redis
- Installed spatie/laravel-encrypted-filesystem and configured compliance disk in filesystems.php
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
