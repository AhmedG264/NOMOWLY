<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an audit event in the database.
     */
    public static function log(
        AuditAction $action,
        ?string $userId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payloadDiff = null
    ): AuditLog {
        // Sanitize payload diff to ensure sensitive tokens or passwords are never logged
        $sanitizedDiff = $payloadDiff !== null ? self::sanitize($payloadDiff) : null;

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload_diff' => $sanitizedDiff,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize sensitive keys recursively.
     */
    protected static function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'remember_token',
            'secret',
            'api_secret',
            'api_key',
            'signature',
            'webhook_secret',
            'credit_card',
            'iban',
            'admin_initial_password',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}
