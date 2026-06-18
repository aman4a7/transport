# ADR 0001: Use React + Laravel + PostgreSQL

## Status
Accepted

## Context
The project needs a maintainable full-stack architecture for dashboards, forms, workflows, secure uploads, and reporting.

## Decision
Use React for the frontend, Laravel for the backend API and domain logic, and PostgreSQL for the primary database.

## Consequences
- Clear separation between frontend and backend concerns
- Strong support for forms, validation, RBAC, and reporting
- Requires maintaining both frontend and backend environments
