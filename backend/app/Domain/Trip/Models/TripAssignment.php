<?php

namespace App\Domain\Trip\Models;

use App\Domain\Passenger\Models\Passenger;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Trip\Enums\TripAssignmentStatus;
use Database\Factories\TripAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAssignment extends Model
{
    /** @use HasFactory<TripAssignmentFactory> */
    use Auditable, HasFactory;

    protected static function newFactory(): TripAssignmentFactory
    {
        return TripAssignmentFactory::new();
    }

    protected $fillable = ['trip_id', 'passenger_id', 'status'];

    protected function casts(): array
    {
        return [
            'status' => TripAssignmentStatus::class,
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }
}
