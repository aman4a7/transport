# Transport Routing Checklist (Route / Trip / Fuel / Garage)

## Eligibility-Gate-Chain
- [ ] `ComplianceDocumentApproved` event dispatched from the approve service method
- [ ] `SyncEntityExpiryDate` listener registered in `AppServiceProvider::boot()` via `Event::listen()`
- [ ] Listener writes Vehicle.registration_expiry / insurance_expiry, Driver.license_expiry from `document->expires_at`
- [ ] Unknown entity/document combos fall through to `default => null` (no crash)
- [ ] Services read entity columns (registration_expiry/insurance_expiry/license_expiry), NOT the documents table, for the common gates
- [ ] Contracted-private trip gate queries `ComplianceDocument` for approved docs (vehicle + driver_license) with `expires_at > now()` or null

## BusinessRuleException with rule slugs
- [ ] Every failure path throws `BusinessRuleException` with a DISTINCT `rule:` slug
- [ ] Slug is stable (tests/frontend key off it); message is human-readable
- [ ] No two rules use the same slug for different meanings across services
- [ ] Render path verified: `{ success:false, message, rule, context }` with 422

## DB::transaction()-wrapped mutations
- [ ] create/update/delete wrap validation + writes + audit log in one `DB::transaction()`
- [ ] Lifecycle transitions (start/complete/cancel/activate/terminate) wrapped
- [ ] Fuel issue wraps: eligibility gates → transaction row (negated quantity) → stock decrement → audit
- [ ] Fuel restock wraps: firstOrCreate stock → positive transaction → increment → audit
- [ ] Fuel adjust wraps: negative-result guard → transaction → stock update → audit
- [ ] No partial state observable if any step throws (rollback verified in tests)

## Audit severity: log() vs logSensitive()
- [ ] Balance/inventory-changing actions (fuel stock adjustment, payments) use `logSensitive()`
- [ ] Ordinary CRUD and lifecycle transitions use `log()`
- [ ] Fuel issue/restock rely on the immutable transaction row + `log()` (no double-sensitive)
- [ ] Lifecycle transitions pass old/new values to the audit call

## Eager loading column-scoped
- [ ] List queries use `->with(['vehicle:id,plate_number', ...])` style — never full related models
- [ ] Detail/show may load full relations (getWithRelations) but list paths stay scoped
- [ ] No N+1 in list loops (no per-row relation queries)

## Route
- [ ] RouteStatus enum (active/inactive), list filters status + search
- [ ] Route delete guarded (422 when assigned trips exist) — verified in tests

## Trip
- [ ] create/update validate inside transaction: validateVehicle → validateDriver → validateRoute → validateCapacity → (contracted_private) validateContractorCompliance
- [ ] validateVehicle order: status active → registration_expiry → insurance_expiry → no in-progress maintenance → (contracted_private) active non-expired contract
- [ ] validateDriver: status active → license_expiry
- [ ] validateContractorCompliance: approved vehicle compliance doc + approved driver license compliance doc
- [ ] Capacity: count(passengerIds) <= vehicle.seating_capacity
- [ ] Lifecycle guards: start requires scheduled; complete requires in_progress; cancel blocked for completed/cancelled; delete blocked for in_progress/completed
- [ ] Passenger assignment via TripAssignment pivot inside the same transaction

## Fuel
- [ ] Issue gate order: category defence_plated → status active → registration_expiry → insurance_expiry → stock sufficiency
- [ ] Transaction quantity negated for issue; stock decremented in same transaction
- [ ] Adjust rejects negative resulting stock (`fuel_adjustment_negative`)
- [ ] FuelTransaction immutable (no update/delete)

## Garage
- [ ] Create/start gate: defence-plated only
- [ ] Start blocked when vehicle has an active trip
- [ ] Expired registration/insurance blocks maintenance creation (422)
- [ ] TripService blocks trip assignment for vehicles under in-progress maintenance (`vehicle_under_maintenance`)

## Cross-module
- [ ] Frontend surfaces the API `rule` field (never just the message)
- [ ] Frontend role visibility never replaces these backend gates

## Tests
- [ ] Positive + each negative gate path asserted (status/reg/insurance/contract/capacity/compliance)
- [ ] Lifecycle invalid-transition guards asserted
- [ ] Transactional integrity asserted (no partial mutation on failure)
- [ ] Audit log row asserted for each lifecycle action
- [ ] Contracted-private with/without active contract asserted
- [ ] Defence-plated-only fuel/garage rule asserted for contracted vehicles