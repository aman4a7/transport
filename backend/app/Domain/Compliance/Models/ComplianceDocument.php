<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Compliance\Enums\ComplianceDocumentType;
use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Database\Factories\ComplianceDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComplianceDocument extends Model
{
    /** @use HasFactory<ComplianceDocumentFactory> */
    use HasFactory;

    protected $table = 'compliance_documents';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'type',
        'status',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'issued_at',
        'expires_at',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => ComplianceDocumentType::class,
            'status' => ComplianceStatus::class,
            'file_size' => 'integer',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ComplianceDocumentFactory
    {
        return ComplianceDocumentFactory::new();
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', ComplianceStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ComplianceStatus::Approved);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', ComplianceStatus::Expired);
    }

    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('documentable_type', Vehicle::class)
            ->where('documentable_id', $vehicleId);
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('documentable_type', Driver::class)
            ->where('documentable_id', $driverId);
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('documentable_type', Owner::class)
            ->where('documentable_id', $ownerId);
    }
}
