@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-xs font-bold border border-purple-200">
            <span>⭐</span>
            <span>باقات اشتراك المسوقين وصناع المحتوى</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">اختر الباقة المناسبة وضاعف أرباحك</h1>
        <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
            انضم إلى الحملات الحصرية لكبرى المتاجر والعلامات التجارية، واستفد من عمولات مرتفعة وأولوية في الاعتماد.
        </p>

        <!-- Currency Selector -->
        <div class="inline-flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-xs mt-2">
            <span class="text-xs font-bold text-slate-500 px-2">عرض الأسعار بـ:</span>
            <div class="flex items-center gap-1">
                @foreach ($currencies as $curr)
                    <a href="{{ route('marketer.plans.index', ['currency' => $curr]) }}" class="px-3 py-1.5 rounded-xl text-xs font-mono font-bold transition {{ $selectedCurrency === $curr ? 'bg-purple-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $curr }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Active Subscription Banner (if any) -->
    @if ($activeSubscription)
        <div class="bg-gradient-to-r from-emerald-900 via-slate-900 to-emerald-900 rounded-3xl p-6 text-white shadow-lg border border-emerald-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold">
                    <span>✓</span>
                    <span>اشتراكك الحالي سارٍ ونشط</span>
                </div>
                <h3 class="text-lg font-black">{{ $activeSubscription->plan_snapshot['name_ar'] ?? $activeSubscription->plan?->name_ar }}</h3>
                <p class="text-xs text-slate-300">
                    صالح حتى: <span class="font-mono font-bold text-white">{{ $activeSubscription->ends_at?->format('Y-m-d') }}</span>
                    (متبقٍ <span class="font-mono font-bold text-emerald-400">{{ $activeSubscription->daysRemaining() }}</span> يوماً)
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('marketer.subscriptions.show', $activeSubscription->id) }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold border border-white/20 transition">
                    تفاصيل الباقة والمزايا
                </a>
                <a href="{{ route('marketer.subscriptions.index') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                    سجل اشتراكاتي
                </a>
            </div>
        </div>
    @endif

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold flex items-center gap-2">
            <span class="text-rose-600 font-black">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Plans Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 pt-4">
        @forelse ($plans as $plan)
            @php
                $priceObj = $plan->priceFor($selectedCurrency);
                $isCurrentPlan = $activeSubscription && $activeSubscription->subscription_plan_id === $plan->id;
            @endphp
            <div class="relative bg-white rounded-3xl border {{ $plan->is_featured ? 'border-purple-600 shadow-xl ring-2 ring-purple-600/20' : 'border-slate-200/80 shadow-xs' }} p-6 sm:p-8 flex flex-col justify-between hover:shadow-lg transition">
                @if($plan->is_featured)
                    <div class="absolute -top-3.5 right-6 px-3 py-1 rounded-full bg-purple-600 text-white text-[11px] font-black shadow-sm">
                        الأكثر شعبية واختياراً
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">{{ $plan->name_ar }}</h3>
                        @if($plan->description_ar)
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $plan->description_ar }}</p>
                        @endif
                    </div>

                    <!-- Price -->
                    <div class="py-3 border-y border-slate-100">
                        @if($priceObj)
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-slate-900 font-mono">{{ number_format((float)$priceObj->price, 2) }}</span>
                                <span class="text-xs font-bold text-slate-500 font-mono">{{ $selectedCurrency }}</span>
                                <span class="text-xs text-slate-400">/ {{ $plan->billing_cycle->label() }}</span>
                            </div>
                        @else
                            <div class="text-xs font-bold text-rose-600 py-2">
                                غير متوفرة بالعملة المحددة حالياً
                            </div>
                        @endif
                    </div>

                    <!-- Key Entitlements -->
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center gap-2 {{ $plan->allows_premium_campaigns ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">
                            <span>{{ $plan->allows_premium_campaigns ? '✓' : '✕' }}</span>
                            <span>التقديم على الحملات المميزة والحصرية</span>
                        </div>

                        @if($plan->max_premium_campaigns_per_month)
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="text-purple-600 font-black">•</span>
                                <span>حتى {{ $plan->max_premium_campaigns_per_month }} حملات مميزة شهرياً</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-emerald-700 font-bold">
                                <span>✓</span>
                                <span>عدد غير محدود من الحملات المميزة</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-slate-700">
                            <span class="text-purple-600 font-black">•</span>
                            <span>مدة الاشتراك: {{ $plan->duration_in_days }} يوماً</span>
                        </div>
                    </div>

                    <!-- Feature List -->
                    @if(!empty($plan->features))
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block">المزايا الإضافية:</span>
                            @foreach ($plan->features as $feature)
                                <div class="flex items-start gap-2 text-xs text-slate-600">
                                    <span class="text-purple-600 font-bold">✓</span>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- CTA Button -->
                <div class="pt-6">
                    @if($isCurrentPlan)
                        <div class="w-full py-3 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl text-center text-xs font-black">
                            باقتك الحالية النشطة ✓
                        </div>
                    @elseif($priceObj)
                        <a href="{{ route('marketer.subscriptions.subscribe', ['plan' => $plan->id, 'currency' => $selectedCurrency]) }}" class="block w-full py-3 {{ $plan->is_featured ? 'bg-purple-600 hover:bg-purple-700 text-white' : 'bg-slate-900 hover:bg-black text-white' }} rounded-2xl text-center text-xs sm:text-sm font-black shadow-md transition">
                            اشترك في الباقة الآن
                        </a>
                    @else
                        <button disabled class="w-full py-3 bg-slate-100 text-slate-400 rounded-2xl text-center text-xs font-bold cursor-not-allowed">
                            غير متاحة بعملة {{ $selectedCurrency }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 text-slate-500">
                لا توجد باقات معلنة حالياً. يرجى مراجعة إدارة المنصة.
            </div>
        @endforelse
    </div>
</div>
@endsection
