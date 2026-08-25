<?php

namespace App\Domain\Trip\Models;

use App\Domain\Driver\Models\Driver;
use App\Domain\Passenger\Models\Passenger;
use App\Domain\Route\Models\Route;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Trip\Enums\TripStatus;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['route_id', 'vehicle_id', 'driver_id', 'scheduled_date', 'departure_time', 'estimated_arrival_time', 'actual_departure_time', 'actual_arrival_time', 'status', 'notes', 'created_by', 'updated_by'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): TripFactory
    {
        return TripFactory::new();
    }

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'departure_time' => 'string',
            'estimated_arrival_time' => 'string',
            'actual_departure_time' => 'datetime',
            'actual_arrival_time' => 'datetime',
            'status' => TripStatus::class,
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function passengers()
    {
        return $this->hasManyThrough(Passenger::class, TripAssignment::class, 'trip_id', 'id', 'id', 'passenger_id');
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
