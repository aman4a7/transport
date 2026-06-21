# Laravel Backend Checklist

Use this checklist before finalizing any backend module work.

## Controller Checklist
- [ ] Controller method is ≤ 10 lines (excluding comments/blanks)
- [ ] `$this->authorize()` is called for every action
- [ ] Input is validated via a Form Request class
- [ ] Business logic is delegated to a Service or Action
- [ ] Response follows the standard API envelope format
- [ ] Route is registered under `/api/v1/`

## Service / Action Checklist
- [ ] Service does not access `Request` object directly
- [ ] Service receives typed parameters, not raw arrays
- [ ] Business rule pre-conditions are checked before mutation
- [ ] Domain-specific exceptions are thrown, not generic ones
- [ ] Events are dispatched for state-changing operations
- [ ] Transactions wrap multi-step mutations

## Policy Checklist
- [ ] Policy exists for the model
- [ ] Policy is registered in `AuthServiceProvider`
- [ ] Policy checks role AND resource ownership/scope
- [ ] Defence-plated eligibility is enforced for fuel/garage operations
- [ ] Compliance status is checked for assignment operations

## Validation Checklist
- [ ] Form Request class exists for store and update
- [ ] All required fields are validated
- [ ] Enum values are validated against allowed values
- [ ] File uploads validate size, type, and mime
- [ ] Unique constraints are globally unique unless there is a confirmed business reason for composite scoping

## Event / Audit Checklist
- [ ] Events are dispatched for create, update, delete, status change
- [ ] Listeners log to audit_logs table
- [ ] Audit log includes: user_id, action, entity_type, entity_id, old_values, new_values
- [ ] Sensitive actions (approval, rejection, fuel issue, permission change) are always logged
- [ ] Listeners are queued when they don't need synchronous completion

## File Upload Checklist
- [ ] Upload validates file size and allowed mime types
- [ ] Files are stored in non-public storage with signed URLs
- [ ] Compliance documents are encrypted at rest
- [ ] Document entity tracks: file_path, status, expiry_date, verified_by, verified_at
- [ ] Replacement workflow preserves the previous version

## Testing Checklist
- [ ] Feature test covers CRUD operations
- [ ] Feature test covers authorization (forbidden for wrong role)
- [ ] Feature test covers validation (422 for bad input)
- [ ] Feature test covers business rule enforcement (e.g., fuel denied for contracted vehicle)
- [ ] Unit test covers Service/Action logic
