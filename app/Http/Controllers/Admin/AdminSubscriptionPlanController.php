<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Services\AuditService;
use App\Services\Math\MoneyMath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSubscriptionPlanController extends Controller
{
    /**
     * Display a listing of all subscription plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::with(['prices', 'creator'])
            ->withCount(['subscriptions', 'subscriptions as active_subscriptions_count' => function ($q) {
                $q->where('status', \App\Enums\SubscriptionStatus::ACTIVE);
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('admin.subscriptions.plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): View
    {
        $currencies = Currency::values();
        $billingCycles = BillingCycle::cases();

        return view('admin.subscriptions.plans.create', compact('currencies', 'billingCycles'));
    }

    /**
     * Store a newly created subscription plan with multi-currency pricing.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'billing_cycle' => ['required', 'string', 'in:' . implode(',', array_column(BillingCycle::cases(), 'value'))],
            'duration_in_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'status' => ['required', 'string', 'in:' . implode(',', array_column(SubscriptionPlanStatus::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'allows_premium_campaigns' => ['nullable', 'boolean'],
            'max_premium_campaigns_per_month' => ['nullable', 'integer', 'min:1'],
            'max_active_applications' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'prices' => ['required', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $featuresArray = [];
        if (!empty($validated['features'])) {
            $rawLines = preg_split('/[\r\n]+/', $validated['features']);
            $featuresArray = array_values(array_filter(array_map('trim', $rawLines)));
        }

        $baseSlug = Str::slug($validated['name_en'] ?? $validated['name_ar']) ?: 'plan';
        $slug = $baseSlug;
        $counter = 1;
        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $plan = DB::transaction(function () use ($validated, $featuresArray, $slug, $request) {
            $plan = SubscriptionPlan::create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'slug' => $slug,
                'description_ar' => $validated['description_ar'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                'billing_cycle' => $validated['billing_cycle'],
                'duration_in_days' => $validated['duration_in_days'],
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_featured' => !empty($validated['is_featured']),
                'allows_premium_campaigns' => isset($validated['allows_premium_campaigns']) ? (bool) $validated['allows_premium_campaigns'] : true,
                'max_premium_campaigns_per_month' => $validated['max_premium_campaigns_per_month'] ?? null,
                'max_active_applications' => $validated['max_active_applications'] ?? null,
                'features' => $featuresArray,
                'created_by_user_id' => $request->user()->id,
            ]);

            // Save prices for each supported currency
            foreach ($validated['prices'] as $currCode => $priceVal) {
                if ($priceVal !== null && $priceVal !== '' && is_numeric($priceVal)) {
                    SubscriptionPlanPrice::create([
                        'subscription_plan_id' => $plan->id,
                        'currency' => strtoupper(trim($currCode)),
                        'price' => MoneyMath::format($priceVal),
                        'is_active' => true,
                    ]);
                }
            }

            AuditService::log(
                action: AuditAction::SUBSCRIPTION_PLAN_CREATED,
                userId: $request->user()->id,
                entityType: SubscriptionPlan::class,
                entityId: $plan->id,
                payloadDiff: [
                    'name_ar' => $plan->name_ar,
                    'billing_cycle' => $plan->billing_cycle->value,
                    'duration_in_days' => $plan->duration_in_days,
                    'prices' => $validated['prices'],
                ],
                status: 'SUCCESS',
                reason: "إنشاء خطة اشتراك جديدة للمسوقين [{$plan->name_ar}]"
            );

            return $plan;
        });

        return redirect()->route('admin.subscriptions.plans.index')
            ->with('status', "تم إنشاء خطة الاشتراك [{$plan->name_ar}] بنجاح.");
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(SubscriptionPlan $plan): View
    {
        $plan->load('prices');
        $currencies = Currency::values();
        $billingCycles = BillingCycle::cases();

        $priceMap = $plan->prices->pluck('price', 'currency.value')->toArray();

        return view('admin.subscriptions.plans.edit', compact('plan', 'currencies', 'billingCycles', 'priceMap'));
    }

    /**
     * Update the specified subscription plan.
     * Note: Future terms and prices are updated; historical paid subscriptions retain their immutable snapshots.
     */
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'billing_cycle' => ['required', 'string', 'in:' . implode(',', array_column(BillingCycle::cases(), 'value'))],
            'duration_in_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'status' => ['required', 'string', 'in:' . implode(',', array_column(SubscriptionPlanStatus::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'allows_premium_campaigns' => ['nullable', 'boolean'],
            'max_premium_campaigns_per_month' => ['nullable', 'integer', 'min:1'],
            'max_active_applications' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'prices' => ['required', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $featuresArray = [];
        if (!empty($validated['features'])) {
            $rawLines = preg_split('/[\r\n]+/', $validated['features']);
            $featuresArray = array_values(array_filter(array_map('trim', $rawLines)));
        }

        DB::transaction(function () use ($plan, $validated, $featuresArray, $request) {
            $oldData = $plan->only([
                'name_ar', 'billing_cycle', 'duration_in_days', 'status',
                'allows_premium_campaigns', 'max_premium_campaigns_per_month'
            ]);

            $plan->update([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'description_ar' => $validated['description_ar'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                'billing_cycle' => $validated['billing_cycle'],
                'duration_in_days' => $validated['duration_in_days'],
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_featured' => !empty($validated['is_featured']),
                'allows_premium_campaigns' => isset($validated['allows_premium_campaigns']) ? (bool) $validated['allows_premium_campaigns'] : true,
                'max_premium_campaigns_per_month' => $validated['max_premium_campaigns_per_month'] ?? null,
                'max_active_applications' => $validated['max_active_applications'] ?? null,
                'features' => $featuresArray,
            ]);

            // Sync or update prices for currencies
            foreach ($validated['prices'] as $currCode => $priceVal) {
                $code = strtoupper(trim($currCode));
                if ($priceVal !== null && $priceVal !== '' && is_numeric($priceVal)) {
                    SubscriptionPlanPrice::updateOrCreate(
                        [
                            'subscription_plan_id' => $plan->id,
                            'currency' => $code,
                        ],
                        [
                            'price' => MoneyMath::format($priceVal),
                            'is_active' => true,
                        ]
                    );
                } else {
                    // Mark inactive or remove price if empty
                    SubscriptionPlanPrice::where('subscription_plan_id', $plan->id)
                        ->where('currency', $code)
                        ->update(['is_active' => false]);
                }
            }

            AuditService::log(
                action: AuditAction::SUBSCRIPTION_PLAN_UPDATED,
                userId: $request->user()->id,
                entityType: SubscriptionPlan::class,
                entityId: $plan->id,
                payloadDiff: [
                    'old' => $oldData,
                    'new' => $plan->only([
                        'name_ar', 'billing_cycle', 'duration_in_days', 'status',
                        'allows_premium_campaigns', 'max_premium_campaigns_per_month'
                    ]),
                ],
                status: 'SUCCESS',
                reason: "تحديث شروط وأسعار خطة الاشتراك [{$plan->name_ar}] للمستقبل دون المساس بالاشتراكات التاريخية القائمة"
            );
        });

        return redirect()->route('admin.subscriptions.plans.index')
            ->with('status', "تم تحديث خطة الاشتراك [{$plan->name_ar}] بنجاح.");
    }

    /**
     * Quick status toggle.
     */
    public function toggleStatus(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_column(SubscriptionPlanStatus::cases(), 'value'))],
        ]);

        $newStatus = SubscriptionPlanStatus::from($request->input('status'));
        $plan->update(['status' => $newStatus]);

        AuditService::log(
            action: AuditAction::SUBSCRIPTION_PLAN_STATUS_CHANGED,
            userId: $request->user()->id,
            entityType: SubscriptionPlan::class,
            entityId: $plan->id,
            payloadDiff: [
                'plan_id' => $plan->id,
                'name' => $plan->name_ar,
                'new_status' => $newStatus->value,
            ],
            status: 'SUCCESS',
            reason: "تعديل حالة خطة الاشتراك إلى: {$newStatus->label()}"
        );

        return back()->with('status', "تم تغيير حالة خطة الاشتراك إلى: {$newStatus->label()}");
    }
}
