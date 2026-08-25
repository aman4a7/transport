<?php

namespace App\Domain\Garage\Models;

use App\Domain\Garage\Enums\MaintenanceStatus;
use App\Domain\Garage\Enums\MaintenanceType;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\MaintenanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['vehicle_id', 'maintenance_type', 'status', 'description', 'scheduled_date', 'started_at', 'completed_at', 'cost', 'notes', 'performed_by', 'created_by', 'updated_by'])]
class MaintenanceRecord extends Model
{
    /** @use HasFactory<MaintenanceRecordFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): MaintenanceRecordFactory
    {
        return MaintenanceRecordFactory::new();
    }

    protected function casts(): array
    {
        return [
            'maintenance_type' => MaintenanceType::class,
            'status' => MaintenanceStatus::class,
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
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
