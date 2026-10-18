@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>💳</span>
                <span>إدارة المدفوعات والإيداعات البنكية • Payment Gateway Foundation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل عمليات شحن المحافظ والمدفوعات الإلكترونية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                متابعة عمليات الإيداع، حالات بوابات الدفع، القيود المحاسبية المرحلة، والاستردادات المالية.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.finance.reconciliation.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-1.5">
                <span>⚖️</span>
                <span>تقرير المطابقة المالية</span>
            </a>
            <a href="{{ route('admin.finance.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                المركز المالي
            </a>
        </div>
    </div>

    <!-- Navigation Bar -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="{{ route('admin.finance.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="{{ route('admin.finance.deposits.index') }}" class="text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-200 font-black">عمليات الإيداع والشحن</a>
        <a href="{{ route('admin.finance.refunds.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">الاستردادات المالية</a>
        <a href="{{ route('admin.finance.reconciliation.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المطابقة المالية مع المزودين</a>
        <a href="{{ route('admin.finance.wallets') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المحافظ</a>
        <a href="{{ route('admin.finance.transactions') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">دفتر القيود</a>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-slate-500">العمليات الناجحة والمكتملة</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($totalSucceeded) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">مرحلة ومودعة بالمحافظ</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-slate-500">عمليات قيد المعالجة / الانتظار</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($totalPending) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">بانتظار إشعار المزود</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-slate-500">عمليات فاشلة أو ملغاة</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ number_format($totalFailed) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">مرفوضة من البنك</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs font-bold text-slate-500">عمليات مستردة (كلي أو جزئي)</div>
            <div class="text-2xl font-black text-purple-600 mt-1">{{ number_format($totalRefunded) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">تم عكس قيودها المحاسبية</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <form method="GET" action="{{ route('admin.finance.deposits.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">بحث</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="رقم الإيداع أو المعاملة أو البريد..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">الحالة</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
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
                <select name="currency" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">جميع العملات</option>
                    @foreach ($currencies as $curr)
                        <option value="{{ $curr->value }}" @selected(($filters['currency'] ?? '') === $curr->value)>
                            {{ $curr->value }} - {{ $curr->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">مزود الدفع</label>
                <select name="provider" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">جميع المزودين</option>
                    @foreach ($providers as $prov)
                        <option value="{{ $prov->value }}" @selected(($filters['provider'] ?? '') === $prov->value)>
                            {{ $prov->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">من تاريخ</label>
                <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold transition">
                    تصفية
                </button>
                @if(request()->anyFilled(['q', 'status', 'currency', 'provider', 'from_date', 'to_date']))
                    <a href="{{ route('admin.finance.deposits.index') }}" class="p-2 text-rose-600 hover:text-rose-800 font-bold" title="إلغاء الفلاتر">✕</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Deposits Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900">سجل عمليات الدفع والإيداع</h3>
            <span class="text-xs font-bold text-slate-500">العدد الإجمالي: {{ $deposits->total() }} عملية</span>
        </div>

        @if ($deposits->isEmpty())
            <div class="p-12 text-center text-slate-400 text-xs">
                لا توجد عمليات إيداع مسجلة مطابقة لمعايير البحث.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الإيداع المرجعي</th>
                            <th class="p-4">المستخدم / المتجر</th>
                            <th class="p-4">المبلغ والعملة</th>
                            <th class="p-4">الرسوم والصافي</th>
                            <th class="p-4">المزود وطريقة الدفع</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">القيد المحاسبي</th>
                            <th class="p-4">التاريخ</th>
                            <th class="p-4 text-center">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($deposits as $dep)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    {{ $dep->deposit_number }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $dep->user->name }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[140px]">{{ $dep->user->email }}</div>
                                </td>
                                <td class="p-4 font-bold text-slate-900">
                                    {{ \App\Services\Math\MoneyMath::display($dep->amount, $dep->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $dep->currency->value }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="font-black text-emerald-700">
                                        {{ \App\Services\Math\MoneyMath::display($dep->net_amount, $dep->currency->value) }}
                                        <span class="text-[10px] font-normal text-slate-400">صافي</span>
                                    </div>
                                    @if(\App\Services\Math\MoneyMath::isPositive($dep->fee_amount))
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            رسوم: {{ \App\Services\Math\MoneyMath::display($dep->fee_amount, $dep->currency->value) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $dep->provider->label() }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $dep->payment_method ?? 'card' }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $dep->status->badgeClass() }}">
                                        {{ $dep->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    @if ($dep->ledgerTransaction)
                                        <a href="{{ route('admin.finance.transactions.show', $dep->ledgerTransaction) }}" class="font-mono text-emerald-700 hover:text-emerald-900 font-bold underline">
                                            {{ $dep->ledgerTransaction->transaction_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $dep->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-4 text-center">
                                    <a href="{{ route('admin.finance.deposits.show', $dep) }}" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold text-[11px] transition inline-block">
                                        عرض التفاصيل
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $deposits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
