# Testing Strategy Checklist

## Backend
- [ ] Model factory exists with default state
- [ ] CRUD tests: list, create (success + validation), show, update (success + auth failure), delete
- [ ] Policy authorization tests: success + failure for each policy method
- [ ] Business rule enforcement tests
- [ ] Unauthenticated request rejected (401)
- [ ] Unauthorized role rejected (403)
- [ ] All tests pass: `php artisan test`

## Frontend — Shared Components
- [ ] Render test exists
- [ ] Loading state test (if applicable)
- [ ] Error state test (if applicable)
- [ ] Accessibility test (vitest-axe, zero violations)

## Frontend — Feature Pages
- [ ] List page renders data from mocked hooks
- [ ] Search/filter interaction works
- [ ] Loading state rendered
- [ ] Error state with retry works

## Frontend — API Hooks
- [ ] Success state: returns expected data shape
- [ ] Error state: fails gracefully

## CI Gates
- [ ] `php artisan test` passes in backend CI job
- [ ] `npm test` passes in frontend CI job
- [ ] `vendor/bin/pint --test` passes
- [ ] `npm run lint` passes
- [ ] `npm run build` passes
- [ ] `composer audit` passes or documented
- [ ] `npm audit --audit-level=high` passes or documented
