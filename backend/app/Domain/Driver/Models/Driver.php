<?php

namespace App\Domain\Driver\Models;

use App\Domain\Driver\Enums\DriverStatus;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'license_number', 'license_category', 'license_expiry', 'status', 'medical_expiry', 'assigned_vehicle_id', 'created_by', 'updated_by'])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): DriverFactory
    {
        return DriverFactory::new();
    }

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'medical_expiry' => 'date',
            'status' => DriverStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
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
