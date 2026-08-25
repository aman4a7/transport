---
name: transport-routing
description: Build Route, Trip, Fuel, and Garage modules using the project's real eligibility-gate-chain pattern — Compliance sync to entity fields, service-side gating, BusinessRuleException with rule slugs, DB::transaction()-wrapped mutations, and log() vs logSensitive() audit severity.
version: 1.0.0
last_updated: 2026-08-20
depends_on:
  - laravel-backend
  - compliance-workflow
  - rbac-security
---

# Transport Routing Skill (Route / Trip / Fuel / Garage)

## When To Use

Use this skill when working on:
- Route CRUD and route status/capacity logic
- Trip creation, assignment, and lifecycle (start/complete/cancel)
- Fuel issue/restock/adjust and stock integrity
- Garage maintenance records and trip-conflict checks
- Any code that must gate an operation on vehicle/driver/contract compliance

## The Eligibility-Gate-Chain Pattern (core cross-module mechanism)

This is the project's confirmed core mechanism. It is a chain with three links, and **each link has a distinct responsibility**. Never bypass a link.

```
1. Compliance sync (write)     SyncEntityExpiryDate listener
        │   ComplianceDocumentApproved event → updates entity columns
        ▼
2. Entity fields (state)       Vehicle.registration_expiry / insurance_expiry
                               Driver.license_expiry
        │   These columns ARE the source of truth read by services
        ▼
3. Service read (gate)         validateVehicle() / validateDriver() / issueFuel()
        throws BusinessRuleException with a distinct rule slug
```

### Link 1 — Compliance sync (write side)

When a compliance document is approved, an event is dispatched and the listener writes the
document's `expires_at` onto the entity column. Example (real code, `SyncEntityExpiryDate`):

```php
public function handle(ComplianceDocumentApproved $event): void
{
    $document = $event->document;
    $entity = $document->documentable;

    match (true) {
        $entity instanceof Vehicle => match ($document->type) {
            ComplianceDocumentType::VehicleRegistration => $entity->update([
                'registration_expiry' => $document->expires_at,
            ]),
            ComplianceDocumentType::Insurance => $entity->update([
                'insurance_expiry' => $document->expires_at,
            ]),
            default => null,
        },
        $entity instanceof Driver && $document->type === ComplianceDocumentType::DriverLicense => $entity->update([
            'license_expiry' => $document->expires_at,
        ]),
        default => null,
    };
}
```

### Link 2 — Entity fields (the state services read)

Services do NOT query `compliance_documents` directly for the common gates. They read the
denormalized entity columns (`registration_expiry`, `insurance_expiry`, `license_expiry`)
that the listener maintains. Only the contracted-private trip gate queries
`ComplianceDocument` for approved driver license compliance.

### Link 3 — Service read (gate)

Every service that performs a gated mutation re-validates the entity at the start of the
operation. **Never trust a previously-checked value; re-check inside the transaction.**

## BusinessRuleException-with-rule-slug Pattern

All business-rule violations throw `BusinessRuleException` with a **distinct rule slug**.
The slug is stable API contract — tests and the frontend key off it. The message is
human-readable; the rule is machine-readable.

```php
throw new BusinessRuleException(
    message: "Only defence-plated vehicles can use fuel services.",
    rule: 'fuel_vehicle_not_defence_plated',
);
```

Constructor signature (`app/Domain/Shared/Exceptions/BusinessRuleException.php`):
`(string $message, ?string $rule = null, array $context = [], int $code = 422, ?Throwable $previous = null)`.
`render()` returns `{ success: false, message, rule, context }` with HTTP 422.

Real slugs in use today (keep these stable):
- Vehicle: `vehicle_not_operational`, `vehicle_registration_expired`, `vehicle_insurance_expired`, `vehicle_under_maintenance`, `vehicle_no_active_contract`
- Driver: `driver_not_eligible`, `driver_license_expired`, `driver_license_compliance_missing`
- Route: `route_not_active`
- Passenger: `passenger_capacity_exceeded`
- Fuel: `fuel_vehicle_not_defence_plated`, `fuel_vehicle_not_operational`, `fuel_vehicle_registration_expired`, `fuel_vehicle_insurance_expired`, `fuel_insufficient_stock`, `fuel_adjustment_negative`
- Contract: `contract_vehicle_not_contracted`, `contract_cannot_delete_active`, `contract_already_active`, `contract_terminate_invalid_status`
- Trip lifecycle: `trip_cannot_delete_active`, `trip_start_invalid_status`, `trip_complete_invalid_status`, `trip_cancel_invalid_status`
- Contractor compliance: `contractor_compliance_missing`

## DB::transaction()-wrapped-mutation Pattern

Any multi-step write MUST be wrapped in `DB::transaction()`. The rule: **validation + all
writes + audit logging happen inside the same transaction.** If any step throws, the whole
operation rolls back — a partially-applied mutation must never be observable.

Real example (`FuelService::issueFuel`): validates vehicle eligibility → checks stock →
creates `FuelTransaction` → decrements `FuelStock` → audit log. All inside one transaction.

```php
public function issueFuel(array $data): FuelTransaction
{
    return DB::transaction(function () use ($data): FuelTransaction {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        // ... eligibility gates (BusinessRuleException, rule slugs) ...
        $transaction = FuelTransaction::create([...]);
        $stock->decrement('current_quantity', $quantity);
        $this->auditLogService->log('fuel_issued', $transaction, null, null, [...]);
        return $transaction->fresh()->load([...]);
    });
}
```

## log() vs logSensitive() Audit Severity Distinction

`AuditLogService` has two entry points:

- `log(...)` — normal auditable actions (created/updated/deleted, lifecycle transitions).
- `logSensitive(...)` — actions with financial/inventory blast radius. `logSensitive()`
  calls `log()` AND also emits a `Log::warning('Sensitive action: ...')`.

Rule of thumb from the real codebase: **stock adjustments that change an inventory balance
use `logSensitive`; ordinary operations use `log`.** Use `logSensitive` for:
- Stock adjustments (fuel stock correction) — see `FuelService::adjustStock`
- Payment/fee changes
- Any action that changes monetary or inventory balances

Ordinary `log` is fine for: entity CRUD, trip start/complete/cancel, fuel issue/restock
(issue/restock already write an immutable transaction row as their audit record).

## Column-Scoped Eager Loading

Eager-load related models with an explicit column list — never load full related models in
list/detail reads.

```php
// REAL pattern (FuelService, ContractService, TripService):
->with(['vehicle:id,plate_number', 'owner:id,company_name'])
->with(['route:id,name', 'vehicle:id,plate_number', 'driver:id'])
```

## Service Method Shape (uniform across Route/Trip/Fuel/Garage/Contract)

1. `list(array $filters = [])` — paginated query with scoped eager-loads and whitelisted
   filter clauses (status, foreign keys, date_from/date_to), sort + per_page.
2. CRUD methods `create/update/delete` — `DB::transaction` wrapping; create stamps
   `created_by`, update stamps `updated_by`; audit-logged.
3. Lifecycle methods (`start/complete/cancel/activate/terminate`) — `DB::transaction`,
   status-guard throws `BusinessRuleException` with rule slug, audit-log with old/new values.
4. `getWithRelations` / show path — full relations for detail pages.

## Route / Trip / Fuel / Garage specifics

### Route
- `RouteStatus` enum (`active`, `inactive`). `Route::query()` filters on `status`, search on
  name/origin/destination/code. Route delete is guarded (ProductionHardeningTest: cannot
  delete route with assigned trips → 422).

### Trip
- The eligibility gate chain runs in `create()` and `update()` inside the transaction:
  `validateVehicle()` → `validateDriver()` → `validateRoute()` → `validateCapacity()` →
  (contracted_private only) `validateContractorCompliance()`.
- `validateVehicle` checks: status active → registration_expiry → insurance_expiry →
  no in-progress maintenance → (contracted_private) active non-expired contract.
- `validateContractorCompliance` queries `ComplianceDocument` for an approved vehicle doc
  and an approved `driver_license` compliance doc for the driver.
- Lifecycle: `start` (scheduled→in_progress), `complete` (in_progress→completed),
  `cancel` (not completed/cancelled→cancelled). Delete is blocked for in-progress/completed.
- Passenger assignment is stored via `TripAssignment` pivot; capacity check uses
  `vehicle.seating_capacity` vs `count($passengerIds)`.

### Fuel
- Issue gate chain (inside transaction): category `defence_plated` → status active →
  registration_expiry → insurance_expiry → stock availability. Then transaction row
  (quantity NEGATED for issue) + stock decrement, in the same transaction.
- Restock: `firstOrCreate` stock, positive transaction row + increment.
- Adjust: negative-result guard (`fuel_adjustment_negative`), then `logSensitive`.
- `FuelTransaction` is immutable (no update/delete).

### Garage
- Create/start gate: defence-plated only; active-trip conflict check (garage officer cannot
  start maintenance on a vehicle with an active trip); vehicle registration/insurance
  expiry gates (ProductionHardeningTest asserts 422 for expired reg/insurance).
- TripService's `validateVehicle` checks the inverse: a vehicle under in-progress
  maintenance cannot be assigned a trip (`vehicle_under_maintenance`).

## Cross-Module Guardrail

Frontend role visibility NEVER replaces these backend gates. Fuel and Garage modules must
"visibly and technically block ineligible vehicle categories" — the technical block is
these `BusinessRuleException` slugs; the frontend must surface the `rule` field from the
API envelope to the user.

## Agent Usage Instructions

1. **Read** this SKILL.md before touching Route/Trip/Fuel/Garage code.
2. **Read** `laravel-backend/SKILL.md` + `templates/` for backend conventions.
3. **Read** `compliance-workflow/SKILL.md` for the sync listener + document lifecycle.
4. **Model** new code on `examples/` — they are verbatim from the real services.
5. **Re-check** entity fields inside the transaction — never reuse a stale check.
6. **Use distinct rule slugs** for every failure path; never reuse another service's slug
   for a different meaning.
7. **Wrap multi-step writes in `DB::transaction()`** including the audit log.
8. **Use `logSensitive()`** for balance-changing actions (stock adjustment, payments).
9. **Column-scope every eager load** in list/detail queries.
10. **Verify** against `.agent/hooks.md` before finalizing.