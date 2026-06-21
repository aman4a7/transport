<?php

namespace App\Domain\Route\Policies;

use App\Domain\Route\Models\Route;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoutePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('routes.view');
    }

    public function view(User $user, Route $route): bool
    {
        return $user->hasPermission('routes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('routes.create');
    }

    public function update(User $user, Route $route): bool
    {
        return $user->hasPermission('routes.update');
    }

    public function delete(User $user, Route $route): bool
    {
        return $user->hasPermission('routes.delete');
    }
}
