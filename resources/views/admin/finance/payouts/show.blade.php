@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-8" x-data="{ rejectModalOpen: false, markPaidModalOpen: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>💸</span>
                <span>تفاصيل طلب سحب الأرباح رقم [{{ $payoutRequest->request_number }}]</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">معاينة طلب السحب المصرفي</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                المسوق: <span class="text-white font-bold">{{ $payoutRequest->user->name }}</span> ({{ $payoutRequest->user->email }})
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.finance.payouts.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة لقائمة الطلبات
            </a>
            <a href="{{ route('admin.finance.index') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                المركز المالي
            </a>
        </div>
    </div>

    <!-- Status Alerts -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="flex items-center gap-2">
                <span class="text-rose-600 font-black">✕</span>
                <span>تعذر إتمام الإجراء:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-medium text-rose-700">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Self Approval Prevention Warning -->
    @if (auth()->id() === $payoutRequest->user_id)
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-800 font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>قاعدة الفصل بين المهام (Separation of Duties): هذا الطلب يخص حسابك الشخصي؛ لا يمكنك اعتماده أو تأكيد صرفه ذاتياً، ويجب أن يتم التدقيق بواسطة مسؤول إداري آخر.</span>
        </div>
    @endif

    <!-- Main Detail Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Request Status Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="text-xs font-bold text-slate-500">حالة الطلب الحالية</div>
            <div>
                <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-black border {{ $payoutRequest->status->badgeClass() }}">
                    {{ $payoutRequest->status->label() }}
                </span>
            </div>
            <div class="text-xs text-slate-500 space-y-1 pt-3 border-t border-slate-100">
                <div>تاريخ التقديم: <span class="font-mono font-bold text-slate-800">{{ $payoutRequest->created_at->format('Y-m-d H:i') }}</span></div>
                @if ($payoutRequest->reviewed_at)
                    <div>تاريخ المراجعة: <span class="font-mono font-bold text-slate-800">{{ $payoutRequest->reviewed_at->format('Y-m-d H:i') }}</span></div>
                    <div>المراجع: <span class="font-bold text-slate-800">{{ $payoutRequest->reviewer?->name ?? 'مسؤول النظام' }}</span></div>
                @endif
                @if ($payoutRequest->paid_at)
                    <div>تاريخ الصرف: <span class="font-mono font-bold text-slate-800">{{ $payoutRequest->paid_at->format('Y-m-d H:i') }}</span></div>
                    <div>المنفذ: <span class="font-bold text-slate-800">{{ $payoutRequest->payer?->name ?? 'مسؤول النظام' }}</span></div>
                @endif
            </div>
        </div>

        <!-- Financial Amount Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="text-xs font-bold text-slate-500">المبلغ المطلوب سحبه</div>
            <div class="text-3xl font-black text-slate-900 tracking-tight">
                {{ \App\Services\Math\MoneyMath::display($payoutRequest->amount, $payoutRequest->currency->value) }}
                <span class="text-sm font-bold text-slate-400">{{ $payoutRequest->currency->value }}</span>
            </div>
            <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 space-y-1">
                <div>حالة المحفظة: <span class="font-bold text-emerald-600">نشطة ({{ $payoutRequest->wallet->status->value }})</span></div>
                <div>الرصيد المتاح حالياً: <span class="font-mono font-bold text-slate-800">{{ \App\Services\Math\MoneyMath::display($payoutRequest->wallet->available_balance, $payoutRequest->currency->value) }}</span></div>
                <div>الرصيد المحجوز حالياً: <span class="font-mono font-bold text-amber-600">{{ \App\Services\Math\MoneyMath::display($payoutRequest->wallet->reserved_balance, $payoutRequest->currency->value) }}</span></div>
            </div>
        </div>

        <!-- Bank Details Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="text-xs font-bold text-slate-500">بيانات التحويل المصرفي</div>
            <div class="space-y-1.5 text-xs text-slate-700">
                <div>البنك: <span class="font-bold text-slate-900">{{ $payoutRequest->bank_name ?? '—' }}</span></div>
                <div>المستفيد: <span class="font-bold text-slate-900">{{ $payoutRequest->account_holder_name ?? '—' }}</span></div>
                <div>الحساب / الآيبان المظلل:</div>
                <div class="font-mono font-bold text-slate-900 bg-slate-50 p-2 rounded-xl text-center dir-ltr border border-slate-200/60">
                    {{ $payoutRequest->account_reference ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection or Payment Notes if available -->
    @if ($payoutRequest->rejection_reason)
        <div class="bg-rose-50 border border-rose-200 rounded-3xl p-6 text-xs text-rose-900 space-y-2">
            <div class="flex items-center gap-2 font-black text-sm">
                <span>✕</span>
                <span>سبب رفض الطلب الموثق:</span>
            </div>
            <p class="font-medium text-slate-800 bg-white p-3 rounded-xl border border-rose-100">{{ $payoutRequest->rejection_reason }}</p>
            <p class="text-[11px] text-slate-500">تمت إعادة كامل المبلغ المحجوز فورياً إلى رصيد المسوق المتاح في المحفظة، وتوثيق العملية في سجل التدقيق.</p>
        </div>
    @endif

    @if ($payoutRequest->payment_reference)
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-xs text-emerald-900 space-y-2">
            <div class="flex items-center gap-2 font-black text-sm">
                <span>✓</span>
                <span>بيانات التحويل المصرفي المنفذ:</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white p-4 rounded-2xl border border-emerald-100">
                <div>
                    <span class="text-slate-500 block">رقم الحوالة البنكية (Reference):</span>
                    <span class="font-mono font-black text-slate-900 text-sm">{{ $payoutRequest->payment_reference }}</span>
                </div>
                @if ($payoutRequest->payout_ledger_transaction_id)
                    <div>
                        <span class="text-slate-500 block">معاملة دفتر الأستاذ المحاسبي:</span>
                        <a href="{{ route('admin.finance.transactions.show', $payoutRequest->payout_ledger_transaction_id) }}"
                           class="font-mono font-bold text-indigo-600 hover:text-indigo-800 underline text-xs">
                            عرض القيد المزدوج (#{{ $payoutRequest->payoutTransaction?->transaction_number ?? 'قيد الدفتر' }}) ←
                        </a>
                    </div>
                @endif
            </div>
            @if ($payoutRequest->admin_notes)
                <div class="mt-2 text-slate-600">
                    <span class="font-bold">ملاحظات الإدارة:</span> {{ $payoutRequest->admin_notes }}
                </div>
            @endif
        </div>
    @endif

    <!-- Workflow Actions for Admin -->
    @if (auth()->id() !== $payoutRequest->user_id)
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-black text-slate-900">إجراءات المراجعة والاعتماد</h3>
                <p class="text-xs text-slate-500">اختر الإجراء المناسب بناءً على تدقيق الحساب المصرفي وصحة الأرباح</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                @if ($payoutRequest->status === \App\Enums\PayoutRequestStatus::REQUESTED)
                    <!-- Approve Button -->
                    <form method="POST" action="{{ route('admin.finance.payouts.approve', $payoutRequest) }}">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black shadow-md transition cursor-pointer flex items-center gap-1.5">
                            <span>✓</span>
                            <span>اعتماد الطلب للصرف</span>
                        </button>
                    </form>

                    <!-- Reject Button triggers modal -->
                    <button type="button" @click="rejectModalOpen = true"
                            class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                        رفض الطلب
                    </button>
                @elseif ($payoutRequest->status === \App\Enums\PayoutRequestStatus::APPROVED)
                    <!-- Mark as Paid Button triggers modal -->
                    <button type="button" @click="markPaidModalOpen = true"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md transition cursor-pointer flex items-center gap-1.5">
                        <span>💰</span>
                        <span>تأكيد الصرف المصرفي وترحيل القيد</span>
                    </button>

                    <!-- Reject Button triggers modal -->
                    <button type="button" @click="rejectModalOpen = true"
                            class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                        إلغاء ورفض الطلب
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Reject Modal -->
    <div x-show="rejectModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="rejectModalOpen = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-right space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-rose-700">رفض طلب سحب الأرباح</h3>
                <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('admin.finance.payouts.reject', $payoutRequest) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">سبب الرفض الموثق للمسوق *</label>
                    <textarea name="reason" required rows="3" placeholder="مثال: رقم الآيبان غير مطابق لاسم المستفيد، أو تعذر إتمام التحويل من قبل البنك."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">تنبيه: سيتم إلغاء حجز المبلغ وإعادته فوراً لرصيد المسوق المتاح.</p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="rejectModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-md cursor-pointer">
                        تأكيد رفض الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mark as Paid Modal -->
    <div x-show="markPaidModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="markPaidModalOpen = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-right space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-emerald-700">تأكيد تحويل وصرف الأرباح</h3>
                <button type="button" @click="markPaidModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('admin.finance.payouts.mark-paid', $payoutRequest) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الرقم المرجعي للتحويل البنكي (Bank Transfer Ref) *</label>
                    <input type="text" name="payment_reference" required placeholder="مثال: REF-TRX-20261018-9842"
                           class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">الرقم الصادر من البنك لإثبات التحويل المصرفي للمسوق.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات إضافية (اختياري)</label>
                    <textarea name="notes" rows="2" placeholder="أي تفاصيل أو ملاحظات مصرفية تخص العملية..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-2xl text-[11px] text-emerald-800 space-y-1">
                    <p class="font-bold">الأثر المحاسبي التلقائي:</p>
                    <p>سيتم ترحيل قيد مزدوج فوري في دفتر الأستاذ:</p>
                    <ul class="list-disc list-inside font-mono text-[10px] space-y-0.5">
                        <li>مدين: 2030_MARKETER_PAYABLE_LIABILITY (إنهاء التزام الأرباح)</li>
                        <li>دائن: 1010_PLATFORM_CASH (خصم النقدية المنصرفة)</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="markPaidModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md cursor-pointer">
                        تأكيد الصرف وترحيل القيد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
