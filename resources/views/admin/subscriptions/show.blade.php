@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.subscriptions.index') }}" class="text-xs font-bold text-purple-700 hover:text-purple-900 transition flex items-center gap-1 mb-2">
                <span>←</span>
                <span>العودة لسجل الاشتراكات</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900">تفاصيل الاشتراك: {{ $subscription->subscription_number }}</h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $subscription->status->badgeClass() }}">
                    {{ $subscription->status->label() }}
                </span>
            </div>
            <p class="text-slate-500 text-xs sm:text-sm mt-0.5">المسوق: {{ $subscription->user->name }} ({{ $subscription->user->email }})</p>
        </div>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Subscription Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4 shadow-xs">
            <h2 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-2">بيانات الاشتراك والفترة</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">الخطة المشترك بها:</span>
                    <span class="font-bold text-slate-900">{{ $subscription->plan_snapshot['name_ar'] ?? $subscription->plan?->name_ar }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">المبلغ المدفوع:</span>
                    <span class="font-bold font-mono text-purple-900">{{ number_format((float)$subscription->price_paid, 2) }} {{ $subscription->currency->value }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">طريقة الدفع:</span>
                    <span class="font-bold">{{ $subscription->payment_method === 'WALLET' ? 'رصيد محفظة الأرباح' : 'بوابة الدفع الإلكتروني' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">تاريخ البدء:</span>
                    <span class="font-bold font-mono">{{ $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i') : 'غير محدد' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">تاريخ الانتهاء:</span>
                    <span class="font-bold font-mono">{{ $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'غير محدد' }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">الأيام المتبقية:</span>
                    <span class="font-bold font-mono {{ $subscription->daysRemaining() > 5 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $subscription->daysRemaining() }} يوماً</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4 shadow-xs">
            <h2 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-2">التوثيق المالي ودفتر الأستاذ</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">المرجع المالي / البوابة:</span>
                    <span class="font-mono font-bold">{{ $subscription->payment_reference ?: 'لا يوجد' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">معاملة دفتر الأستاذ:</span>
                    @if($subscription->ledgerTransaction)
                        <a href="{{ route('admin.finance.transactions.show', $subscription->ledgerTransaction->id) }}" class="font-mono font-bold text-purple-700 hover:underline">
                            {{ $subscription->ledgerTransaction->transaction_number }}
                        </a>
                    @else
                        <span class="text-slate-400">لا يوجد قيد مرحل</span>
                    @endif
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">الحملات المميزة:</span>
                    <span class="font-bold {{ $subscription->allowsPremiumCampaigns() ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $subscription->allowsPremiumCampaigns() ? 'مسموح التقديم' : 'غير مشمول' }}
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">الحد الشهري للحملات المميزة:</span>
                    <span class="font-bold">{{ $subscription->entitlement('max_premium_campaigns_per_month') ?: 'غير محدود' }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">مفتاح عدم التكرار (Idempotency):</span>
                    <span class="font-mono text-[10px] text-slate-500 truncate max-w-[200px]">{{ $subscription->idempotency_key }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Immutable Plan Snapshot -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-xs">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
            <span>النسخة الثابتة لشروط وسعر الباقة وقت الشراء (Immutable Snapshot)</span>
            <span class="text-xs font-mono font-normal text-slate-400">محمية من أي تعديل رجعي</span>
        </h2>

        <div class="bg-slate-900 rounded-2xl p-4 text-emerald-400 font-mono text-xs overflow-x-auto text-left" dir="ltr">
            <pre>{{ json_encode($subscription->plan_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <!-- Admin Moderation Actions -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-xs">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">الإجراءات الإدارية المتاحة</h2>

        <div class="flex flex-wrap items-center gap-4">
            @if($subscription->status === \App\Enums\SubscriptionStatus::ACTIVE)
                <button type="button" onclick="document.getElementById('suspend-modal').classList.remove('hidden')" class="px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition cursor-pointer">
                    تعليق الاشتراك إدارياً
                </button>

                <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription->id) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الاشتراك؟ لن يؤدي الإلغاء لإلغاء أي مشاركات أو عمولات تاريخية.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                        إلغاء الاشتراك
                    </button>
                </form>
            @elseif($subscription->status === \App\Enums\SubscriptionStatus::SUSPENDED)
                <div class="p-3 bg-red-50 text-red-800 rounded-xl text-xs">
                    <strong>سبب التعليق:</strong> {{ $subscription->suspension_reason }} (في {{ $subscription->suspended_at?->format('Y-m-d H:i') }})
                </div>
            @endif
        </div>
    </div>

    <!-- Suspend Modal -->
    <div id="suspend-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4 text-right">
            <h3 class="text-lg font-black text-slate-900">تعليق اشتراك المسوق</h3>
            <p class="text-xs text-slate-500">يجب ذكر سبب واضح وموثق في سجل التدقيق لتعليق هذا الاشتراك.</p>

            <form method="POST" action="{{ route('admin.subscriptions.suspend', $subscription->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">سبب التعليق الإداري *</label>
                    <textarea name="reason" rows="3" required placeholder="مثال: مخالفة سياسات الترويج أو الاشتباه بنشاط غير معتمد..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-purple-600 outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('suspend-modal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        إلغاء
                    </button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                        تأكيد التعليق
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
