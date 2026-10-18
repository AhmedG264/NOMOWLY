@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" x-data="{ refundModalOpen: false }">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>💳</span>
                <span>تفاصيل عملية الإيداع والشحن</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">معاملة إيداع رقم [{{ $deposit->deposit_number }}]</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                المستخدم: <span class="text-white font-bold">{{ $deposit->user->name }}</span>
                • العملة: <span class="text-emerald-400 font-bold">{{ $deposit->currency->value }}</span>
                • الحالة: <span class="font-bold">{{ $deposit->status->label() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.finance.deposits.reconcile', $deposit) }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-1.5 cursor-pointer">
                    <span>⚖️</span>
                    <span>فحص ومطابقة المزود</span>
                </button>
            </form>
            @if(in_array($deposit->status->value, ['SUCCEEDED', 'PARTIALLY_REFUNDED']) && \App\Services\Math\MoneyMath::isPositive($deposit->remainingRefundableAmount()))
                <button type="button" @click="refundModalOpen = true"
                        class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-1.5 cursor-pointer">
                    <span>↩️</span>
                    <span>إجراء استرداد مالي</span>
                </button>
            @endif
            <a href="{{ route('admin.finance.deposits.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للإيداعات
            </a>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="md:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h3 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">بيانات العملية المالية</h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">رقم الإيداع</span>
                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $deposit->deposit_number }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">المبلغ المدفوع</span>
                    <span class="font-black text-slate-900 text-sm">
                        {{ \App\Services\Math\MoneyMath::display($deposit->amount, $deposit->currency->value) }}
                        {{ $deposit->currency->value }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">الصافي المضاف للمحفظة</span>
                    <span class="font-black text-emerald-700 text-sm">
                        {{ \App\Services\Math\MoneyMath::display($deposit->net_amount, $deposit->currency->value) }}
                        {{ $deposit->currency->value }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">رسوم بوابة الدفع</span>
                    <span class="font-mono font-bold text-slate-600">
                        {{ \App\Services\Math\MoneyMath::display($deposit->fee_amount, $deposit->currency->value) }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">مزود الدفع</span>
                    <span class="font-bold text-slate-800">{{ $deposit->provider->label() }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">حالة العملية</span>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $deposit->status->badgeClass() }}">
                        {{ $deposit->status->label() }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">معرف المعاملة لدى المزود</span>
                    <span class="font-mono text-slate-700 truncate block max-w-xs">{{ $deposit->provider_transaction_id ?: '—' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">تاريخ الإنشاء</span>
                    <span class="font-mono text-slate-600">{{ $deposit->created_at->format('Y-m-d H:i:s') }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">تاريخ التأكيد والدفع</span>
                    <span class="font-mono text-slate-600">{{ $deposit->paid_at ? $deposit->paid_at->format('Y-m-d H:i:s') : '—' }}</span>
                </div>
            </div>

            @if($deposit->failure_reason)
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-800 space-y-1">
                    <span class="font-bold">سبب الفشل:</span>
                    <p>{{ $deposit->failure_reason }}</p>
                </div>
            @endif
        </div>

        <!-- User / Wallet Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div>
                <h3 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">حساب العميل والمحفظة</h3>
                <div class="space-y-3 mt-4 text-xs">
                    <div>
                        <span class="text-slate-400 block">اسم صاحب الحساب</span>
                        <span class="font-bold text-slate-900">{{ $deposit->user->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">البريد الإلكتروني</span>
                        <span class="font-mono text-slate-700">{{ $deposit->user->email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">العلامة التجارية</span>
                        <span class="font-bold text-slate-800">{{ $deposit->user->brandProfile?->company_name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">رصيد المحفظة الحالي المتاح</span>
                        <span class="font-black text-emerald-700 text-sm">
                            {{ \App\Services\Math\MoneyMath::display($deposit->wallet->available_balance, $deposit->currency->value) }}
                            {{ $deposit->currency->value }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">الرصيد القابل للاسترداد من هذه الدفعة</span>
                        <span class="font-mono font-bold text-indigo-700">
                            {{ \App\Services\Math\MoneyMath::display($deposit->remainingRefundableAmount(), $deposit->currency->value) }}
                            {{ $deposit->currency->value }}
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <a href="{{ route('admin.finance.wallets.show', $deposit->wallet) }}" class="block text-center py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                    عرض محفظة العميل ←
                </a>
            </div>
        </div>
    </div>

    <!-- Linked Double-Entry Ledger Transaction -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900">القيد المحاسبي المزدوج في دفتر الأستاذ</h3>
                <p class="text-xs text-slate-500 mt-0.5">القيد المرحل آلياً لتأكيد إيداع النقدية وزيادة التزامات محفظة المتجر</p>
            </div>
            @if ($deposit->ledgerTransaction)
                <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 text-xs">
                    {{ $deposit->ledgerTransaction->transaction_number }}
                </span>
            @endif
        </div>

        @if (!$deposit->ledgerTransaction)
            <div class="p-8 text-center text-slate-400 text-xs">
                لا يوجد قيد محاسبي مرحل (العملية لم تكتمل أو قيد الانتظار).
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">حساب دفتر الأستاذ</th>
                            <th class="p-4">طبيعة القيد</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">المحفظة المستهدفة</th>
                            <th class="p-4">البيان</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($deposit->ledgerTransaction->entries as $entry)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4">
                                    <div class="font-mono font-bold text-slate-900">{{ $entry->account->code }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $entry->account->name_ar }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $entry->entry_type->value === 'DEBIT' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                                        {{ $entry->entry_type->label() }}
                                    </span>
                                </td>
                                <td class="p-4 font-black {{ $entry->entry_type->value === 'DEBIT' ? 'text-amber-700' : 'text-blue-700' }}">
                                    {{ \App\Services\Math\MoneyMath::display($entry->amount, $entry->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $entry->currency->value }}</span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-600">
                                    {{ $entry->wallet_id ? substr($entry->wallet_id, 0, 8) . '...' : 'حساب أصول عام' }}
                                </td>
                                <td class="p-4 text-slate-600">
                                    {{ $entry->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Refunds on this Deposit -->
    @if($deposit->refunds->isNotEmpty())
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900">سجل الاستردادات المرتبطة بهذه الدفعة</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الاسترداد</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">السبب</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">المنفذ</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($deposit->refunds as $ref)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">{{ $ref->refund_number }}</td>
                                <td class="p-4 font-black text-rose-600">
                                    -{{ \App\Services\Math\MoneyMath::display($ref->amount, $ref->currency->value) }}
                                    {{ $ref->currency->value }}
                                </td>
                                <td class="p-4 text-slate-700">{{ $ref->reason }}</td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ref->status->badgeClass() }}">
                                        {{ $ref->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-600">{{ $ref->creator?->name ?? 'النظام' }}</td>
                                <td class="p-4 font-mono text-slate-500">{{ $ref->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Refund Modal -->
    <div x-show="refundModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="refundModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-right space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-black">↩️</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900">استرداد مالي (كامل أو جزئي)</h3>
                        <p class="text-xs text-slate-500">عملية الإيداع: {{ $deposit->deposit_number }}</p>
                    </div>
                </div>
                <button type="button" @click="refundModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.finance.deposits.refund', $deposit) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        المبلغ المراد استرداده ({{ $deposit->currency->value }}) *
                    </label>
                    <input type="number" step="any" min="0.01" max="{{ $deposit->remainingRefundableAmount() }}"
                           name="amount" value="{{ $deposit->remainingRefundableAmount() }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">
                        الحد الأقصى القابل للاسترداد حالياً: <span class="font-bold text-slate-800">{{ $deposit->remainingRefundableAmount() }} {{ $deposit->currency->value }}</span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">سبب الاسترداد لتوثيق القيد الدفتري *</label>
                    <textarea name="reason" rows="3" required placeholder="اذكر سبب الاسترداد الإداري أو البنكي..."
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-[11px] text-amber-800 space-y-1">
                    <p class="font-bold">⚠️ تنبيه محاسبي ملزم:</p>
                    <p>سيتم خصم المبلغ فوراً من الرصيد المتاح بمحفظة المتجر، وترحيل قيد عكسي مالي في دفتر الأستاذ العام وتأكيده لدى مزود الدفع.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="refundModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                        تأكيد وترحيل الاسترداد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
