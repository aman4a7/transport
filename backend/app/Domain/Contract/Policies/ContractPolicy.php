<?php

namespace App\Domain\Contract\Policies;

use App\Domain\Contract\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contracts.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        if (! $user->hasPermission('contracts.view')) {
            return false;
        }

        if ($user->hasRole('contractor')) {
            return $contract->owner_id === $user->owner?->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasPermission('contracts.update');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasPermission('contracts.delete');
    }

    public function processPayments(User $user, Contract $contract): bool
    {
        return $user->hasPermission('contracts.process_payments');
    }

    public function activate(User $user, Contract $contract): bool
    {
        return $user->hasPermission('contracts.update');
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $user->hasPermission('contracts.update');
    }
}
