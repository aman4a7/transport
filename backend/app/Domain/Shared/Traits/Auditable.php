<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogService::class)->log('created', $model, null, null, $model->toArray());
        });

        static::updated(function (Model $model): void {
            $changed = $model->getDirty();
            $old = [];
            $new = [];
            foreach ($changed as $key => $value) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            if (! empty($changed)) {
                app(AuditLogService::class)->log('updated', $model, null, $old, $new);
            }
        });

        static::deleted(function (Model $model): void {
            app(AuditLogService::class)->log('deleted', $model, null, $model->toArray(), null);
        });
    }
}
