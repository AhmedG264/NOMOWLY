<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentDepositService $depositService
    ) {}

    /**
     * Handle incoming payment webhooks from configured gateways (Moyasar, Tap, etc.).
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->depositService->processWebhook($request, $provider);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (UnauthorizedHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (HttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Payment Webhook Processing Uncaught Exception', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error while processing webhook',
            ], 500);
        }
    }
}
