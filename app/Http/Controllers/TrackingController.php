<?php

namespace App\Http\Controllers;

use App\Services\AttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TrackingController extends Controller
{
    public function __construct(
        protected AttributionService $attributionService
    ) {}

    /**
     * Handle marketer tracking link redirection: /go/{tracking_code}
     */
    public function track(Request $request, string $tracking_code): RedirectResponse
    {
        $result = $this->attributionService->recordClick($request, $tracking_code);

        if (!$result['success']) {
            return redirect()->route('marketplace')
                ->with('error', $result['message'] ?? 'رابط التتبع غير صالح أو منتهي الصلاحية.');
        }

        $destinationUrl = $result['destination_url'];
        $visitorId = $result['visitor_id'];
        $cookieDays = $result['cookie_days'] ?? 30;

        // Set first-party HTTP-only cookie with attribution window duration
        $attributionCookie = cookie(
            name: AttributionService::VISITOR_COOKIE,
            value: $visitorId,
            minutes: $cookieDays * 24 * 60,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        // Also queue session tracking context for seamless in-app attribution testing
        $request->session()->put('nmw_last_click_id', $result['click']->id);
        $request->session()->put('nmw_last_tracking_code', $result['click']->tracking_code);
        $request->session()->put('nmw_last_campaign_id', $result['campaign']->id);

        return redirect()->away($destinationUrl)->withCookie($attributionCookie);
    }
}
