<?php

namespace App\Domain\Fuel\Models;

use App\Domain\Shared\Casts\DecimalNumber;
use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\FuelStockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['fuel_type', 'current_quantity', 'minimum_quantity', 'last_restocked_at', 'notes', 'created_by', 'updated_by'])]
class FuelStock extends Model
{
    /** @use HasFactory<FuelStockFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): FuelStockFactory
    {
        return FuelStockFactory::new();
    }

    protected function casts(): array
    {
        return [
            'current_quantity' => DecimalNumber::class.':2',
            'minimum_quantity' => DecimalNumber::class.':2',
            'last_restocked_at' => 'datetime',
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
