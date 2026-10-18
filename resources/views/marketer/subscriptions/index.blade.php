@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-xs font-bold border border-purple-200 mb-2">
                <span>⭐</span>
                <span>باقات واشتراكات المسوق</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">سجل اشتراكاتي في باقات نمولي</h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-0.5">متابعة حالة اشتراكك، وتاريخ التجديد، والمزايا المشمولة في باقتك الحالية.</p>
        </div>

        <a href="{{ route('marketer.plans.index') }}" class="px-5 py-2.5 bg-purple-900 hover:bg-purple-950 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
            استعراض وترقية الباقات
        </a>
    </div>

    <!-- Active Subscription Card -->
    @if ($activeSubscription)
        <div class="bg-gradient-to-r from-purple-900 via-slate-900 to-purple-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-purple-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-400/30">
                    <span>✓</span>
                    <span>اشتراكك الحالي سارٍ ونشط</span>
                </div>
                <h2 class="text-2xl font-black">{{ $activeSubscription->plan_snapshot['name_ar'] ?? $activeSubscription->plan?->name_ar }}</h2>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-1">
                    <span>رقم الاشتراك: <strong class="font-mono text-white">{{ $activeSubscription->subscription_number }}</strong></span>
                    <span>•</span>
                    <span>صالح حتى: <strong class="font-mono text-emerald-400">{{ $activeSubscription->ends_at?->format('Y-m-d') }}</strong></span>
                    <span>•</span>
                    <span>متبقٍ: <strong class="font-mono text-white">{{ $activeSubscription->daysRemaining() }}</strong> يوماً</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('marketer.subscriptions.show', $activeSubscription->id) }}" class="px-5 py-2.5 bg-white text-purple-900 hover:bg-slate-100 rounded-xl text-xs font-black shadow-sm transition">
                    عرض المزايا واللقطة التعاقدية
                </a>
            </div>
        </div>
    @else
        <div class="bg-amber-50 rounded-3xl p-6 border border-amber-200 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="font-black text-sm block">ليس لديك اشتراك نشط حالياً</span>
                <span class="text-xs text-amber-700 block mt-0.5">اشترك في إحدى باقات المسوقين للتقديم على الحملات المميزة والحصرية لكبرى المتاجر.</span>
            </div>
            <a href="{{ route('marketer.plans.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition">
                اختيار باقة الآن
            </a>
        </div>
    @endif

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- History Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="text-base font-black text-slate-900">سجل الاشتراكات السابقة والحالية</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <th class="py-3.5 px-6">رقم الاشتراك</th>
                        <th class="py-3.5 px-4">الباقة</th>
                        <th class="py-3.5 px-4">المبلغ والعملة</th>
                        <th class="py-3.5 px-4">وسيلة الدفع</th>
                        <th class="py-3.5 px-4">تاريخ الانتهاء</th>
                        <th class="py-3.5 px-4">الحالة</th>
                        <th class="py-3.5 px-6 text-left">التفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse ($subscriptions as $sub)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6 font-mono font-bold text-purple-900">
                                {{ $sub->subscription_number }}
                            </td>
                            <td class="py-4 px-4 font-bold">
                                {{ $sub->plan_snapshot['name_ar'] ?? $sub->plan?->name_ar }}
                            </td>
                            <td class="py-4 px-4 font-mono font-bold">
                                {{ number_format((float)$sub->price_paid, 2) }} {{ $sub->currency->value }}
                            </td>
                            <td class="py-4 px-4 text-xs font-bold">
                                {{ $sub->payment_method === 'WALLET' ? 'محفظة الأرباح' : 'بوابة الدفع' }}
                            </td>
                            <td class="py-4 px-4 font-mono text-xs">
                                {{ $sub->ends_at ? $sub->ends_at->format('Y-m-d') : '—' }}
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $sub->status->badgeClass() }}">
                                    {{ $sub->status->label() }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-left">
                                <a href="{{ route('marketer.subscriptions.show', $sub->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition">
                                    عرض
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-500">
                                لا توجد اشتراكات مسجلة في حسابك حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
