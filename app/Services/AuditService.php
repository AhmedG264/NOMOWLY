<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an audit event in the database.
     * Fully backwards compatible while supporting status and reason.
     */
    public static function log(
        AuditAction $action,
        ?string $userId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payloadDiff = null,
        string $status = 'SUCCESS',
        ?string $reason = null
    ): AuditLog {
        // Sanitize payload diff to ensure sensitive tokens, secrets, or passwords are never logged
        $sanitizedDiff = $payloadDiff !== null ? self::sanitize($payloadDiff) : null;

        // Auto-detect status if not explicitly passed
        $finalStatus = $status;
        if ($status === 'SUCCESS') {
            if ($action === AuditAction::LOGIN_FAILED) {
                $finalStatus = 'FAILED';
            } elseif ($action === AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT) {
                $finalStatus = 'UNAUTHORIZED';
            } elseif ($action === AuditAction::CAMPAIGN_APPLICATION_REJECTED || $action === AuditAction::CONVERSION_REJECTED) {
                $finalStatus = 'REJECTED';
            } elseif ($action === AuditAction::CONVERSION_CANCELLED) {
                $finalStatus = 'CANCELLED';
            } elseif ($action === AuditAction::CAMPAIGN_APPLICATION_APPROVED || $action === AuditAction::CONVERSION_APPROVED) {
                $finalStatus = 'APPROVED';
            }
        }

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'status' => $finalStatus,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload_diff' => $sanitizedDiff,
            'reason' => $reason ? substr($reason, 0, 500) : null,
            'ip_address' => Request::ip() ?? '127.0.0.1',
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
            'access_token',
            'refresh_token',
            'bearer_token',
            'auth_token',
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
