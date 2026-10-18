<?php

namespace App\Services\Payment\Gateways;

use App\Enums\Currency;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Services\Math\MoneyMath;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentGatewayResponse;
use App\Services\Payment\DTO\PaymentGatewayResult;
use App\Services\Payment\DTO\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGatewayInterface
{
    protected ?string $simulatedStatus = null;
    protected ?string $simulatedFee = null;
    protected ?string $simulatedFailureReason = null;

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::FAKE;
    }

    public function isConfigured(): bool
    {
        // Allowed in testing / local development only
        if (app()->environment('production')) {
            return false;
        }

        return (bool) config('services.fake_gateway.enabled', true);
    }

    public function supportsCurrency(Currency|string $currency): bool
    {
        $curr = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        return in_array($curr, Currency::values(), true);
    }

    public function supportsCountry(string $country): bool
    {
        return true;
    }

    public function supportedMethods(): array
    {
        return ['sandbox_card', 'sandbox_applepay'];
    }

    /**
     * For tests: simulate specific outcome (e.g. FAILED, SUCCEEDED, PROCESSING).
     */
    public function simulateOutcome(?string $status, ?string $fee = null, ?string $reason = null): void
    {
        $this->simulatedStatus = $status;
        $this->simulatedFee = $fee;
        $this->simulatedFailureReason = $reason;
    }

    public function createPayment(PaymentDeposit $deposit, array $options = []): PaymentGatewayResponse
    {
        $simulatedTxId = 'fake_tx_' . Str::random(16);

        return new PaymentGatewayResponse(
            success: true,
            redirectUrl: route('brand.wallet.index', [
                'currency' => $deposit->currency->value,
                'deposit_simulated' => $deposit->deposit_number,
            ]),
            transactionId: $simulatedTxId,
            message: 'تم إنشاء جلسة دفع تجريبية بنجاح.',
            raw: [
                'provider' => 'fake',
                'deposit_number' => $deposit->deposit_number,
                'amount' => MoneyMath::format($deposit->amount),
                'currency' => $deposit->currency->value,
            ]
        );
    }

    public function verifyPayment(PaymentDeposit $deposit, array $payload = []): PaymentGatewayResult
    {
        $statusStr = $this->simulatedStatus ?? ($payload['simulated_status'] ?? PaymentDepositStatus::SUCCEEDED->value);
        $status = PaymentDepositStatus::tryFrom(strtoupper($statusStr)) ?? PaymentDepositStatus::SUCCEEDED;

        $fee = $this->simulatedFee ?? ($payload['fee_amount'] ?? '0.0000');
        $txId = $deposit->provider_transaction_id ?: ('fake_tx_' . Str::random(16));

        return new PaymentGatewayResult(
            status: $status,
            transactionId: $txId,
            amount: MoneyMath::format($deposit->amount),
            currency: $deposit->currency->value,
            fee: MoneyMath::format($fee),
            message: $status === PaymentDepositStatus::SUCCEEDED ? 'عملية دفع تجريبية معتمدة' : ($this->simulatedFailureReason ?? 'فشل محاكاة الدفع التجريبي'),
            raw: ['verified_by' => 'FakePaymentGateway', 'timestamp' => now()->toIso8601String()]
        );
    }

    public function checkStatus(PaymentDeposit $deposit): PaymentGatewayResult
    {
        $status = $this->simulatedStatus ? (PaymentDepositStatus::tryFrom($this->simulatedStatus) ?? $deposit->status) : $deposit->status;

        return new PaymentGatewayResult(
            status: $status,
            transactionId: $deposit->provider_transaction_id ?? ('fake_tx_' . Str::random(12)),
            amount: MoneyMath::format($deposit->amount),
            currency: $deposit->currency->value,
            fee: MoneyMath::format($deposit->fee_amount),
            message: 'حالة العملية مسجلة لدى المزود التجريبي',
            raw: ['status' => $status->value]
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Fake-Signature') ?? $request->input('signature');
        $expected = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');

        return hash_equals($expected, (string) $signature);
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $eventId = (string) ($request->input('event_id') ?? $request->header('X-Event-Id') ?? ('evt_' . Str::random(16)));
        $eventType = (string) ($request->input('event_type') ?? 'payment.captured');
        $depositNumber = (string) ($request->input('deposit_number') ?? $request->input('data.deposit_number') ?? '');
        $txId = (string) ($request->input('transaction_id') ?? $request->input('data.id') ?? ('fake_tx_' . Str::random(12)));

        $rawStatus = strtoupper((string) ($request->input('status') ?? $request->input('data.status') ?? 'SUCCEEDED'));
        $status = match ($rawStatus) {
            'SUCCEEDED', 'CAPTURED', 'PAID' => PaymentDepositStatus::SUCCEEDED,
            'FAILED', 'DECLINED' => PaymentDepositStatus::FAILED,
            'CANCELLED' => PaymentDepositStatus::CANCELLED,
            'PROCESSING' => PaymentDepositStatus::PROCESSING,
            default => PaymentDepositStatus::PENDING,
        };

        $amount = $request->input('amount') ?? $request->input('data.amount');
        $currency = $request->input('currency') ?? $request->input('data.currency');
        $fee = $request->input('fee') ?? $request->input('data.fee') ?? '0.0000';
        $failureReason = $request->input('failure_reason') ?? $request->input('data.failure_reason');

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            depositNumber: $depositNumber ?: null,
            transactionId: $txId,
            status: $status,
            amount: $amount !== null ? MoneyMath::format($amount) : null,
            currency: $currency ? strtoupper((string) $currency) : null,
            fee: MoneyMath::format($fee),
            failureReason: $failureReason,
            raw: $request->all()
        );
    }

    public function refund(PaymentRefund $refund): PaymentGatewayResult
    {
        $statusStr = $this->simulatedStatus ?? PaymentRefundStatus::SUCCEEDED->value;
        $status = PaymentRefundStatus::tryFrom($statusStr) ?? PaymentRefundStatus::SUCCEEDED;

        return new PaymentGatewayResult(
            status: $status === PaymentRefundStatus::SUCCEEDED ? PaymentDepositStatus::SUCCEEDED : PaymentDepositStatus::FAILED,
            transactionId: 'fake_ref_' . Str::random(16),
            amount: MoneyMath::format($refund->amount),
            currency: $refund->currency->value,
            fee: '0.0000',
            message: $status === PaymentRefundStatus::SUCCEEDED ? 'تم تنفيذ الاسترداد التجريبي بنجاح' : ($this->simulatedFailureReason ?? 'فشل الاسترداد التجريبي'),
            raw: ['refund_id' => $refund->id, 'amount' => $refund->amount]
        );
    }
}
