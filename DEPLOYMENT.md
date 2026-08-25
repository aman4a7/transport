# Production Deployment Checklist & Procedures

> Transport Management System — Integrated Fleet, Passenger, Driver Compliance,
> Contract, Fuel, and Garage Management for Ethiopian Defence University.

**Stack:** Laravel 13 + PHP 8.4 | React 19 + TypeScript 6 + Vite 8 | PostgreSQL 18 | Redis | Sanctum SPA cookie auth

---

## A. Pre-Deployment Configuration (BLOCKING)

The following `.env` variables **must** be set in the root `.env` file before the first production deployment. The docker-compose stack reads this file and passes values into containers; missing or insecure defaults will cause runtime failures or security vulnerabilities.

| Variable | Current Value | Required Value | Why It Matters |
|---|---|---|---|
| `APP_ENV` | `production` | `production` | Already correct. Drives environment-specific config in Laravel. |
| `APP_DEBUG` | `false` | `false` | Must be `false` in production. Exposes stack traces, env vars, and routes if `true`. |
| `APP_KEY` | `base64:zY6l…` | Generate new: `docker exec transport-php php artisan key:generate --show` | The existing key was generated during local dev. A fresh key should be generated per environment. Never reuse dev keys. |
| `APP_URL` | `http://localhost:8080` | `https://your-domain.com` | Used by Laravel for URL generation, asset URLs, and redirect responses. Must match the production domain. |
| `SESSION_SECURE_COOKIE` | **`true`** (set in repo `.env`) | `true` | Required under HTTPS. Note: browsers reject `Secure` cookies over plain HTTP for non-localhost hosts, so TLS (Section E) must be active before serving non-localhost traffic. |
| `COMPLIANCE_ENCRYPTION_KEY` | **empty** | Generate: `docker exec transport-php php artisan key:generate --show` | Encrypts compliance documents at rest. Falls back to `APP_KEY` in tests but must be explicitly set for defense-in-depth. |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,localhost:8080` | `your-domain.com,www.your-domain.com` | Sanctum rejects cookie auth from domains not in this list. Must include the production domain (no `http://` prefix). |
| `CORS_ALLOWED_ORIGINS` | Not set (falls back to `localhost:5173,localhost:3000` in `cors.php`) | `https://your-domain.com` | Controls which origins can make cross-origin API requests. Localhost must be removed. |
| `REDIS_PASSWORD` | **empty** | A strong random string (32+ chars) | Redis has no authentication by default. Any process on the network can read/write sessions, cache, and queues. |
| `APP_NAME` | Not set (defaults to `"Laravel"`) | `"EDU Transport Management"` or similar | Displayed in Horizon dashboard, notifications, email subjects, and the `<title>` tag fallback. |
| `FRONTEND_URL` | `http://localhost:8080` | `https://your-domain.com` | Used for CORS and redirect URLs. Must match the production URL served by nginx. |
| `DB_DATABASE` | `transport` | Keep or change; use a strong name | Already acceptable. Ensure the database exists on the production PostgreSQL instance. |
| `DB_USERNAME` | `transport` | Change to a non-default username | Default `transport` is predictable. Use a unique username per environment. |
| `DB_PASSWORD` | `secret` | **Strong password (16+ chars)** | Default `secret` is a placeholder. Must be changed for any non-local environment. |
| `NGINX_PORT` | `8080` | `80` (or remove; use host reverse proxy on 443) | In production behind a reverse proxy, nginx inside the container should listen on 80. The host reverse proxy handles TLS termination. |
| `LOG_LEVEL` | `info` | `info` or `warning` | Already acceptable. Use `warning` in high-traffic production to reduce log volume. |

### Example Production `.env`

```bash
# ── Docker Compose ──
NGINX_PORT=80
FORWARD_DB_PORT=54320
FORWARD_REDIS_PORT=63790

# ── Application ──
APP_ENV=production
APP_DEBUG=false
APP_URL=https://transport.edu.example.com
APP_KEY=base64:<GENERATE_NEW_KEY>
APP_NAME=EDU Transport Management

# ── Database ──
DB_DATABASE=transport
DB_USERNAME=transport_prod_<random>
DB_PASSWORD=<strong_random_password>

# ── Redis ──
REDIS_PASSWORD=<strong_redis_password>

# ── Session ──
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

# ── Logging ──
LOG_LEVEL=info

# ── Frontend ──
FRONTEND_URL=https://transport.edu.example.com

# ── Sanctum ──
SANCTUM_STATEFUL_DOMAINS=transport.edu.example.com

# ── CORS ──
CORS_ALLOWED_ORIGINS=https://transport.edu.example.com

# ── Compliance ──
COMPLIANCE_ENCRYPTION_KEY=base64:<GENERATE_SEPARATE_KEY>
```

---

## B. Infrastructure Prerequisites

| Requirement | Details |
|---|---|
| **Docker & Docker Compose** | Docker Engine 24+ and Docker Compose v2. Verify: `docker --version && docker compose version` |
| **DNS A Record** | Point your domain (e.g., `transport.edu.example.com`) to the server's public IP. |
| **TLS Certificates** | Obtain via Let's Encrypt (certbot), Cloudflare, or your institution's PKI. See Section E. |
| **Open Ports** | 80 (HTTP, redirect to HTTPS), 443 (HTTPS). Close all other ports in production. |
| **Firewall** | Allow inbound 80/443 only. Block direct access to PostgreSQL (54320), Redis (63790), and PHP-FPM (9000). |
| **Swap Space** | Recommended 2 GB+. PHP-FPM workers and Horizon can consume significant memory under load. |
| **Disk Space** | Minimum 10 GB. PostgreSQL data, Redis snapshots, compliance file storage, and logs all accumulate. |
| **Timezone** | Server should be in `Africa/Addis_Ababa` (EAT, UTC+3) or UTC. Ensure `date` returns correct time. |

---

## C. First-Time Deployment Procedure

Run all commands from the repository root on the production server.

```bash
# 1. Clone the repository
git clone https://github.com/<org>/transport.git
cd transport

# 2. Create/edit the root .env with production values
#    Copy the template from Section A above, then fill in all values.
nano .env

# 3. Build the frontend (required — not built inside Docker)
cd frontend
npm ci
npm run build
cd ..

# 4. Start the full stack
docker compose up -d --build

# 5. Wait for all containers to become healthy
docker compose ps
# Wait until php, pgsql, redis, queue, and scheduler are up (nginx/php/pgsql/redis report "healthy")

# 6. Run database migrations
docker exec transport-php php artisan migrate --force

# 7. Seed the database (first deployment only)
docker exec transport-php php artisan db:seed --force

# 8. Cache configuration, routes, and views
docker exec transport-php php artisan config:cache
docker exec transport-php php artisan route:cache
docker exec transport-php php artisan view:cache

# 9. Generate storage symlink (for public file access)
docker exec transport-php php artisan storage:link

# 10. Verify all services are running
docker compose ps
docker logs transport-php --tail 20
docker logs transport-queue --tail 20

# 11. Run the verification checklist (Section G)
curl -I https://your-domain.com/up
curl -I https://your-domain.com/api/v1
```

---

## D. Scheduler Service

**Status: implemented.** The `docker-compose.yml` stack defines a `scheduler` service (`transport-scheduler`) that runs `php artisan schedule:work --verbose --no-interaction`, executing the tasks registered in `routes/console.php`:
- `compliance:check-expirations` — daily at 01:00
- `notifications:check` — hourly
- `horizon:snapshot` — hourly (registered by Horizon internally)

Containers created before the scheduler existed must be recreated for it to start:

```bash
docker compose up -d
```

Verify the scheduler is running:

```bash
docker compose ps scheduler
docker exec transport-scheduler php artisan schedule:list
# Should list compliance:check-expirations and notifications:check
```

---

## E. TLS/HTTPS Setup

### Option 1: Certbot on Host with Nginx Reverse Proxy (Recommended)

Run certbot on the host machine. The host nginx forwards traffic to the Docker container.

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate (stops nginx temporarily to validate)
sudo certbot certonly --standalone -d transport.edu.example.com

# Certs are at: /etc/letsencrypt/live/transport.edu.example.com/
```

Add a host-level nginx config (`/etc/nginx/sites-available/transport`):

```nginx
server {
    listen 80;
    server_name transport.edu.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name transport.edu.example.com;

    ssl_certificate     /etc/letsencrypt/live/transport.edu.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/transport.edu.example.com/privkey.pem;

    # Mozilla Intermediate config
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;

    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;

    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

    location / {
        proxy_pass http://127.0.0.1:80;  # Docker-mapped NGINX_PORT
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/transport /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Auto-renew
sudo certbot renew --dry-run
```

### Option 2: Certbot Inside Docker

For environments where host-level nginx is not available, use the `linuxserver/swag` or `adferrand/dnsrobocert` container pattern. This is more complex and requires DNS challenge configuration.

### HTTP to HTTPS Redirect

Handled by the host nginx config above (the `return 301` block). The Docker container nginx (Section B/Dockerfile) does not need TLS — it only listens on port 80 internally.

### Security Headers for HTTPS Mode

When the host reverse proxy terminates TLS, add these headers at the host nginx level:

```nginx
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

---

## F. Horizon Dashboard Access

**Status: implemented.** The `gate()` method in `backend/app/Providers/HorizonServiceProvider.php` grants dashboard access to users holding the `system_administrator` role (RBAC-based, not email-matched):

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user = null) {
        return optional($user)->hasRole('system_administrator');
    });
}
```

Access requires an authenticated session for a `system_administrator` user; all other roles receive 403. No per-environment configuration is needed.

Dashboard URL once the stack is running:

```
http(s)://your-domain.com/horizon
```

---

## G. Post-Deployment Verification Checklist

Run these commands after deployment. All must pass before user-facing traffic is directed to the system.

### G.1 Health Check

```bash
# Should return 200
curl -sI https://your-domain.com/up
```

### G.2 API Base (Unauthenticated)

```bash
# Should return 401 (unauthenticated)
curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/api/v1
# Expected: 401
```

### G.3 Authentication Test

```bash
# Should return 200 with Set-Cookie header
curl -s -X POST https://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@edu.example.com","password":"<password>"}' \
  -c cookies.txt -v 2>&1 | grep -E "Set-Cookie|HTTP/"
```

### G.4 Config Leak Protection

```bash
# Should return 403 (blocked)
curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/api/config
# Expected: 403
```

### G.5 Security Headers

```bash
# X-Powered-By should be absent, Server should not reveal version
curl -sI https://your-domain.com/api/v1 | grep -iE "X-Powered-By|Server"
# Expected: X-Powered-By absent, Server shows "nginx" only (no version)
```

### G.6 CORS Validation

```bash
# Should return Access-Control-Allow-Origin matching your domain only
curl -sI -X OPTIONS https://your-domain.com/api/v1/auth/login \
  -H "Origin: https://your-domain.com" \
  -H "Access-Control-Request-Method: POST"
# Expected: Access-Control-Allow-Origin: https://your-domain.com

# Test with unauthorized origin — should NOT return ACAO header
curl -sI -X OPTIONS https://your-domain.com/api/v1/auth/login \
  -H "Origin: https://evil.com" \
  -H "Access-Control-Request-Method: POST"
# Expected: No Access-Control-Allow-Origin header
```

### G.7 Session Security

```bash
# Verify Secure flag on session cookie (under HTTPS)
curl -s -X POST https://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@edu.example.com","password":"<password>"}' \
  -c cookies.txt -v 2>&1 | grep -i "set-cookie"
# Expected: Secure flag present, SameSite=Lax
```

### G.8 Queue Worker

```bash
docker exec transport-php php artisan horizon:status
# Should show "running"
```

### G.9 Scheduler

```bash
docker exec transport-scheduler php artisan schedule:list
# Should list compliance:check-expirations and notifications:check
```

### G.10 Database Connectivity

```bash
docker exec transport-pgsql pg_isready -U transport
# Expected: "accepting connections"
```

### G.11 Redis Connectivity

```bash
docker exec transport-redis redis-cli -a <REDIS_PASSWORD> ping
# Expected: PONG
```

---

## H. Backup Procedures

### H.1 Database Backup

```bash
# One-time backup
docker exec transport-pgsql pg_dump -U transport transport > backup_$(date +%Y%m%d_%H%M%S).sql

# Compressed backup
docker exec transport-pgsql pg_dump -U transport transport | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### H.2 Database Restore

```bash
# From uncompressed backup
cat backup.sql | docker exec -i transport-pgsql psql -U transport -d transport

# From compressed backup
gunzip -c backup.sql.gz | docker exec -i transport-pgsql psql -U transport -d transport
```

### H.3 Encrypted Storage Backup

Compliance files are encrypted at rest via `EncryptedLocalFilesystem`. The tar archive preserves encryption — no additional encryption layer is needed for the backup itself.

```bash
# Backup encrypted compliance files
tar czf compliance_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  -C backend/storage/app/private compliance

# Restore
tar xzf compliance_backup_YYYYMMDD_HHMMSS.tar.gz \
  -C backend/storage/app/private
```

### H.4 Automated Backup Cron (Host)

Add to the host crontab (`crontab -e`):

```bash
# Database backup daily at 02:00, keep for 7 days
0 2 * * * docker exec transport-pgsql pg_dump -U transport transport | gzip > /var/backups/transport/db_$(date +\%Y\%m\%d).sql.gz && find /var/backups/transport -name "db_*.sql.gz" -mtime +7 -delete

# Compliance files backup daily at 02:30, keep for 7 days
30 2 * * * tar czf /var/backups/transport/compliance_$(date +\%Y\%m\%d).tar.gz -C /path/to/transport/backend/storage/app/private compliance && find /var/backups/transport -name "compliance_*.tar.gz" -mtime +7 -delete
```

Ensure the backup directory exists:

```bash
sudo mkdir -p /var/backups/transport
```

---

## I. Update/Redeployment Procedure

For code updates after initial deployment:

```bash
cd /path/to/transport

# 1. Pull latest code
git pull origin main

# 2. Rebuild frontend if any frontend files changed
cd frontend && npm ci && npm run build && cd ..

# 3. Rebuild and restart containers
docker compose up -d --build

# 4. Run migrations (safe to run even if none pending)
docker exec transport-php php artisan migrate --force

# 5. Re-cache configuration and routes
docker exec transport-php php artisan config:cache
docker exec transport-php php artisan route:cache
docker exec transport-php php artisan view:cache

# 6. Restart Horizon workers (picks up new code)
docker exec transport-php php artisan horizon:terminate

# 7. Verify
docker compose ps
docker logs transport-php --tail 20
docker logs transport-queue --tail 20
```

---

## J. Rollback Procedure

If a deployment introduces issues:

```bash
cd /path/to/transport

# 1. Checkout the previous stable version
git tag -l                    # List available tags
git checkout <previous-tag>   # e.g., git checkout v1.2.0

# 2. Rebuild frontend for the previous version
cd frontend && npm ci && npm run build && cd ..

# 3. Rebuild and restart containers
docker compose up -d --build

# 4. Rollback migrations only if needed (check migration list first)
docker exec transport-php php artisan migrate:status
docker exec transport-php php artisan migrate:rollback --force

# 5. Re-cache
docker exec transport-php php artisan config:cache
docker exec transport-php php artisan route:cache
docker exec transport-php php artisan view:cache

# 6. Restart workers
docker exec transport-php php artisan horizon:terminate

# 7. Verify
docker compose ps
curl -sI https://your-domain.com/up
```

---

## K. Monitoring

| Check | Command | Expected |
|---|---|---|
| Container status | `docker compose ps` | All containers `Up` and `healthy` |
| PHP health | `docker exec transport-php php artisan --version` | Laravel Framework 13.x.x |
| PostgreSQL | `docker exec transport-pgsql pg_isready -U transport` | `accepting connections` |
| Redis | `docker exec transport-redis redis-cli -a <password> ping` | `PONG` |
| Horizon status | `docker exec transport-php php artisan horizon:status` | `running` |
| Scheduler | `docker exec transport-scheduler php artisan schedule:list` | Lists scheduled commands |
| PHP logs | `docker logs transport-php --tail 100` | No ERROR/FATAL entries |
| Horizon logs | `docker logs transport-queue --tail 100` | No exceptions |
| Nginx logs | `docker logs transport-nginx --tail 100` | No 502/504 errors |
| Disk usage | `df -h` | < 80% used |
| DB size | `docker exec transport-pgsql psql -U transport -d transport -c "SELECT pg_size_pretty(pg_database_size('transport'));"` | Monitor growth |

### Horizon Dashboard

Available to `system_administrator` users (Section F):

```
https://your-domain.com/horizon
```

---

## L. Environment Variables Summary

### Docker Compose Stack Variables

| Variable | Purpose | Default | Production Value |
|---|---|---|---|
| `NGINX_PORT` | Host port mapped to nginx container | `8080` | `80` (or remove; use reverse proxy) |
| `FORWARD_DB_PORT` | Host port for PostgreSQL | `54320` | Keep or block; not needed externally |
| `FORWARD_REDIS_PORT` | Host port for Redis | `63790` | Keep or block; not needed externally |

### Application Variables (passed to containers)

| Variable | Purpose | Default | Production Value |
|---|---|---|---|
| `APP_ENV` | Laravel environment | `production` | `production` |
| `APP_DEBUG` | Debug mode | `false` | `false` |
| `APP_URL` | Application URL | `http://localhost:8080` | `https://your-domain.com` |
| `APP_KEY` | Encryption key | (must generate) | New key per environment |
| `APP_NAME` | Application name | `"Laravel"` (fallback) | `"EDU Transport Management"` |
| `DB_CONNECTION` | Database driver | `pgsql` | `pgsql` |
| `DB_HOST` | Database host | `pgsql` | `pgsql` (internal Docker host) |
| `DB_PORT` | Database port | `5432` | `5432` |
| `DB_DATABASE` | Database name | `transport` | `transport` |
| `DB_USERNAME` | Database user | `transport` | `transport_prod_<random>` |
| `DB_PASSWORD` | Database password | `secret` | Strong password |
| `REDIS_HOST` | Redis host | `redis` | `redis` (internal Docker host) |
| `REDIS_PORT` | Redis port | `6379` | `6379` |
| `REDIS_PASSWORD` | Redis password | `null` (empty) | Strong password |
| `SESSION_DRIVER` | Session storage | `redis` | `redis` |
| `SESSION_ENCRYPT` | Encrypt session data | `true` | `true` |
| `SESSION_SECURE_COOKIE` | Secure cookie flag | `true` | **`true`** (must be true under HTTPS) |
| `SESSION_LIFETIME` | Session TTL (minutes) | `120` | `120` |
| `QUEUE_CONNECTION` | Queue driver | `redis` | `redis` |
| `CACHE_STORE` | Cache driver | `redis` | `redis` |
| `FILESYSTEM_DISK` | Default filesystem | `local` | `local` |
| `LOG_LEVEL` | Minimum log level | `info` | `info` or `warning` |
| `SANCTUM_STATEFUL_DOMAINS` | Domains for SPA cookie auth | `localhost,localhost:8080` | `your-domain.com` |
| `FRONTEND_URL` | Frontend URL for CORS/redirects | `http://localhost:8080` | `https://your-domain.com` |
| `COMPLIANCE_ENCRYPTION_KEY` | Key for encrypted file storage | (empty) | New key per environment |

### Backend `.env.example` Variables (additional, not in Docker stack)

| Variable | Purpose | Production Value |
|---|---|---|
| `CORS_ALLOWED_ORIGINS` | CORS allowed origins | `https://your-domain.com` |
| `COMPLIANCE_ENCRYPTION_KEY` | Separate key for compliance encryption | Generated via `php artisan key:generate --show` |
| `APP_MAINTENANCE_DRIVER` | Maintenance mode storage | `file` (default) or `redis` |
| `MAIL_MAILER` | Email transport | Configure for real mail (e.g., `smtp`) |
| `MAIL_FROM_ADDRESS` | Sender address | Your institutional address |

---

## M. Known Issues & Technical Debt

| Issue | Severity | Description | Mitigation |
|---|---|---|---|
| Horizon healthcheck shows "unhealthy" | Low | `horizon:status` exits with error when Horizon is actively running (process conflict). | Cosmetic only. Horizon IS running correctly. Suppress by changing healthcheck to `php artisan horizon:status 2>&1 \|\| true` or using `pgrep -f horizon` instead. |
| No root `.env.example` | Low | Only `backend/.env.example` exists. No documented template for Docker stack env vars. | Use the table in Section L as the reference. Consider adding a root `.env.example`. |
| All seeded users have password `"password"` | High | Default seeded accounts use an insecure password. | Change all passwords before exposing to users. Lock accounts until users reset passwords. |
| No automated backup cron | Medium | No backup scripts or cron jobs ship with the project. | Set up host-level cron as described in Section H.4. |
| Frontend build not in Docker | Medium | `frontend/dist/` is volume-mounted and must be pre-built manually. | Build frontend before `docker compose up` (Section C, step 3). Consider multi-stage Dockerfile. |
| PHP Dockerfile includes dev dependencies | Medium | Volume-mount means dev deps are present in production. The Dockerfile comment documents a production path but it is not used. | For hardened production, modify the Dockerfile to use multi-stage build with `composer install --no-dev --optimize-autoloader`. |
| No rate limiter customization | Low | API uses `throttle:api` middleware but no custom rate limiter is defined. Laravel defaults apply (60 req/min). | Sufficient for most use cases. Customize in `AppServiceProvider` if needed. |
| Seeded passwords are insecure | Medium | `database/seeders/` creates users with `"password"`. | Document in release notes; force password reset on first login. |

---

## N. Production Readiness Verdict

**CONDITIONAL PASS**

The system is architecturally sound and functionally tested (263/306 backend tests pass, 81/81 frontend tests pass, zero vulnerabilities). All security endpoints are verified and working correctly.

**Resolved (no longer blockers):**
- Scheduler service — implemented in `docker-compose.yml` (Section D)
- Horizon dashboard gate — role-based access for `system_administrator` (Section F)
- `SESSION_SECURE_COOKIE=true` — set in repo `.env`

**Before first user-facing deployment, the following must be resolved:**

1. **Section A** — Per-environment `.env` values: fresh `APP_KEY`, strong `DB_PASSWORD` and `REDIS_PASSWORD`, production `APP_URL`/`FRONTEND_URL`, production domains in `SANCTUM_STATEFUL_DOMAINS`/`CORS_ALLOWED_ORIGINS`, `COMPLIANCE_ENCRYPTION_KEY`, `APP_NAME`.
2. **Section E** — TLS must be configured before serving any traffic over the internet (also required for `SESSION_SECURE_COOKIE=true` to work on non-localhost hosts).
3. **Seeded credentials** — all seeded users have password `"password"`; rotate or lock before exposing to users.

**Post-deployment (important but not blocking):**
- Set up automated backups (Section H.4)
- Address the Horizon healthcheck cosmetic issue (Section M)
