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

            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
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
