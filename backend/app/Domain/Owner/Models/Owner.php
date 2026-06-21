<?php

namespace App\Domain\Owner\Models;

use App\Domain\Owner\Enums\OwnerStatus;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'company_name', 'contact_person', 'phone', 'email', 'address', 'status', 'created_by', 'updated_by'])]
class Owner extends Model
{
    /** @use HasFactory<OwnerFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): OwnerFactory
    {
        return OwnerFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => OwnerStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
