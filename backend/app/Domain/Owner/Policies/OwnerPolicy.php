<?php

namespace App\Domain\Owner\Policies;

use App\Domain\Owner\Models\Owner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OwnerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('owners.view');
    }

    public function view(User $user, Owner $owner): bool
    {
        return $user->hasPermission('owners.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('owners.create');
    }

    public function update(User $user, Owner $owner): bool
    {
        return $user->hasPermission('owners.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('owners.delete');
    }
}
