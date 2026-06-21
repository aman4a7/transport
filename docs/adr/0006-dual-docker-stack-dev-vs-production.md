# ADR 0006: Separate Development (Sail) and Production Docker Stacks

## Status
Accepted

## Context
Local development uses Laravel Sail (backend/compose.yaml) with developer-friendly defaults
(custom ports 8000/54320/63790, APP_DEBUG enabled by default, no Nginx).
Production requires a hardened stack: Nginx reverse proxy, PHP-FPM, locked-down environment
defaults, and standard ports. A second, independent docker-compose.yml was created at the
project root for this purpose, using docker/nginx/ and docker/php/ configuration.

## Decision
Maintain two independent Docker Compose stacks:
1. backend/compose.yaml — Sail-based local development stack
2. docker-compose.yml (root) — Nginx + PHP-FPM + PostgreSQL + Redis production stack

These stacks MUST NOT run simultaneously. Both bind to the same default host ports for
PostgreSQL (54320) and Redis (63790) unless overridden via environment variables, and will
conflict if started at the same time.

## Consequences
- Developers must stop the Sail stack (cd backend && docker compose down) before starting
  the production stack locally for testing, and vice versa.
- Environment files are NOT shared: backend/.env is for Sail, root .env is for the production
  stack. Editing the wrong one is a common mistake — consider adding a startup check script
  that warns if both stacks' containers are detected running simultaneously.
- CI does not use either Docker stack directly; it provisions PostgreSQL and Redis as GitHub
  Actions services instead (see .github/workflows/ci.yml).
- A future improvement could add a Makefile or shell script wrapper (e.g., make dev, make prod)
  to reduce the risk of stack confusion.
