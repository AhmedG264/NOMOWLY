@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div>
        <a href="{{ route('marketer.plans.index', ['currency' => $selectedCurrency]) }}" class="text-xs font-bold text-purple-700 hover:text-purple-900 transition flex items-center gap-1 mb-2">
            <span>←</span>
            <span>العودة لمقارنة الباقات</span>
        </a>
        <h1 class="text-2xl font-black text-slate-900">إتمام الاشتراك في باقة: {{ $plan->name_ar }}</h1>
        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">اختر وسيلة الدفع المناسبة لتفعيل باقتك فوراً وبدء الترويج للحملات المميزة.</p>
    </div>

    <!-- Error Alert -->
    @if (session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold flex items-center gap-2">
            <span class="text-rose-600 font-black">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('marketer.subscriptions.process-payment') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
        <input type="hidden" name="currency" value="{{ $selectedCurrency }}">

        <!-- Order Summary Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-xs">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">ملخص طلب الاشتراك</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500 text-xs">اسم الباقة:</span>
                    <span class="font-black text-slate-900">{{ $plan->name_ar }}</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500 text-xs">مدة الاشتراك:</span>
                    <span class="font-bold text-slate-700">{{ $plan->duration_in_days }} يوماً ({{ $plan->billing_cycle->label() }})</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500 text-xs">الوصول للحملات المميزة:</span>
                    <span class="font-bold text-emerald-700">مشمول ومفعل ✓</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                    <span class="text-xs font-black text-slate-900">الإجمالي المستحق للسداد:</span>
                    <div class="text-left font-mono">
                        <span class="text-2xl font-black text-purple-900">{{ number_format((float)$price->price, 2) }}</span>
                        <span class="text-xs font-bold text-slate-500">{{ $selectedCurrency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-xs">
            <div>
                <h2 class="text-base font-black text-slate-900">اختر وسيلة الدفع</h2>
                <p class="text-xs text-slate-500 mt-0.5">يمكنك السداد عبر البطاقات البنكية أو مباشرة من رصيد أرباحك التسويقية المتاحة.</p>
            </div>

            <div class="space-y-3 pt-2">
                <!-- Option 1: Payment Gateway -->
                <label class="flex items-start gap-3 p-4 rounded-2xl border border-slate-200 cursor-pointer hover:border-purple-600 hover:bg-purple-50/20 transition">
                    <input type="radio" name="payment_method" value="GATEWAY" checked class="mt-1 w-4 h-4 text-purple-600">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-black text-slate-900">بوابة الدفع الإلكتروني المباشرة</span>
                            <span class="text-xs font-bold text-slate-400">مدى / فيزا / ماستركارد / Apple Pay</span>
                        </div>
                        <span class="text-xs text-slate-500 mt-1 block">سداد آمن وفوري مع تفعيل لحظي للباقة.</span>
                    </div>
                </label>

                <!-- Option 2: Wallet Balance -->
                <label class="flex items-start gap-3 p-4 rounded-2xl border border-slate-200 {{ $canPayFromWallet ? 'cursor-pointer hover:border-purple-600 hover:bg-purple-50/20' : 'opacity-60 bg-slate-50 cursor-not-allowed' }} transition">
                    <input type="radio" name="payment_method" value="WALLET" {{ $canPayFromWallet ? '' : 'disabled' }} class="mt-1 w-4 h-4 text-purple-600">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-black text-slate-900">خصم من رصيد أرباحي المتاح بالعملة نفسها</span>
                            <span class="text-xs font-mono font-bold {{ $canPayFromWallet ? 'text-emerald-700' : 'text-rose-600' }}">
                                الرصيد المتاح: {{ number_format((float)$walletBalance, 2) }} {{ $selectedCurrency }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-500 mt-1 block">
                            @if($canPayFromWallet)
                                رصيدك كافٍ لإتمام السداد الفوري من المحفظة دون الحاجة لبطاقة بنكية.
                            @else
                                رصيدك المتاح غير كافٍ لسداد هذا المبلغ. يمكنك استخدام بوابة الدفع.
                            @endif
                        </span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Terms and Confirmation -->
        <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200/80 text-xs text-slate-600 space-y-1">
            <span class="font-bold block text-slate-800">سياسة الاشتراكات والحماية التعاقدية:</span>
            <p>عند تأكيد السداد، سيتم تفعيل الباقة فوراً وحفظ نسخة ثابتة غير قابلة للتغيير من شروط وسعر الباقة لحمايتك. انتهاء الاشتراك لاحقاً لا يؤثر على عمولاتك المستحقة أو مشاركاتك القائمة.</p>
        </div>

        <button type="submit" class="w-full py-4 bg-purple-900 hover:bg-purple-950 text-white rounded-2xl text-sm font-black shadow-lg transition cursor-pointer">
            تأكيد السداد والاشتراك الفوري
        </button>
    </form>
</div>
@endsection
