<?php

namespace App\Domain\Contract\Models;

use App\Domain\Contract\Enums\ContractStatus;
use App\Domain\Owner\Models\Owner;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['vehicle_id', 'owner_id', 'contract_number', 'start_date', 'end_date', 'status', 'contract_value', 'payment_terms', 'notes', 'created_by', 'updated_by'])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): ContractFactory
    {
        return ContractFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'contract_value' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
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
