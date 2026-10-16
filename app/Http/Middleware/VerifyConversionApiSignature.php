<?php

namespace App\Http\Middleware;

use App\Models\BrandIntegration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyConversionApiSignature
{
    /**
     * Tolerance window for replay attack prevention in seconds (5 minutes)
     */
    public const TIMESTAMP_TOLERANCE_SECONDS = 300;

    /**
     * Handle an incoming conversion ingestion request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract API Key (from X-Nomowly-Key or Authorization Bearer header)
        $apiKey = $request->header('X-Nomowly-Key');
        if (empty($apiKey)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $apiKey = substr($authHeader, 7);
            }
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'error' => 'unauthenticated',
                'message' => 'Missing API key. Provide X-Nomowly-Key or Bearer token.',
            ], 401);
        }

        // 2. Locate Active Brand Integration
        $integration = BrandIntegration::with('brandProfile')
            ->where('api_key', $apiKey)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$integration || !$integration->brandProfile) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Invalid or inactive integration credentials.',
            ], 401);
        }

        // 3. Extract and Validate Timestamp Header (Replay Attack Prevention)
        $timestamp = $request->header('X-Nomowly-Timestamp');
        if (empty($timestamp) || !is_numeric($timestamp)) {
            return response()->json([
                'success' => false,
                'error' => 'missing_timestamp',
                'message' => 'Missing or invalid X-Nomowly-Timestamp header.',
            ], 401);
        }

        $currentTime = time();
        $requestTime = (int) $timestamp;

        if (abs($currentTime - $requestTime) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return response()->json([
                'success' => false,
                'error' => 'timestamp_out_of_bounds',
                'message' => 'Request timestamp is outside the allowed 5-minute tolerance window.',
            ], 401);
        }

        // 4. Extract and Validate Signature Header (HMAC-SHA256)
        $providedSignature = $request->header('X-Nomowly-Signature');
        if (empty($providedSignature)) {
            return response()->json([
                'success' => false,
                'error' => 'missing_signature',
                'message' => 'Missing X-Nomowly-Signature header.',
            ], 401);
        }

        // Canonical payload: timestamp + "." + raw_request_body
        $rawBody = $request->getContent();
        if (!$integration->verifySignature($rawBody, (string) $timestamp, $providedSignature)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_signature',
                'message' => 'Invalid request signature.',
            ], 401);
        }

        // 5. Update last_used_at on integration
        $integration->update(['last_used_at' => now()]);

        // 6. Bind integration and brandProfile to request attributes
        $request->attributes->set('integration', $integration);
        $request->attributes->set('brandProfile', $integration->brandProfile);

        return $next($request);
    }
}
