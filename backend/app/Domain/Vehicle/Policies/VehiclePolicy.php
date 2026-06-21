<?php

namespace App\Domain\Vehicle\Policies;

use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiclePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if (! $user->hasPermission('vehicles.view')) {
            return false;
        }

        if ($user->hasRole('contractor')) {
            return $vehicle->owner_id === $user->owner?->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if (! $user->hasPermission('vehicles.update')) {
            return false;
        }

        if ($user->hasRole('contractor')) {
            return $vehicle->owner_id === $user->owner?->id;
        }

        return true;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.delete');
    }
}
