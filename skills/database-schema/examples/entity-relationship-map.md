# Entity Relationship Map

This document shows the relationships between all 23+ entities defined in `context.md`.

## Legend

- `──<` = One-to-many (parent has many children)
- `>──<` = Many-to-many (via pivot table)
- `──` = One-to-one or belongs-to
- `(FK)` = Foreign key column
- `*` = Eligibility-restricted relationship

---

## Core Identity

```
users ─┬── drivers          (drivers.user_id FK)
       ├── passengers       (passengers.user_id FK)
       ├── vehicle_owners   (vehicle_owners.user_id FK)
       └──< user_role >──< roles >──< role_permission >──< permissions
```

- A `user` may be linked to exactly one `driver`, `passenger`, or `vehicle_owner` record
- `user_role` pivot: assigns roles to users
- `role_permission` pivot: assigns permissions to roles

---

## Fleet

```
vehicle_owners ──< vehicles    (vehicles.owner_id FK, nullable)

vehicles ──< vehicle_documents (vehicle_documents.vehicle_id FK)
```

- `owner_id` is NULL for `defence_plated` vehicles
- `owner_id` is REQUIRED for `contracted_private` vehicles
- Each vehicle can have multiple compliance documents (license, insurance, inspection)

---

## Personnel

```
drivers ──< driver_documents   (driver_documents.driver_id FK)
drivers ──< driver_violations  (driver_violations.driver_id FK)
```

- Drivers have compliance documents (license, medical, etc.)
- Driver violations are recorded for compliance tracking

---

## Operations

```
routes ──< route_stops         (route_stops.route_id FK)
routes ──< trips               (trips.route_id FK)

trips ──< trip_assignments     (trip_assignments.trip_id FK)

trip_assignments ── vehicles   (trip_assignments.vehicle_id FK)
trip_assignments ── drivers    (trip_assignments.driver_id FK)
```

- A route has ordered stops
- A trip is a scheduled instance of a route
- Trip assignments link a vehicle + driver to a trip
- **Pre-conditions enforced at app layer:**
  - Driver must have valid compliance
  - Vehicle must have valid compliance
  - Contract must be active (for contracted_private vehicles)
  - Route capacity must not be exceeded

---

## Fuel (Defence-Plated Only) *

```
fuel_stock               (standalone inventory table)

vehicles ──< fuel_transactions   (fuel_transactions.vehicle_id FK)
users    ──< fuel_transactions   (fuel_transactions.issued_by FK)
```

- **ONLY `defence_plated` vehicles** can have fuel transactions
- Enforced at: `VehiclePolicy::accessFuel()` + `FuelService::issue()`
- `fuel_stock` tracks inventory levels independent of transactions

---

## Maintenance (Defence-Plated Only) *

```
vehicles ──< maintenance_requests   (maintenance_requests.vehicle_id FK)
users    ──< maintenance_requests   (maintenance_requests.requested_by FK)

maintenance_requests ──< garage_jobs  (garage_jobs.maintenance_request_id FK)
```

- **ONLY `defence_plated` vehicles** can have maintenance requests
- Enforced at: `VehiclePolicy::accessGarage()` + `GarageService::createJob()`
- A maintenance request may result in one or more garage jobs

---

## Contracts & Finance

```
vehicle_owners ──< contracts        (contracts.owner_id FK)
vehicles       ──< contracts        (contracts.vehicle_id FK)

contracts ──< contract_payments     (contract_payments.contract_id FK)
```

- Contracts are between the university and a `contracted_private` vehicle owner
- Contracts reference both the owner and the specific vehicle
- Payments are tracked per contract

---

## System

```
users ──< notifications     (notifications.user_id FK)
users ──< audit_logs        (audit_logs.user_id FK)
```

- Notifications are per-user
- Audit logs are **append-only / immutable** (no update, no delete)
- Audit logs track: action, entity_type, entity_id, old_values, new_values

---

## Full Table List (23 tables)

| # | Table | Category | Dependencies |
|---|-------|----------|--------------|
| 1 | `users` | Identity | — |
| 2 | `roles` | Identity | — |
| 3 | `permissions` | Identity | — |
| 4 | `role_permission` | Identity | roles, permissions |
| 5 | `user_role` | Identity | users, roles |
| 6 | `vehicle_owners` | Fleet | users |
| 7 | `vehicles` | Fleet | vehicle_owners |
| 8 | `vehicle_documents` | Fleet | vehicles |
| 9 | `drivers` | Personnel | users |
| 10 | `driver_documents` | Personnel | drivers |
| 11 | `driver_violations` | Personnel | drivers |
| 12 | `passengers` | Personnel | users |
| 13 | `routes` | Operations | — |
| 14 | `route_stops` | Operations | routes |
| 15 | `trips` | Operations | routes |
| 16 | `trip_assignments` | Operations | trips, vehicles, drivers |
| 17 | `fuel_stock` | Fuel | — |
| 18 | `fuel_transactions` | Fuel | vehicles, users |
| 19 | `maintenance_requests` | Maintenance | vehicles, users |
| 20 | `garage_jobs` | Maintenance | maintenance_requests |
| 21 | `contracts` | Finance | vehicle_owners, vehicles |
| 22 | `contract_payments` | Finance | contracts |
| 23 | `notifications` | System | users |
| 24 | `audit_logs` | System | users |

> **Note**: 24 tables listed (context.md listed 23 entities; `contract_payments` was implied by the contracts module but not explicitly listed). The entity catalog in SKILL.md has been updated to include it.
