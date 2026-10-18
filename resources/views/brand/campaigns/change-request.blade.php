@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8" x-data="{
    currentType: '{{ $currentVersion->commission_type instanceof \App\Enums\CampaignCommissionType ? $currentVersion->commission_type->value : $currentVersion->commission_type }}',
    currentValue: '{{ $currentVersion->commission_value }}',
    currentCookies: '{{ $currentVersion->cookie_duration_days }}',
    currentCurrency: '{{ $currentVersion->currency }}',
    newType: '{{ old('commission_type', $currentVersion->commission_type instanceof \App\Enums\CampaignCommissionType ? $currentVersion->commission_type->value : $currentVersion->commission_type) }}',
    newValue: '{{ old('commission_value', $currentVersion->commission_value) }}',
    newCookies: '{{ old('cookie_duration_days', $currentVersion->cookie_duration_days) }}',
    newCurrency: '{{ old('currency', $currentVersion->currency) }}'
}">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('brand.campaigns.terms', $campaign) }}" class="text-xs font-bold text-slate-500 hover:text-indigo-700 transition">
                ← العودة إلى سجل الشروط التعاقدية
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">تقديم طلب تغيير شروط الحملة (Change Request)</h1>
            <p class="text-xs text-slate-500 mt-1">الحملة: {{ $campaign->title }} • الإصدار الحالي: v{{ $currentVersion->version_number }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="font-black">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="list-disc list-inside text-xs font-normal">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Information Card -->
    <div class="bg-indigo-50 border border-indigo-200 p-5 rounded-3xl text-xs text-indigo-900 space-y-2">
        <div class="font-black flex items-center gap-2">
            <span>ℹ️</span>
            <span>سياسة حماية الشروط والترقية التعاقدية</span>
        </div>
        <p class="leading-relaxed">
            عند اعتماد هذا الطلب من إدارة المنصة، سيتم إصدار شروط تعاقدية جديدة برقم تسلسلي أعلى (v{{ $currentVersion->version_number + 1 }}). لن تتأثر التحويلات السابقة أو العمولات المحققة أو المستحقة مسبقاً بأي شكل من الأشكال.
        </p>
    </div>

    <form method="POST" action="{{ route('brand.campaigns.change-requests.store', $campaign) }}" class="space-y-8">
        @csrf

        <!-- 1. Scope & Reason -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">1. تفاصيل ونطاق طلب التعديل</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">نطاق تطبيق الشروط الجديدة *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach(\App\Enums\CampaignTermsChangeScope::cases() as $scope)
                            <label class="flex items-start gap-2.5 p-3.5 rounded-2xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-indigo-300 transition">
                                <input type="radio" name="scope" value="{{ $scope->value }}" {{ old('scope', \App\Enums\CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value) === $scope->value ? 'checked' : '' }} class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-xs font-black text-slate-800 block">{{ $scope->label() }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">سبب طلب التعديل ودوافعه التجارية *</label>
                    <textarea name="reason" rows="3" required placeholder="اذكر أسباب التعديل بالتفصيل لمراجعة الإدارة (مثلاً: إطلاق موسم تخفيضات صيفي، تحسين نسب العمولات، تعديل استراتيجية التسويق)..." class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ بداية السريان المقترح (اختياري)</label>
                    <input type="datetime-local" name="proposed_effective_from" value="{{ old('proposed_effective_from') }}" class="w-full sm:w-72 text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">إذا تُرك فارغاً، سيبدأ السريان فور اعتماد الطلب من الإدارة.</p>
                </div>
            </div>
        </div>

        <!-- 2. Proposed Terms Changes -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">2. الشروط التعاقدية المقترحة</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">نوع نموذج التعويض المقترح *</label>
                        <select name="commission_type" x-model="newType" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(\App\Enums\CampaignCommissionType::cases() as $type)
                                <option value="{{ $type->value }}">
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">قيمة العمولة المقترحة *</label>
                        <input type="text" name="commission_value" x-model="newValue" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">مدة الكوكيز بالأيام المقترحة *</label>
                        <input type="number" name="cookie_duration_days" x-model="newCookies" min="1" max="365" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">العملة الأساسية المقترحة *</label>
                        <select name="currency" x-model="newCurrency" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['SAR', 'EGP', 'AED', 'KWD', 'QAR', 'OMR', 'BHD'] as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تعديل شروط احتساب التحويل (اختياري)</label>
                    <textarea name="conversion_qualification_rules" rows="2" class="w-full text-sm rounded-xl border border-slate-300 p-3">{{ old('conversion_qualification_rules', $currentVersion->conversion_qualification_rules) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تعديل شروط الموافقة والاعتماد (اختياري)</label>
                    <textarea name="approval_rules" rows="2" class="w-full text-sm rounded-xl border border-slate-300 p-3">{{ old('approval_rules', $currentVersion->approval_rules) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تعديل قواعد الإلغاء والاسترداد (اختياري)</label>
                    <textarea name="cancellation_refund_rules" rows="2" class="w-full text-sm rounded-xl border border-slate-300 p-3">{{ old('cancellation_refund_rules', $currentVersion->cancellation_refund_rules) }}</textarea>
                </div>
            </div>
        </div>

        <!-- 3. Live Diff Preview -->
        <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200 space-y-4">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🔍</span>
                <span>المعاينة الفورية للفروقات (Live Diff View)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="bg-white p-4 rounded-2xl border border-slate-200">
                    <span class="font-bold text-slate-400 block mb-1">الإصدار الحالي (v{{ $currentVersion->version_number }})</span>
                    <ul class="space-y-1 text-slate-700">
                        <li>نوع العمولة: <strong class="text-slate-900">{{ $currentVersion->commission_type?->label() }}</strong></li>
                        <li>قيمة العمولة: <strong class="text-emerald-700">{{ $currentVersion->commission_value }}</strong></li>
                        <li>الكوكيز: <strong class="text-slate-900">{{ $currentVersion->cookie_duration_days }} يوماً</strong></li>
                        <li>العملة: <strong class="text-slate-900">{{ $currentVersion->currency }}</strong></li>
                    </ul>
                </div>

                <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-200">
                    <span class="font-bold text-indigo-700 block mb-1">الإصدار المقترح الجديد (v{{ $currentVersion->version_number + 1 }})</span>
                    <ul class="space-y-1 text-slate-700">
                        <li>نوع العمولة: <strong class="text-indigo-900" x-text="newType"></strong></li>
                        <li>قيمة العمولة: <strong class="text-emerald-700 font-black" x-text="newValue"></strong></li>
                        <li>الكوكيز: <strong class="text-indigo-900" x-text="newCookies + ' يوماً'"></strong></li>
                        <li>العملة: <strong class="text-indigo-900" x-text="newCurrency"></strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('brand.campaigns.terms', $campaign) }}" class="px-5 py-3 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                إرسال طلب التعديل للمراجعة الإدارية ←
            </button>
        </div>
    </form>
</div>
@endsection
