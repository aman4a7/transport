---
name: compliance-workflow
description: Build compliance document upload, approval/rejection workflow, expiry tracking, and eligibility gating for vehicle/driver/contract compliance.
version: 1.0.0
last_updated: 2026-06-20
depends_on:
  - laravel-backend
  - file-upload-compliance
  - rbac-security
---

# Compliance Workflow Skill

## When To Use

Use this skill when working on:
- Compliance document upload endpoints (vehicle registration, insurance, driver license, contract documents)
- Approval/rejection workflow (pending → approved/rejected/expired)
- Expiry tracking and automated status transitions
- Trip-assignment eligibility gates (checking vehicle status + driver compliance + contract validity)
- Audit logging for compliance state changes

## Architecture Principles

### 1. Encrypted Document Storage

All compliance documents use the `encrypted-local` Flysystem disk already configured in `config/filesystems.php`:

```php
'compliance' => [
    'driver' => 'encrypted-local',
    'root' => storage_path('app/compliance'),
],
```

The `EncryptedLocalFilesystem` driver lives at `app/Domain/Shared/Filesystem/` and wraps Laravel's `Storage` with `Crypt::encryptString` / `Crypt::decryptString` (backed by `APP_KEY`).

Upload flow:
1. Validate file type/size in Form Request
2. Store via `Storage::disk('compliance')->put($path, $contents)`
3. Save file path reference in `compliance_documents` table (never expose raw file path to client)
4. Return document record with signed URL for download/view

### 2. Compliance Documents Table Schema

```php
Schema::create('compliance_documents', function (Blueprint $table) {
    $table->id();
    $table->morphs('documentable');                     // vehicle, driver, contract, etc.
    $table->string('type');                             // registration, insurance, license, contract, inspection
    $table->string('file_path');                        // encrypted path on compliance disk
    $table->string('original_name');                    // user-friendly filename
    $table->string('mime_type');
    $table->unsignedInteger('file_size');
    $table->string('status')->default('pending');       // pending, approved, rejected, expired
    $table->text('rejection_reason')->nullable();
    $table->foreignId('uploaded_by')->constrained('users');
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->date('expiry_date')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['documentable_type', 'documentable_id']);
    $table->index('status');
    $table->index('type');
});
```

### 3. Approval/Rejection Workflow States

Compliance documents follow this state machine:
```
  pending ──→ approved
  pending ──→ rejected
  approved ─→ expired   (automatic, via scheduler)
  expired  ─→ pending   (re-upload)
```

State transitions:
- `pending → approved`: Compliance Officer role (`compliance_officer`) only, via `ComplianceDocumentController@approve`
- `pending → rejected`: Compliance Officer only, requires `rejection_reason`, via `ComplianceDocumentController@reject`
- `approved → expired`: Scheduled task (`php artisan compliance:check-expirations`) runs daily
- `expired → pending`: Document owner re-uploads a new version

### 4. Expiry Tracking Pattern

Expiry dates are checked at two levels:

**Document-level:** The `compliance_documents.expiry_date` field tracks per-document expiry (e.g., insurance document expires on a date). A scheduled command runs daily to mark documents as `expired` where `expiry_date < now()` and status is `approved`.

**Entity-level:** Vehicles have `registration_expiry` and `insurance_expiry` date columns. These are updated by an observer/listener when a compliance document is approved:
```php
// In an Event Listener
$vehicle->registration_expiry = $document->expiry_date;
$vehicle->save();
```

### 5. Eligibility Gate Pattern

Before a Trip can be assigned, the `TripService` MUST check:

```php
// Pseudocode — TripService.preAssignmentCheck
function preAssignmentCheck(Vehicle $vehicle, Driver $driver): void {
    // 1. Vehicle must be 'active' status
    if ($vehicle->status !== VehicleStatus::Active) {
        throw new BusinessRuleException('Vehicle is not active.');
    }

    // 2. Driver must be 'active' status
    if ($driver->status !== DriverStatus::Active) {
        throw new BusinessRuleException('Driver is not active.');
    }

    // 3. Vehicle registration and insurance must be valid (not expired)
    if ($vehicle->registration_expiry && $vehicle->registration_expiry->isPast()) {
        throw new BusinessRuleException('Vehicle registration has expired.');
    }
    if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast()) {
        throw new BusinessRuleException('Vehicle insurance has expired.');
    }

    // 4. For contracted_private vehicles, contract must be valid
    if ($vehicle->category === VehicleCategory::ContractedPrivate) {
        $contract = $vehicle->activeContract;
        if (!$contract || $contract->end_date->isPast()) {
            throw new BusinessRuleException('No valid contract for this vehicle.');
        }
    }

    // 5. Driver license must be valid
    if ($driver->license_expiry && $driver->license_expiry->isPast()) {
        throw new BusinessRuleException('Driver license has expired.');
    }
}
```

### 6. Policy Structure (template from VehiclePolicy)

Model compliance policies should follow the same row-level-scoping pattern as `VehiclePolicy`:

```php
class ComplianceDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('compliance.view');
    }

    public function view(User $user, ComplianceDocument $document): bool
    {
        if (!$user->hasPermission('compliance.view')) {
            return false;
        }
        // Contractors can only see their own documents
        if ($user->hasRole('contractor')) {
            return $document->documentable_type === 'vehicle'
                && $document->documentable->owner_id === $user->owner?->id;
        }
        return true;
    }

    public function approve(User $user, ComplianceDocument $document): bool
    {
        return $user->hasRole('compliance_officer')
            && $user->hasPermission('compliance.approve');
    }

    public function reject(User $user, ComplianceDocument $document): bool
    {
        return $user->hasRole('compliance_officer')
            && $user->hasPermission('compliance.reject');
    }
}
```

### 7. Required Audit Log Entries

Every state-changing operation on a compliance document MUST be logged via `AuditLogService`:

| Action | Audit Log Entry |
|--------|----------------|
| Document uploaded | `compliance_document.uploaded` — actor, document ID, type |
| Document approved | `compliance_document.approved` — actor, document ID, reviewer ID |
| Document rejected | `compliance_document.rejected` — actor, document ID, reason |
| Document expired | `compliance_document.expired` — system, document ID |
| Expiry threshold warning | `compliance.expiry_warning` — system, entity type, entity ID, days remaining |

## File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Migration | `create_compliance_documents_table` | `2026_06_20_000001_create_compliance_documents_table.php` |
| Model | `ComplianceDocument.php` | `app/Domain/Compliance/Models/ComplianceDocument.php` |
| Service | `{Domain}Service.php` | `app/Domain/Compliance/Services/ComplianceService.php` |
| Policy | `ComplianceDocumentPolicy.php` | `app/Domain/Compliance/Policies/ComplianceDocumentPolicy.php` |
| Controller | `ComplianceDocumentController.php` | `app/Http/Controllers/Api/V1/ComplianceDocumentController.php` |
| Event | `{Entity}{PastVerb}.php` | `ComplianceDocumentApproved.php` |
| Command | `ComplianceCheckExpirations.php` | `app/Console/Commands/ComplianceCheckExpirations.php` |

## Business Rule Enforcement Points

| Rule | Enforcement Location |
|------|---------------------|
| Defence-plated fuel eligibility | FuelService + FuelPolicy |
| Defence-plated garage eligibility | GarageService + GaragePolicy |
| Vehicle compliance before trip | TripService.preAssignmentCheck |
| Driver compliance before trip | TripService.preAssignmentCheck |
| Contract validity for contracted_private | TripService.preAssignmentCheck |
| Document approval by Compliance Officer only | ComplianceDocumentPolicy |
| Expiry auto-detection | Scheduled command |
| Audit logging on state change | Event Listeners |

## Agent Usage Instructions

1. **Read** this SKILL.md before creating compliance-related code
2. **Read** `laravel-backend/SKILL.md` and `templates/` for general backend patterns
3. **Read** `file-upload-compliance/SKILL.md` for upload-specific patterns
4. **Create** the `compliance_documents` migration (append-only-adjacent — supports status updates unlike audit_logs)
5. **Create** ComplianceDocument model with relationships, casts, and scopes
6. **Create** ComplianceService with document upload, approval, rejection, expiry-check methods
7. **Create** ComplianceDocumentPolicy with role + row-level scoping
8. **Create** Form Requests for upload, approve, reject operations
9. **Create** Events + Listeners for audit logging on every state change
10. **Create** scheduled command `compliance:check-expirations` for daily expiry detection
11. **Create** controller endpoints (thin, delegated to service)
12. **Register** routes under `/api/v1/compliance/documents`
13. **Register** the scheduled command in `bootstrap/app.php` kernel schedule
14. **Create** PermissionsSeeder entries for `compliance.view`, `compliance.approve`, `compliance.reject`
15. **Verify** against `.agent/hooks.md` before finalizing
