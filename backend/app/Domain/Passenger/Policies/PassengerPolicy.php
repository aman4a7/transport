<?php

namespace App\Domain\Passenger\Policies;

use App\Domain\Passenger\Models\Passenger;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PassengerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('passengers.view');
    }

    public function view(User $user, Passenger $passenger): bool
    {
        return $user->hasPermission('passengers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('passengers.create');
    }

    public function update(User $user, Passenger $passenger): bool
    {
        return $user->hasPermission('passengers.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('passengers.delete');
    }
}
