<?php

namespace App\Domain\Compliance\Models;

use App\Models\User;
use Database\Factories\ComplianceDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['documentable_type', 'documentable_id', 'type', 'file_path', 'original_name', 'mime_type', 'file_size', 'status', 'rejection_reason', 'uploaded_by', 'reviewed_by', 'reviewed_at', 'expiry_date'])]
class ComplianceDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
            'expiry_date' => 'date',
            'status' => ComplianceDocumentStatus::class,
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }
}
