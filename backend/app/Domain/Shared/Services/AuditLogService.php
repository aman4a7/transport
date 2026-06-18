<?php

namespace App\Domain\Shared\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public function log(
        string $action,
        Model $subject,
        ?User $actor = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
    ): void {
        DB::table('audit_logs')->insert([
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'actor_id' => $actor?->id ?? auth()->id(),
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'description' => $description ?? "$action on ".class_basename($subject),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logSensitive(
        string $action,
        Model $subject,
        ?User $actor = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
    ): void {
        $this->log($action, $subject, $actor, $oldValues, $newValues, $description);

        Log::warning("Sensitive action: {$action}", [
            'subject' => class_basename($subject).'#'.$subject->getKey(),
            'actor' => $actor?->id ?? auth()->id(),
        ]);
    }
}
