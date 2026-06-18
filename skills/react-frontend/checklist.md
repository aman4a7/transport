# React Frontend Checklist

Use this checklist before finalizing any frontend module work.

## Page Component Checklist
- [ ] Page handles loading state (spinner or skeleton)
- [ ] Page handles error state (message + retry button)
- [ ] Page handles empty state (EmptyState component)
- [ ] Page handles success state (renders data)
- [ ] Page title is set via document title or route meta
- [ ] Page is registered in `src/router/routes.tsx`
- [ ] Page is wrapped with appropriate auth/role guard

## Form Checklist
- [ ] Form uses React Hook Form with `useForm`
- [ ] Form uses Zod schema via `zodResolver`
- [ ] All required fields are validated client-side
- [ ] Server validation errors are mapped to form fields
- [ ] Submit button shows loading state during submission
- [ ] Form sections are grouped logically for long forms
- [ ] File upload inputs show size/type constraints
- [ ] Cancel navigates back without losing intended context

## Table / List Checklist
- [ ] Table uses TanStack Table for sorting and pagination
- [ ] Search input is debounced (300ms minimum)
- [ ] Filters update URL query parameters
- [ ] Status columns use `StatusBadge` (text + icon, not color alone)
- [ ] Row actions are accessible via keyboard
- [ ] Empty state is shown when no results match filters
- [ ] Pagination controls are rendered below the table

## API Hook Checklist
- [ ] Query hook uses descriptive `queryKey` (e.g., `['vehicles', filters]`)
- [ ] Query hook uses the API function from `api/` directory
- [ ] Mutation hook uses `useMutation` with `onSuccess` invalidation
- [ ] Mutation hook surfaces error messages from API envelope
- [ ] Mutation hook provides `isLoading` / `isPending` state

## TypeScript / Types Checklist
- [ ] Types file exists in `types/{module}.ts`
- [ ] Interfaces match the backend API response shape
- [ ] Enums mirror backend enums (VehicleCategory, VehicleStatus, etc.)
- [ ] Filter params are typed (e.g., `VehicleFilters`)
- [ ] Create/update payloads are typed (e.g., `CreateVehicleData`)

## Accessibility Checklist
- [ ] All form inputs have associated `<label>` elements
- [ ] Interactive elements are keyboard-reachable
- [ ] Focus styles are visible on focusable elements
- [ ] Status badges use text + icon (not color only)
- [ ] Tables have proper `<th>` with `scope` attributes
- [ ] Modals trap focus and dismiss on Escape
- [ ] Color contrast meets WCAG AA (4.5:1 for text)
- [ ] Mobile tap targets are at least 44×44px

## State Management Checklist
- [ ] Server data is fetched via TanStack Query (not local state)
- [ ] Client-only state uses Zustand (if needed) or React state
- [ ] No API data is duplicated into Zustand stores
- [ ] Auth state (user, token, permissions) is in `authStore`

## Routing Checklist
- [ ] Route follows the pattern `/app/{module}` for lists
- [ ] Route follows the pattern `/app/{module}/new` for create
- [ ] Route follows the pattern `/app/{module}/:id` for detail
- [ ] Route follows the pattern `/app/{module}/:id/edit` for edit
- [ ] Route is protected by `AuthGuard` and `RoleGuard` where needed
- [ ] Route lazy-loads the page component

## Design Alignment Checklist
- [ ] Page follows the relevant pattern from DESIGN.md
- [ ] Dashboard pages have: KPI cards, alerts, charts, tables, quick actions
- [ ] Detail pages have: header with status, summary cards, tabs
- [ ] Spacing and typography are consistent with design system
- [ ] Mobile layout is responsive for driver/passenger views
