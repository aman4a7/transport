import type { LucideIcon } from 'lucide-react';
import {
  LayoutDashboard,
  Truck,
  UserCircle,
  UserPlus,
  MapPin,
  Route,
  Users,
  Fuel,
  Wrench,
  ShieldCheck,
  FileText,
  BarChart3,
  Settings,
} from 'lucide-react';

export interface NavItem {
  label: string;
  path: string;
  icon: LucideIcon;
  roles?: string[];
  permissions?: string[];
}

export interface NavSection {
  label?: string;
  items: NavItem[];
}

export const navigationConfig: NavSection[] = [
  {
    items: [
      { label: 'Dashboard', path: '/app/dashboard', icon: LayoutDashboard },
    ],
  },
  {
    label: 'Fleet Management',
    items: [
      { label: 'Vehicles', path: '/app/vehicles', icon: Truck },
      { label: 'Drivers', path: '/app/drivers', icon: UserCircle },
      { label: 'Contractors', path: '/app/contractors', icon: UserPlus },
    ],
  },
  {
    label: 'Operations',
    items: [
      { label: 'Routes', path: '/app/routes', icon: MapPin },
      { label: 'Trips', path: '/app/trips', icon: Route },
      { label: 'Passengers', path: '/app/passengers', icon: Users },
    ],
  },
  {
    label: 'Services',
    items: [
      { label: 'Fuel', path: '/app/fuel', icon: Fuel },
      { label: 'Garage', path: '/app/garage', icon: Wrench },
    ],
  },
  {
    label: 'Compliance & Contracts',
    items: [
      { label: 'Compliance', path: '/app/compliance', icon: ShieldCheck, permissions: ['compliance.view'] },
      { label: 'Contracts', path: '/app/contracts', icon: FileText },
    ],
  },
  {
    label: 'Administration',
    items: [
      { label: 'Reports', path: '/app/reports', icon: BarChart3 },
      { label: 'Settings', path: '/app/settings', icon: Settings },
    ],
  },
];
