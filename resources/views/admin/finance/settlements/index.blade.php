@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>⚖️</span>
                <span>تدقيق تسويات العمولات • Commission Settlements Audit</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل تسويات العمولات المحاسبية</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                سجل القيود المحاسبية التلقائية المترتبة على اعتماد تحويلات المبيعات، وتحويل المستحقات من ضمان الحملة لمحفظة المسوق.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.finance.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← المركز المالي
            </a>
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                لوحة التحكم
            </a>
        </div>
    </div>

    <!-- Sub-navigation links -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80 flex-wrap">
        <a href="{{ route('admin.finance.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="{{ route('admin.finance.wallets') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">المحافظ</a>
        <a href="{{ route('admin.finance.transactions') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">سجل القيود</a>
        <a href="{{ route('admin.finance.trial-balance') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">ميزان المراجعة</a>
        <a href="{{ route('admin.finance.payouts.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">طلبات السحب</a>
        <a href="{{ route('admin.finance.settlements.index') }}" class="text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">تسويات العمولات ({{ $settlements->total() }})</a>
    </div>

    <!-- Currency Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">تصفية حسب العملة:</span>
            <a href="{{ route('admin.finance.settlements.index') }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ empty($filters['currency']) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                الكل
            </a>
            @foreach ($currencies as $curr)
                <a href="{{ route('admin.finance.settlements.index', ['currency' => $curr->value]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($filters['currency'] ?? '') === $curr->value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                    {{ $curr->value }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Settlements Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if ($settlements->isEmpty())
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد تسويات عمولات مسجلة في النظام حتى الآن.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الطلب الخارجي</th>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">العلامة التجارية (الدافع)</th>
                            <th class="p-4">المسوق (المستحق)</th>
                            <th class="p-4">قيمة العمولة</th>
                            <th class="p-4">قيد دفتر الأستاذ</th>
                            <th class="p-4">الحالة والتاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($settlements as $settlement)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    #{{ $settlement->conversion?->external_order_id ?? '—' }}
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    {{ $settlement->conversion?->campaign?->title ?? '—' }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ $settlement->brandWallet?->user?->name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">محفظة #{{ substr($settlement->brand_wallet_id, 0, 8) }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ $settlement->marketerWallet?->user?->name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">محفظة #{{ substr($settlement->marketer_wallet_id, 0, 8) }}</div>
                                </td>
                                <td class="p-4 font-black text-slate-900">
                                    {{ \App\Services\Math\MoneyMath::display($settlement->commission_amount, $settlement->currency->value) }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $settlement->currency->value }}</span>
                                </td>
                                <td class="p-4">
                                    <a href="{{ route('admin.finance.transactions.show', $settlement->ledger_transaction_id) }}"
                                       class="font-mono font-bold text-indigo-600 hover:text-indigo-800 underline text-xs">
                                        #{{ $settlement->ledgerTransaction?->transaction_number ?? substr($settlement->ledger_transaction_id, 0, 8) }}
                                    </a>
                                </td>
                                <td class="p-4">
                                    @if ($settlement->isReversed())
                                        <div>
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                قيد معكوس
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                            {{ $settlement->reversed_at?->format('Y-m-d H:i') }}
                                        </div>
                                    @else
                                        <div>
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                مرحل بنجاح
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                            {{ $settlement->settled_at->format('Y-m-d H:i') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
