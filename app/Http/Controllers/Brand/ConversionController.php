<?php

namespace App\Http\Controllers\Brand;

use App\Enums\AuditAction;
use App\Enums\ConversionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Services\AuditService;
use App\Services\Ledger\CommissionSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversionController extends Controller
{
    public function __construct(
        protected CommissionSettlementService $settlementService
    ) {}

    /**
     * Display a listing of conversions for the authenticated Brand
     */
    public function index(Request $request): View
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile, 403, 'الملف التجاري غير مكتمل');

        $campaignIds = $brandProfile->campaigns()->pluck('id');

        $query = Conversion::with(['campaign', 'campaignApplication.marketerProfile', 'attributionClick', 'settlement.ledgerTransaction'])
            ->whereIn('campaign_id', $campaignIds)
            ->latest('occurred_at');

        // Optional filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Optional filter by campaign
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->input('campaign_id'));
        }

        $conversions = $query->paginate(20)->withQueryString();

        // Real metrics calculation directly from database
        $stats = [
            'total_conversions' => Conversion::whereIn('campaign_id', $campaignIds)->count(),
            'pending_count' => Conversion::whereIn('campaign_id', $campaignIds)->where('status', ConversionStatus::PENDING)->count(),
            'approved_count' => Conversion::whereIn('campaign_id', $campaignIds)->where('status', ConversionStatus::APPROVED)->count(),
            'rejected_count' => Conversion::whereIn('campaign_id', $campaignIds)->where('status', ConversionStatus::REJECTED)->count(),
            'total_order_value' => (float) Conversion::whereIn('campaign_id', $campaignIds)->sum('order_value'),
            'approved_commission' => (float) Conversion::whereIn('campaign_id', $campaignIds)->where('status', ConversionStatus::APPROVED)->sum('commission_value'),
            'pending_commission' => (float) Conversion::whereIn('campaign_id', $campaignIds)->where('status', ConversionStatus::PENDING)->sum('commission_value'),
        ];

        $campaigns = $brandProfile->campaigns()->select('id', 'title')->get();

        return view('brand.conversions.index', compact('conversions', 'stats', 'campaigns'));
    }

    /**
     * Approve and verify an attributed conversion and settle commission in Double-Entry Ledger
     */
    public function approve(Request $request, Conversion $conversion): RedirectResponse
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile && $conversion->campaign->brand_profile_id === $brandProfile->id, 403, 'غير مصرح بمراجعة هذا التحويل');

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $conversion, $brandProfile) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ConversionStatus::APPROVED) {
                if (!$locked->isSettled()) {
                    try {
                        $this->settlementService->settleConversion($locked, $request->user());
                        return back()->with('success', "تم استكمال ترحيل وتسوية عمولة التحويل المعتمد رقم #{$locked->external_order_id} بنجاح.");
                    } catch (InsufficientFundsException $e) {
                        return back()->with('warning', "التحويل معتمد مسبقاً، ولكن رصيد الضمان المحجوز ما زال غير كافٍ للتسوية المالية.");
                    } catch (\Throwable $e) {
                        return back()->with('warning', "تعذرت تسوية التحويل المعتمد: " . $e->getMessage());
                    }
                }
                return back()->with('info', "التحويل رقم الطلب #{$locked->external_order_id} معتمد ومؤكد مسبقاً.");
            }

            if ($locked->status === ConversionStatus::REJECTED) {
                return back()->with('error', "لا يمكن اعتماد تحويل تم رفضه مسبقاً إلا عبر تدقيق الإدارة العامة.");
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
                userId: $request->user()->id,
                entityType: 'Conversion',
                entityId: $locked->id,
                payloadDiff: [
                    'conversion_id' => $locked->id,
                    'external_order_id' => $locked->external_order_id,
                    'order_value' => $locked->order_value,
                    'commission_value' => $locked->commission_value,
                    'brand_profile_id' => $brandProfile->id,
                ]
            );

            // Attempt Double-Entry Commission Settlement if escrow is funded
            try {
                $this->settlementService->settleConversion($locked, $request->user());
                return back()->with('success', "تم اعتماد التحويل رقم الطلب #{$locked->external_order_id} وترحيل العمولة بنجاح لمستحقات المسوق.");
            } catch (InsufficientFundsException $e) {
                return back()->with('warning', "تم اعتماد التحويل بنجاح، ولكن ميزانية الحملة المحجوزة بالضمان غير كافية للتسوية المالية الآلية، وتم تسجيل الحالة للمراجعة الإدارية.");
            } catch (\Throwable $e) {
                return back()->with('warning', "تم اعتماد التحويل، ولكن تعذرت التسوية المالية التلقائية: " . $e->getMessage());
            }
        });
    }

    /**
     * Reject an invalid or returned conversion with documented reason
     */
    public function reject(Request $request, Conversion $conversion): RedirectResponse
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile && $conversion->campaign->brand_profile_id === $brandProfile->id, 403, 'غير مصرح بمراجعة هذا التحويل');

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $conversion, $brandProfile, $validated) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ConversionStatus::REJECTED) {
                return back()->with('info', "التحويل رقم الطلب #{$locked->external_order_id} مرفوض مسبقاً.");
            }

            if ($locked->status === ConversionStatus::APPROVED && $locked->isSettled()) {
                return back()->with('error', "لا يمكن رفض تحويل معتمد ومرحل مالياً مباشرة، يجب استخدام خيار الإلغاء/الاسترداد لعكس القيد المحاسبي بأمان.");
            }

            $locked->update([
                'status' => ConversionStatus::REJECTED,
                'rejection_reason' => $validated['reason'],
                'verified_at' => now(),
            ]);

            AuditService::log(
                action: AuditAction::CONVERSION_REJECTED,
                userId: $request->user()->id,
                entityType: 'Conversion',
                entityId: $locked->id,
                payloadDiff: [
                    'conversion_id' => $locked->id,
                    'external_order_id' => $locked->external_order_id,
                    'rejection_reason' => $validated['reason'],
                    'brand_profile_id' => $brandProfile->id,
                ],
                status: 'REJECTED',
                reason: $validated['reason']
            );

            return back()->with('success', "تم رفض التحويل رقم الطلب #{$locked->external_order_id} وتوثيق السبب.");
        });
    }

    /**
     * Cancel an approved conversion and reverse commission settlement if settled
     */
    public function cancel(Request $request, Conversion $conversion): RedirectResponse
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile && $conversion->campaign->brand_profile_id === $brandProfile->id, 403, 'غير مصرح بإلغاء هذا التحويل');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $reason = $validated['reason'] ?? 'تم إرجاع الطلب أو إلغاؤه من قبل العميل';

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $conversion, $brandProfile, $reason) {
            $locked = Conversion::where('id', $conversion->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ConversionStatus::CANCELLED) {
                return back()->with('info', "التحويل رقم الطلب #{$locked->external_order_id} ملغى مسبقاً.");
            }

            if ($locked->status !== ConversionStatus::APPROVED) {
                return back()->with('error', "لا يمكن إلغاء سوى التحويلات المعتمدة مسبقاً.");
            }

            $locked->update([
                'status' => ConversionStatus::CANCELLED,
                'rejection_reason' => $reason,
            ]);

            AuditService::log(
                action: AuditAction::CONVERSION_CANCELLED,
                userId: $request->user()->id,
                entityType: 'Conversion',
                entityId: $locked->id,
                payloadDiff: [
                    'conversion_id' => $locked->id,
                    'external_order_id' => $locked->external_order_id,
                    'reason' => $reason,
                    'brand_profile_id' => $brandProfile->id,
                ],
                status: 'CANCELLED',
                reason: $reason
            );

            // Reverse settlement if one exists
            try {
                $this->settlementService->reverseSettlement($locked, $request->user(), $reason);
                return back()->with('success', "تم إلغاء التحويل رقم الطلب #{$locked->external_order_id} وعكس القيد المحاسبي بنجاح.");
            } catch (\Throwable $e) {
                return back()->with('warning', "تم إلغاء التحويل، لكن تعذر عكس القيد المحاسبي تلقائياً: " . $e->getMessage());
            }
        });
    }
}
