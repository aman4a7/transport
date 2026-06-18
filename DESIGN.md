# DESIGN.md

## App Shell
- Use a left sidebar for primary navigation.
- Use a top navbar for page title, search, notifications, and profile.
- Keep the main content area focused and modular.
- Use drawers or modals for quick actions, approvals, or lightweight detail views.

## Layout Architecture

```
┌─────────────────────────────────────────────────┐
│ (Skip to content — hidden until keyboard focus)  │
├──────────┬──────────────────────────────────────┤
│          │  Topbar                               │
│ Sidebar  │  - Page title (set via context)       │
│          │  - Notification bell (placeholder)    │
│ 260px    │  - User avatar + dropdown (profile,   │
│ (64px    │    settings, sign out)                │
│  when    ├──────────────────────────────────────┤
│  col-    │  Content (Outlet)                     │
│  lapsed) │  max-width: 1440px, centered          │
│          │                                       │
└──────────┴──────────────────────────────────────┘
```

### Responsive breakpoints
- **Desktop (>=1024px):** Persistent sidebar, collapsible (260px / 64px icon-only). Hover expands collapsed sidebar.
- **Tablet (768-1023px):** Sidebar hidden by default, opens as overlay via hamburger toggle in Topbar.
- **Mobile (<768px):** Same as tablet but hides user name in Topbar for space.

### Component hierarchy
```
<AppShell>                        — Context provider (sidebar state, page title, mobile state)
  <Sidebar />                     — Navigation panel
  <div class="app-main">
    <Topbar />                    — Header bar
    <main class="app-content">    — Content slot
      <Outlet />                  — Child route content
    </main>
  </div>
</AppShell>
```

### Key behaviors
- **Sidebar collapse:** Toggle button in sidebar header. Width animates between 260px and 64px.
- **Sidebar hover-expand:** When collapsed on desktop, hovering expands the sidebar (absolute position overlays content).
- **Mobile sidebar:** Hamburger button in topbar opens sidebar as an overlay drawer with backdrop.
- **Page title:** Set via `useAppShell().setPageTitle()` from within any child route.
- **Escape key:** Closes mobile sidebar overlay.

## Design Tokens

### Color palette
All tokens defined in `src/styles/tokens.css`. Each semantic color has a full 50-900 scale.

| Category | Token prefix | Example |
|----------|-------------|---------|
| Gray (neutral) | `--gray-*` | `--gray-50`, `--gray-900` |
| Blue (primary) | `--blue-*` | `--blue-600` = #2563eb |
| Green (success) | `--green-*` | `--green-600` = #16a34a |
| Red (danger) | `--red-*` | `--red-600` = #dc2626 |
| Yellow (warning) | `--yellow-*` | `--yellow-600` = #d97706 |
| Orange | `--orange-*` | `--orange-600` = #ea580c |
| Purple (info/audit) | `--purple-*` | `--purple-600` = #9333ea |

### Semantic color tokens

| Token | Value |
|-------|-------|
| `--color-primary` | `--blue-600` |
| `--color-primary-hover` | `--blue-700` |
| `--color-primary-light` | `--blue-50` |
| `--color-success` | `--green-600` |
| `--color-warning` | `--yellow-600` |
| `--color-danger` | `--red-600` |
| `--color-info` | `--blue-500` |
| `--color-bg` | `--gray-50` |
| `--color-surface` | `#ffffff` |
| `--color-sidebar` | `#ffffff` |
| `--color-text` | `--gray-900` |
| `--color-text-secondary` | `--gray-500` |

### Spacing scale
Uses Tailwind-inspired numeric spacing: `--space-{1..24}` where each unit = 0.25rem.
Legacy space-{xs,sm,md,lg,xl,2xl} tokens retained for backward compatibility.

### Typography
- **Font families:** `--font-sans` (Inter, system-ui), `--font-mono` (JetBrains Mono)
- **Scale:** xs(0.75rem) → 4xl(2.25rem)
- **Weights:** normal(400), medium(500), semibold(600), bold(700)
- **Line heights:** tight(1.25), base(1.5), relaxed(1.75)

### Shadows
| Token | Use |
|-------|-----|
| `--shadow-xs` | Cards, panels |
| `--shadow-sm` | Elevated cards |
| `--shadow-md` | Dropdowns, menus |
| `--shadow-lg` | Modals, dialogs |
| `--shadow-xl` | Sidebar expanded overlay |
| `--shadow-topbar` | Topbar sticky shadow |
| `--shadow-dropdown` | User menu dropdown |

### Z-index scale
| Token | Value | Use |
|-------|-------|-----|
| `--z-dropdown` | 100 | Dropdown menus |
| `--z-sticky` | 200 | Sticky topbar |
| `--z-sidebar` | 300 | Desktop sidebar |
| `--z-backdrop` | 350 | Mobile sidebar backdrop |
| `--z-sidebar-mobile` | 400 | Mobile sidebar overlay |
| `--z-modal` | 400 | Modal dialogs |
| `--z-toast` | 500 | Toast notifications |
| `--z-tooltip` | 600 | Tooltips |

## Navigation

### Configuration
Navigation items are defined in `src/shared/config/navigation.ts`. Each item specifies:
- `label` — Display text
- `path` — Route path (e.g. `/app/vehicles`)
- `icon` — Lucide React icon component
- `roles` (optional) — Only visible to users with matching role slugs
- `permissions` (optional) — Only visible to users with matching permission slugs

### Sections
Items are grouped into labeled sections:
1. Dashboard (ungrouped)
2. Fleet Management — Vehicles, Drivers, Contractors
3. Operations — Routes, Trips, Passengers
4. Services — Fuel, Garage
5. Compliance & Contracts — Compliance, Contracts
6. Administration — Reports, Settings

### Role-based filtering
The Sidebar component filters nav items based on the current user's roles and permissions using the `hasPermission` utility. This is a UI-only filter; backend authorization still enforces access.

## Future Module Integration
Each new module follows this pattern to integrate with AppShell:

1. Add a route in `src/router/routes.tsx` under the `AppLayout` (protected by `AuthGuard`):
   ```tsx
   { path: '/app/vehicles', element: <VehicleList /> },
   ```
2. Add a navigation item in `src/shared/config/navigation.ts`:
   ```tsx
   { label: 'Vehicles', path: '/app/vehicles', icon: Truck },
   ```
3. (Optional) Restrict visibility with `roles` or `permissions` arrays.
4. Set the page title in the page component:
   ```tsx
   import { useAppShell } from '@/shared/layouts/AppShell';
   const { setPageTitle } = useAppShell();
   useEffect(() => { setPageTitle('Vehicles'); }, []);
   ```

## Dashboard Pattern
Each dashboard should include:
1. KPI summary cards
2. Alerts and pending actions
3. Charts or trend widgets
4. Recent activity or operational tables
5. Quick actions

## Module Page Pattern
Most modules should use:
- List page
- Add/Edit form
- Detail page
- Report/analytics page when needed

## Detail Page Pattern
- Header with title, status badge, and actions
- Summary cards row
- Tabbed sections
- Related history and activity logs

## Form Pattern
- Group long forms into logical sections
- Use validation hints and helper text
- Show file upload rules clearly
- Highlight blocked actions caused by policy rules

## Table Pattern
- Search
- Filters
- Sort
- Status badges
- Row actions
- Export where useful

## Visual Principles
- Clean, dashboard-focused layout
- Strong hierarchy
- Consistent spacing
- Role-appropriate complexity
- Responsive for driver and passenger views
- Avoid decorative clutter in operational screens

## Accessibility
- Keyboard reachable navigation
- Visible focus states (`:focus-visible` with 2px primary color ring)
- Sufficient contrast (text on surfaces meets WCAG AA)
- Label every form field
- Avoid color-only status meaning
- Support accessible modals and drawers
- Skip-to-content link (hidden until keyboard focus)
- `aria-label` on navigation sections, `aria-current="page"` on active links
- Screen reader only utility class (`.sr-only`)
- Escape key closes mobile sidebar overlay

## Mobile Priority
Highest mobile priority:
- Driver dashboard
- Passenger dashboard
- Document upload screens
- Complaint and incident submission

## Dashboard Notes
- KPI cards should link to deeper operational pages when appropriate.
- Show reasons for blocked or denied actions.
- Prefer clear tables over decorative charts when an action is needed.
