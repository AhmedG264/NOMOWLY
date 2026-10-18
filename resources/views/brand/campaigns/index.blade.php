@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>🏬</span>
                <span>لوحة تحكم العلامة التجارية • إدارة الحملات</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">حملات {{ auth()->user()->brandProfile?->company_name ?? auth()->user()->name }}</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                إدارة حملات الأداء، ومراجعة طلبات صناع المحتوى والمسوقين، وتوليد أكواد التتبع المخصصة.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('brand.conversions.index') }}" class="px-4 py-2.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-200 border border-emerald-400/40 rounded-xl text-xs sm:text-sm font-black transition flex items-center gap-2">
                <span>🎯</span>
                <span>سجل التحويلات والإسناد</span>
            </a>
            <a href="{{ route('brand.campaigns.create') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>+</span>
                <span>إنشاء حملة تسويقية جديدة</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Key Performance Stats -->
    <div class="space-y-4">
        <!-- Campaign & Creators Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">إجمالي الحملات</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">الحملات النشطة</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['active'] }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">إجمالي طلبات الانضمام</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total_applicants'] }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">صناع المحتوى المعتمدون</div>
                <div class="text-2xl font-black text-teal-600 mt-1">{{ $stats['approved_creators'] }}</div>
            </div>
        </div>

        <!-- Real Attribution Engine Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>إجمالي النقرات المسجلة</span>
                    <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">فعلي</span>
                </div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_clicks'] ?? 0) }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5 font-medium">نقرات عبر روابط التتبع المعتمدة</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>التحويلات والمبيعات</span>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-black">إسناد فعلي</span>
                </div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['total_conversions'] ?? 0) }}</div>
                <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">معتمدة: {{ number_format($stats['approved_conversions'] ?? 0) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>معدل التحويل المحقق</span>
                    <span class="text-[10px] bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-black">حساب فعلي</span>
                </div>
                <div class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($stats['conversion_rate'] ?? 0, 2) }}%</div>
                <div class="text-[11px] text-slate-400 mt-0.5 font-medium">نسبة التحويلات إلى إجمالي النقرات</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>التزامات العمولات المعتمدة</span>
                    <a href="{{ route('brand.conversions.index') }}" class="text-[10px] text-emerald-600 hover:underline">عرض ←</a>
                </div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['approved_commission'] ?? 0, 2) }} <span class="text-xs text-slate-500 font-normal">ر.س</span></div>
                <div class="text-[11px] text-amber-600 mt-0.5 font-medium">قيد المراجعة: {{ number_format($stats['pending_commission'] ?? 0, 2) }} ر.س</div>
            </div>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-black text-slate-900">قائمة الحملات التسويقية</h2>

            <div class="flex items-center gap-2 text-xs">
                <a href="{{ route('brand.campaigns.index') }}" class="px-3 py-1.5 rounded-lg font-bold {{ !request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">الكل</a>
                <a href="{{ route('brand.campaigns.index', ['status' => 'ACTIVE']) }}" class="px-3 py-1.5 rounded-lg font-bold {{ request('status') === 'ACTIVE' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700' }}">النشطة</a>
                <a href="{{ route('brand.campaigns.index', ['status' => 'PAUSED']) }}" class="px-3 py-1.5 rounded-lg font-bold {{ request('status') === 'PAUSED' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700' }}">المتوقفة</a>
            </div>
        </div>

        @if($campaigns->isEmpty())
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-2xl">
                    📢
                </div>
                <h3 class="text-lg font-black text-slate-900">لم تقم بإنشاء أي حملة تسويقية بعد</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    ابدأ الآن بإطلاق أول حملة تسويق أداء لمتجرك وحدد نسبة العمولة والشروط لجذب أفضل صناع المحتوى في المنطقة.
                </p>
                <a href="{{ route('brand.campaigns.create') }}" class="inline-block px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                    + إنشاء أول حملة الآن
                </a>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($campaigns as $campaign)
                    <div class="p-6 hover:bg-slate-50/60 transition flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black border {{ $campaign->status->badgeClasses() }}">
                                    {{ $campaign->status->label() }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600">
                                    {{ $campaign->category }}
                                </span>
                                @if($campaign->isTermsLocked())
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                        🔒 شروط مثبتة (v{{ $campaign->currentTermsVersion?->version_number ?? 1 }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        🔓 شروط أولية (v1)
                                    </span>
                                @endif
                                @if($campaign->badge)
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ★ {{ $campaign->badge }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-black text-slate-900">
                                <a href="{{ route('marketplace.show', $campaign->slug) }}" class="hover:text-emerald-700 transition">
                                    {{ $campaign->title }}
                                </a>
                            </h3>

                            <p class="text-xs text-slate-500 line-clamp-1">
                                {{ $campaign->description }}
                            </p>

                            <div class="flex flex-wrap items-center gap-4 text-xs pt-1">
                                <span class="text-emerald-700 font-bold">العمولة: {{ $campaign->commission_value }}</span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600">الكوكيز: {{ $campaign->cookie_duration_days }} يوماً</span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600">الأسواق: {{ is_array($campaign->target_markets) ? implode(', ', $campaign->target_markets) : $campaign->target_markets }}</span>
                            </div>
                        </div>

                        <!-- Actions & Applicants count -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="{{ route('brand.campaigns.terms', $campaign) }}" class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-black transition flex items-center gap-1">
                                <span>📜</span>
                                <span>الشروط (v{{ $campaign->currentTermsVersion?->version_number ?? 1 }})</span>
                            </a>

                            <a href="{{ route('brand.campaigns.applications', $campaign) }}" class="px-4 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 text-xs font-black transition flex items-center gap-1.5">
                                <span>المتقدمين:</span>
                                <span class="px-1.5 py-0.5 bg-teal-200/80 rounded-md text-[11px]">{{ $campaign->applications_count }}</span>
                                @if($campaign->approved_applications_count > 0)
                                    <span class="text-emerald-700 text-[11px]">({{ $campaign->approved_applications_count }} معتمد)</span>
                                @endif
                            </a>

                            <a href="{{ route('marketplace.show', $campaign->slug) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                                استعراض في السوق ↗
                            </a>

                            <a href="{{ route('brand.campaigns.edit', $campaign) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                                تعديل
                            </a>

                            <form method="POST" action="{{ route('brand.campaigns.status', $campaign) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                @if($campaign->status === \App\Enums\CampaignStatus::ACTIVE)
                                    <input type="hidden" name="status" value="PAUSED">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold transition cursor-pointer">
                                        إيقاف مؤقت
                                    </button>
                                @else
                                    <input type="hidden" name="status" value="ACTIVE">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition cursor-pointer">
                                        تفعيل
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
