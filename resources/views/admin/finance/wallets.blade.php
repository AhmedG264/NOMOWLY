@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>👛</span>
                <span>إدارة المحافظ متعددة العملات (Multi-Currency Wallets)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">قائمة محافظ المستخدمين</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                استعراض ومراقبة محافظ العلامات التجارية والمسوقين المنفصلة لكل عملة بشكل صارم.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.finance.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                المركز المالي
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            سجل العمليات والمراقبة
        </a>
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة المستخدمين
        </a>
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-xl text-sm font-black bg-emerald-600 text-white shadow-xs">
            المالية والمحافظ المحاسبية
        </a>
    </div>

    <!-- Sub-navigation links -->
    <div class="flex items-center gap-3 text-xs font-bold text-slate-600 bg-white p-3 rounded-2xl border border-slate-200/80">
        <a href="{{ route('admin.finance.index') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">نظرة عامة</a>
        <a href="{{ route('admin.finance.wallets') }}" class="text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">قائمة المحافظ ({{ $wallets->total() }})</a>
        <a href="{{ route('admin.finance.transactions') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">سجل القيود والمعاملات</a>
        <a href="{{ route('admin.finance.trial-balance') }}" class="hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-50">ميزان المراجعة والتطابق المحاسبي</a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.finance.wallets') }}" class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">البحث باسم المستخدم أو البريد:</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ابحث بالاسم أو البريد..."
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">العملة:</label>
                <select name="currency" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    <option value="">كافة العملات</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->value }}" {{ ($filters['currency'] ?? '') === $curr->value ? 'selected' : '' }}>
                            {{ $curr->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع المحفظة:</label>
                <select name="type" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    <option value="">كافة الأنواع</option>
                    @foreach($walletTypes as $type)
                        <option value="{{ $type->value }}" {{ ($filters['type'] ?? '') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">الحالة:</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    <option value="">كافة الحالات</option>
                    @foreach($walletStatuses as $status)
                        <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <a href="{{ route('admin.finance.wallets') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 px-3 py-2">
                إعادة ضبط
            </a>
            <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition">
                تطبيق التصفية
            </button>
        </div>
    </form>

    <!-- Wallets Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($wallets->isEmpty())
            <div class="p-12 text-center text-slate-500 text-xs">
                لا توجد محافظ مطابقة لمعايير البحث المحددة.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                        <tr>
                            <th class="p-4">مالك المحفظة</th>
                            <th class="p-4">النوع</th>
                            <th class="p-4">العملة</th>
                            <th class="p-4">الرصيد المتاح</th>
                            <th class="p-4">المحجوز (Escrow)</th>
                            <th class="p-4">إجمالي الرصيد</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($wallets as $wallet)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ $wallet->user?->name ?? 'محفظة وسيطة للنظام' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $wallet->user?->email }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                        {{ $wallet->wallet_type->label() }}
                                    </span>
                                </td>
                                <td class="p-4 font-black text-slate-800">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200">
                                        {{ $wallet->currency->value }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono font-bold text-emerald-700">
                                    {{ \App\Services\Math\MoneyMath::display($wallet->available_balance, $wallet->currency) }}
                                </td>
                                <td class="p-4 font-mono font-bold text-amber-600">
                                    {{ \App\Services\Math\MoneyMath::display($wallet->reserved_balance, $wallet->currency) }}
                                </td>
                                <td class="p-4 font-mono font-black text-slate-900">
                                    {{ \App\Services\Math\MoneyMath::display($wallet->total_balance, $wallet->currency) }}
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $wallet->status->badgeClass() }}">
                                        {{ $wallet->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="{{ route('admin.finance.wallets.show', $wallet) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-200">
                                        كشف الحساب والمطابقة ←
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $wallets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
