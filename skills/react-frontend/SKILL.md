---
name: react-frontend
description: Build or update React frontend modules, pages, routing, state handling, forms, tables, and dashboard components for the transport management system.
version: 2.0.0
last_updated: 2026-06-07
depends_on:
  - laravel-backend
  - database-schema
---

# React Frontend Skill

## When To Use

Use this skill when working on:
- Page components (list, form, detail, dashboard, report)
- Routes and navigation
- API integration hooks
- Form handling and validation
- Data tables with filtering and sorting
- Shared layout components (sidebar, topbar, modals)
- TypeScript type definitions
- State management (server state and client state)

## Architecture Principles

### 1. Domain-Driven Folder Structure

All domain code lives under `src/features/{module}/`. Each module is self-contained:

```
src/features/vehicle/
├── api/
│   └── vehicleApi.ts          # Axios request functions
├── hooks/
│   ├── useVehicles.ts         # TanStack Query hook for list
│   ├── useVehicle.ts          # TanStack Query hook for single
│   └── useVehicleMutations.ts # TanStack Query mutations
├── components/
│   ├── VehicleStatusBadge.tsx  # Domain-specific UI component
│   └── VehicleCategoryTag.tsx
├── pages/
│   ├── VehicleList.tsx         # List page
│   ├── VehicleCreate.tsx       # Create form page
│   ├── VehicleEdit.tsx         # Edit form page
│   └── VehicleDetail.tsx       # Detail page with tabs
├── types/
│   └── vehicle.ts             # TypeScript interfaces and enums
└── schemas/
    └── vehicleSchema.ts       # Zod validation schemas
```

### 2. Shared Infrastructure

Cross-cutting code lives in `src/shared/`:

```
src/shared/
├── api/
│   ├── axiosClient.ts         # Axios instance with auth interceptor
│   └── apiEnvelope.ts         # Response type wrappers
├── components/
│   ├── layout/
│   │   ├── AppShell.tsx       # Sidebar + topbar shell
│   │   ├── Sidebar.tsx
│   │   └── Topbar.tsx
│   ├── ui/
│   │   ├── DataTable.tsx      # Reusable TanStack Table
│   │   ├── StatusBadge.tsx
│   │   ├── KpiCard.tsx
│   │   ├── ConfirmDialog.tsx
│   │   ├── EmptyState.tsx
│   │   └── LoadingSpinner.tsx
│   └── forms/
│       ├── FormField.tsx
│       ├── FileUpload.tsx
│       └── DatePicker.tsx
├── hooks/
│   ├── useAuth.ts
│   ├── usePagination.ts
│   └── useDebounce.ts
├── stores/
│   └── authStore.ts           # Zustand store for auth state
├── types/
│   ├── api.ts                 # API envelope types
│   ├── auth.ts                # User, role, permission types
│   └── common.ts              # Shared types (pagination, etc.)
└── utils/
    ├── dates.ts               # Day.js helpers (Gregorian + Ethiopic)
    ├── formatters.ts
    └── permissions.ts         # Role/permission check helpers
```

### 3. Routing

Routes are defined centrally in `src/router/` using React Router v7:

```
src/router/
├── index.tsx                  # Router setup
├── routes.tsx                 # Route definitions
├── guards/
│   ├── AuthGuard.tsx          # Redirect to login if unauthenticated
│   └── RoleGuard.tsx          # Redirect if missing required role
└── layouts/
    ├── PublicLayout.tsx        # Layout for public pages (home, login)
    └── AppLayout.tsx           # Layout for authenticated pages
```

Route naming convention:
- `/` — Public home
- `/login` — Login
- `/app/dashboard` — Role-based dashboard
- `/app/vehicles` — Vehicle list
- `/app/vehicles/new` — Vehicle create
- `/app/vehicles/:id` — Vehicle detail
- `/app/vehicles/:id/edit` — Vehicle edit

### 4. State Management

**Server state** (API data): TanStack React Query
- All API data fetched through custom hooks
- Stale time, cache time, and refetch configured per-query
- Mutations use `useMutation` with `onSuccess` invalidation

**Client state** (UI state, auth): Zustand
- Auth store for user session, token, and permissions
- Minimal UI stores only when needed (sidebar collapse, theme)

**Form state**: React Hook Form + Zod
- Every form uses `useForm` with a Zod resolver
- Schemas defined in `schemas/` directory per module
- Server errors mapped back to form fields

### 5. API Layer

Every module has an `api/` directory with plain async functions:

```typescript
// src/features/vehicle/api/vehicleApi.ts
import { axiosClient } from '@/shared/api/axiosClient';
import type { Vehicle, VehicleFilters, CreateVehicleData } from '../types/vehicle';
import type { PaginatedResponse, ApiResponse } from '@/shared/types/api';

export const vehicleApi = {
  list: (filters: VehicleFilters) =>
    axiosClient.get<PaginatedResponse<Vehicle>>('/api/v1/vehicles', { params: filters }),

  get: (id: number) =>
    axiosClient.get<ApiResponse<Vehicle>>(`/api/v1/vehicles/${id}`),

  create: (data: CreateVehicleData) =>
    axiosClient.post<ApiResponse<Vehicle>>('/api/v1/vehicles', data),

  update: (id: number, data: Partial<CreateVehicleData>) =>
    axiosClient.patch<ApiResponse<Vehicle>>(`/api/v1/vehicles/${id}`, data),

  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/api/v1/vehicles/${id}`),
};
```

Hooks consume the API functions:

```typescript
// src/features/vehicle/hooks/useVehicles.ts
import { useQuery } from '@tanstack/react-query';
import { vehicleApi } from '../api/vehicleApi';

export function useVehicles(filters: VehicleFilters) {
  return useQuery({
    queryKey: ['vehicles', filters],
    queryFn: () => vehicleApi.list(filters).then(r => r.data),
  });
}
```

### 6. Component Patterns

**List pages** follow the table pattern from DESIGN.md:
- Search bar + filter controls
- TanStack Table with sorting, pagination
- Status badges in rows
- Row actions (view, edit, delete)
- Empty state component when no data

**Form pages** follow the form pattern from DESIGN.md:
- Grouped sections for long forms
- Validation hints and helper text
- File upload with rules displayed
- Submit/cancel actions
- Loading state during submission

**Detail pages** follow the detail pattern from DESIGN.md:
- Header with title, status badge, action buttons
- Summary cards row
- Tabbed content sections
- Related activity logs

**Dashboard pages** follow the dashboard pattern from DESIGN.md:
- KPI summary cards (clickable, linking to deeper pages)
- Alerts and pending actions
- Charts (Chart.js)
- Recent activity table
- Quick action buttons

### 7. UI State Handling

Every data-fetching component MUST handle four states:
1. **Loading** — `LoadingSpinner` or skeleton
2. **Error** — Error message with retry button
3. **Empty** — `EmptyState` component with action prompt
4. **Success** — Render data

### 8. API Response Type Wrappers

```typescript
// src/shared/types/api.ts
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    pagination: {
      current_page: number;
      total: number;
      per_page: number;
      last_page: number;
    };
  };
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}
```

## File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Page component | `{Entity}{Action}.tsx` | `VehicleList.tsx` |
| API functions | `{entity}Api.ts` | `vehicleApi.ts` |
| Query hook | `use{Entity}.ts` or `use{Entities}.ts` | `useVehicles.ts` |
| Mutation hook | `use{Entity}Mutations.ts` | `useVehicleMutations.ts` |
| Types file | `{entity}.ts` | `vehicle.ts` |
| Zod schema | `{entity}Schema.ts` | `vehicleSchema.ts` |
| Domain component | `{Entity}{Descriptor}.tsx` | `VehicleStatusBadge.tsx` |
| Shared component | `{Descriptor}.tsx` | `DataTable.tsx` |
| Zustand store | `{domain}Store.ts` | `authStore.ts` |
| Utility file | `{descriptor}.ts` | `dates.ts` |

## Accessibility Requirements

From DESIGN.md and accessibility-review skill:
- Every interactive element keyboard-reachable
- Visible focus states on all focusable elements
- All form inputs have associated labels
- Status badges use text + icon (not color alone)
- Tables have proper `<th>` and `scope` attributes
- Modals trap focus and are dismissible via Escape
- Sufficient color contrast (WCAG AA minimum)

## Dual Date Display

From memory.md:
- All dates stored in Gregorian format
- Display both Gregorian and Ethiopic calendar dates
- Use Day.js with a custom Ethiopic plugin/helper in `src/shared/utils/dates.ts`

## Agent Usage Instructions

When implementing a new frontend module:

1. **Read** this SKILL.md and the `templates/` directory
2. **Read** `laravel-backend/SKILL.md` to understand the API shape
3. **Read** `../../DESIGN.md` for layout patterns
4. **Create** the feature directory under `src/features/{module}/`
5. **Define** TypeScript types in `types/{module}.ts`
6. **Create** Zod schemas in `schemas/{module}Schema.ts`
7. **Create** API functions in `api/{module}Api.ts`
8. **Create** query hooks in `hooks/use{Entities}.ts`
9. **Create** mutation hooks in `hooks/use{Entity}Mutations.ts`
10. **Build** page components in `pages/`
11. **Register** routes in `src/router/routes.tsx`
12. **Handle** all four UI states (loading, error, empty, success)
13. **Verify** against `../../.agent/hooks.md` before finalizing
