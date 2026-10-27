<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            if (! $model->shouldAudit()) {
                return;
            }

            AuditLogger::log(
                'created',
                $model,
                [],
                $model->auditableAttributes(),
                tags: $model->auditTag()
            );
        });

        static::updated(function (Model $model): void {
            if (! $model->shouldAudit()) {
                return;
            }

            $allChanges = $model->getChanges();
            $excluded = method_exists($model, 'auditExcluded')
                ? $model->auditExcluded()
                : ['created_at', 'updated_at'];
            $redactedChanges = array_values(array_diff(
                array_intersect(array_keys($allChanges), $excluded),
                ['created_at', 'updated_at']
            ));
            $changes = collect($allChanges)->except($excluded)->all();

            if ($changes === [] && $redactedChanges === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }
            if ($redactedChanges !== []) {
                $changes['redacted_fields_changed'] = $redactedChanges;
            }

            AuditLogger::log(
                'updated',
                $model,
                $old,
                $changes,
                tags: $model->auditTag()
            );
        });

        static::deleting(function (Model $model): void {
            if (! $model->shouldAudit()) {
                return;
            }

            AuditLogger::log(
                'deleted',
                $model,
                $model->auditableAttributes(),
                [],
                tags: $model->auditTag()
            );
        });
    }

    public function shouldAudit(): bool
    {
        return property_exists($this, 'auditEnabled') ? (bool) $this->auditEnabled : true;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditableAttributes(): array
    {
        $excluded = method_exists($this, 'auditExcluded')
            ? $this->auditExcluded()
            : ['created_at', 'updated_at'];

        return collect($this->getAttributes())
            ->except($excluded)
            ->all();
    }

    public function auditTag(): string
    {
        return class_basename($this);
    }
}
