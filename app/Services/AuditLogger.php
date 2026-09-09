<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

class AuditLogger
{
    /**
     * Record an audit event. Append-only: rows are inserted, never updated.
     *
     * @param string        $event      created|updated|deleted|restored|pivot_attached|pivot_detached|...
     * @param string        $entityType Fully-qualified model class name.
     * @param string|int    $entityId   Record identifier.
     * @param array|null    $before     Snapshot of state prior to the change.
     * @param array|null    $after      Snapshot of state after the change.
     * @param array|null    $changes    Attribute-level diff (for updates).
     * @param array         $metadata   Optional extra context (route, source, etc.).
     */
    public static function record(
        string $event,
        string $entityType,
        string|int $entityId,
        ?array $before = null,
        ?array $after = null,
        ?array $changes = null,
        array $metadata = []
    ): void {
        try {
            AuditLog::create([
                'actor_username'   => self::resolveActorUsername(),
                'actor_ip'         => self::resolveActorIp(),
                'actor_user_agent' => self::resolveActorUserAgent(),
                'event'            => $event,
                'entity_type'      => $entityType,
                'entity_id'        => (string) $entityId,
                'before'           => self::redact($before),
                'after'            => self::redact($after),
                'changes'          => self::redact($changes),
                'metadata'         => $metadata ?: null,
                'created_at'       => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Audit log write failed: '.$e->getMessage());
        }
    }

    /**
     * Snapshot a model's attributes for auditing.
     */
    public static function snapshot(\Illuminate\Database\Eloquent\Model $model): array
    {
        return $model->getAttributes();
    }

    /**
     * Compute an attribute-level diff (before => after) for updated records.
     */
    public static function diff(array $before, array $after): array
    {
        $diff = [];

        foreach ($after as $key => $value) {
            if (! array_key_exists($key, $before) || $before[$key] !== $value) {
                $diff[$key] = ['before' => $before[$key] ?? null, 'after' => $value];
            }
        }

        foreach ($before as $key => $value) {
            if (! array_key_exists($key, $after)) {
                $diff[$key] = ['before' => $value, 'after' => null];
            }
        }

        return $diff;
    }

    /**
     * Remove sensitive values from any snapshot/diff before persistence.
     */
    public static function redact(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $redacted = [];

        foreach ($data as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = self::redact($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        if (in_array($normalized, ['password', 'pw', 'remember_token', 'secret', 'cron_token'], true)) {
            return true;
        }

        foreach (['_token', '_secret', '_key', 'password', 'token'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function resolveActorUsername(): ?string
    {
        $user = auth()->user();

        if ($user !== null) {
            $username = $user->getAuthIdentifierName() === 'username'
                ? $user->username
                : $user->getAuthIdentifier();

            return $username !== null && $username !== '' ? (string) $username : null;
        }

        return app()->runningInConsole() ? 'system' : null;
    }

    private static function resolveActorIp(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return Request::ip();
    }

    private static function resolveActorUserAgent(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $agent = Request::userAgent();

        $length = function_exists('mb_strlen') ? mb_strlen($agent) : strlen($agent);

        return $length > 500
            ? (function_exists('mb_substr') ? mb_substr($agent, 0, 500) : substr($agent, 0, 500))
            : $agent;
    }
}