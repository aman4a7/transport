# E2E Tests (Playwright)

## Prerequisites

- Frontend dev server: `npm run dev` (http://localhost:5173)
- Backend API via Sail: `cd ../backend && docker compose up -d` (proxied at `/api`, direct http://localhost:8000)
- Browsers installed once: `npx playwright install chromium`

## Run

```bash
npm run e2e        # headless
npm run e2e:ui     # interactive UI mode
```

## Test tiers

- `auth.spec.ts` — pure frontend smoke tests; no backend required.
- `login.spec.ts` — full login critical path. Skips automatically unless:
  - the backend health endpoint responds, AND
  - `E2E_EMAIL` / `E2E_PASSWORD` env vars are set with a seeded user.

## Environment variables

| Variable         | Default                 | Purpose                     |
| ---------------- | ----------------------- | --------------------------- |
| `E2E_BASE_URL`   | `http://localhost:5173` | Frontend base URL           |
| `E2E_API_URL`    | `http://localhost:8000` | Backend API base URL        |
| `E2E_EMAIL`      | —                       | Seeded login email          |
| `E2E_PASSWORD`   | —                       | Seeded login password       |

## CI policy

E2E is intentionally NOT wired into CI yet. It requires a running PostgreSQL +
Redis stack and seeded users; add it only after a stable environment strategy
exists (e.g., Playwright webServer booting both stacks).
