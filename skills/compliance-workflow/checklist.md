# Compliance Workflow Checklist

## Database
- [ ] `compliance_documents` migration created (morphs, type, file_path, status, expiry, review fields)
- [ ] Indexes on status, type, documentable polymorphic columns
- [ ] Soft deletes included

## Model
- [ ] ComplianceDocument model with morphTo('documentable') relationship
- [ ] Status enum cast (pending, approved, rejected, expired)
- [ ] File size/original_name/mime_type casts
- [ ] belongsTo relationships for uploadedBy, reviewedBy

## Service
- [ ] Document upload stores via encrypted-local disk
- [ ] Approval sets status=approved, reviewed_by, reviewed_at
- [ ] Rejection sets status=rejected, rejection_reason, reviewed_by, reviewed_at
- [ ] Expiry check queries approved documents with expiry_date < now()

## Policy
- [ ] viewAny/view checks `compliance.view` permission
- [ ] approve checks `compliance_officer` role + `compliance.approve` permission
- [ ] reject checks `compliance_officer` role + `compliance.reject` permission
- [ ] Contractor row-level scoping (only own documents)

## Scheduled Command
- [ ] `compliance:check-expirations` runs daily
- [ ] Marks documents as expired where expiry_date < now() and status=approved
- [ ] Logs audit entries for each expired document
- [ ] Optionally sends notifications for upcoming expirations (30/14/7 day warnings)

## Audit Logging
- [ ] Upload event logged
- [ ] Approval event logged
- [ ] Rejection event logged
- [ ] Expiry event logged
- [ ] Expiry warning events logged (for notifications)

## Routes
- [ ] POST `/api/v1/compliance/documents` — upload
- [ ] GET `/api/v1/compliance/documents` — list (filterable by type, status, documentable)
- [ ] PATCH `/api/v1/compliance/documents/{document}/approve` — approve
- [ ] PATCH `/api/v1/compliance/documents/{document}/reject` — reject
- [ ] GET `/api/v1/compliance/documents/{document}` — show
- [ ] DELETE `/api/v1/compliance/documents/{document}` — soft delete

## Tests
- [ ] Upload success + validation failure
- [ ] Approval by compliance_officer succeeds
- [ ] Approval by non-compliance_officer fails
- [ ] Rejection with reason succeeds
- [ ] Rejection without reason fails
- [ ] Expiry auto-detection command
- [ ] Contractor can only see own documents
