<?php

namespace App\Domain\Driver\Policies;

use App\Domain\Driver\Models\Driver;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DriverPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('drivers.view');
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->hasPermission('drivers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('drivers.create');
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->hasPermission('drivers.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('drivers.delete');
    }
}
