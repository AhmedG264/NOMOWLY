@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-400/30">
                <span>🛡️</span>
                <span>سجل العمليات والمراقبة الإدارية والأمنية</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">سجل العمليات والمراقبة (Audit Log)</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                سجل تاريخي تدقيقي غير قابل للتعديل يوثق جميع الأنشطة الحساسة، والتغييرات الإدارية، ومحاولات الوصول، والعمليات المالية والترويجية.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition">
                إدارة الحملات
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 rounded-xl text-xs sm:text-sm font-bold border border-rose-500/30 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة الحملات
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-xl text-sm font-black bg-indigo-600 text-white shadow-xs">
            سجل العمليات والمراقبة ({{ $stats['total_logs'] }})
        </a>
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            الأدوار والصلاحيات
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
            إدارة المستخدمين
        </a>
    </div>

    <!-- System KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي السجلات التدقيقية</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_logs']) }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">عمليات آخر 24 ساعة</div>
            <div class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($stats['last_24h']) }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">عمليات مرفوضة / فاشلة</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['failed_or_rejected']) }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">تنبيهات أمنية ومحاولات غير مصرح بها</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ number_format($stats['security_alerts']) }}</div>
        </div>
    </div>

    <!-- Search & Multi-Dimensional Filters -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🔍</span>
                <span>تصفية وفحص السجل المتقدم</span>
            </h2>
            @if(request()->anyFilled(['q', 'action', 'status', 'entity_type', 'entity_id', 'ip_address', 'date_from', 'date_to']))
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700">
                    ✕ إلغاء جميع الفلاتر
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search text -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">بحث عام</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="البريد، المعرف، IP، السبب..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Action Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع العملية</label>
                    <select name="action" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع العمليات ({{ count($actions) }})</option>
                        @foreach($actions as $action)
                            <option value="{{ $action->value }}" @selected(request('action') === $action->value)>
                                {{ $action->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status / Result Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نتيجة العملية</label>
                    <select name="status" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع الحالات</option>
                        <option value="SUCCESS" @selected(request('status') === 'SUCCESS')>ناجحة (SUCCESS)</option>
                        <option value="FAILED" @selected(request('status') === 'FAILED')>فاشلة (FAILED)</option>
                        <option value="APPROVED" @selected(request('status') === 'APPROVED')>معتمدة (APPROVED)</option>
                        <option value="REJECTED" @selected(request('status') === 'REJECTED')>مرفوضة (REJECTED)</option>
                        <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>ملغاة (CANCELLED)</option>
                        <option value="UNAUTHORIZED" @selected(request('status') === 'UNAUTHORIZED')>محظورة أمنياً (UNAUTHORIZED)</option>
                    </select>
                </div>

                <!-- Entity Type Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع الكيان</label>
                    <select name="entity_type" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">جميع الكيانات</option>
                        @foreach($availableEntityTypes as $type)
                            <option value="{{ $type }}" @selected(request('entity_type') === $type)>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Entity ID -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">معرف الكيان (Entity ID)</label>
                    <input type="text" name="entity_id" value="{{ request('entity_id') }}" placeholder="معرف الحملة أو التحويل..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono">
                </div>

                <!-- IP Address -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان IP</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}" placeholder="e.g. 192.168.1.1" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none font-mono">
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition cursor-pointer shadow-xs">
                    تطبيق الفلاتر والبحث
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">سجل الأحداث والعمليات (مرتب من الأحدث للأقدم)</h2>
            <div class="text-xs font-bold text-slate-500">
                عرض {{ $logs->firstItem() ?? 0 }} إلى {{ $logs->lastItem() ?? 0 }} من أصل {{ number_format($logs->total()) }} سجل
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                        <th class="py-3 px-4">التاريخ والوقت</th>
                        <th class="py-3 px-4">نوع العملية</th>
                        <th class="py-3 px-4">الحالة / النتيجة</th>
                        <th class="py-3 px-4">المستخدم المنفذ</th>
                        <th class="py-3 px-4">الكيان المستهدف</th>
                        <th class="py-3 px-4">عنوان IP</th>
                        <th class="py-3 px-4">السبب / الملاحظات</th>
                        <th class="py-3 px-4 text-center">التفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Timestamp -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400 font-medium">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-black border {{ $log->action->badgeClass() }}">
                                    {{ $log->actionLabel() }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black border {{ $log->statusBadgeClass() }}">
                                    {{ $log->statusLabel() }}
                                </span>
                            </td>

                            <!-- User -->
                            <td class="py-3.5 px-4">
                                @if ($log->user)
                                    <div class="font-bold text-slate-900 truncate max-w-[150px]">{{ $log->user->email }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $log->user->role->label() }}</div>
                                @else
                                    <span class="text-slate-400 font-medium italic">نظام / زائر غير مسجل</span>
                                @endif
                            </td>

                            <!-- Target Entity -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px]">
                                @if ($log->entity_type)
                                    <div class="font-bold text-slate-800">{{ $log->entity_type }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ $log->entity_id ?: '-' }}</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- IP Address -->
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $log->ip_address ?: '-' }}
                            </td>

                            <!-- Reason / Notes -->
                            <td class="py-3.5 px-4 max-w-[200px]">
                                @if ($log->reason)
                                    <span class="text-slate-700 truncate block font-medium" title="{{ $log->reason }}">{{ $log->reason }}</span>
                                @elseif ($log->payload_diff && isset($log->payload_diff['rejection_reason']))
                                    <span class="text-rose-600 truncate block font-medium">{{ $log->payload_diff['rejection_reason'] }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Details Link -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white text-[11px] font-bold transition shadow-2xs">
                                    عرض التفاصيل
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">📜</div>
                                <div class="font-bold text-slate-800">لا توجد سجلات تطابق الفلاتر المحددة</div>
                                <div class="text-xs text-slate-400 mt-1">جرّب تغيير خيارات البحث أو إعادة ضبط الفلاتر.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($logs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Security & Immutability Notice -->
    <div class="rounded-2xl bg-slate-100 border border-slate-200/80 p-4 text-xs text-slate-600 flex items-start gap-3">
        <span class="text-lg">🔒</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-800">ضمانات أمان وسرية سجل العمليات (Audit Trail Policy):</div>
            <p class="leading-relaxed">
                جميع السجلات المعروضة محمية برمجياً بنمط الإضافة فقط (Append-Only). يُحظر تعديل أي سجل أو حذفه من خلال واجهات أو وظائف التطبيق. يتم تشفير وتجريد أي كلمات مرور أو مفاتيح سرية فور تسجيل الحدث لضمان الامتثال التام لمعايير أمن البيانات.
            </p>
        </div>
    </div>
</div>
@endsection
