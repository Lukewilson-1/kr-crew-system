<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class EloquentAuditObserver
{
    public function created(Model $model): void
    {
        $before = null;
        $after = AuditLogger::snapshot($model);

        AuditLogger::record(
            event: 'created',
            entityType: $model::class,
            entityId: $model->getKey(),
            before: $before,
            after: $after,
            changes: null,
        );
    }

    public function updated(Model $model): void
    {
        $before = [];
        $after = AuditLogger::snapshot($model);

        foreach ($model->getChanges() as $attribute => $newValue) {
            $before[$attribute] = $model->getOriginal($attribute);
        }

        $changes = AuditLogger::diff($before, $after);

        if (empty($changes)) {
            return;
        }

        AuditLogger::record(
            event: 'updated',
            entityType: $model::class,
            entityId: $model->getKey(),
            before: $before ?: null,
            after: $after,
            changes: $changes,
        );
    }

    public function deleted(Model $model): void
    {
        AuditLogger::record(
            event: 'deleted',
            entityType: $model::class,
            entityId: $model->getKey(),
            before: AuditLogger::snapshot($model),
            after: null,
        );
    }

    public function restored(Model $model): void
    {
        AuditLogger::record(
            event: 'restored',
            entityType: $model::class,
            entityId: $model->getKey(),
            before: null,
            after: AuditLogger::snapshot($model),
        );
    }
}