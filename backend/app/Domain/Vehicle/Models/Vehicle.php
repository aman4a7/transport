<?php

namespace App\Domain\Vehicle\Models;

use App\Domain\Owner\Models\Owner;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Enums\FuelType;
use App\Domain\Vehicle\Enums\VehicleCategory;
use App\Domain\Vehicle\Enums\VehicleStatus;
use App\Models\User;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['owner_id', 'plate_number', 'make', 'model', 'year', 'color', 'vin', 'engine_number', 'seating_capacity', 'fuel_type', 'category', 'status', 'status_changed_at', 'registration_expiry', 'insurance_expiry', 'created_by', 'updated_by'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seating_capacity' => 'integer',
            'category' => VehicleCategory::class,
            'status' => VehicleStatus::class,
            'fuel_type' => FuelType::class,
            'registration_expiry' => 'date',
            'insurance_expiry' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $vehicle): void {
            if ($vehicle->isDirty('status')) {
                $vehicle->status_changed_at = now();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
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
