import { Navigate, type RouteObject } from 'react-router-dom';
import { PublicLayout } from './layouts/PublicLayout';
import { AppLayout } from './layouts/AppLayout';
import { AuthGuard } from './guards/AuthGuard';
import { LoginPage } from '@/features/auth/pages/LoginPage';
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage';
import { VehicleList } from '@/features/vehicles/pages/VehicleList';
import { VehicleForm } from '@/features/vehicles/pages/VehicleForm';
import { VehicleDetail } from '@/features/vehicles/pages/VehicleDetail';
import { DriverList } from '@/features/drivers/pages/DriverList';
import { DriverForm } from '@/features/drivers/pages/DriverForm';
import { DriverDetail } from '@/features/drivers/pages/DriverDetail';
import { OwnerList } from '@/features/owners/pages/OwnerList';
import { OwnerForm } from '@/features/owners/pages/OwnerForm';
import { OwnerDetail } from '@/features/owners/pages/OwnerDetail';
import { PassengerList } from '@/features/passengers/pages/PassengerList';
import { PassengerForm } from '@/features/passengers/pages/PassengerForm';
import { PassengerDetail } from '@/features/passengers/pages/PassengerDetail';
import { RouteList } from '@/features/routes/pages/RouteList';
import { RouteForm } from '@/features/routes/pages/RouteForm';
import { RouteDetail } from '@/features/routes/pages/RouteDetail';
import { ComplianceList } from '@/features/compliance/pages/ComplianceList';
import { ComplianceDetail } from '@/features/compliance/pages/ComplianceDetail';
import { ComplianceReview } from '@/features/compliance/pages/ComplianceReview';
import { ComplianceUpload } from '@/features/compliance/pages/ComplianceUpload';

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
          { path: '/app/vehicles', element: <VehicleList /> },
          { path: '/app/vehicles/new', element: <VehicleForm /> },
          { path: '/app/vehicles/:id', element: <VehicleDetail /> },
          { path: '/app/vehicles/:id/edit', element: <VehicleForm /> },
          { path: '/app/drivers', element: <DriverList /> },
          { path: '/app/drivers/new', element: <DriverForm /> },
          { path: '/app/drivers/:id', element: <DriverDetail /> },
          { path: '/app/drivers/:id/edit', element: <DriverForm /> },
          { path: '/app/contractors', element: <OwnerList /> },
          { path: '/app/contractors/new', element: <OwnerForm /> },
          { path: '/app/contractors/:id', element: <OwnerDetail /> },
          { path: '/app/contractors/:id/edit', element: <OwnerForm /> },
          { path: '/app/passengers', element: <PassengerList /> },
          { path: '/app/passengers/new', element: <PassengerForm /> },
          { path: '/app/passengers/:id', element: <PassengerDetail /> },
          { path: '/app/passengers/:id/edit', element: <PassengerForm /> },
          { path: '/app/routes', element: <RouteList /> },
          { path: '/app/routes/new', element: <RouteForm /> },
          { path: '/app/routes/:id', element: <RouteDetail /> },
          { path: '/app/routes/:id/edit', element: <RouteForm /> },
          { path: '/app/trips', element: <div>Trips</div> },
          { path: '/app/passengers', element: <div>Passengers</div> },
          { path: '/app/fuel', element: <div>Fuel</div> },
          { path: '/app/garage', element: <div>Garage</div> },
          { path: '/app/compliance', element: <ComplianceList /> },
          { path: '/app/compliance/upload', element: <ComplianceUpload /> },
          { path: '/app/compliance/:id', element: <ComplianceDetail /> },
          { path: '/app/compliance/:id/review', element: <ComplianceReview /> },
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
