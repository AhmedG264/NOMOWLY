@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 text-xs font-bold mb-3 border border-rose-400/30">
                <span>↩️</span>
                <span>الاستردادات المالية والقيود العكسية • Financial Refunds & Reversals</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل استرداد المدفوعات</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                توثيق القيود العكسية، أسباب الاسترداد، وحالات تنفيذها لدى بوابات الدفع الإلكترونية.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.finance.deposits.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                سجل الإيداعات
            </a>
            <a href="{{ route('admin.finance.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                المركز المالي
            </a>
        </div>
    </div>

    <!-- Navigation Bar -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="{{ route('admin.finance.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="{{ route('admin.finance.deposits.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">عمليات الإيداع والشحن</a>
        <a href="{{ route('admin.finance.refunds.index') }}" class="text-rose-700 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 font-black">الاستردادات المالية</a>
        <a href="{{ route('admin.finance.reconciliation.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المطابقة المالية مع المزودين</a>
        <a href="{{ route('admin.finance.wallets') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المحافظ</a>
        <a href="{{ route('admin.finance.transactions') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">دفتر القيود</a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.finance.refunds.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">بحث</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="رقم الاسترداد أو الإيداع..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">الحالة</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                    <option value="">جميع الحالات</option>
                    @foreach ($statuses as $st)
                        <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">العملة</label>
                <select name="currency" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                    <option value="">جميع العملات</option>
                    @foreach ($currencies as $curr)
                        <option value="{{ $curr->value }}" @selected(($filters['currency'] ?? '') === $curr->value)>
                            {{ $curr->value }} - {{ $curr->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition">
                    تصفية
                </button>
                @if(request()->anyFilled(['q', 'status', 'currency']))
                    <a href="{{ route('admin.finance.refunds.index') }}" class="p-2 text-rose-600 hover:text-rose-800 font-bold" title="إلغاء الفلاتر">✕</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Refunds Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900">سجل عمليات الاسترداد</h3>
            <span class="text-xs font-bold text-slate-500">إجمالي السجلات: {{ $refunds->total() }}</span>
        </div>

        @if ($refunds->isEmpty())
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد عمليات استرداد مسجلة حتى الآن.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الاسترداد</th>
                            <th class="p-4">عملية الإيداع الأصلية</th>
                            <th class="p-4">المستخدم</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">السبب</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">القيد العكسي</th>
                            <th class="p-4">تاريخ التنفيذ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($refunds as $ref)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">{{ $ref->refund_number }}</td>
                                <td class="p-4 font-mono">
                                    <a href="{{ route('admin.finance.deposits.show', $ref->deposit) }}" class="text-indigo-600 hover:text-indigo-800 font-bold underline">
                                        {{ $ref->deposit->deposit_number }}
                                    </a>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $ref->user->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $ref->user->email }}</div>
                                </td>
                                <td class="p-4 font-black text-rose-600">
                                    -{{ \App\Services\Math\MoneyMath::display($ref->amount, $ref->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $ref->currency->value }}</span>
                                </td>
                                <td class="p-4 text-slate-700 max-w-xs truncate" title="{{ $ref->reason }}">
                                    {{ $ref->reason }}
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ref->status->badgeClass() }}">
                                        {{ $ref->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    @if($ref->ledgerTransaction)
                                        <a href="{{ route('admin.finance.transactions.show', $ref->ledgerTransaction) }}" class="font-mono text-emerald-700 font-bold underline">
                                            {{ $ref->ledgerTransaction->transaction_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-slate-500">{{ $ref->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $refunds->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
