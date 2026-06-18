---
name: database-schema
description: Design or update database schema, relationships, migrations, constraints, naming conventions, and entity catalogs for the transport management platform.
version: 2.0.0
last_updated: 2026-06-07
depends_on: []
---

# Database Schema Skill

## When To Use

Use this skill when working on:
- Database migrations (creating, altering, or dropping tables)
- Table design and column definitions
- Relationships and foreign keys
- Indexes and constraints
- Status and enum fields
- Seeders and factory data
- Entity relationship mapping
- Schema documentation

## Architecture Principles

### 1. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Table name | `snake_case`, plural | `vehicles`, `fuel_transactions` |
| Column name | `snake_case` | `plate_number`, `owner_id` |
| Foreign key | `{singular_related}_id` | `vehicle_id`, `driver_id` |
| Pivot table | Alphabetical singular pair | `driver_vehicle` |
| Index name | `{table}_{columns}_index` | `vehicles_plate_number_index` |
| Unique constraint | `{table}_{columns}_unique` | `vehicles_plate_number_category_unique` |
| Enum column | Descriptive noun or adjective | `status`, `category`, `priority` |
| Boolean column | `is_` or `has_` prefix | `is_active`, `has_insurance` |
| Timestamp column | `{action}_at` | `approved_at`, `expired_at` |

### 2. Standard Column Set

Every table SHOULD include these columns:

```php
// Standard columns for all tables
$table->id();                          // bigint auto-increment primary key
$table->timestamps();                  // created_at, updated_at
$table->softDeletes();                 // deleted_at (when soft delete needed)
```

Audit-sensitive tables SHOULD also include:

```php
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('updated_by')->nullable()->constrained('users');
```

### 3. Enum and Status Columns

Use string columns with CHECK constraints or Laravel enum casting for status fields.
Do NOT use database-level ENUM types (they are hard to alter in PostgreSQL).

```php
// Preferred: string column with known values, backed by PHP enum
$table->string('status', 30)->default('active');
$table->string('category', 30);

// In the Model:
protected $casts = [
    'status' => VehicleStatus::class,
    'category' => VehicleCategory::class,
];
```

PHP enum backing:

```php
enum VehicleCategory: string
{
    case DefencePlated = 'defence_plated';
    case ContractedPrivate = 'contracted_private';
}

enum VehicleStatus: string
{
    case Active = 'active';
    case InMaintenance = 'in_maintenance';
    case Suspended = 'suspended';
    case Decommissioned = 'decommissioned';
}
```

### 4. Foreign Keys and Relationships

Always define explicit foreign keys:

```php
$table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
$table->foreignId('driver_id')->constrained()->nullOnDelete();
$table->foreignId('owner_id')->nullable()->constrained('vehicle_owners')->nullOnDelete();
```

Cascade/restrict rules:
- **cascadeOnDelete**: Use for owned child records (e.g., vehicle_documents → vehicles)
- **nullOnDelete**: Use when the parent can be removed but the child should survive (e.g., trips → driver)
- **restrictOnDelete**: Use when the parent cannot be deleted while children exist (e.g., vehicles → active trips)

### 5. Indexing Strategy

Index columns that are:
- Foreign keys (auto-indexed by `constrained()`)
- Frequently filtered (`status`, `category`, `type`)
- Frequently searched (`plate_number`, `name`, `email`)
- Used in unique constraints

```php
$table->index('status');
$table->index(['category', 'status']);
$table->unique(['plate_number', 'category']);
```

### 6. Document and File Tracking Tables

For compliance documents (license, insurance, inspection, contract):

```php
$table->id();
$table->morphs('documentable');        // documentable_type, documentable_id
$table->string('document_type', 50);   // license, insurance, inspection, etc.
$table->string('file_path');
$table->string('original_filename');
$table->string('mime_type', 50);
$table->unsignedInteger('file_size');
$table->string('status', 30)->default('pending'); // pending, verified, rejected, expired
$table->date('expiry_date')->nullable();
$table->foreignId('verified_by')->nullable()->constrained('users');
$table->timestamp('verified_at')->nullable();
$table->text('rejection_reason')->nullable();
$table->timestamps();
$table->softDeletes();
```

### 7. Audit Log Table

The `audit_logs` table is append-only (no update/delete):

```php
$table->id();
$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
$table->string('action', 50);          // created, updated, deleted, approved, etc.
$table->string('entity_type', 100);    // Vehicle, Driver, FuelTransaction, etc.
$table->unsignedBigInteger('entity_id');
$table->jsonb('old_values')->nullable();
$table->jsonb('new_values')->nullable();
$table->ipAddress('ip_address')->nullable();
$table->string('user_agent')->nullable();
$table->timestamps();

// NO softDeletes — audit logs are immutable
// NO updated_at trigger — rows are never updated
```

### 8. Fuel Transaction Eligibility

From core business rules: only `defence_plated` vehicles can have fuel transactions.
Enforce at the application layer (Policy + Service) AND document at the schema level:

```php
// fuel_transactions table
$table->foreignId('vehicle_id')->constrained();
// Application-level: VehiclePolicy::accessFuel() checks category
// Application-level: FuelService::issue() checks category
```

## Entity Catalog

The full entity list from `context.md`, with categories:

### Core Identity
| Table | Description |
|-------|-------------|
| `users` | System users (all roles) |
| `roles` | Role definitions |
| `permissions` | Permission definitions |
| `role_permission` | Pivot: role ↔ permission |
| `user_role` | Pivot: user ↔ role |

### Fleet
| Table | Description |
|-------|-------------|
| `vehicles` | All vehicles (defence_plated + contracted_private) |
| `vehicle_owners` | Owners of contracted private vehicles |
| `vehicle_documents` | Compliance documents for vehicles |

### Personnel
| Table | Description |
|-------|-------------|
| `drivers` | Registered drivers |
| `driver_documents` | Compliance documents for drivers |
| `driver_violations` | Recorded driver violations |
| `passengers` | Registered passengers / staff transport users |

### Operations
| Table | Description |
|-------|-------------|
| `routes` | Defined transport routes |
| `route_stops` | Stops along a route |
| `trips` | Scheduled trips |
| `trip_assignments` | Driver + vehicle assignment to a trip |

### Fuel
| Table | Description |
|-------|-------------|
| `fuel_transactions` | Fuel issuance records (defence_plated only) |
| `fuel_stock` | Fuel inventory levels |

### Maintenance
| Table | Description |
|-------|-------------|
| `maintenance_requests` | Requests for vehicle maintenance (defence_plated only) |
| `garage_jobs` | Garage work orders |

### Contracts & Finance
| Table | Description |
|-------|-------------|
| `contracts` | Contracts with private vehicle owners |
| `contract_payments` | Payment records for contracts |

### System
| Table | Description |
|-------|-------------|
| `notifications` | User notifications |
| `audit_logs` | Immutable action log |

## Key Relationships

```
users ─┬─< user_role >─ roles ─< role_permission >─ permissions
       ├─< drivers (user_id)
       ├─< passengers (user_id)
       └─< vehicle_owners (user_id)

vehicles ─┬─< vehicle_documents
           ├─< trip_assignments
           ├─< fuel_transactions (defence_plated only)
           ├─< maintenance_requests (defence_plated only)
           └── vehicle_owners (owner_id, for contracted_private)

drivers ─┬─< driver_documents
          ├─< driver_violations
          └─< trip_assignments

routes ─┬─< route_stops
        └─< trips ─< trip_assignments

contracts ─< contract_payments
```

## Migration Ordering

Migrations must be created in dependency order:

1. `users`, `roles`, `permissions` (no foreign deps)
2. `role_permission`, `user_role` (depends on users, roles, permissions)
3. `vehicle_owners` (depends on users)
4. `vehicles` (depends on vehicle_owners)
5. `vehicle_documents` (depends on vehicles)
6. `drivers` (depends on users)
7. `driver_documents`, `driver_violations` (depends on drivers)
8. `passengers` (depends on users)
9. `routes`, `route_stops` (depends on routes)
10. `trips` (depends on routes)
11. `trip_assignments` (depends on trips, vehicles, drivers)
12. `fuel_stock` (no foreign deps)
13. `fuel_transactions` (depends on vehicles, users)
14. `maintenance_requests` (depends on vehicles, users)
15. `garage_jobs` (depends on maintenance_requests)
16. `contracts` (depends on vehicle_owners, vehicles)
17. `contract_payments` (depends on contracts)
18. `notifications` (depends on users)
19. `audit_logs` (depends on users)

## Agent Usage Instructions

When designing or modifying schema:

1. **Read** this SKILL.md and the `templates/` directory
2. **Read** `../../context.md` for the entity catalog and business rules
3. **Read** `../../memory.md` for naming conventions and stable decisions
4. **Check** migration ordering — new tables must come after their dependencies
5. **Use** the migration template from `templates/migration.stub`
6. **Name** columns following the naming conventions table above
7. **Add** indexes for frequently filtered/searched columns
8. **Add** foreign keys with appropriate cascade/restrict rules
9. **Add** standard columns (id, timestamps, soft deletes if needed)
10. **Document** any new entity in the Entity Catalog section
11. **Update** the entity-relationship-map in `examples/` if relationships change
12. **Verify** against `../../.agent/hooks.md` before finalizing
