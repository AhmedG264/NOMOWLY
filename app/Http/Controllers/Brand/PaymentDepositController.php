<?php

namespace App\Http\Controllers\Brand;

use App\Enums\Currency;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\PaymentDeposit;
use App\Services\Math\MoneyMath;
use App\Services\Payment\PaymentDepositService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentDepositController extends Controller
{
    public function __construct(
        protected PaymentDepositService $depositService,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Initiate deposit from Brand Wallet.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', Rule::enum(Currency::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'provider' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $currency = Currency::from($validated['currency']);
        $amount = MoneyMath::format($validated['amount']);

        // Check if any gateway is configured for this currency
        $availableGateways = $this->gatewayManager->getAvailableGateways($currency);
        if (empty($availableGateways)) {
            return redirect()->route('brand.wallet.index', ['currency' => $currency->value])
                ->with('error', "خدمة الشحن الإلكتروني بالعملة [{$currency->value}] غير متاحة حالياً. يمكنك استخدام التحويل المصرفي المباشر.");
        }

        try {
            $result = $this->depositService->initiateDeposit([
                'user' => $user,
                'currency' => $currency,
                'amount' => $amount,
                'provider' => $validated['provider'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'card',
            ]);

            /** @var PaymentDeposit $deposit */
            $deposit = $result['deposit'];
            $gatewayResponse = $result['gateway_response'];

            if ($gatewayResponse->redirectUrl()) {
                return redirect()->away($gatewayResponse->redirectUrl());
            }

            return redirect()->route('brand.wallet.index', ['currency' => $currency->value])
                ->with('status', "تم إنشاء طلب شحن الرصيد رقم [{$deposit->deposit_number}]. بانتظار استكمال عملية الدفع.");
        } catch (\Throwable $e) {
            return redirect()->route('brand.wallet.index', ['currency' => $currency->value])
                ->with('error', 'تعذر بدء عملية الشحن: ' . $e->getMessage());
        }
    }

    /**
     * Handle customer return from payment gateway checkout.
     */
    public function callback(Request $request): RedirectResponse
    {
        $depositRef = $request->query('deposit_ref') ?? $request->query('deposit_simulated');
        $currency = $request->query('currency', Currency::SAR->value);

        if (!$depositRef) {
            return redirect()->route('brand.wallet.index', ['currency' => $currency]);
        }

        /** @var PaymentDeposit|null $deposit */
        $deposit = PaymentDeposit::where('deposit_number', $depositRef)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$deposit) {
            return redirect()->route('brand.wallet.index', ['currency' => $currency])
                ->with('error', 'لم يتم العثور على سجل الإيداع المطلوب.');
        }

        // If already succeeded
        if ($deposit->isPaid()) {
            return redirect()->route('brand.wallet.index', ['currency' => $deposit->currency->value])
                ->with('status', "تم تأكيد وشحن محفظتك بمبلغ {$deposit->net_amount} {$deposit->currency->value} بنجاح.");
        }

        // Verify status with gateway
        try {
            $gateway = $this->gatewayManager->getGateway($deposit->provider);
            $verifyResult = $gateway->verifyPayment($deposit, $request->all());

            if ($verifyResult->isSuccessful()) {
                $this->depositService->confirmDeposit(
                    deposit: $deposit,
                    providerTransactionId: $verifyResult->transactionId(),
                    confirmedFee: $verifyResult->fee()
                );

                return redirect()->route('brand.wallet.index', ['currency' => $deposit->currency->value])
                    ->with('status', "تم تأكيد الدفع وإيداع {$deposit->net_amount} {$deposit->currency->value} في محفظتك بنجاح.");
            }

            if ($verifyResult->isFailed()) {
                $this->depositService->failDeposit($deposit, $verifyResult->message() ?? 'فشلت عملية الدفع.');

                return redirect()->route('brand.wallet.index', ['currency' => $deposit->currency->value])
                    ->with('error', 'تعذر إتمام عملية الدفع: ' . ($verifyResult->message() ?? 'تم رفض المعاملة من قِبل البنك.'));
            }

            return redirect()->route('brand.wallet.index', ['currency' => $deposit->currency->value])
                ->with('status', "عملية الدفع رقم [{$deposit->deposit_number}] قيد المعالجة من قِبل مزود الدفع.");
        } catch (\Throwable $e) {
            return redirect()->route('brand.wallet.index', ['currency' => $deposit->currency->value])
                ->with('error', 'حدث خطأ أثناء التحقق من نتيجة الدفع: ' . $e->getMessage());
        }
    }
}
