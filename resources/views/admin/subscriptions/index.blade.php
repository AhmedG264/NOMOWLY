@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8" dir="rtl">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-purple-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold mb-3 border border-purple-400/30">
                <span>📋</span>
                <span>سجل الاشتراكات الفعلي للمسوقين</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل اشتراكات المسوقين والمدفوعات</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                متابعة الاشتراكات النشطة والمنتهية، وفحص قيود المحاسبة ولقطات الشروط الثابتة وقت الشراء.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                إدارة الخطط
            </a>
            <a href="{{ route('admin.subscriptions.plans.create') }}" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition">
                + إنشاء خطة
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block">إجمالي الاشتراكات</span>
            <span class="text-xl font-black text-slate-900 mt-1 block font-mono">{{ $stats['total'] }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block">الاشتراكات النشطة</span>
            <span class="text-xl font-black text-emerald-600 mt-1 block font-mono">{{ $stats['active'] }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block">بانتظار السداد</span>
            <span class="text-xl font-black text-amber-600 mt-1 block font-mono">{{ $stats['pending'] }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block">المنتهية</span>
            <span class="text-xl font-black text-slate-600 mt-1 block font-mono">{{ $stats['expired'] }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-500 block">المعلقة إدارياً</span>
            <span class="text-xl font-black text-rose-600 mt-1 block font-mono">{{ $stats['suspended'] }}</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-600 mb-1">بحث برقم الاشتراك أو المسوق</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="SUB-... أو اسم المسوق" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-purple-600 outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">الحالة</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-purple-600 outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach (\App\Enums\SubscriptionStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">الخطة</label>
                <select name="plan_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-purple-600 outline-none">
                    <option value="">جميع الخطط</option>
                    @foreach ($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') === $p->id ? 'selected' : '' }}>
                            {{ $p->name_ar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 bg-purple-900 text-white rounded-xl font-black hover:bg-purple-950 transition cursor-pointer">
                    تصفية
                </button>
                <a href="{{ route('admin.subscriptions.index') }}" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition">
                    إعادة
                </a>
            </div>
        </form>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Subscriptions Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <th class="py-3.5 px-6">رقم الاشتراك</th>
                        <th class="py-3.5 px-4">المسوق</th>
                        <th class="py-3.5 px-4">الخطة</th>
                        <th class="py-3.5 px-4">المبلغ والعملة</th>
                        <th class="py-3.5 px-4">طريقة السداد</th>
                        <th class="py-3.5 px-4">فترة السريان</th>
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
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900 block">{{ $sub->user->name }}</span>
                                <span class="text-xs text-slate-400 block font-mono">{{ $sub->user->email }}</span>
                            </td>
                            <td class="py-4 px-4 font-bold">
                                {{ $sub->plan_snapshot['name_ar'] ?? $sub->plan?->name_ar }}
                            </td>
                            <td class="py-4 px-4 font-mono font-bold">
                                {{ number_format((float)$sub->price_paid, 2) }} {{ $sub->currency->value }}
                            </td>
                            <td class="py-4 px-4 text-xs font-bold">
                                @if($sub->payment_method === 'WALLET')
                                    <span class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200">
                                        محفظة الأرباح
                                    </span>
                                @else
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        بوابة الدفع
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-xs">
                                @if($sub->starts_at && $sub->ends_at)
                                    <span class="font-mono text-slate-600 block">{{ $sub->starts_at->format('Y-m-d') }}</span>
                                    <span class="font-mono text-slate-400 block">إلى {{ $sub->ends_at->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-slate-400">غير مفعل</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $sub->status->badgeClass() }}">
                                    {{ $sub->status->label() }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-left">
                                <a href="{{ route('admin.subscriptions.show', $sub->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition">
                                    فحص
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-500">
                                لا توجد اشتراكات مطابقة لمعايير البحث الحالية.
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
