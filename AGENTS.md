# AGENTS.md

## Project
Integrated Fleet, Passenger, Driver Compliance, Contract, Fuel, and Garage Management System for Ethiopian Defence University.

## Instruction Priority
1. User prompt
2. AGENTS.md (this file)
3. CLAUDE.md
4. `context.md` — system purpose, business rules, roles, modules
5. `memory.md` — stable decisions, naming conventions, module status
6. Relevant `skills/*/SKILL.md` before domain-specific work
7. `.agent/hooks.md` (pre-finalization checklist) and `.agent/settings.md` (guardrails)
8. `docs/adr/*` for architecture decisions
9. `DESIGN.md` before changing layout, UI, dashboards, forms, tables, navigation, or design tokens
10. `skills/*/SKILL.md` templates when generating module code

## Core Business Rules
- Vehicles are `defence_plated` or `contracted_private`.
- Only `defence_plated` vehicles can use university fuel services.
- Only `defence_plated` vehicles can use university garage services.
- Driver, vehicle, and contract compliance must be valid before trip assignment.
- Passenger assignment respects route capacity, approval, and route eligibility.
- Frontend role visibility never replaces backend authorization + policy enforcement.
- Audit logs are immutable (append-only, no update/delete).
- Dates stored in Gregorian; displayed in Gregorian + Ethiopic (Day.js helper in `src/shared/utils/dates.ts`).

## Module Status (from memory.md)
| Module | Backend | Frontend | Tests |
|--------|---------|----------|-------|
| Auth/RBAC | ✅ | ⚙️ scaffold | ✅ |
| All others (Vehicle, Driver, Owner, Passenger, Route, Trip, Fuel, Garage, Compliance, Contract, Reports) | ❌ | ❌ | ❌ |

## Architecture
- **Frontend:** React 19 + TypeScript 6 + Vite 8 SPA (separate repo from backend, NOT Inertia). Entry: `frontend/src/main.tsx`.
- **Backend:** Laravel 13 + PHP 8.4 API. Entry: `backend/routes/api.php` under `api/v1`.
- **Auth:** Sanctum SPA cookie auth (not JWT, not Passport). `axiosClient` uses `withCredentials: true`.
- **State:** TanStack React Query (server), Zustand (client), React Hook Form + Zod (forms).
- **Backend domain structure:** `app/Domain/{Module}/` with `Models/`, `Services/`, `Actions/`, `Policies/`, `Requests/`, `Events/`, `Listeners/`, `DTOs/`, `Enums/`. Controllers are thin wrappers in `app/Http/Controllers/Api/V1/`.
- **Frontend module structure:** `src/features/{module}/` with `api/`, `hooks/`, `components/`, `pages/`, `types/`, `schemas/`. Shared code in `src/shared/`.
- **API docs:** Scramble auto-generates OpenAPI from Laravel (no manual spec).
- **Compliance files:** Encrypted at rest via custom `EncryptedLocalFilesystem` driver (`app/Domain/Shared/Filesystem/`), registered as the `encrypted-local` Flysystem adapter in `config/filesystems.php`. Uses Laravel's `Crypt::encryptString` / `Crypt::decryptString` (backed by `APP_KEY`).
- **Routing:** React Router v7. Routes defined in `src/router/routes.tsx`. Sidebar nav items in `src/shared/config/navigation.ts`.

## Development Setup — Two Independent Docker Stacks

### Local dev (Sail)
```bash
cd backend
docker compose up -d          # starts pgsql + redis + laravel.test
docker compose exec -T laravel.test php artisan serve
docker compose exec -T laravel.test bash
```

### Production stack
```bash
docker compose -f docker-compose.yml up -d   # nginx + php + pgsql + redis
```
**Never run both stacks simultaneously** — they share the same exposed DB/Redis host ports (54320 / 63790) and will conflict.

## Commands

### Backend (run from `backend/` directory)
| Action | Command |
|--------|---------|
| Dev server | `docker compose exec -T laravel.test php artisan serve` |
| Shell | `docker compose exec -T laravel.test bash` |
| Artisan | `docker compose exec -T laravel.test php artisan <cmd>` |
| Composer | `docker compose exec -T laravel.test composer <cmd>` |
| Test (all) | `docker compose exec -T laravel.test php artisan test` (runs Pest/ PHPUnit via `composer test` which does `config:clear` first) |
| Lint | `docker compose exec -T laravel.test vendor/bin/pint` |
| Tinker | `docker compose exec -T laravel.test php artisan tinker` |
| Setup | `composer setup` (install, .env, key:generate, migrate, npm) |

### Frontend (run from `frontend/` directory)
| Action | Command |
|--------|---------|
| Dev | `npm run dev` (Vite dev server, proxies `/api` → `localhost:8000`) |
| Build | `npm run build` (runs `tsc && vite build`) |
| Lint | `npm run lint` (ESLint flat config on `src/`) |
| Format | `npm run format` (Prettier on `src/`) |
| Preview | `npm run preview` |

## Key Conventions
- **Controllers must be ≤10 lines** excluding blanks/comments. Delegate to Services/Actions.
- **Every controller method** must call `$this->authorize()` or use a policy gate.
- **API envelope:** all responses use `{ success, data, message, meta? }` format.
- **Frontend hooks:** must handle loading / error / empty / success states for every data-fetching component.
- **Migrations:** Laravel default timestamp prefix, snake_case table names plural (e.g., `fuel_transactions`).
- **Naming:** `PascalCase` for models/components, `camelCase` for hooks/vars, `snake_case` for DB/api fields.
- **Dual Docker stacks** documented in `memory.md` and `docker-compose.yml` header.

## What's NOT present (do not assume)
- No GitHub Actions CI workflows (`.github/workflows/` is empty/wiped).
- No frontend test setup (Vitest/Testing Library referenced in `memory.md` but not installed in `package.json`).
- No business module pages beyond Auth (all route pages render placeholder `<div>`).
- No `opencode.json` config exists.

## Before Finalizing Any Task
Refer to `.agent/hooks.md` for the full pre-finalization checklist. Key items:
- Run lint + tests.
- Verify business rules were not bypassed.
- Update `context.md` if architecture changed, `memory.md` if stable decisions changed, `docs/adr/` if policy/architecture decisions were made.
- Create or update a `skills/*/SKILL.md` if a workflow becomes repeatable.
