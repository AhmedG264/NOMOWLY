<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\ConversionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Conversion;
use App\Models\FraudDetectionRule;
use App\Services\AuditService;
use App\Services\Fraud\FraudDetectionService;
use App\Services\Ledger\CommissionSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminConversionController extends Controller
{
    public function __construct(
        protected CommissionSettlementService $settlementService,
        protected FraudDetectionService $fraudService
    ) {}

    /**
     * Display conversions list with fraud risk filters, suspicious alerts, and metrics
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.view') || $user->isSuperAdmin(), 403, 'غير مصرح بعرض التحويلات');

        $query = Conversion::with([
            'campaign.brandProfile',
            'campaignApplication.marketerProfile.user',
            'attributionClick',
            'settlement',
            'investigatedBy',
        ])->latest('occurred_at');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Suspicious filter
        if ($request->boolean('suspicious_only')) {
            $query->where('is_suspicious', true);
        } elseif ($request->filled('risk_level')) {
            if ($request->input('risk_level') === 'high') {
                $query->where('fraud_risk_score', '>=', 60);
            } elseif ($request->input('risk_level') === 'medium') {
                $query->whereBetween('fraud_risk_score', [30, 59]);
            } elseif ($request->input('risk_level') === 'low') {
                $query->where('fraud_risk_score', '<', 30);
            }
        }

        // Campaign filter
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->input('campaign_id'));
        }

        // Search by external_order_id or visitor_id
        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('external_order_id', 'like', "%{$term}%")
                  ->orWhere('visitor_id', 'like', "%{$term}%");
            });
        }

        $conversions = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Conversion::count(),
            'pending' => Conversion::where('status', ConversionStatus::PENDING)->count(),
            'approved' => Conversion::where('status', ConversionStatus::APPROVED)->count(),
            'rejected' => Conversion::where('status', ConversionStatus::REJECTED)->count(),
            'cancelled' => Conversion::where('status', ConversionStatus::CANCELLED)->count(),
            'suspicious' => Conversion::where('is_suspicious', true)->count(),
            'high_risk' => Conversion::where('fraud_risk_score', '>=', 60)->count(),
            'total_order_value' => (float) Conversion::sum('order_value'),
            'total_commission' => (float) Conversion::where('status', ConversionStatus::APPROVED)->sum('commission_value'),
        ];

        $campaigns = Campaign::select('id', 'title')->orderBy('title')->get();

        return view('admin.conversions.index', compact('conversions', 'stats', 'campaigns'));
    }

    /**
     * Display conversion deep dive with full attribution, fraud signals, terms snapshot, and audit trail
     */
    public function show(Request $request, Conversion $conversion): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.view') || $user->isSuperAdmin(), 403, 'غير مصرح بعرض تفاصيل التحويل');

        $conversion->load([
            'campaign.brandProfile.user',
            'campaignApplication.marketerProfile.user',
            'attributionClick',
            'termsVersion',
            'settlement.ledgerTransaction',
            'settlement.reversalLedgerTransaction',
            'investigatedBy',
        ]);

        $auditLogs = AuditLog::where('entity_type', 'Conversion')
            ->where('entity_id', $conversion->id)
            ->with('user')
            ->latest('created_at')
            ->get();

        return view('admin.conversions.show', compact('conversion', 'auditLogs'));
    }

    /**
     * Conduct an administrative fraud investigation: clear suspicion or confirm fraud & reject
     */
    public function investigate(Request $request, Conversion $conversion): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.manage_fraud') || $user->isSuperAdmin(), 403, 'غير مصرح بإجراء تحقيقات مكافحة الاحتيال');

        $validated = $request->validate([
            'decision' => 'required|in:CLEAR_SUSPICION,CONFIRM_FRAUD_REJECT',
            'notes' => 'required|string|min:5|max:1000',
        ]);

        try {
            $this->fraudService->investigate(
                $conversion,
                $user,
                $validated['decision'],
                $validated['notes']
            );

            $msg = $validated['decision'] === 'CLEAR_SUSPICION'
                ? 'تمت تبرئة التحويل من شبهة الاحتيال بنجاح وتوثيق ملاحظات التدقيق.'
                : 'تم تأكيد شبهة الاحتيال ورفض التحويل أمنياً بنجاح.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر استكمال إجراء التحقيق: ' . $e->getMessage());
        }
    }

    /**
     * Administrative approval and double-entry commission settlement
     */
    public function approve(Request $request, Conversion $conversion): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.approve') || $user->isSuperAdmin(), 403, 'غير مصرح باعتماد التحويلات');

        return DB::transaction(function () use ($request, $conversion, $user) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ConversionStatus::APPROVED) {
                if (!$locked->isSettled()) {
                    try {
                        $this->settlementService->settleConversion($locked, $user);
                        return back()->with('success', "تم استكمال ترحيل وتسوية عمولة التحويل المعتمد رقم #{$locked->external_order_id} بنجاح.");
                    } catch (InsufficientFundsException $e) {
                        return back()->with('warning', "التحويل معتمد مسبقاً، ولكن رصيد الضمان المحجوز ما زال غير كافٍ للصرف الآلي.");
                    } catch (\Throwable $e) {
                        return back()->with('warning', "تعذرت التسوية المالية للتحويل المعتمد: " . $e->getMessage());
                    }
                }
                return back()->with('info', "التحويل معتمد مسبقاً ومرحل مالياً.");
            }

            if ($locked->status === ConversionStatus::CANCELLED) {
                return back()->with('error', "لا يمكن اعتماد تحويل ملغى.");
            }

            $locked->update([
                'status' => ConversionStatus::APPROVED,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            AuditService::log(
                action: AuditAction::CONVERSION_APPROVED,
                userId: $user->id,
                entityType: 'Conversion',
                entityId: $locked->id,
                payloadDiff: [
                    'conversion_id' => $locked->id,
                    'external_order_id' => $locked->external_order_id,
                    'approved_by_admin' => true,
                    'admin_user_id' => $user->id,
                ]
            );

            try {
                $this->settlementService->settleConversion($locked, $user);
                return back()->with('success', "تم اعتماد التحويل رقم الطلب #{$locked->external_order_id} وترحيل العمولة بنجاح.");
            } catch (InsufficientFundsException $e) {
                return back()->with('warning', "تم اعتماد التحويل، ولكن رصيد الضمان المحجوز للعلامة التجارية غير كافٍ للصرف الآلي.");
            } catch (\Throwable $e) {
                return back()->with('warning', "تم اعتماد التحويل، ولكن تعذرت التسوية المالية: " . $e->getMessage());
            }
        });
    }

    /**
     * Administrative rejection with documented reason
     */
    public function reject(Request $request, Conversion $conversion): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.reject') || $user->isSuperAdmin(), 403, 'غير مصرح برفض التحويلات');

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $conversion, $user, $validated) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ConversionStatus::REJECTED) {
                return back()->with('info', "التحويل مرفوض مسبقاً.");
            }

            if ($locked->status === ConversionStatus::APPROVED && $locked->isSettled()) {
                return back()->with('error', "لا يمكن رفض تحويل مرحل مالياً مباشرة دون عكس القيد المحاسبي أولاً.");
            }

            $locked->update([
                'status' => ConversionStatus::REJECTED,
                'rejection_reason' => $validated['reason'],
                'verified_at' => now(),
            ]);

            AuditService::log(
                action: AuditAction::CONVERSION_REJECTED,
                userId: $user->id,
                entityType: 'Conversion',
                entityId: $locked->id,
                payloadDiff: [
                    'conversion_id' => $locked->id,
                    'reason' => $validated['reason'],
                    'rejected_by_admin' => true,
                ],
                status: 'REJECTED',
                reason: $validated['reason']
            );

            return back()->with('success', "تم رفض التحويل رقم الطلب #{$locked->external_order_id} بنجاح.");
        });
    }

    /**
     * Display configurable fraud detection rules and threshold settings
     */
    public function rules(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.manage_fraud') || $user->isSuperAdmin(), 403, 'غير مصرح بإدارة قواعد الاحتيال');

        $rules = FraudDetectionRule::orderBy('risk_weight', 'desc')->get();

        return view('admin.conversions.rules', compact('rules'));
    }

    /**
     * Update a fraud detection rule threshold or active status
     */
    public function updateRule(Request $request, FraudDetectionRule $rule): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('conversions.manage_fraud') || $user->isSuperAdmin(), 403, 'غير مصرح بتعديل قواعد الاحتيال');

        $validated = $request->validate([
            'threshold_value' => 'required|string|max:50',
            'risk_weight' => 'required|integer|min:1|max:100',
            'is_active' => 'required|boolean',
        ]);

        $oldValues = $rule->only(['threshold_value', 'risk_weight', 'is_active']);
        $rule->update($validated);

        AuditService::log(
            action: AuditAction::FRAUD_RULE_UPDATED,
            userId: $user->id,
            entityType: FraudDetectionRule::class,
            entityId: $rule->id,
            payloadDiff: [
                'rule_key' => $rule->rule_key,
                'old' => $oldValues,
                'new' => $validated,
            ],
            status: 'SUCCESS',
            reason: "تعديل إعدادات قاعدة كشف الاحتيال [{$rule->name_ar}]"
        );

        return back()->with('success', "تم تحديث إعدادات قاعدة كشف الاحتيال ({$rule->name_ar}) بنجاح.");
    }
}
