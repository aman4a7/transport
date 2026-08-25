<?php

namespace App\Domain\Fuel\Models;

use App\Domain\Fuel\Enums\FuelTransactionType;
use App\Domain\Shared\Casts\DecimalNumber;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\FuelTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelTransaction extends Model
{
    /** @use HasFactory<FuelTransactionFactory> */
    use Auditable, HasFactory;

    protected static function newFactory(): FuelTransactionFactory
    {
        return FuelTransactionFactory::new();
    }

    const UPDATED_AT = null;

    protected $fillable = ['vehicle_id', 'fuel_type', 'quantity', 'transaction_type', 'unit_cost', 'total_cost', 'issued_by', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => DecimalNumber::class.':2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'transaction_type' => FuelTransactionType::class,
            'created_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
