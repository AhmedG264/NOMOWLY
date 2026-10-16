@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-400/30">
                <span>🎯</span>
                <span>محرك الإسناد وتتبع التحويلات • Attribution & Conversions</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل التحويلات والمبيعات المنسوبة</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                مراجعة وتأكيد طلبات المبيعات المحققة عبر روابط صناع المحتوى والمسوقين وفق نموذج الإسناد المعتمد.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('brand.campaigns.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                ← العودة للحملات
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold flex items-center gap-2">
            <span class="text-rose-600 font-black">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Key Performance KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي التحويلات المسجلة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_conversions']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5 font-medium">مبيعات عبر المنصة</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">تحويلات معتمدة ومؤكدة</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['approved_count']) }}</div>
            <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">محققة لشروط العمولة</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">تحويلات قيد المراجعة</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['pending_count']) }}</div>
            <div class="text-[11px] text-amber-600/80 mt-0.5 font-medium">تنتظر تأكيد الدفع أو الشحن</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">عمولات معتمدة مستحقة</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['approved_commission'], 2) }} <span class="text-xs text-slate-500 font-normal">ر.س</span></div>
            <div class="text-[11px] text-slate-400 mt-0.5 font-medium">قيد التحقق: {{ number_format($stats['pending_commission'], 2) }} ر.س</div>
        </div>
    </div>

    <!-- Conversions Table / List -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">سجل عمليات التحويل والمبيعات</h2>
                <p class="text-xs text-slate-500 mt-0.5">تفاصيل الإسناد، رمز التتبع، وقيمة العمولات لكل طلب</p>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-2 text-xs">
                <a href="{{ route('brand.conversions.index') }}" class="px-3 py-1.5 rounded-lg font-bold {{ !request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">الكل</a>
                <a href="{{ route('brand.conversions.index', ['status' => 'PENDING']) }}" class="px-3 py-1.5 rounded-lg font-bold {{ request('status') === 'PENDING' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700' }}">قيد المراجعة</a>
                <a href="{{ route('brand.conversions.index', ['status' => 'APPROVED']) }}" class="px-3 py-1.5 rounded-lg font-bold {{ request('status') === 'APPROVED' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700' }}">المعتمدة</a>
                <a href="{{ route('brand.conversions.index', ['status' => 'REJECTED']) }}" class="px-3 py-1.5 rounded-lg font-bold {{ request('status') === 'REJECTED' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700' }}">المرفوضة</a>
            </div>
        </div>

        @if($conversions->isEmpty())
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 text-2xl">
                    📊
                </div>
                <h3 class="text-lg font-black text-slate-900">لا توجد عمليات تحويل مسجلة حالياً</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    عندما يقوم الزوار بالشراء عبر روابط المسوقين وأكواد التتبع الخاصة بهم، ستظهر طلبات التحويل هنا تلقائياً لتوثيق الإسناد والعمولة.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-4">رقم الطلب الخارجي</th>
                            <th class="p-4">الحملة التسويقية</th>
                            <th class="p-4">المسوق / كود التتبع</th>
                            <th class="p-4">قيمة الطلب</th>
                            <th class="p-4">العمولة المحسوبة</th>
                            <th class="p-4">تاريخ العملية</th>
                            <th class="p-4">حالة التحقق</th>
                            <th class="p-4 text-center">إجراءات المراجعة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($conversions as $conv)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-4 font-mono font-bold text-slate-900">
                                    #{{ $conv->external_order_id }}
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    {{ $conv->campaign?->title ?? '—' }}
                                </td>
                                <td class="p-4">
                                    @if($conv->campaignApplication)
                                        <div class="font-bold text-slate-900">{{ $conv->campaignApplication->marketerProfile?->full_name ?? 'صانع محتوى' }}</div>
                                        <span class="inline-block mt-0.5 font-mono text-[11px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">
                                            {{ $conv->campaignApplication->tracking_code }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">حركة مباشرة (غير منسوبة)</span>
                                    @endif
                                </td>
                                <td class="p-4 font-bold text-slate-900">
                                    {{ number_format($conv->order_value, 2) }} {{ $conv->currency }}
                                </td>
                                <td class="p-4 font-black text-emerald-600">
                                    {{ number_format($conv->commission_value, 2) }} {{ $conv->currency }}
                                </td>
                                <td class="p-4 text-slate-500 font-mono text-[11px]">
                                    {{ $conv->occurred_at ? $conv->occurred_at->format('Y-m-d H:i') : $conv->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-black border {{ $conv->status->badgeClasses() }}">
                                        {{ $conv->status->label() }}
                                    </span>
                                    @if($conv->rejection_reason)
                                        <div class="text-[10px] text-rose-600 mt-1" title="{{ $conv->rejection_reason }}">
                                            السبب: {{ Str::limit($conv->rejection_reason, 20) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    @if($conv->status === \App\Enums\ConversionStatus::PENDING)
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Approve Form -->
                                            <form method="POST" action="{{ route('brand.conversions.approve', $conv->id) }}">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-xs transition cursor-pointer">
                                                    اعتماد
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="{{ route('brand.conversions.reject', $conv->id) }}" onsubmit="return promptRejectReason(this);">
                                                @csrf
                                                <input type="hidden" name="reason" value="طلب مسترجع أو غير مؤهل">
                                                <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition cursor-pointer">
                                                    رفض
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($conv->status === \App\Enums\ConversionStatus::APPROVED)
                                        <span class="text-[11px] font-bold text-emerald-600">✓ مؤكد ومعتمد</span>
                                    @else
                                        <span class="text-[11px] text-slate-400">مكتمل</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $conversions->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function promptRejectReason(form) {
    var reason = prompt("يرجى كتابة سبب رفض التحويل (مثال: طلب ملغي، مرتجع، عملية دفع غير مكتملة):", "طلب ملغي أو مسترجع");
    if (!reason || reason.trim() === "") {
        return false;
    }
    form.querySelector('input[name="reason"]').value = reason;
    return true;
}
</script>
@endsection
