<?php

namespace App\Domain\Trip\Policies;

use App\Domain\Passenger\Models\Passenger;
use App\Domain\Trip\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TripPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('trips.view');
    }

    public function view(User $user, Trip $trip): bool
    {
        if (! $user->hasPermission('trips.view')) {
            return false;
        }

        if ($user->hasRole('driver')) {
            return $trip->driver_id === $user->driver?->id;
        }

        if ($user->hasRole('passenger')) {
            $passengerId = Passenger::where('user_id', $user->id)->value('id');

            return $passengerId !== null && $trip->assignments()->where('passenger_id', $passengerId)->exists();
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('trips.create');
    }

    public function update(User $user, Trip $trip): bool
    {
        if (! $user->hasPermission('trips.update')) {
            return false;
        }

        if ($user->hasRole('driver')) {
            return $trip->driver_id === $user->driver?->id;
        }

        return true;
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.delete');
    }

    public function assign(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.assign');
    }

    public function start(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.update');
    }

    public function complete(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.update');
    }

    public function cancel(User $user, Trip $trip): bool
    {
        return $user->hasPermission('trips.update');
    }
}
