<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            'system_administrator' => Permission::pluck('slug')->toArray(),

            'transport_manager' => [
                'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
                'drivers.view', 'drivers.create', 'drivers.update', 'drivers.delete',
                'owners.view', 'owners.create', 'owners.update', 'owners.delete',
                'routes.view', 'routes.create', 'routes.update', 'routes.delete',
                'trips.view', 'trips.create', 'trips.update', 'trips.delete', 'trips.assign',
                'passengers.view', 'passengers.create', 'passengers.update', 'passengers.delete',
                'reports.view', 'reports.generate',
                'notifications.view', 'notifications.manage',
            ],

            'compliance_officer' => [
                'compliance.view', 'compliance.create', 'compliance.update', 'compliance.verify', 'compliance.reject',
                'drivers.view',
                'vehicles.view',
                'contracts.view',
                'owners.view',
                'reports.view',
                'notifications.view',
            ],

            'fuel_attendant' => [
                'fuel.view', 'fuel.create', 'fuel.update', 'fuel.view_stock', 'fuel.adjust_stock',
                'vehicles.view',
                'notifications.view',
            ],

            'garage_officer' => [
                'garage.view', 'garage.create', 'garage.update', 'garage.delete',
                'vehicles.view',
                'notifications.view',
            ],

            'finance_officer' => [
                'contracts.view', 'contracts.create', 'contracts.update', 'contracts.delete', 'contracts.process_payments',
                'owners.view',
                'reports.view', 'reports.generate',
                'notifications.view',
            ],

            'driver' => [
                'trips.view',
                'vehicles.view',
                'compliance.view', 'compliance.create', 'compliance.update',
                'notifications.view',
            ],

            'contractor' => [
                'owners.view',
                'contracts.view',
                'compliance.view', 'compliance.create', 'compliance.update',
                'vehicles.view',
                'notifications.view',
            ],

            'passenger' => [
                'trips.view',
                'passengers.view',
                'notifications.view',
            ],

            'auditor' => [
                'audit.view',
                'reports.view',
                'notifications.view',
            ],
        ];

        foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->toArray();
            $role->permissions()->sync($permissionIds);
        }
    }
}
