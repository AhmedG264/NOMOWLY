<?php

namespace App\Http\Controllers\Brand;

use App\Enums\AuditAction;
use App\Enums\ConversionStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversionController extends Controller
{
    /**
     * Display a listing of conversions for the authenticated Brand
     */
    public function index(Request $request): View
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile, 403, 'الملف التجاري غير مكتمل');

        $campaignIds = $brandProfile->campaigns()->pluck('id');

        $query = Conversion::with(['campaign', 'campaignApplication.marketerProfile', 'attributionClick'])
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
     * Approve and verify an attributed conversion
     */
    public function approve(Request $request, Conversion $conversion): RedirectResponse
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile && $conversion->campaign->brand_profile_id === $brandProfile->id, 403, 'غير مصرح بمراجعة هذا التحويل');

        $conversion->update([
            'status' => ConversionStatus::APPROVED,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        AuditService::log(
            action: AuditAction::CONVERSION_APPROVED,
            userId: $request->user()->id,
            entityType: 'Conversion',
            entityId: $conversion->id,
            payloadDiff: [
                'conversion_id' => $conversion->id,
                'external_order_id' => $conversion->external_order_id,
                'order_value' => $conversion->order_value,
                'commission_value' => $conversion->commission_value,
                'brand_profile_id' => $brandProfile->id,
            ]
        );

        return back()->with('success', "تم اعتماد التحويل رقم الطلب #{$conversion->external_order_id} بنجاح.");
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

        $conversion->update([
            'status' => ConversionStatus::REJECTED,
            'rejection_reason' => $validated['reason'],
            'verified_at' => now(),
        ]);

        AuditService::log(
            action: AuditAction::CONVERSION_REJECTED,
            userId: $request->user()->id,
            entityType: 'Conversion',
            entityId: $conversion->id,
            payloadDiff: [
                'conversion_id' => $conversion->id,
                'external_order_id' => $conversion->external_order_id,
                'rejection_reason' => $validated['reason'],
                'brand_profile_id' => $brandProfile->id,
            ]
        );

        return back()->with('success', "تم رفض التحويل رقم الطلب #{$conversion->external_order_id} وتوثيق السبب.");
    }

    /**
     * Cancel an approved conversion (e.g. order returned or cancelled after approval)
     */
    public function cancel(Request $request, Conversion $conversion): RedirectResponse
    {
        $brandProfile = $request->user()->brandProfile;
        abort_unless($brandProfile && $conversion->campaign->brand_profile_id === $brandProfile->id, 403, 'غير مصرح بإلغاء هذا التحويل');

        abort_unless($conversion->status === ConversionStatus::APPROVED, 422, 'لا يمكن إلغاء سوى التحويلات المعتمدة مسبقاً');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $conversion->update([
            'status' => ConversionStatus::CANCELLED,
            'rejection_reason' => $validated['reason'] ?? 'تم إرجاع الطلب أو إلغاؤه من قبل العميل',
        ]);

        AuditService::log(
            action: AuditAction::CONVERSION_CANCELLED,
            userId: $request->user()->id,
            entityType: 'Conversion',
            entityId: $conversion->id,
            payloadDiff: [
                'conversion_id' => $conversion->id,
                'external_order_id' => $conversion->external_order_id,
                'reason' => $validated['reason'] ?? 'تم إرجاع الطلب',
                'brand_profile_id' => $brandProfile->id,
            ]
        );

        return back()->with('success', "تم إلغاء التحويل رقم الطلب #{$conversion->external_order_id}.");
    }
}
