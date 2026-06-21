<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_role', 'user_id', 'role_id')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->select('permissions.*')
            ->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();

        return $this->roles()
            ->whereIn('slug', $roles)
            ->where(function ($query) {
                $query->whereNull('user_role.expires_at')
                    ->orWhere('user_role.expires_at', '>', now());
            })
            ->exists();
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles(array $roles): bool
    {
        return collect($roles)->every(fn ($role) => $this->hasRole($role));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->where(function ($query) {
                $query->whereNull('user_role.expires_at')
                    ->orWhere('user_role.expires_at', '>', now());
            })
            ->whereHas('permissions', function ($query) use ($permission): void {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->where(function ($query) {
                $query->whereNull('user_role.expires_at')
                    ->orWhere('user_role.expires_at', '>', now());
            })
            ->whereHas('permissions', function ($query) use ($permissions): void {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    public function hasAllPermissions(array $permissions): bool
    {
        return collect($permissions)->every(fn ($perm) => $this->hasPermission($perm));
    }

    public function assignRole(string|array $roles, ?int $assignedBy = null): void
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        $assignedBy = $assignedBy ?? auth()->id();

        $roleIds = Role::whereIn('slug', $roles)->pluck('id')->toArray();

        foreach ($roleIds as $roleId) {
            $this->roles()->syncWithoutDetaching([
                $roleId => ['assigned_by' => $assignedBy, 'assigned_at' => now()],
            ]);
        }
    }

    public function removeRole(string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        $roleIds = Role::whereIn('slug', $roles)->pluck('id')->toArray();
        $this->roles()->detach($roleIds);
    }

    public function syncRoles(array $roles, ?int $assignedBy = null): void
    {
        $assignedBy = $assignedBy ?? auth()->id();
        $roleIds = Role::whereIn('slug', $roles)->pluck('id')->toArray();

        $pivotData = [];
        foreach ($roleIds as $roleId) {
            $pivotData[$roleId] = ['assigned_by' => $assignedBy, 'assigned_at' => now()];
        }

        $this->roles()->sync($pivotData);
    }
}
