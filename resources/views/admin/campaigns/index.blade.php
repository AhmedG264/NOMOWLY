@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-purple-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold mb-3 border border-purple-400/30">
                <span>🛡️</span>
                <span>لوحة التحكم الإدارية العليا (Super Admin)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">إدارة حملات منصة نمولي</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                الإشراف العام على حملات العلامات التجارية، ومراقبة الالتزام، والتحكم في حالة النشر والتسويق.
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                تسجيل الخروج
            </button>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap text-sm">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl font-black bg-purple-900 text-white shadow-xs">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.campaigns.change-requests.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            طلبات تعديل الشروط
        </a>
        <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>⭐</span>
            <span>خطط الاشتراكات</span>
        </a>
        <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            اشتراكات المسوقين
        </a>
        <a href="{{ route('admin.conversions.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>🔍</span>
            <span>التحويلات ومكافحة الاحتيال</span>
        </a>
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الإدارة المالية والمحاسبة
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>🛡️</span>
            <span>سجل العمليات</span>
        </a>
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            المستخدمون
        </a>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- System KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي حملات المنصة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الحملات النشطة في السوق</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الحملات المميزة الحصرية ⭐</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $stats['premium'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الحملات المتوقفة</div>
            <div class="text-2xl font-black text-slate-500 mt-1">{{ $stats['paused'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي طلبات الانضمام</div>
            <div class="text-2xl font-black text-purple-600 mt-1">{{ $stats['total_applications'] }}</div>
        </div>
    </div>

    <!-- Campaigns Moderation Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-base font-black text-slate-900">جميع حملات المنصة وإعدادات التمييز</h2>

            <form method="GET" action="{{ route('admin.campaigns.index') }}" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث بالعنوان أو المتجر..." class="text-xs rounded-xl border border-slate-300 p-2 w-48">
                <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">بحث</button>
            </form>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($campaigns as $camp)
                <div class="p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 hover:bg-slate-50/50 transition" x-data="{ openPremiumSettings: false }">
                    <div class="space-y-2 max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black border {{ $camp->status->badgeClasses() }}">
                                {{ $camp->status->label() }}
                            </span>

                            @if($camp->is_premium)
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black bg-amber-50 text-amber-800 border border-amber-300 flex items-center gap-1">
                                    <span>⭐</span>
                                    <span>حملة مميزة للمشتركين</span>
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    حملة عادية
                                </span>
                            @endif

                            <span class="text-xs font-bold text-slate-500">العلامة:</span>
                            <span class="text-xs font-black text-slate-800">{{ $camp->brandProfile?->company_name ?? 'علامة غير معروفة' }}</span>
                            <span class="text-xs text-slate-400">({{ $camp->brandProfile?->user?->email }})</span>
                        </div>

                        <h3 class="text-base font-black text-slate-900">
                            {{ $camp->title }}
                        </h3>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                            <span>العمولة: <strong class="text-emerald-700">{{ $camp->commission_value }}</strong></span>
                            <span>•</span>
                            <span>التصنيف: {{ $camp->category }}</span>
                            <span>•</span>
                            <span>المتقدمون: <strong class="text-purple-700">{{ $camp->applications_count }}</strong></span>
                            <span>•</span>
                            <span>المعتمدون: <strong class="text-emerald-700">{{ $camp->approved_applications_count }}</strong></span>
                        </div>

                        @if($camp->is_premium && !empty($camp->allowed_plan_ids))
                            <div class="text-[11px] text-amber-800 bg-amber-50/80 p-2 rounded-xl border border-amber-200/60 inline-flex items-center gap-1">
                                <span class="font-bold">الباقات المؤهلة المسموح بها:</span>
                                <span>
                                    {{ \App\Models\SubscriptionPlan::whereIn('id', (array)$camp->allowed_plan_ids)->pluck('name_ar')->implode(' • ') ?: 'كل الباقات النشطة' }}
                                </span>
                            </div>
                        @endif

                        <!-- Expandable Premium Settings Panel -->
                        <div x-show="openPremiumSettings" x-collapse style="display: none;" class="mt-4 p-5 rounded-2xl bg-purple-50/60 border border-purple-200/80 space-y-4">
                            <div class="flex items-center justify-between border-b border-purple-200/60 pb-2">
                                <span class="font-black text-xs text-purple-950 flex items-center gap-1.5">
                                    <span>⚙️</span>
                                    <span>إدارة أهلية الحملة المميزة والخطط المسموح بها</span>
                                </span>
                                <button type="button" @click="openPremiumSettings = false" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                                    إغلاق ✕
                                </button>
                            </div>

                            <form method="POST" action="{{ route('admin.campaigns.premium', $camp) }}" class="space-y-4">
                                @csrf
                                @method('PATCH')

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">نوع الحملة والأهلية:</label>
                                        <select name="is_premium" class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white font-bold">
                                            <option value="0" {{ !$camp->is_premium ? 'selected' : '' }}>حملة عادية (متاحة لكافة المسوقين)</option>
                                            <option value="1" {{ $camp->is_premium ? 'selected' : '' }}>حملة مميزة حصرية (تتطلب باقة اشتراك نشطة) ⭐</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات أو متطلبات خاصة:</label>
                                        <input type="text" name="premium_requirements" value="{{ $camp->premium_requirements }}" placeholder="اختياري: مثال للمسوقين المحترفين فقط..." class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        تحديد الباقات المسموح لها بالمشاركة (اتركها فارغة لإتاحتها لجميع الباقات التي تتضمن الميزة):
                                    </label>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach($subscriptionPlans as $plan)
                                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold cursor-pointer hover:border-purple-600 transition">
                                                <input type="checkbox" name="allowed_plan_ids[]" value="{{ $plan->id }}"
                                                       {{ is_array($camp->allowed_plan_ids) && in_array($plan->id, $camp->allowed_plan_ids) ? 'checked' : '' }}
                                                       class="rounded text-purple-600">
                                                <span>{{ $plan->name_ar }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="px-4 py-2 bg-purple-900 hover:bg-black text-white rounded-xl text-xs font-black shadow-sm transition cursor-pointer">
                                        حفظ إعدادات التمييز
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Moderation Status & Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 shrink-0">
                        <button type="button" @click="openPremiumSettings = !openPremiumSettings" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer">
                            <span>⭐</span>
                            <span>إعدادات التمييز</span>
                        </button>

                        <a href="{{ route('marketplace.show', $camp->slug) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition text-center">
                            معاينة ↗
                        </a>

                        <form method="POST" action="{{ route('admin.campaigns.status', $camp) }}" class="flex items-center gap-1.5">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="text-xs rounded-lg border border-slate-300 p-1.5 bg-white font-bold">
                                @foreach(\App\Enums\CampaignStatus::cases() as $st)
                                    <option value="{{ $st->value }}" {{ $camp->status === $st ? 'selected' : '' }}>
                                        {{ $st->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                تطبيق
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>
@endsection
