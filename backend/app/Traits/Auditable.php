<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            AuditLog::logEvent(
                event: strtolower(class_basename($model)) . '.updated',
                auditable: $model,
                oldValues: $original,
                newValues: $changes,
                reason: request()->input('reason', 'Admin update')
            );
        });

        static::deleted(function ($model) {
            AuditLog::logEvent(
                event: strtolower(class_basename($model)) . '.deleted',
                auditable: $model,
                oldValues: $model->getAttributes(),
                newValues: null,
                reason: request()->input('reason', 'Admin deletion')
            );
        });
    }
}
