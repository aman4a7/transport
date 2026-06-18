import { Navigate, type RouteObject } from 'react-router-dom';
import { PublicLayout } from './layouts/PublicLayout';
import { AppLayout } from './layouts/AppLayout';
import { AuthGuard } from './guards/AuthGuard';
import { LoginPage } from '@/features/auth/pages/LoginPage';
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage';

export const routes: RouteObject[] = [
  {
    element: <PublicLayout />,
    children: [
      { path: '/login', element: <LoginPage /> },
      { path: '/forgot-password', element: <ForgotPasswordPage /> },
      { path: '/', element: <Navigate to="/login" replace /> },
    ],
  },
  {
    element: <AuthGuard />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { path: '/app/dashboard', element: <div>Dashboard</div> },
          { path: '/app/vehicles', element: <div>Vehicles</div> },
          { path: '/app/drivers', element: <div>Drivers</div> },
          { path: '/app/contractors', element: <div>Contractors</div> },
          { path: '/app/routes', element: <div>Routes</div> },
          { path: '/app/trips', element: <div>Trips</div> },
          { path: '/app/passengers', element: <div>Passengers</div> },
          { path: '/app/fuel', element: <div>Fuel</div> },
          { path: '/app/garage', element: <div>Garage</div> },
          { path: '/app/compliance', element: <div>Compliance</div> },
          { path: '/app/contracts', element: <div>Contracts</div> },
          { path: '/app/reports', element: <div>Reports</div> },
          { path: '/app/settings', element: <div>Settings</div> },
        ],
      },
    ],
  },
  {
    path: '*',
    element: <Navigate to="/login" replace />,
  },
];
