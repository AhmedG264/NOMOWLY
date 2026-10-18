@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" dir="rtl">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-purple-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold mb-3 border border-purple-400/30">
                <span>⭐</span>
                <span>باقات واشتراكات المسوقين (Phase 8)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">إدارة خطط الاشتراكات والحملات المميزة</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تحديد أسعار الباقات بالعملات المعتمدة، وضبط مزايا الوصول للحملات الحصرية وحدود المشاركة.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.subscriptions.plans.create') }}" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>+</span>
                <span>إنشاء خطة اشتراك جديدة</span>
            </a>
            <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                سجل الاشتراكات
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap text-sm">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-4 py-2 rounded-xl font-black bg-purple-900 text-white shadow-xs">
            خطط الاشتراكات
        </a>
        <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            اشتراكات المسوقين
        </a>
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الإدارة المالية والمحاسبة
        </a>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Plans Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">قائمة باقات الاشتراك المتاحة للمسوقين</h2>
                <p class="text-xs text-slate-500 mt-0.5">تغيير الأسعار هنا ينطبق على المشتركين الجدد ولا يؤثر بأثر رجعي على الاشتراكات القائمة.</p>
            </div>
            <span class="text-xs font-bold text-slate-500">إجمالي الخطط: {{ $plans->count() }}</span>
        </div>

        @if($plans->isEmpty())
            <div class="text-center py-16 px-4">
                <span class="text-4xl block mb-3">📦</span>
                <p class="text-slate-700 font-black text-base">لا توجد خطط اشتراك مضافة حتى الآن</p>
                <p class="text-slate-400 text-xs mt-1">ابدأ بإنشاء أول خطة اشتراك لتفعيل ميزة الحملات المميزة للمسوقين.</p>
                <a href="{{ route('admin.subscriptions.plans.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl text-xs font-black hover:bg-purple-700 transition">
                    + إنشاء أول خطة اشتراك
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                            <th class="py-3.5 px-6">الخطة</th>
                            <th class="py-3.5 px-4">الدورة والمدة</th>
                            <th class="py-3.5 px-4">الأسعار والعملات</th>
                            <th class="py-3.5 px-4">مزايا الحملات المميزة</th>
                            <th class="py-3.5 px-4">المشتركون النشطون</th>
                            <th class="py-3.5 px-4">الحالة</th>
                            <th class="py-3.5 px-6 text-left">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @foreach ($plans as $plan)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-6 font-bold">
                                    <div class="flex items-center gap-2">
                                        @if($plan->is_featured)
                                            <span class="text-amber-500 text-xs" title="خطة مميزة">⭐</span>
                                        @endif
                                        <span class="font-black text-slate-900">{{ $plan->name_ar }}</span>
                                    </div>
                                    @if($plan->name_en)
                                        <span class="text-xs text-slate-400 block font-mono">{{ $plan->name_en }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    <span class="font-bold text-slate-700">{{ $plan->billing_cycle->label() }}</span>
                                    <span class="text-slate-400 block font-mono">{{ $plan->duration_in_days }} يوماً</span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @forelse($plan->prices as $price)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ number_format((float)$price->price, 2) }} {{ $price->currency->value }}
                                            </span>
                                        @empty
                                            <span class="text-rose-500 text-xs">غير مسعرة</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    @if($plan->allows_premium_campaigns)
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                            <span>✓</span>
                                            <span>مشاركة بالحملات المميزة</span>
                                        </span>
                                        <span class="text-slate-500 block text-[11px]">
                                            شهرياً: {{ $plan->max_premium_campaigns_per_month ? $plan->max_premium_campaigns_per_month . ' حملات' : 'غير محدود' }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-bold">حملات عادية فقط</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-xs font-mono font-bold">
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-200">
                                        {{ $plan->active_subscriptions_count }} نشط
                                    </span>
                                    <span class="text-slate-400 block mt-0.5">إجمالي: {{ $plan->subscriptions_count }}</span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $plan->status->badgeClass() }}">
                                        {{ $plan->status->label() }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-left">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.subscriptions.plans.edit', $plan->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition">
                                            تعديل
                                        </a>

                                        <form method="POST" action="{{ route('admin.subscriptions.plans.status', $plan->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            @if($plan->status === \App\Enums\SubscriptionPlanStatus::ACTIVE)
                                                <input type="hidden" name="status" value="INACTIVE">
                                                <button type="submit" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                                    تعطيل
                                                </button>
                                            @else
                                                <input type="hidden" name="status" value="ACTIVE">
                                                <button type="submit" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                                    تفعيل
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
