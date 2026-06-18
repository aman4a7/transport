<?php

namespace App\Domain\Vehicle\Policies;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Enums\VehicleCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * EXAMPLE: Vehicle policy using the laravel-backend skill templates.
 *
 * Key patterns demonstrated:
 * - Permission-based checks via hasPermission()
 * - Ownership scope checks (contractors can only see their vehicles)
 * - Defence-plated eligibility guard for fuel/garage operations
 * - Custom policy method for status changes
 */
class VehiclePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vehicles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    /**
     * Determine whether the user can view the vehicle.
     * Contractors can only view their own vehicles.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        if (!$user->hasPermission('vehicles.view')) {
            return false;
        }

        // Scope check: contractors see only their own vehicles
        if ($user->hasRole('contractor')) {
            return $vehicle->owner_id === $user->owner_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create vehicles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('vehicles.create');
    }

    /**
     * Determine whether the user can update the vehicle.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        if (!$user->hasPermission('vehicles.update')) {
            return false;
        }

        // Scope check: contractors can only update their own vehicles
        if ($user->hasRole('contractor')) {
            return $vehicle->owner_id === $user->owner_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the vehicle.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.delete');
    }

    /**
     * Determine whether the user can update the vehicle's operational status.
     */
    public function updateStatus(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.update_status');
    }

    /**
     * Determine whether the vehicle is eligible for fuel operations.
     * CORE BUSINESS RULE: Only defence-plated vehicles can use university fuel.
     */
    public function accessFuel(User $user, Vehicle $vehicle): bool
    {
        if (!$user->hasPermission('fuel.issue')) {
            return false;
        }

        return $vehicle->category === VehicleCategory::DefencePlated->value;
    }

    /**
     * Determine whether the vehicle is eligible for garage operations.
     * CORE BUSINESS RULE: Only defence-plated vehicles can use university garage.
     */
    public function accessGarage(User $user, Vehicle $vehicle): bool
    {
        if (!$user->hasPermission('garage.create_job')) {
            return false;
        }

        return $vehicle->category === VehicleCategory::DefencePlated->value;
    }
}
