<?php

namespace App\Domain\Reporting\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }

    public function generate(User $user): bool
    {
        return $user->hasPermission('reports.generate');
    }
}
