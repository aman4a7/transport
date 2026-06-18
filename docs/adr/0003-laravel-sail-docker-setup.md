# ADR 0003: Laravel Sail + Docker Development Setup

## Status
Accepted

## Context
The team needs a consistent, portable local development environment that matches the on-premises target infrastructure. All developers must run the same stack: PHP 8.4, PostgreSQL, and Redis.

## Decision
Use Laravel Sail (Docker Compose) for local development with:
- PHP 8.4 + FPM (via Laravel's official Sail PHP image)
- PostgreSQL 17 as the primary database
- Redis 7 as the queue/cache/session driver
- Custom ports to avoid conflicts:
  - Application: 8000
  - PostgreSQL: 54320
  - Redis: 63790

Authentication uses Laravel Sanctum with SPA cookie-based auth (no JWT, no Passport).

## Consequences
- One `./vendor/bin/sail up` command boots the entire stack.
- Custom ports prevent collision with other local services.
- Developers must have Docker Desktop installed (no local PHP/Composer required).
- Sail images are maintained by Laravel, reducing custom Dockerfile overhead.
- Production deployment will use separate Docker Compose or Kubernetes config (not Sail).
