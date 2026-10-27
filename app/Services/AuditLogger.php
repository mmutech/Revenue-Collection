<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class AuditLogger
{
    /** @var array<int, string> */
    private const REDACTED = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function log(
        string $event,
        Model|string|null $subject = null,
        array $old = [],
        array $new = [],
        ?string $reason = null,
        ?string $tags = null,
        ?User $actor = null,
    ): AuditLog {
        $subjectType = $subject instanceof Model ? $subject->getMorphClass() : $subject;

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey() ?? Auth::id(),
            'event' => $event,
            'auditable_type' => $subjectType,
            'auditable_id' => $subject instanceof Model ? $subject->getKey() : null,
            'old_values' => self::clean($old) ?: null,
            'new_values' => self::clean($new) ?: null,
            'reason' => $reason,
            'ip_address' => self::requestIp(),
            'user_agent' => self::userAgent(),
            'url' => self::url(),
            'tags' => $tags,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function clean(array $values): array
    {
        foreach (self::REDACTED as $key) {
            unset($values[$key]);
        }

        return array_filter(
            $values,
            fn ($value) => ! is_object($value) && ! is_resource($value)
        );
    }

    private static function requestIp(): ?string
    {
        return app()->runningInConsole() ? null : request()?->ip();
    }

    private static function userAgent(): ?string
    {
        $agent = app()->runningInConsole() ? null : request()?->userAgent();

        return $agent ? mb_substr($agent, 0, 1000) : null;
    }

    private static function url(): ?string
    {
        return app()->runningInConsole() ? null : request()?->fullUrl();
    }
}
