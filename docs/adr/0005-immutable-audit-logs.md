# ADR 0005: Immutable Append-Only Audit Logs

## Status
Accepted

## Context
The system handles sensitive government/defence operations including fuel issuance, compliance approvals,
contract changes, and permission assignments. A tamper-evident audit trail is required.

## Decision
The audit_logs table is append-only. No UPDATE or DELETE operations are permitted on audit log rows.
This is enforced at the application layer: AuditLogService only exposes a log() method with no edit or delete methods.
Future hardening may add a database-level trigger to block UPDATE/DELETE on the table.

## Consequences
- AuditLogService must never expose update() or delete() methods.
- No soft deletes on audit_logs (deleted_at column must not be added).
- Log queries must be read-only; filtering by subject, actor, action, and date range is supported via indexes.
- Storage will grow indefinitely — a log archiving/rotation strategy must be defined before production.
- The Auditor role has read-only access to audit_logs via the audit_log.view permission.
