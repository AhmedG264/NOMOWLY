<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketerSubscription;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionPaymentService $paymentService
    ) {}

    /**
     * Display a listing of all marketer subscriptions.
     */
    public function index(Request $request): View
    {
        $query = MarketerSubscription::with(['user', 'marketerProfile', 'plan', 'ledgerTransaction'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('subscription_plan_id', $request->query('plan_id'));
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->query('currency'));
        }

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('subscription_number', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => MarketerSubscription::count(),
            'active' => MarketerSubscription::where('status', SubscriptionStatus::ACTIVE)->count(),
            'pending' => MarketerSubscription::where('status', SubscriptionStatus::PENDING_PAYMENT)->count(),
            'expired' => MarketerSubscription::where('status', SubscriptionStatus::EXPIRED)->count(),
            'suspended' => MarketerSubscription::where('status', SubscriptionStatus::SUSPENDED)->count(),
        ];

        $plans = SubscriptionPlan::orderBy('sort_order', 'asc')->get();
        $currencies = Currency::values();

        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans', 'currencies'));
    }

    /**
     * Display detailed subscription information and immutable snapshot.
     */
    public function show(MarketerSubscription $subscription): View
    {
        $subscription->load(['user', 'marketerProfile', 'plan', 'ledgerTransaction.entries.account']);

        return view('admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Administratively suspend a subscription.
     */
    public function suspend(Request $request, MarketerSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->paymentService->suspendSubscription($subscription, $request->user(), $validated['reason']);

        return back()->with('status', "تم تعليق اشتراك المسوق [{$subscription->subscription_number}] بنجاح.");
    }

    /**
     * Cancel an active subscription.
     */
    public function cancel(Request $request, MarketerSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->paymentService->cancelSubscription($subscription, $request->user(), $validated['reason'] ?? 'إلغاء إداري');

        return back()->with('status', "تم إلغاء الاشتراك [{$subscription->subscription_number}] بنجاح.");
    }
}
