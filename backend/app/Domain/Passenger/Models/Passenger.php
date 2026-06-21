<?php

namespace App\Domain\Passenger\Models;

use App\Domain\Passenger\Enums\PassengerStatus;
use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\PassengerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'employee_id', 'first_name', 'last_name', 'email', 'phone', 'department', 'status', 'created_by', 'updated_by'])]
class Passenger extends Model
{
    /** @use HasFactory<PassengerFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected static function newFactory(): PassengerFactory
    {
        return PassengerFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => PassengerStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
