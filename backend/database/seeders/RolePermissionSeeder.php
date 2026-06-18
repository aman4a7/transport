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
                'routes.view', 'routes.create', 'routes.update', 'routes.delete',
                'trips.view', 'trips.create', 'trips.update', 'trips.delete', 'trips.assign',
                'owners.view',
                'passengers.view',
                'reports.view', 'reports.generate',
            ],

            'compliance_officer' => [
                'compliance.view', 'compliance.verify', 'compliance.reject',
                'drivers.view',
                'vehicles.view',
                'contracts.view',
                'owners.view',
                'reports.view',
            ],

            'fuel_attendant' => [
                'fuel.view', 'fuel.create', 'fuel.update', 'fuel.view_stock', 'fuel.adjust_stock',
                'vehicles.view',
            ],

            'garage_officer' => [
                'garage.view', 'garage.create', 'garage.update', 'garage.delete',
                'vehicles.view',
            ],

            'finance_officer' => [
                'contracts.view', 'contracts.create', 'contracts.update', 'contracts.process_payments',
                'owners.view',
                'reports.view', 'reports.generate',
            ],

            'driver' => [
                'trips.view',
                'vehicles.view',
                'compliance.view',
            ],

            'contractor' => [
                'owners.view',
                'contracts.view',
                'compliance.view',
                'vehicles.view',
            ],

            'passenger' => [
                'trips.view',
                'passengers.view',
            ],

            'auditor' => [
                'audit.view',
                'reports.view',
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
