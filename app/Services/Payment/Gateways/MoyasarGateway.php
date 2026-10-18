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

class MoyasarGateway implements PaymentGatewayInterface
{
    protected string $baseUrl = 'https://api.moyasar.com/v1';

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::MOYASAR;
    }

    public function isConfigured(): bool
    {
        $apiKey = config('services.moyasar.api_key');

        return !empty($apiKey);
    }

    public function supportsCurrency(Currency|string $currency): bool
    {
        $curr = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        // Moyasar supports SAR and USD
        return in_array($curr, ['SAR', 'USD'], true);
    }

    public function supportsCountry(string $country): bool
    {
        return strtoupper($country) === 'SA';
    }

    public function supportedMethods(): array
    {
        return ['mada', 'creditcard', 'applepay'];
    }

    public function createPayment(PaymentDeposit $deposit, array $options = []): PaymentGatewayResponse
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResponse(
                success: false,
                message: 'بوابة الدفع ميسر (Moyasar) غير مفعلة أو لم يتم إعداد مفاتيح الربط بعد.'
            );
        }

        $apiKey = (string) config('services.moyasar.api_key');
        $callbackUrl = route('brand.wallet.index', [
            'currency' => $deposit->currency->value,
            'deposit_ref' => $deposit->deposit_number,
        ]);

        // Moyasar expects amounts in minor currency units (e.g. 100 SAR = 10000 Halalas)
        $amountInHalalas = (int) bcmul(MoneyMath::format($deposit->amount), '100', 0);

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->post("{$this->baseUrl}/invoices", [
                    'amount' => $amountInHalalas,
                    'currency' => $deposit->currency->value,
                    'description' => "شحن محفظة العلامة التجارية - عملية رقم {$deposit->deposit_number}",
                    'callback_url' => $callbackUrl,
                    'metadata' => [
                        'deposit_number' => $deposit->deposit_number,
                        'user_id' => $deposit->user_id,
                        'wallet_id' => $deposit->wallet_id,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new PaymentGatewayResponse(
                    success: true,
                    redirectUrl: $data['url'] ?? null,
                    transactionId: $data['id'] ?? null,
                    message: 'تم إنشاء فاتورة الدفع في ميسر بنجاح.',
                    raw: $data
                );
            }

            Log::warning('Moyasar createPayment returned non-success HTTP status', [
                'status' => $response->status(),
                'deposit' => $deposit->deposit_number,
            ]);

            return new PaymentGatewayResponse(
                success: false,
                message: $response->json('message') ?? 'تعذر بدء جلسة الدفع عبر ميسر. يُرجى مراجعة الدعم الفني.',
                raw: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Moyasar createPayment Exception', [
                'error' => $e->getMessage(),
                'deposit' => $deposit->deposit_number,
            ]);

            return new PaymentGatewayResponse(
                success: false,
                message: 'حدث خطأ في الاتصال بمزود الدفع ميسر.'
            );
        }
    }

    public function verifyPayment(PaymentDeposit $deposit, array $payload = []): PaymentGatewayResult
    {
        $paymentId = $payload['id'] ?? $deposit->provider_transaction_id;

        if (empty($paymentId)) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'لم يتم العثور على معرف العملية لدى مزود الدفع ميسر.'
            );
        }

        return $this->fetchPaymentFromApi($paymentId, $deposit);
    }

    public function checkStatus(PaymentDeposit $deposit): PaymentGatewayResult
    {
        if (empty($deposit->provider_transaction_id)) {
            return new PaymentGatewayResult(
                status: $deposit->status,
                message: 'لا يوجد معرف عملية لدى المزود للتحقق منه.'
            );
        }

        return $this->fetchPaymentFromApi($deposit->provider_transaction_id, $deposit);
    }

    protected function fetchPaymentFromApi(string $paymentId, PaymentDeposit $deposit): PaymentGatewayResult
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'مفاتيح الربط لميسر غير مهيأة.'
            );
        }

        $apiKey = (string) config('services.moyasar.api_key');

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->successful()) {
                $data = $response->json();
                $rawStatus = strtoupper((string) ($data['status'] ?? ''));

                $status = match ($rawStatus) {
                    'PAID', 'CAPTURED' => PaymentDepositStatus::SUCCEEDED,
                    'FAILED' => PaymentDepositStatus::FAILED,
                    'REFUNDED' => PaymentDepositStatus::REFUNDED,
                    'PARTIALLY_REFUNDED' => PaymentDepositStatus::PARTIALLY_REFUNDED,
                    default => PaymentDepositStatus::PROCESSING,
                };

                // Amount in SAR/USD from halalas
                $rawAmount = $data['amount'] ?? 0;
                $amount = bcdiv((string) $rawAmount, '100', 4);

                $rawFee = $data['fee'] ?? 0;
                $fee = bcdiv((string) $rawFee, '100', 4);

                return new PaymentGatewayResult(
                    status: $status,
                    transactionId: $data['id'] ?? $paymentId,
                    amount: MoneyMath::format($amount),
                    currency: strtoupper((string) ($data['currency'] ?? $deposit->currency->value)),
                    fee: MoneyMath::format($fee),
                    message: $data['description'] ?? ($status === PaymentDepositStatus::SUCCEEDED ? 'عملية دفع مكتملة' : 'قيد المعالجة'),
                    raw: $data
                );
            }

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'تعذر جلب تفاصيل المعاملة من ميسر.'
            );
        } catch (\Throwable $e) {
            Log::error('Moyasar checkStatus exception', ['error' => $e->getMessage()]);

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'خطأ في الاتصال بخوادم ميسر.'
            );
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.moyasar.webhook_secret');
        if (empty($secret)) {
            return false;
        }

        // Moyasar sends the shared webhook secret in the authorization or token header or payload secret
        $token = $request->header('X-Moyasar-Secret')
            ?? $request->bearerToken()
            ?? $request->input('secret');

        if ($token) {
            return hash_equals($secret, (string) $token);
        }

        // HMAC verification if signature header is provided
        $signature = $request->header('X-Moyasar-Signature');
        if ($signature) {
            $computed = hash_hmac('sha256', $request->getContent(), $secret);

            return hash_equals($computed, $signature);
        }

        return false;
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $paymentData = $data['data'] ?? $data;

        $eventId = (string) ($data['id'] ?? $paymentData['id'] ?? '');
        $eventType = (string) ($data['type'] ?? 'payment.captured');
        $txId = (string) ($paymentData['id'] ?? '');

        $rawStatus = strtoupper((string) ($paymentData['status'] ?? ''));
        $status = match ($rawStatus) {
            'PAID', 'CAPTURED' => PaymentDepositStatus::SUCCEEDED,
            'FAILED' => PaymentDepositStatus::FAILED,
            'REFUNDED' => PaymentDepositStatus::REFUNDED,
            'PARTIALLY_REFUNDED' => PaymentDepositStatus::PARTIALLY_REFUNDED,
            default => PaymentDepositStatus::PROCESSING,
        };

        $rawAmount = $paymentData['amount'] ?? 0;
        $amount = bcdiv((string) $rawAmount, '100', 4);

        $rawFee = $paymentData['fee'] ?? 0;
        $fee = bcdiv((string) $rawFee, '100', 4);

        $currency = strtoupper((string) ($paymentData['currency'] ?? 'SAR'));
        $metadata = $paymentData['metadata'] ?? [];
        $depositNumber = $metadata['deposit_number'] ?? null;

        return new WebhookPayload(
            eventId: $eventId ?: $txId,
            eventType: $eventType,
            depositNumber: $depositNumber,
            transactionId: $txId,
            status: $status,
            amount: MoneyMath::format($amount),
            currency: $currency,
            fee: MoneyMath::format($fee),
            failureReason: $paymentData['source']['message'] ?? null,
            raw: $data
        );
    }

    public function refund(PaymentRefund $refund): PaymentGatewayResult
    {
        if (!$this->isConfigured()) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'مفاتيح الربط لميسر غير مهيأة.'
            );
        }

        $apiKey = (string) config('services.moyasar.api_key');
        $paymentId = $refund->deposit->provider_transaction_id;

        if (empty($paymentId)) {
            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'معرف العملية لدى ميسر غير متوفر للاسترداد.'
            );
        }

        $amountInHalalas = (int) bcmul(MoneyMath::format($refund->amount), '100', 0);

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->post("{$this->baseUrl}/payments/{$paymentId}/refund", [
                    'amount' => $amountInHalalas,
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
                    message: 'تم استرداد المبلغ بنجاح عبر ميسر.',
                    raw: $data
                );
            }

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: $response->json('message') ?? 'فشل طلب الاسترداد لدى ميسر.',
                raw: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Moyasar refund exception', ['error' => $e->getMessage()]);

            return new PaymentGatewayResult(
                status: PaymentDepositStatus::FAILED,
                message: 'خطأ في الاتصال بميسر أثناء محاولة الاسترداد.'
            );
        }
    }
}
