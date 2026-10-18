@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>📜</span>
                <span>إدارة الشروط التعاقدية وإصدارات الحملة (Campaign Terms & Versioning)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $campaign->title }}</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تاريخ وتتبع جميع الإصدارات التعاقدية، وتثبيت الشروط، وطلبات التعديل المعتمدة لحماية حقوق الأطراف.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('brand.campaigns.edit', $campaign) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للتعديل
            </a>
            <a href="{{ route('brand.campaigns.change-requests.create', $campaign) }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>📝</span>
                <span>تقديم طلب تغيير شروط</span>
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Lock State & Version Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">حالة التثبيت التعاقدي (Lock Status)</div>
            <div class="flex items-center gap-2 mt-1">
                @if($campaign->isTermsLocked())
                    <span class="px-3 py-1 rounded-xl bg-amber-50 text-amber-800 border border-amber-300 text-xs font-black flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>شروط مثبتة ومحمية</span>
                    </span>
                @else
                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-black flex items-center gap-1.5">
                        <span>🔓</span>
                        <span>شروط قابلة للتعديل المباشر (قبل انضمام المسوقين)</span>
                    </span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                {{ $campaign->terms_lock_reason ?? 'يتم تثبيت الشروط تلقائياً فور انضمام أول مسوق أو تسجيل أول نقرة إسناد.' }}
            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">الإصدار الساري المعتمد حالياً</div>
            <div class="text-2xl font-black text-indigo-700 mt-1">
                v{{ $campaign->currentTermsVersion?->version_number ?? 1 }}
                <span class="text-xs font-normal text-slate-500">({{ $campaign->currentTermsVersion?->status->label() ?? 'سارٍ' }})</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                العمولة: <strong>{{ $campaign->currentTermsVersion?->commission_value ?? $campaign->commission_value }}</strong>
                ({{ $campaign->currentTermsVersion?->commission_type?->label() ?? $campaign->commission_type->label() }})
            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs text-slate-500 font-bold">إجمالي طلبات التعديل المقدمة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">
                {{ $changeRequests->count() }}
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                قيد المراجعة: {{ $changeRequests->where('status', \App\Enums\CampaignTermsChangeRequestStatus::PENDING)->count() }} |
                معتمد: {{ $changeRequests->where('status', \App\Enums\CampaignTermsChangeRequestStatus::APPROVED)->count() }}
            </p>
        </div>
    </div>

    <!-- Terms Versions History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>📑</span>
                <span>سجل الإصدارات التعاقدية للحملة (Terms Version History)</span>
            </h2>
            <span class="text-xs text-slate-500 font-bold">إجمالي الإصدارات: {{ $versions->count() }}</span>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($versions as $v)
                <div class="p-6 hover:bg-slate-50/60 transition space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                الإصدار v{{ $v->version_number }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $v->status->badgeClasses() }}">
                                {{ $v->status->label() }}
                            </span>
                            <span class="text-xs text-slate-500">
                                تاريخ السريان: {{ $v->effective_from?->format('Y-m-d H:i') ?? '-' }}
                                @if($v->effective_until)
                                    — حتى: {{ $v->effective_until->format('Y-m-d H:i') }}
                                @endif
                            </span>
                        </div>

                        <div class="text-xs text-slate-400">
                            أنشئ في: {{ $v->created_at?->format('Y-m-d') }}
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/70 text-xs">
                        <div>
                            <span class="text-slate-500 block">نموذج التعويض:</span>
                            <span class="font-bold text-slate-900">{{ $v->commission_type?->label() }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">قيمة العمولة:</span>
                            <span class="font-black text-emerald-700">{{ $v->commission_value }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">مدة الكوكيز:</span>
                            <span class="font-bold text-slate-900">{{ $v->cookie_duration_days }} يوماً</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">العملة الأساسية:</span>
                            <span class="font-bold text-slate-900">{{ $v->currency }}</span>
                        </div>
                    </div>

                    @if($v->conversion_qualification_rules || $v->approval_rules || $v->cancellation_refund_rules)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-1">
                            @if($v->conversion_qualification_rules)
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">شروط احتساب التحويل:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">{{ $v->conversion_qualification_rules }}</p>
                                </div>
                            @endif
                            @if($v->approval_rules)
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">شروط الموافقة والاعتماد:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">{{ $v->approval_rules }}</p>
                                </div>
                            @endif
                            @if($v->cancellation_refund_rules)
                                <div class="bg-white p-3 rounded-xl border border-slate-200">
                                    <span class="font-bold text-slate-700 block mb-1">قواعد الإلغاء والاسترداد:</span>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">{{ $v->cancellation_refund_rules }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Change Requests History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>🔄</span>
                <span>سجل طلبات تغيير الشروط (Change Requests History)</span>
            </h2>
            <a href="{{ route('brand.campaigns.change-requests.create', $campaign) }}" class="text-xs font-bold text-indigo-700 hover:underline">
                + طلب تعديل جديد
            </a>
        </div>

        @if($changeRequests->isEmpty())
            <div class="p-8 text-center text-slate-500 text-xs">
                لم يتم تقديم أي طلبات لتعديل شروط الحملة حتى الآن.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($changeRequests as $cr)
                    <div class="p-6 hover:bg-slate-50/60 transition space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $cr->status->badgeClasses() }}">
                                    {{ $cr->status->label() }}
                                </span>
                                <span class="text-xs font-bold text-slate-700">
                                    نطاق التأثير: {{ $cr->scope->label() }}
                                </span>
                            </div>

                            <span class="text-xs text-slate-400">
                                قُدم بتاريخ: {{ $cr->created_at->format('Y-m-d H:i') }}
                            </span>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-2">
                            <div>
                                <span class="text-slate-500 font-bold">سبب طلب التعديل:</span>
                                <p class="text-slate-800 mt-0.5 font-medium">{{ $cr->reason }}</p>
                            </div>

                            @if($cr->diff_summary)
                                <div class="pt-2 border-t border-slate-200">
                                    <span class="text-slate-500 font-bold block mb-1.5">مقارنة التعديلات المقترحة (Visual Diff):</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($cr->diff_summary as $field => $diff)
                                            <div class="bg-white p-2.5 rounded-xl border border-slate-200 text-[11px]">
                                                <div class="font-bold text-slate-700 mb-1">{{ $diff['label'] ?? $field }}</div>
                                                <div class="flex items-center gap-2">
                                                    <span class="line-through text-rose-600">{{ $diff['old'] }}</span>
                                                    <span>←</span>
                                                    <span class="font-bold text-emerald-700">{{ $diff['new'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($cr->reviewer_notes)
                                <div class="pt-2 border-t border-slate-200">
                                    <span class="text-slate-500 font-bold">ملاحظات مراجعة الإدارة:</span>
                                    <p class="text-slate-800 mt-0.5">{{ $cr->reviewer_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
