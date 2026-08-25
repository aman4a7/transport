<?php

namespace App\Domain\Garage\Policies;

use App\Domain\Garage\Models\MaintenanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('garage.view');
    }

    public function view(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('garage.create');
    }

    public function update(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.update');
    }

    public function delete(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.delete');
    }

    public function start(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.update');
    }

    public function complete(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.update');
    }

    public function cancel(User $user, MaintenanceRecord $maintenanceRecord): bool
    {
        return $user->hasPermission('garage.delete');
    }
}
