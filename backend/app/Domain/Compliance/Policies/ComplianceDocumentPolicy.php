<?php

namespace App\Domain\Compliance\Policies;

use App\Domain\Compliance\Models\ComplianceDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplianceDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('compliance.view');
    }

    public function view(User $user, ComplianceDocument $document): bool
    {
        if (! $user->hasPermission('compliance.view')) {
            return false;
        }

        if ($user->hasRole('driver')) {
            return $document->documentable_type === 'App\Domain\Driver\Models\Driver'
                && $document->documentable_id === $user->driver?->id;
        }

        if ($user->hasRole('contractor')) {
            return $document->documentable_type === 'App\Domain\Owner\Models\Owner'
                && $document->documentable_id === $user->owner?->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('compliance.create');
    }

    public function update(User $user, ComplianceDocument $document): bool
    {
        if (! $user->hasPermission('compliance.update')) {
            return false;
        }

        if ($user->hasRole('driver')) {
            return $document->documentable_type === 'App\Domain\Driver\Models\Driver'
                && $document->documentable_id === $user->driver?->id;
        }

        if ($user->hasRole('contractor')) {
            return $document->documentable_type === 'App\Domain\Owner\Models\Owner'
                && $document->documentable_id === $user->owner?->id;
        }

        return true;
    }

    public function delete(User $user, ComplianceDocument $document): bool
    {
        return $user->hasPermission('compliance.delete');
    }

    public function approve(User $user, ComplianceDocument $document): bool
    {
        return $user->hasRole('compliance_officer')
            && $user->hasPermission('compliance.verify');
    }

    public function reject(User $user, ComplianceDocument $document): bool
    {
        return $user->hasRole('compliance_officer')
            && $user->hasPermission('compliance.reject');
    }
}
