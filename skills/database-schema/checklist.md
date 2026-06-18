# Database Schema Checklist

Use this checklist before finalizing any migration or schema work.

## Migration Checklist
- [ ] Migration file uses Laravel timestamp prefix naming
- [ ] Table name is `snake_case` and plural
- [ ] `$table->id()` is present
- [ ] `$table->timestamps()` is present
- [ ] `$table->softDeletes()` is added where records should not be permanently deleted
- [ ] Migration runs forward (`up()`) and backward (`down()`) without errors
- [ ] Migration follows dependency ordering (runs after tables it references)

## Column Checklist
- [ ] Column names are `snake_case`
- [ ] Foreign keys follow `{singular_related}_id` pattern
- [ ] Boolean columns use `is_` or `has_` prefix
- [ ] Timestamp columns use `{action}_at` pattern
- [ ] Status/enum columns use `string` type (not database ENUM)
- [ ] Status columns have a sensible default value
- [ ] Nullable columns are explicitly marked `->nullable()`
- [ ] String columns have appropriate max length when warranted

## Foreign Key Checklist
- [ ] Foreign keys use `->constrained()` to create the database constraint
- [ ] Cascade rule is appropriate: `cascadeOnDelete` for owned children
- [ ] Null rule is appropriate: `nullOnDelete` for optional relationships
- [ ] Restrict rule is applied where deletion should be blocked
- [ ] Nullable foreign keys match optional relationships in the data model

## Index Checklist
- [ ] Foreign key columns are indexed (auto via `constrained()`)
- [ ] Frequently filtered columns have indexes (`status`, `category`, `type`)
- [ ] Frequently searched columns have indexes (`plate_number`, `name`, `email`)
- [ ] Composite indexes are used for multi-column filters
- [ ] Unique constraints enforce business rules (e.g., `plate_number` + `category`)

## Enum / Status Checklist
- [ ] Status values are documented (in SKILL.md or migration comments)
- [ ] PHP backing enum exists in `app/Domain/{Module}/Enums/`
- [ ] Model casts the column to the PHP enum
- [ ] Default value is set in the migration

## Audit Columns Checklist
- [ ] `created_by` / `updated_by` columns are added for audit-sensitive tables
- [ ] Audit log table is append-only (no update, no soft delete)
- [ ] Audit log captures: user_id, action, entity_type, entity_id, old_values, new_values

## Business Rule Checklist
- [ ] Vehicle category (`defence_plated` / `contracted_private`) is enforced
- [ ] Fuel tables only reference defence-plated vehicles (enforced at app layer)
- [ ] Garage tables only reference defence-plated vehicles (enforced at app layer)
- [ ] Contract tables reference contracted-private vehicles
- [ ] Compliance document tables support status + expiry + verification fields

## Seeder Checklist
- [ ] Seeder creates realistic test data
- [ ] Seeder respects foreign key ordering
- [ ] Seeder creates users with all defined roles
- [ ] Seeder creates vehicles in both categories
- [ ] Seeder creates compliance documents in various statuses
- [ ] Seeder is idempotent (safe to run multiple times)
