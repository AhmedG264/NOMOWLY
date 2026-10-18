<?php

namespace App\Http\Controllers\Marketer;

use App\Enums\Currency;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketerSubscription;
use App\Models\SubscriptionPlan;
use App\Services\Math\MoneyMath;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Subscription\SubscriptionEntitlementService;
use App\Services\Subscription\SubscriptionPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketerSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionPaymentService $paymentService,
        protected SubscriptionEntitlementService $entitlementService,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Display public/marketer plans comparison page.
     */
    public function plans(Request $request): View
    {
        $user = $request->user();
        $selectedCurrency = $request->query('currency', Currency::SAR->value);
        if (!in_array($selectedCurrency, Currency::values(), true)) {
            $selectedCurrency = Currency::SAR->value;
        }

        $plans = SubscriptionPlan::where('status', SubscriptionPlanStatus::ACTIVE)
            ->with(['prices'])
            ->orderBy('sort_order', 'asc')
            ->get();

        $activeSubscription = $this->entitlementService->getActiveSubscription($user);
        $currencies = Currency::values();

        return view('marketer.subscriptions.plans', compact('plans', 'activeSubscription', 'selectedCurrency', 'currencies'));
    }

    /**
     * Show checkout / subscription review page for a specific plan.
     */
    public function subscribe(Request $request, SubscriptionPlan $plan): View|RedirectResponse
    {
        if (!$plan->isActive()) {
            return redirect()->route('marketer.plans.index')
                ->with('error', 'هذه الخطة غير متاحة للاشتراك حالياً.');
        }

        $user = $request->user();
        $selectedCurrency = $request->query('currency', Currency::SAR->value);
        if (!in_array($selectedCurrency, Currency::values(), true)) {
            $selectedCurrency = Currency::SAR->value;
        }

        $price = $plan->priceFor($selectedCurrency);
        if (!$price) {
            return redirect()->route('marketer.plans.index')
                ->with('error', "هذه الخطة غير مسعّرة بالعملة المحددة ({$selectedCurrency}).");
        }

        // Check wallet balance for this currency
        $wallet = $user->getWallet($selectedCurrency);
        $walletBalance = $wallet ? MoneyMath::format($wallet->available_balance) : '0.0000';
        $canPayFromWallet = MoneyMath::comp($walletBalance, $price->price) >= 0;

        $activeSubscription = $this->entitlementService->getActiveSubscription($user);

        return view('marketer.subscriptions.subscribe', compact(
            'plan',
            'price',
            'selectedCurrency',
            'walletBalance',
            'canPayFromWallet',
            'activeSubscription'
        ));
    }

    /**
     * Process subscription purchase (Gateway or Wallet payment).
     */
    public function processPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:subscription_plans,id'],
            'currency' => ['required', 'string', 'in:' . implode(',', Currency::values())],
            'payment_method' => ['required', 'string', 'in:GATEWAY,WALLET'],
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $currency = Currency::from($validated['currency']);
        $paymentMethod = $validated['payment_method'];

        $idempotencyKey = 'SUB_REQ_' . $user->id . '_' . $plan->id . '_' . date('YmdHi') . '_' . Str::random(4);

        try {
            $subscription = $this->paymentService->initiateSubscription([
                'user' => $user,
                'plan' => $plan,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'idempotency_key' => $idempotencyKey,
            ]);

            if ($paymentMethod === 'WALLET') {
                $this->paymentService->payWithWalletBalance($subscription);

                return redirect()->route('marketer.subscriptions.show', $subscription->id)
                    ->with('status', "تم سداد وتفعيل اشتراكك في باقة [{$plan->name_ar}] بنجاح من رصيد محفظتك!");
            }

            // Payment method is GATEWAY
            // For testing or online mock checkout, confirm using gateway manager
            $gateway = $this->gatewayManager->getDefaultGatewayFor($currency);
            $providerTxId = 'GW_TX_' . strtoupper(Str::random(12));
            $confirmedFee = '0.0000';

            $this->paymentService->confirmGatewayPayment($subscription, $providerTxId, $confirmedFee, [
                'provider' => $gateway ? $gateway->getProvider()->value : 'FAKE',
                'auto_confirmed' => true,
            ]);

            return redirect()->route('marketer.subscriptions.show', $subscription->id)
                ->with('status', "تم تأكيد عملية الدفع وتفعيل باقة [{$plan->name_ar}] بنجاح!");

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display marketer's subscriptions history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $subscriptions = MarketerSubscription::where('user_id', $user->id)
            ->with(['plan', 'ledgerTransaction'])
            ->latest('created_at')
            ->paginate(10);

        $activeSubscription = $this->entitlementService->getActiveSubscription($user);

        return view('marketer.subscriptions.index', compact('subscriptions', 'activeSubscription'));
    }

    /**
     * Display details of a specific subscription.
     */
    public function show(Request $request, MarketerSubscription $subscription): View
    {
        // Enforce user isolation: marketer can only view their own subscription
        if ($subscription->user_id !== $request->user()->id && !$request->user()->isSuperAdmin()) {
            abort(403, 'غير مصرح لك باستعراض بيانات هذا الاشتراك.');
        }

        $subscription->load(['plan', 'ledgerTransaction.entries.account']);

        return view('marketer.subscriptions.show', compact('subscription'));
    }
}
