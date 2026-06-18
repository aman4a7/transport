# ADR 0004: Sanctum SPA Cookie Authentication

## Status
Accepted

## Context
The system needs authentication for a React SPA frontend communicating with a Laravel API backend.
Options considered: Laravel Sanctum (SPA cookie), Laravel Passport (OAuth2/JWT), plain JWT tokens.

## Decision
Use Laravel Sanctum with SPA cookie-based authentication (stateful sessions, not token strings).

## Consequences
- The SPA and API must share the same top-level domain or be explicitly listed in SANCTUM_STATEFUL_DOMAINS.
- CORS must be configured to allow credentials (withCredentials: true in Axios).
- CSRF protection is handled automatically by Sanctum via the /sanctum/csrf-cookie endpoint.
- No JWT tokens are stored in localStorage — eliminates XSS token theft risk.
- Logout invalidates the session server-side — no token revocation complexity.
- Session driver must be Redis (not file or database) for horizontal scaling.
