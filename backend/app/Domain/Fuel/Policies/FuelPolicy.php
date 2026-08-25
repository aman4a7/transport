<?php

namespace App\Domain\Fuel\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuelPolicy
{
    use HandlesAuthorization;

    public function viewTransactions(User $user): bool
    {
        return $user->hasPermission('fuel.view');
    }

    public function viewTransaction(User $user): bool
    {
        return $user->hasPermission('fuel.view');
    }

    public function issue(User $user): bool
    {
        return $user->hasPermission('fuel.create');
    }

    public function restock(User $user): bool
    {
        return $user->hasPermission('fuel.create');
    }

    public function adjustStock(User $user): bool
    {
        return $user->hasPermission('fuel.adjust_stock');
    }

    public function viewStock(User $user): bool
    {
        return $user->hasPermission('fuel.view_stock');
    }
}
