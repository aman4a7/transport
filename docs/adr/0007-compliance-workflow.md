# ADR 0007: Compliance Document Workflow

## Status
Accepted

## Context
Vehicles, drivers, and contractors must maintain valid compliance documents (registration, insurance, license, contracts) before being assigned to trips. Documents require approval/rejection workflow, expiry tracking, and audit logging.

## Decision
- Single `compliance_documents` table with polymorphic relationship to vehicles, drivers, and owners.
- State machine: pending → approved/rejected, approved → expired (scheduler), expired → pending (re-upload).
- Documents stored on `encrypted-local` filesystem disk (encrypted at rest via `Crypt::encryptString`).
- Approval/rejection restricted to `compliance_officer` role via `ComplianceDocumentPolicy`.
- Document-level expiry tracked via `expires_at` column; daily command marks expired records.
- Every state change logged via `AuditLogService`.
- No soft deletes (documents are immutable audit records when deleted, only file + DB row removed).

## Consequences
- Compliance check before trip assignment uses document status + expiry date.
- Re-upload creates a new record; old expired records remain for audit trail.
- Permissions `compliance.create`, `compliance.update`, `compliance.delete` added alongside existing `compliance.view`, `compliance.verify`, `compliance.reject`.

## Future Work
- Expiry auto-detection (`compliance:check-expirations` command) and entity-level expiry sync (`SyncEntityExpiryDate` listener) were implemented in a follow-up pass after initial Phase 3 completion. See Change Log entry "Compliance Expiry Loop Closed" in memory.md.
