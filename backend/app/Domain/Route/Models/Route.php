<?php

namespace App\Domain\Route\Models;

use App\Domain\Route\Enums\RouteStatus;
use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\RouteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'description', 'origin', 'destination', 'distance_km', 'estimated_duration_minutes', 'capacity', 'status', 'created_by', 'updated_by'])]
class Route extends Model
{
    /** @use HasFactory<RouteFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): RouteFactory
    {
        return RouteFactory::new();
    }

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'capacity' => 'integer',
            'status' => RouteStatus::class,
        ];
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
