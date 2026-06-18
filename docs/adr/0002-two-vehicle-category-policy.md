# ADR 0002: Two vehicle categories with policy-based service eligibility

## Status
Accepted

## Context
The university operates both defence-plated vehicles and contracted private vehicles.

## Decision
Model two explicit vehicle categories:
- defence_plated
- contracted_private

Only defence-plated vehicles are eligible for internal fuel and garage services.
Contracted private vehicles may participate in transport operations but are excluded from internal fuel and garage workflows.

## Consequences
- Fuel and garage logic must enforce eligibility checks
- Vehicle registration and reporting must clearly expose category and eligibility
- Contracted vehicle workflows require separate contract and document handling
