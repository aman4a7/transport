---
name: laravel-backend
description: Build or update Laravel backend modules, APIs, validation, policies, services, file handling, domain logic, and audit-aware workflows.
version: 2.0.0
last_updated: 2026-05-26
depends_on:
  - database-schema
  - rbac-security
---

# Laravel Backend Skill

## When To Use

Use this skill when working on:
- Controllers (API endpoints)
- Form Requests (validation)
- Services (domain logic)
- Actions (single-purpose operations)
- Policies (authorization)
- Models (Eloquent)
- Events and Listeners (side effects, audit)
- File upload endpoints
- Approval workflows
- Business rule enforcement

## Architecture Principles

### 1. Domain-Driven Folder Structure

All domain code lives under `app/Domain/{Module}/`. Each module is self-contained:

```
app/Domain/Vehicle/
├── Models/
│   └── Vehicle.php
├── Services/
│   └── VehicleService.php
├── Actions/
│   ├── CreateVehicleAction.php
│   └── UpdateVehicleAction.php
├── Policies/
│   └── VehiclePolicy.php
├── Requests/
│   ├── StoreVehicleRequest.php
│   └── UpdateVehicleRequest.php
├── Events/
│   ├── VehicleCreated.php
│   └── VehicleStatusChanged.php
├── Listeners/
│   └── LogVehicleStatusChange.php
├── DTOs/
│   └── VehicleData.php
└── Enums/
    ├── VehicleCategory.php
    └── VehicleStatus.php
```

Controllers stay in `app/Http/Controllers/Api/V1/` and are thin wrappers:

```
app/Http/Controllers/Api/V1/
├── VehicleController.php
├── DriverController.php
├── FuelController.php
└── ...
```

### 2. Thin Controllers

Controllers MUST NOT contain business logic. Their only job:
1. Authorize the request (via Policy)
2. Validate input (via Form Request)
3. Delegate to a Service or Action
4. Return the response

**Maximum controller method length: 10 lines** (excluding blank lines and comments).

### 3. Service Layer

Services contain multi-step domain logic and orchestrate Actions. Services:
- Are injected via constructor dependency injection
- Return DTOs or Models, never raw arrays
- Throw domain-specific exceptions
- Never access `Request` directly (receive typed parameters)

### 4. Actions

Actions are single-purpose classes for atomic operations. Use when:
- The operation is reusable across multiple services
- The operation has clear pre/post conditions
- The operation triggers events

### 5. Policy-First Authorization

Every controller method MUST call `$this->authorize()` or use a policy gate. Authorization rules:
- Check user role AND resource ownership/scope
- Defence-plated vehicle checks for fuel/garage operations
- Compliance status checks for assignment operations
- Never rely on frontend visibility for security

### 6. Event-Driven Side Effects

Side effects (audit logs, notifications, compliance updates) fire via Events/Listeners:
- Events are dispatched from Services or Actions
- Listeners handle audit logging, notification dispatch, cache invalidation
- Listeners are queued when possible for performance

### 7. API Response Format

All API responses follow a consistent envelope:

```json
{
  "success": true,
  "data": { ... },
  "message": "Vehicle created successfully",
  "meta": {
    "pagination": { "current_page": 1, "total": 50, "per_page": 15 }
  }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "plate_number": ["The plate number is required."]
  }
}
```

### 8. Route Conventions

```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'store']);
    Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus']);
});
```

Naming rules:
- RESTful resource routes via `apiResource`
- Nested resources for child entities (e.g., vehicle documents)
- Custom actions use descriptive verbs (e.g., `updateStatus`, `approve`)
- Always version under `/api/v1/`

## File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Model | `{Entity}.php` | `Vehicle.php` |
| Service | `{Entity}Service.php` | `VehicleService.php` |
| Action | `{Verb}{Entity}Action.php` | `CreateVehicleAction.php` |
| Policy | `{Entity}Policy.php` | `VehiclePolicy.php` |
| Form Request | `{Verb}{Entity}Request.php` | `StoreVehicleRequest.php` |
| Controller | `{Entity}Controller.php` | `VehicleController.php` |
| Event | `{Entity}{PastVerb}.php` | `VehicleCreated.php` |
| Listener | `{Verb}{Entity}{Noun}.php` | `LogVehicleStatusChange.php` |
| DTO | `{Entity}Data.php` | `VehicleData.php` |
| Enum | `{Entity}{Attribute}.php` | `VehicleCategory.php` |

## Business Rule Enforcement Points

| Rule | Enforcement Location |
|------|---------------------|
| Vehicle category eligibility (fuel/garage) | Policy + Service pre-condition |
| Driver compliance before assignment | TripService pre-condition check |
| Contract validity before route assignment | ContractService validation |
| Document expiry blocking | ComplianceService check |
| Passenger capacity limits | RouteService capacity check |
| RBAC role restrictions | Policy classes |
| Audit trail for sensitive actions | Event Listeners |

## Agent Usage Instructions

When implementing a new backend module:

1. **Read** this SKILL.md and the `templates/` directory
2. **Read** `database-schema/SKILL.md` if creating migrations
3. **Read** `rbac-security/SKILL.md` if adding permissions
4. **Copy** the relevant template stubs from `templates/`
5. **Replace** all `{Entity}`, `{entity}`, `{entities}` placeholders
6. **Implement** domain logic in the Service, not the Controller
7. **Add** Policy methods for every controller action
8. **Dispatch** Events for all state-changing operations
9. **Register** routes in `routes/api.php` under the v1 group
10. **Create** Form Requests for all store/update operations
11. **Verify** against `../../.agent/hooks.md` before finalizing
