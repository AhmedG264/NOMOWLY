@extends('layouts.app')

@section('title', 'تدقيق التحويلات ومكافحة الاحتيال - الإدارة العامة')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-purple-700 bg-purple-50 px-3 py-1 rounded-full w-fit mb-2">
                <span>🛡️</span>
                <span>المرحلة 9: سلامة الإسناد والعمولات واكتشاف الاحتيال</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">تدقيق التحويلات ومكافحة الاحتيال</h1>
            <p class="text-sm text-slate-500 mt-1">مراقبة دقة الإسناد، رصد المؤشرات المشبوهة، والتحقق الأمني من العمولات قبل الصرف النهائي</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.conversions.rules') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>إعدادات قواعد كشف الاحتيال</span>
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap text-sm">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.conversions.index') }}" class="px-4 py-2 rounded-xl font-black bg-purple-900 text-white shadow-xs flex items-center gap-1.5">
            <span>🔍</span>
            <span>التحويلات ومكافحة الاحتيال</span>
        </a>
        <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>⭐</span>
            <span>خطط الاشتراكات</span>
        </a>
        <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            اشتراكات المسوقين
        </a>
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الإدارة المالية
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-1.5">
            <span>🛡️</span>
            <span>سجل العمليات</span>
        </a>
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-bold block">إجمالي التحويلات</span>
            <span class="text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['total']) }}</span>
            <span class="text-[11px] text-slate-400 mt-0.5 block">جميع العمليات</span>
        </div>

        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-200 shadow-xs">
            <span class="text-xs text-amber-700 font-bold block">قيد التحقق (Pending)</span>
            <span class="text-2xl font-black text-amber-900 mt-1 block">{{ number_format($stats['pending']) }}</span>
            <span class="text-[11px] text-amber-600 mt-0.5 block">بانتظار الاعتماد</span>
        </div>

        <div class="bg-rose-50 rounded-2xl p-4 border border-rose-300 shadow-xs relative overflow-hidden">
            <span class="text-xs text-rose-700 font-bold block flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                <span>تحويلات مشبوهة</span>
            </span>
            <span class="text-2xl font-black text-rose-900 mt-1 block">{{ number_format($stats['suspicious']) }}</span>
            <span class="text-[11px] text-rose-600 mt-0.5 block">إشارات اشتباه نشطة</span>
        </div>

        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200 shadow-xs">
            <span class="text-xs text-emerald-700 font-bold block">معتمدة ومرحلة</span>
            <span class="text-2xl font-black text-emerald-900 mt-1 block">{{ number_format($stats['approved']) }}</span>
            <span class="text-[11px] text-emerald-600 mt-0.5 block">مسواة بالدفتر</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-bold block">مبيعات محققة (GMV)</span>
            <span class="text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['total_order_value'], 2) }}</span>
            <span class="text-[11px] text-slate-400 mt-0.5 block">ريال سعودي</span>
        </div>

        <div class="bg-purple-50 rounded-2xl p-4 border border-purple-200 shadow-xs">
            <span class="text-xs text-purple-700 font-bold block">عمولات معتمدة</span>
            <span class="text-2xl font-black text-purple-900 mt-1 block">{{ number_format($stats['total_commission'], 2) }}</span>
            <span class="text-[11px] text-purple-600 mt-0.5 block">مستحقة للمسوقين</span>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.conversions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">بحث برقم الطلب أو الزائر</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="رقم الطلب # أو معرف الزائر..." class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">الحملة التسويقية</label>
                <select name="campaign_id" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden">
                    <option value="">جميع الحملات</option>
                    @foreach($campaigns as $camp)
                        <option value="{{ $camp->id }}" @selected(request('campaign_id') == $camp->id)>{{ $camp->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">حالة التحويل</label>
                <select name="status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden">
                    <option value="">جميع الحالات</option>
                    @foreach(\App\Enums\ConversionStatus::cases() as $st)
                        <option value="{{ $st->value }}" @selected(request('status') == $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">مستوى الخطورة ومكافحة الاحتيال</label>
                <select name="risk_level" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden">
                    <option value="">كافة درجات الخطورة</option>
                    <option value="high" @selected(request('risk_level') == 'high')>🔴 خطورة مرتفعة (60+)</option>
                    <option value="medium" @selected(request('risk_level') == 'medium')>🟡 خطورة متوسطة (30-59)</option>
                    <option value="low" @selected(request('risk_level') == 'low')>🟢 طبيعي ومنخفض (&lt;30)</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2.5 px-4 bg-purple-900 text-white rounded-xl text-xs font-black hover:bg-purple-800 transition cursor-pointer">
                    تصفية النتائج
                </button>
                @if(request()->hasAny(['search', 'campaign_id', 'status', 'risk_level', 'suspicious_only']))
                    <a href="{{ route('admin.conversions.index') }}" class="py-2.5 px-3 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-xs font-bold transition whitespace-nowrap">
                        إعادة ضبط
                    </a>
                @endif
            </div>
        </form>

        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.conversions.index', array_merge(request()->query(), ['suspicious_only' => request('suspicious_only') ? null : '1'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border font-bold transition {{ request('suspicious_only') ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    <span>🚨</span>
                    <span>عرض التحويلات المشبوهة فقط ({{ $stats['suspicious'] }})</span>
                </a>
            </div>
            <span class="text-slate-400">عرض {{ $conversions->count() }} من أصل {{ $conversions->total() }} تحويل مسجل</span>
        </div>
    </div>

    <!-- Conversions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">رقم الطلب الخارجي</th>
                        <th class="py-3.5 px-4">الحملة والعلامة التجارية</th>
                        <th class="py-3.5 px-4">المسوق ورمز التتبع</th>
                        <th class="py-3.5 px-4">قيمة الطلب</th>
                        <th class="py-3.5 px-4">العمولة</th>
                        <th class="py-3.5 px-4">مؤشر المخاطر</th>
                        <th class="py-3.5 px-4">الحالة</th>
                        <th class="py-3.5 px-4">تاريخ الحدث</th>
                        <th class="py-3.5 px-4 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($conversions as $conv)
                        <tr class="hover:bg-slate-50/60 transition {{ $conv->is_suspicious ? 'bg-rose-50/20' : '' }}">
                            <td class="py-3.5 px-4">
                                <div class="font-mono font-bold text-slate-900">#{{ $conv->external_order_id }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ Str::limit($conv->id, 8) }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $conv->campaign?->title ?? '—' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $conv->campaign?->brandProfile?->company_name ?? '—' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($conv->campaignApplication)
                                    <div class="font-bold text-slate-900">{{ $conv->campaignApplication->marketerProfile?->user?->name ?? 'مسوق معتمد' }}</div>
                                    <div class="text-[11px] font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded w-fit mt-0.5">
                                        {{ $conv->campaignApplication->tracking_code }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">غير مسند لمسوق</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ number_format($conv->order_value, 2) }} <span class="text-[10px] text-slate-500">{{ $conv->currency }}</span>
                            </td>

                            <td class="py-3.5 px-4 font-black text-emerald-700">
                                {{ number_format($conv->commission_value, 2) }} <span class="text-[10px] text-emerald-600">{{ $conv->currency }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    @php
                                        $score = $conv->fraud_risk_score ?? 0;
                                        $badgeBg = $score >= 60 ? 'bg-rose-100 text-rose-800 border-rose-300' : ($score >= 30 ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200');
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeBg }}">
                                        {{ $score }}/100
                                    </span>
                                    @if($conv->is_suspicious)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse">
                                            مشبوه
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($conv->fraud_signals))
                                    <div class="text-[10px] text-rose-600 mt-1">
                                        {{ count($conv->fraud_signals) }} إشارات اشتباه
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $conv->status->badgeClasses() }}">
                                    {{ $conv->status->label() }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                {{ $conv->occurred_at ? $conv->occurred_at->format('Y-m-d H:i') : $conv->created_at->format('Y-m-d H:i') }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('admin.conversions.show', $conv->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-800 hover:bg-purple-900 hover:text-white transition shadow-2xs">
                                    <span>فحص وتدقيق</span>
                                    <span>←</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="text-4xl mb-2">🔍</div>
                                <div class="text-sm font-bold text-slate-600">لا توجد تحويلات تطابق محددات البحث الحالية</div>
                                <div class="text-xs text-slate-400 mt-1">يمكنك تغيير عوامل التصفية أو إزالة الفلاتر</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($conversions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $conversions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
