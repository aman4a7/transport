---
name: testing-strategy
description: Formalized testing patterns for backend (Pest + PHPUnit) and frontend (Vitest + Testing Library) across all modules.
version: 1.0.0
last_updated: 2026-06-20
depends_on:
  - laravel-backend
  - react-frontend
---

# Testing Strategy Skill

## When To Use

Use this skill when:
- Creating new test files for a backend module
- Creating new test files for a frontend feature
- Reviewing test coverage before marking a module as "Done"
- Ensuring CI gates are met for a new module

## Backend Testing (Pest + PHPUnit)

### Standard File Structure

Every backend module test file follows this structure:

```php
<?php

use App\Domain\{Module}\Models\{Model};
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

// CRUD tests
test('{role} can list {entities}', function (): void { ... });
test('{role} can create a {entity}', function (): void { ... });
test('{role} can show a {entity}', function (): void { ... });
test('{role} can update a {entity}', function (): void { ... });
test('{role} can delete a {entity}', function (): void { ... });

// Validation tests
test('creating {entity} without required field fails', function (): void { ... });

// Authorization tests
test('unauthorized user cannot list {entities}', function (): void { ... });
test('unauthenticated request to {entities} is rejected', function (): void { ... });

// Business rule tests
test('{business rule} is enforced', function (): void { ... });
```

### Required Test Cases Per CRUD Module

| Test Case | Description |
|-----------|-------------|
| List (with pagination/filter) | Confirm paginated response structure, verify filtering works |
| Create (success) | 201 response, verify data persisted |
| Create (validation failure) | 422 response, verify error messages |
| Show (success) | 200 response, verify data matches |
| Update (success) | 200 response, verify changes persisted |
| Update (authorization failure) | 403 for non-owners or insufficient role |
| Delete (success) | 200 response, verify soft delete |
| Unauthorized access (wrong role) | 403 for any action without permission |
| Unauthenticated access | 401 for any action without auth |
| Policy-specific test | e.g., contractor scoping test from VehiclePolicy |

### Factory Requirements

Every model MUST have a factory. Minimum factory state presets:
- Default state (valid record)
- `with{Relation}` state for relation setup

### Policy Authorization Tests

Every policy method needs at least two tests:
1. **Success case:** User with the required role/permission can perform the action
2. **Failure case:** User without the required role/permission receives 403

For row-level scoping policies (e.g., contractor can only see their own vehicles):
3. **Scoping case:** Create two records (one owned, one not), confirm the constrained user can only see/act on their own

## Frontend Testing (Vitest + Testing Library)

### Shared Component Test Requirements

Every shared UI component in `src/shared/components/ui/` MUST have:

1. **Render test:** Component renders without crashing with default props
2. **Loading state test:** Component renders loading/skeleton state (if applicable)
3. **Error state test:** Component renders error state (if applicable)
4. **Accessibility test:** `vitest-axe` scan with zero violations

Example pattern:

```tsx
import { render } from '@testing-library/react';
import { test, expect } from 'vitest';
import { axe } from 'vitest-axe';
import { MyComponent } from '../MyComponent';

test('renders with default props', () => {
  const { getByText } = render(<MyComponent prop1="value" />);
  expect(getByText('value')).toBeTruthy();
});

test('has no accessibility violations', async () => {
  const { container } = render(<MyComponent prop1="value" />);
  const results = await axe(container);
  expect(results.violations).toHaveLength(0);
});
```

### Feature Page/List Test Requirements

Every feature's list page needs:

1. **Data render test:** Mock the API hook, confirm data renders in the DataTable
2. **Search/filter interaction test:** Confirm search input and filter dropdowns render and respond to changes

Example for VehicleList:

```tsx
vi.mock('../hooks/useVehicles', () => ({
  useVehicles: vi.fn(),
  useDeleteVehicle: vi.fn(() => ({ mutateAsync: vi.fn(), isPending: false })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

test('renders DataTable with vehicle data', () => {
  vi.mocked(useVehicles).mockReturnValue({
    data: { success: true, data: [/* ... */], meta: { /* ... */ } },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never);

  renderWithProviders(<VehicleList />);
  expect(screen.getByText('DEF-001')).toBeTruthy();
});
```

### API Hook Test Requirements

Every feature's API hooks (in `hooks/`) need:

1. **Success state test:** Mock Axios, confirm hook returns expected shape on success
2. **Error state test:** Mock Axios rejection, confirm hook reports error state

Example:

```tsx
const mockAxiosClient = { get: vi.fn() };
vi.mock('@/shared/api/axiosClient', () => ({ default: mockAxiosClient }));

test('returns expected shape on success', async () => {
  mockAxiosClient.get.mockResolvedValue({ data: mockResponse });
  const { result } = renderHook(() => useMyList(filters), { wrapper });
  await waitFor(() => expect(result.current.isSuccess).toBe(true));
  expect(result.current.data?.success).toBe(true);
});
```

## Minimum Coverage Expectations

Before a module can be marked "Done" in the Module Status table (`memory.md`), these minimums must be met:

### Backend
- [ ] Migration exists and tests confirm it runs (via `RefreshDatabase + migrate`)
- [ ] Model factory exists
- [ ] CRUD tests: list, create (success + validation), show, update (success + auth failure), delete
- [ ] Policy tests: authorization (success + failure) for each policy method
- [ ] Business rule tests: each unique business rule has at least one test
- [ ] All backend tests pass in CI (via `php artisan test` in `backend` job)

### Frontend
- [ ] Every shared component used by the module has render + a11y tests
- [ ] List page renders data correctly (with mocked hook)
- [ ] Search/filter interaction works
- [ ] API hooks have success + error state tests
- [ ] All frontend tests pass in CI (via `npm test` in `frontend` job)

### Test Count Benchmarks

| Module Type | Minimum Backend Tests | Minimum Frontend Tests |
|-------------|----------------------|----------------------|
| Simple CRUD (e.g., Vehicle, Driver) | 8 | 4 |
| CRUD + Business Rules (e.g., Passenger) | 10 | 5 |
| CRUD + Workflow (e.g., Compliance, Contract) | 14 | 6 |
| Auth/RBAC | 8 | 4 |

## CI Gate Requirement

A new module is NOT considered complete until:

1. **Backend tests pass** in CI (`name: Backend — Lint + Test` in `.github/workflows/ci.yml`)
2. **Frontend tests pass** in CI (`name: Frontend — Lint + Build + Test`)
3. **Lint passes** for both: `vendor/bin/pint --test` (backend) and `npm run lint` (frontend)
4. **Build passes** for frontend: `npm run build` (tsc + vite build)
5. **Dependency audits pass** or are documented as accepted risk: `composer audit` (backend) and `npm audit --audit-level=high` (frontend)

## Agent Usage Instructions

1. **Read** this SKILL.md when creating tests for any module
2. **Reference** existing test files as examples: `VehicleTest.php`, `DriverTest.php`, `OwnerTest.php`, `PassengerTest.php`, `AuthTest.php`
3. **Create** backend tests in `backend/tests/Feature/{Module}Test.php`
4. **Create** frontend tests in `frontend/src/features/{module}/__tests__/` for pages/hooks, and `frontend/src/shared/components/ui/__tests__/` for shared components
5. **Run** backend tests before marking completion: `cd backend && php artisan test`
6. **Run** frontend tests before marking completion: `cd frontend && npm test`
7. **Update** `memory.md` Module Status table when coverage minimums are met
8. **Verify** against `.agent/hooks.md` before finalizing
