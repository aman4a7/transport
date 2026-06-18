<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'is_system'])]
class Role extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()
            ->where('slug', $permissionSlug)
            ->exists();
    }

    public function givePermissionTo(Permission|string $permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : Permission::where('slug', $permission)->value('id');

        if ($permissionId) {
            $this->permissions()->syncWithoutDetaching([$permissionId]);
        }
    }

    public function revokePermission(Permission|string $permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : Permission::where('slug', $permission)->value('id');

        if ($permissionId) {
            $this->permissions()->detach($permissionId);
        }
    }
}
