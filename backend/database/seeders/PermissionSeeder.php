<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Vehicle Management
            ['name' => 'View vehicles', 'slug' => 'vehicles.view', 'group' => 'vehicles'],
            ['name' => 'Create vehicles', 'slug' => 'vehicles.create', 'group' => 'vehicles'],
            ['name' => 'Update vehicles', 'slug' => 'vehicles.update', 'group' => 'vehicles'],
            ['name' => 'Delete vehicles', 'slug' => 'vehicles.delete', 'group' => 'vehicles'],

            // Driver Management
            ['name' => 'View drivers', 'slug' => 'drivers.view', 'group' => 'drivers'],
            ['name' => 'Create drivers', 'slug' => 'drivers.create', 'group' => 'drivers'],
            ['name' => 'Update drivers', 'slug' => 'drivers.update', 'group' => 'drivers'],
            ['name' => 'Delete drivers', 'slug' => 'drivers.delete', 'group' => 'drivers'],

            // Owner / Contractor Management
            ['name' => 'View owners', 'slug' => 'owners.view', 'group' => 'owners'],
            ['name' => 'Create owners', 'slug' => 'owners.create', 'group' => 'owners'],
            ['name' => 'Update owners', 'slug' => 'owners.update', 'group' => 'owners'],
            ['name' => 'Delete owners', 'slug' => 'owners.delete', 'group' => 'owners'],

            // Passenger Management
            ['name' => 'View passengers', 'slug' => 'passengers.view', 'group' => 'passengers'],
            ['name' => 'Create passengers', 'slug' => 'passengers.create', 'group' => 'passengers'],
            ['name' => 'Update passengers', 'slug' => 'passengers.update', 'group' => 'passengers'],
            ['name' => 'Delete passengers', 'slug' => 'passengers.delete', 'group' => 'passengers'],

            // Route Management
            ['name' => 'View routes', 'slug' => 'routes.view', 'group' => 'routes'],
            ['name' => 'Create routes', 'slug' => 'routes.create', 'group' => 'routes'],
            ['name' => 'Update routes', 'slug' => 'routes.update', 'group' => 'routes'],
            ['name' => 'Delete routes', 'slug' => 'routes.delete', 'group' => 'routes'],

            // Trip Management
            ['name' => 'View trips', 'slug' => 'trips.view', 'group' => 'trips'],
            ['name' => 'Create trips', 'slug' => 'trips.create', 'group' => 'trips'],
            ['name' => 'Update trips', 'slug' => 'trips.update', 'group' => 'trips'],
            ['name' => 'Delete trips', 'slug' => 'trips.delete', 'group' => 'trips'],
            ['name' => 'Assign trips', 'slug' => 'trips.assign', 'group' => 'trips'],

            // Fuel Management (defence-plated only)
            ['name' => 'View fuel transactions', 'slug' => 'fuel.view', 'group' => 'fuel'],
            ['name' => 'Issue fuel', 'slug' => 'fuel.create', 'group' => 'fuel'],
            ['name' => 'Update fuel transactions', 'slug' => 'fuel.update', 'group' => 'fuel'],
            ['name' => 'Delete fuel transactions', 'slug' => 'fuel.delete', 'group' => 'fuel'],
            ['name' => 'View fuel stock', 'slug' => 'fuel.view_stock', 'group' => 'fuel'],
            ['name' => 'Adjust fuel stock', 'slug' => 'fuel.adjust_stock', 'group' => 'fuel'],

            // Garage / Maintenance (defence-plated only)
            ['name' => 'View garage jobs', 'slug' => 'garage.view', 'group' => 'garage'],
            ['name' => 'Create maintenance requests', 'slug' => 'garage.create', 'group' => 'garage'],
            ['name' => 'Update garage jobs', 'slug' => 'garage.update', 'group' => 'garage'],
            ['name' => 'Delete garage jobs', 'slug' => 'garage.delete', 'group' => 'garage'],

            // Compliance
            ['name' => 'View compliance records', 'slug' => 'compliance.view', 'group' => 'compliance'],
            ['name' => 'Create compliance documents', 'slug' => 'compliance.create', 'group' => 'compliance'],
            ['name' => 'Update compliance documents', 'slug' => 'compliance.update', 'group' => 'compliance'],
            ['name' => 'Delete compliance documents', 'slug' => 'compliance.delete', 'group' => 'compliance'],
            ['name' => 'Verify compliance documents', 'slug' => 'compliance.verify', 'group' => 'compliance'],
            ['name' => 'Reject compliance documents', 'slug' => 'compliance.reject', 'group' => 'compliance'],

            // Contracts
            ['name' => 'View contracts', 'slug' => 'contracts.view', 'group' => 'contracts'],
            ['name' => 'Create contracts', 'slug' => 'contracts.create', 'group' => 'contracts'],
            ['name' => 'Update contracts', 'slug' => 'contracts.update', 'group' => 'contracts'],
            ['name' => 'Delete contracts', 'slug' => 'contracts.delete', 'group' => 'contracts'],
            ['name' => 'Process payments', 'slug' => 'contracts.process_payments', 'group' => 'contracts'],

            // Notifications
            ['name' => 'View notifications', 'slug' => 'notifications.view', 'group' => 'notifications'],
            ['name' => 'Manage notifications', 'slug' => 'notifications.manage', 'group' => 'notifications'],

            // Reports
            ['name' => 'View reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'Generate reports', 'slug' => 'reports.generate', 'group' => 'reports'],

            // Administration
            ['name' => 'View users', 'slug' => 'users.view', 'group' => 'admin'],
            ['name' => 'Create users', 'slug' => 'users.create', 'group' => 'admin'],
            ['name' => 'Update users', 'slug' => 'users.update', 'group' => 'admin'],
            ['name' => 'Delete users', 'slug' => 'users.delete', 'group' => 'admin'],
            ['name' => 'Manage roles', 'slug' => 'admin.roles', 'group' => 'admin'],
            ['name' => 'Manage permissions', 'slug' => 'admin.permissions', 'group' => 'admin'],
            ['name' => 'View system settings', 'slug' => 'admin.settings', 'group' => 'admin'],
            ['name' => 'Update system settings', 'slug' => 'admin.settings.update', 'group' => 'admin'],

            // Audit
            ['name' => 'View audit logs', 'slug' => 'audit.view', 'group' => 'audit'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData,
            );
        }
    }
}
