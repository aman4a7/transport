# AGENTS.md

## Project
Integrated Fleet, Passenger, Driver Compliance, Contract, Fuel, and Garage Management System for Ethiopian Defence University.

## Architecture
- **Backend:** Laravel 13 + PHP 8.4 API. Entry: `backend/routes/api.php` under `api/v1`.
- **Frontend:** React 19 + TypeScript 6 + Vite 8 SPA (separate repo, NOT Inertia). Entry: `frontend/src/main.tsx`.
- **Auth:** Sanctum SPA cookie auth. `axiosClient` (`frontend/src/shared/api/axiosClient.ts`) uses `withCredentials: true`, base `/api/v1`, proxies `/api` → `localhost:8000`.
- **State:** TanStack React Query (server, `staleTime: 30s, retry: 2, refetchOnWindowFocus: false`), Zustand (client), React Hook Form + Zod 4 (forms).
- **Backend structure:** `app/Domain/{Module}/` with Models/Services/Actions/Policies/Requests/Events/Listeners/DTOs/Enums. Controllers thin in `app/Http/Controllers/Api/V1/`. Notifications live in `app/Domain/Notification/` with custom in-app `Notification` + `NotificationPreference` models (NOT Laravel morph notifications).
- **Frontend structure:** `src/features/{module}/` with api/hooks/components/pages/types/schemas. Shared in `src/shared/`. Import alias `@/` → `./src/`.
- **DB/Queue/Cache/Session:** PostgreSQL 18 + Redis (via Horizon).
- **API docs:** Scramble auto-generates OpenAPI from Laravel at `/docs/api`. Production-gated to `system_administrator` via `viewApiDocs` Gate.
- **Compliance files:** Encrypted at rest via custom `EncryptedLocalFilesystem` driver (`app/Domain/Shared/Filesystem/`), registered as `encrypted-local` Flysystem adapter.

## Core Business Rules
- Vehicles are `defence_plated` or `contracted_private`.
- Only `defence_plated` vehicles can use university fuel or garage services.
- Vehicle `fuel_type` must exactly match the requested fuel for issuance (electric/hybrid vehicles never receive liquid fuel).
- A contract's `owner_id` must own its `vehicle_id`; a vehicle may have at most one active contract at any time (inclusive overlap, same-day adjacency counts as overlap).
- Driver, vehicle, and contract compliance must be valid before trip assignment.
- Passenger assignment respects route capacity, approval, and route eligibility.
- Frontend role visibility never replaces backend authorization + policy enforcement.
- Audit logs immutable (append-only, no update/delete).
- Dates stored Gregorian; displayed Gregorian + Ethiopic (Day.js helper in `src/shared/utils/dates.ts`).

## Development — Two Docker Stacks (never run both)
- **Local dev (Sail):** `cd backend && docker compose up -d` (pgsql + redis + laravel.test)
- **Production stack:** `docker compose -f docker-compose.yml up -d` (nginx + php + pgsql + redis)
- Both bind ports 54320 (DB) and 63790 (Redis) — will conflict together.
- Pre-flight: `scripts/check-docker-stacks.sh` detects port conflicts.

## Commands
### Backend (from `backend/`, via Sail container)
| Action | Command |
|--------|---------|
| Shell | `docker compose exec laravel.test bash` |
| Artisan | `docker compose exec laravel.test php artisan <cmd>` |
| All tests | `docker compose exec laravel.test php artisan test` (runs `config:clear` first) |
| Single test | `docker compose exec laravel.test php artisan test --filter <TestName>` |
| Lint | `docker compose exec laravel.test vendor/bin/pint` |
| Setup | `composer setup` (install, .env, key, migrate, npm) |

### Frontend (from `frontend/`)
| Action | Command |
|--------|---------|
| Dev | `npm run dev` |
| Build | `npm run build` (tsc + vite build) |
| Lint | `npm run lint` (ESLint flat config on `src/`) |
| Format | `npm run format` (Prettier) |
| All tests | `npm test` (Vitest, jsdom env) |
| Single test | `npm test -- --run src/features/<module>/__tests__/<file>` |
| E2E | `npm run e2e` (Playwright, chromium; auto-starts Vite — see `frontend/e2e/README.md`; NOT in CI) |

## Key Conventions
- **Controllers ≤ 10 lines** excluding blanks/comments. Delegate to Services/Actions.
- **Every controller method** calls `$this->authorize()` or uses a policy gate.
- **API envelope:** all responses use `{ success, data, message, meta? }`.
- **Frontend hooks:** must handle loading / error / empty / success states.
- **DB tables:** snake_case plural (e.g., `fuel_transactions`).
- **Feature folders:** plural noun matching DB table (e.g., `vehicles/`, `drivers/`). Exception: `auth/`. No stale singular folders should be created.
- **Naming:** `PascalCase` for models/components, `camelCase` for hooks/vars, `snake_case` for DB/api fields.
- **List endpoints:** sort fields must be whitelisted via `App\Domain\Shared\Support\SafeSort` (never pass client `sort_by` straight into `orderBy`).
- **Mass assignment:** clients must never set workflow status or audit fields on create; services own initial state and `created_by`/`updated_by`.
- **Custom Flysystem drivers:** never test them through `Storage::fake()` (it swaps the driver) — override the disk root and `forgetDisk()` instead; stream writes (`writeStream`) must apply the same transformation as string writes.

## Source-of-Truth Order
1. User prompt
2. This file
3. `context.md` — business rules, roles, modules
4. `memory.md` — stable decisions, naming, module status
5. `DESIGN.md` — before changing layout, UI, dashboards, forms, tables, navigation, design tokens
6. `.agent/hooks.md` (pre-finalization checklist) + `.agent/settings.md` (guardrails)
7. `docs/adr/*` — architecture decisions
8. Relevant `skills/*/SKILL.md` before domain-specific work

## What's NOT Present
- No `opencode.json`.
- No integration tests (backend Pest + frontend Vitest unit/component tests only). A Playwright E2E **foundation** exists (`frontend/e2e/`, `playwright.config.ts`: auth smoke + guarded login critical-path) but runs locally/manually — it is excluded from CI by policy (needs pg+redis+seeded users).
- No mobile app.
- No dashboard pages implemented (all render placeholder `<div>Dashboard</div>`).
- Contract payment processing not implemented (columns/policy capability exist).

## CI
- `.github/workflows/ci.yml` — runs backend (lint + test) and frontend (lint + build + test) on push/PR to `main`/`develop`.
