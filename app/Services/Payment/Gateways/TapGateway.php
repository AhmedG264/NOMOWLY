<?php

namespace App\Services\Payment\Gateways;

use App\Enums\Currency;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Services\Math\MoneyMath;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentGatewayResponse;
use App\Services\Payment\DTO\PaymentGatewayResult;
use App\Services\Payment\DTO\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapGateway implements PaymentGatewayInterface
{
    protected string $baseUrl = 'https://api.tap.company/v2';

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::TAP;
    }

    public function isConfigured(): bool
    {
        $secretKey = config('services.tap.secret_key');

        return !empty($secretKey);
    }

    public function supportsCurrency(Currency|string $currency): bool
    {
        $curr = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        // Tap supports all GCC & regional currencies
        return in_array($curr, ['SAR', 'KWD', 'AED', 'BHD', 'QAR', 'OMR', 'EGP', 'USD'], true);
    }

    public function supportsCountry(string $country): bool
    {
        return in_array(strtoupper($country), ['SA', 'KW', 'AE', 'BH', 'QA', 'OM', 'EG'], true);
    }

    public function supportedMethods(): array
    {
        return ['knet', 'mada', 'benefit', 'fawry', 'creditcard', 'applepay'];
    }

    public function createPayment(PaymentDeposit $deposit, array $options = []): PaymentGatewayResponse
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResponse(
                success: false,
                message: 'بوابة الدفع تاب (Tap Payments) غير مفعلة أو لم يتم إعداد مفاتيح الربط بعد.'
            );
        }

        $secretKey = (string) config('services.tap.secret_key');
        $callbackUrl = route('brand.wallet.index', [
            'currency' => $deposit->currency->value,
            'deposit_ref' => $deposit->deposit_number,
        ]);

        $user = $deposit->user;

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/charges", [
                    'amount' => (float) MoneyMath::format($deposit->amount),
                    'currency' => $deposit->currency->value,
                    'customer' => [
                        'first_name' => $user->name,
                        'email' => $user->email,
                    ],
                    'source' => ['id' => 'src_all'],
                    'redirect' => [
                        'url' => $callbackUrl,
                    ],
                    'post' => [
                        'url' => route('api.webhooks.payments', ['provider' => 'tap']),
                    ],
                    'description' => "شحن رصيد محفظة نمولي - طلب {$deposit->deposit_number}",
                    'reference' => [
                        'transaction' => $deposit->deposit_number,
                        'order' => $deposit->deposit_number,
                    ],
                    'metadata' => [
                        'deposit_number' => $deposit->deposit_number,
                        'user_id' => $deposit->user_id,
                        'wallet_id' => $deposit->wallet_id,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $redirectUrl = $data['transaction']['url'] ?? null;

                return new PaymentGatewayResponse(
                    success: true,
                    redirectUrl: $redirectUrl,
                    transactionId: $data['id'] ?? null,
                    message: 'تم تجهيز رابط الدفع عبر بوابة تاب.',
                    raw: $data
                );
            }

            Log::warning('Tap createPayment returned non-success', [
                'status' => $response->status(),
                'deposit' => $deposit->deposit_number,
                'body' => $response->json(),
            ]);

            return new PaymentGatewayResponse(
                success: false,
                message: $response->json('errors.0.description') ?? 'تعذر تجهيز رابط الدفع عبر تاب.',
                raw: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Tap createPayment Exception', [
                'error' => $e->getMessage(),
                'deposit' => $deposit->deposit_number,
            ]);

            return new PaymentGatewayResponse(
                success: false,
                message: 'حدث خطأ أثناء الاتصال ببوابة تاب للمدفوعات.'
            );
        }
    }

    public function verifyPayment(PaymentDeposit $deposit, array $payload = []): PaymentGatewayResult
    {
        $tapId = $payload['tap_id'] ?? $payload['id'] ?? $deposit->provider_transaction_id;

        if (empty($tapId)) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'لم يتم العثور على معرف العملية لدى مزود تاب.'
            );
        }

        return $this->fetchChargeFromApi($tapId, $deposit);
    }

    public function checkStatus(PaymentDeposit $deposit): PaymentGatewayResult
    {
        if (empty($deposit->provider_transaction_id)) {
            return new PaymentGatewayResult(
                status: $deposit->status,
                message: 'لا يوجد معرف عملية لدى تاب للتحقق منه.'
            );
        }

        return $this->fetchChargeFromApi($deposit->provider_transaction_id, $deposit);
    }

    protected function fetchChargeFromApi(string $chargeId, PaymentDeposit $deposit): PaymentGatewayResult
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'مفاتيح الربط لتاب غير مهيأة.'
            );
        }

        $secretKey = (string) config('services.tap.secret_key');

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->get("{$this->baseUrl}/charges/{$chargeId}");

            if ($response->successful()) {
                $data = $response->json();
                $rawStatus = strtoupper((string) ($data['status'] ?? ''));

                $status = match ($rawStatus) {
                    'CAPTURED' => PaymentDepositStatus::SUCCEEDED,
                    'FAILED', 'DECLINED', 'RESTRICTED' => PaymentDepositStatus::FAILED,
                    'CANCELLED' => PaymentDepositStatus::CANCELLED,
                    default => PaymentDepositStatus::PROCESSING,
                };

                $amount = (string) ($data['amount'] ?? $deposit->amount);
                $fee = '0.0000';
                $currency = strtoupper((string) ($data['currency'] ?? $deposit->currency->value));

                return new PaymentGatewayResult(
                    status: $status,
                    transactionId: $data['id'] ?? $chargeId,
                    amount: MoneyMath::format($amount),
                    currency: $currency,
                    fee: MoneyMath::format($fee),
                    message: $data['response']['message'] ?? 'عملية دفع تم التحقق منها',
                    raw: $data
                );
            }

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'تعذر جلب بيانات العملية من تاب.'
            );
        } catch (\Throwable $e) {
            Log::error('Tap fetchCharge exception', ['error' => $e->getMessage()]);

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'خطأ في الاتصال بخوادم تاب.'
            );
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.tap.webhook_secret');
        if (empty($secret)) {
            return false;
        }

        // Tap passes a hash header or signature token
        $hashHeader = $request->header('hash') ?? $request->header('X-Tap-Hash');
        if ($hashHeader) {
            $rawContent = $request->getContent();
            $computed = hash_hmac('sha256', $rawContent, $secret);

            return hash_equals($computed, $hashHeader);
        }

        // Alternative token check
        $token = $request->bearerToken() ?? $request->header('X-Webhook-Secret');
        if ($token) {
            return hash_equals($secret, (string) $token);
        }

        return false;
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $data = $request->json()->all();

        $eventId = (string) ($data['id'] ?? '');
        $eventType = (string) ($data['event'] ?? 'charge.captured');
        $txId = (string) ($data['id'] ?? '');

        $rawStatus = strtoupper((string) ($data['status'] ?? ''));
        $status = match ($rawStatus) {
            'CAPTURED' => PaymentDepositStatus::SUCCEEDED,
            'FAILED', 'DECLINED' => PaymentDepositStatus::FAILED,
            'CANCELLED' => PaymentDepositStatus::CANCELLED,
            default => PaymentDepositStatus::PROCESSING,
        };

        $amount = (string) ($data['amount'] ?? '0');
        $currency = strtoupper((string) ($data['currency'] ?? 'SAR'));
        $metadata = $data['metadata'] ?? [];
        $depositNumber = $metadata['deposit_number'] ?? $data['reference']['transaction'] ?? null;

        return new WebhookPayload(
            eventId: $eventId ?: $txId,
            eventType: $eventType,
            depositNumber: $depositNumber,
            transactionId: $txId,
            status: $status,
            amount: MoneyMath::format($amount),
            currency: $currency,
            fee: '0.0000',
            failureReason: $data['response']['message'] ?? null,
            raw: $data
        );
    }

    public function refund(PaymentRefund $refund): PaymentGatewayResult
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'مفاتيح الربط لتاب غير مهيأة.'
            );
        }

        $secretKey = (string) config('services.tap.secret_key');
        $chargeId = $refund->deposit->provider_transaction_id;

        if (empty($chargeId)) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'معرف العملية لدى تاب غير متوفر للاسترداد.'
            );
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/refunds", [
                    'charge_id' => $chargeId,
                    'amount' => (float) MoneyMath::format($refund->amount),
                    'currency' => $refund->currency->value,
                    'reason' => $refund->reason,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new PaymentGatewayResult(
                    status: PaymentDepositStatus::SUCCEEDED,
                    transactionId: $data['id'] ?? null,
                    amount: MoneyMath::format($refund->amount),
                    currency: $refund->currency->value,
                    fee: '0.0000',
                    message: 'تم استرداد المبلغ بنجاح عبر تاب.',
                    raw: $data
                );
            }

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: $response->json('errors.0.description') ?? 'فشل طلب الاسترداد لدى تاب.',
                raw: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Tap refund exception', ['error' => $e->getMessage()]);

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'خطأ في الاتصال بخوادم تاب أثناء الاسترداد.'
            );
        }
    }
}
