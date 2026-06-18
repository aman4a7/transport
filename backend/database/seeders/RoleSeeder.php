<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'System Administrator',
                'slug' => 'system_administrator',
                'description' => 'Full system access including user management, role assignment, and system configuration',
                'is_system' => true,
            ],
            [
                'name' => 'Transport Manager',
                'slug' => 'transport_manager',
                'description' => 'Manages vehicles, drivers, routes, trips, and fleet operations',
                'is_system' => true,
            ],
            [
                'name' => 'Compliance Officer',
                'slug' => 'compliance_officer',
                'description' => 'Manages driver, vehicle, and contract compliance documents and verification workflows',
                'is_system' => true,
            ],
            [
                'name' => 'Fuel Attendant',
                'slug' => 'fuel_attendant',
                'description' => 'Issues fuel to defence-plated vehicles and manages fuel stock records',
                'is_system' => true,
            ],
            [
                'name' => 'Garage Officer',
                'slug' => 'garage_officer',
                'description' => 'Manages maintenance requests and garage jobs for defence-plated vehicles',
                'is_system' => true,
            ],
            [
                'name' => 'Finance Officer',
                'slug' => 'finance_officer',
                'description' => 'Manages contracts, payments, and financial reporting for contracted vehicles',
                'is_system' => true,
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'description' => 'Assigned to trips, views schedules, submits compliance documents',
                'is_system' => true,
            ],
            [
                'name' => 'Contractor',
                'slug' => 'contractor',
                'description' => 'Private vehicle owner with contracted vehicle; manages own vehicle documents and contract',
                'is_system' => true,
            ],
            [
                'name' => 'Passenger',
                'slug' => 'passenger',
                'description' => 'Staff transport user; views trips, requests rides, manages profile',
                'is_system' => true,
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Read-only access to audit logs, compliance reports, and system activity',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData,
            );
        }
    }
}
