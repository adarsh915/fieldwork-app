<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public bool $disableAuditLog = false;

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            if (app()->runningInConsole() && !auth()->check()) {
                return;
            }

            if (!empty($model->disableAuditLog)) {
                return;
            }

            $newValues = static::filterAuditableAttributes($model->getAttributes(), $model);

            $reason = request()->input('reason')
                ?? request()->input('note')
                ?? ('Created ' . class_basename($model));

            AuditLog::logEvent(
                event: strtolower(class_basename($model)) . '.created',
                auditable: $model,
                oldValues: null,
                newValues: $newValues,
                reason: $reason
            );
        });

        static::updated(function (Model $model) {
            if (app()->runningInConsole() && !auth()->check()) {
                return;
            }

            if (!empty($model->disableAuditLog)) {
                return;
            }

            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            $oldValues = static::filterAuditableAttributes($original, $model);
            $newValues = static::filterAuditableAttributes($changes, $model);

            $reason = request()->input('reason')
                ?? request()->input('rejection_reason')
                ?? request()->input('final_note')
                ?? request()->input('temp_approved_note')
                ?? request()->input('remarks')
                ?? request()->input('note')
                ?? ('Updated ' . class_basename($model));

            AuditLog::logEvent(
                event: strtolower(class_basename($model)) . '.updated',
                auditable: $model,
                oldValues: $oldValues,
                newValues: $newValues,
                reason: $reason
            );
        });

        static::deleted(function (Model $model) {
            if (app()->runningInConsole() && !auth()->check()) {
                return;
            }

            if (!empty($model->disableAuditLog)) {
                return;
            }

            $attributes = static::filterAuditableAttributes($model->getAttributes(), $model);

            $reason = request()->input('reason')
                ?? request()->input('delete_reason')
                ?? ('Deleted ' . class_basename($model));

            AuditLog::logEvent(
                event: strtolower(class_basename($model)) . '.deleted',
                auditable: $model,
                oldValues: $attributes,
                newValues: null,
                reason: $reason
            );
        });
    }

    protected static function filterAuditableAttributes(array $attributes, Model $model): array
    {
        $sensitiveKeys = array_merge(
            $model->getHidden() ?? [],
            ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'token']
        );

        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = '[REDACTED]';
            }
        }

        return $attributes;
    }
}
