@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
            <span aria-hidden="true">→</span>
            <span>العودة إلى سجل العمليات والمراقبة</span>
        </a>
        <span class="text-xs font-mono text-slate-400">معرف الحدث: {{ $auditLog->id }}</span>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black border {{ $auditLog->action->badgeClass() }}">
                        {{ $auditLog->actionLabel() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black border {{ $auditLog->statusBadgeClass() }}">
                        {{ $auditLog->statusLabel() }}
                    </span>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600">
                        {{ $auditLog->action->category() }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">تفاصيل حدث التدقيق والمراقبة</h1>
                <p class="text-xs text-slate-500 mt-1">
                    تم توثيق هذا الحدث آلياً في سجلات المنصة التاريخية بتوقيت: {{ $auditLog->created_at->format('Y-m-d H:i:s') }} ({{ $auditLog->created_at->diffForHumans() }}).
                </p>
            </div>
            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 text-center min-w-[140px]">
                <div class="text-[11px] font-bold text-slate-500">حالة العملية</div>
                <div class="text-sm font-black text-slate-900 mt-0.5">{{ $auditLog->statusLabel() }}</div>
            </div>
        </div>

        <!-- Event Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <!-- User Info -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                <div class="font-bold text-slate-500 flex items-center gap-1.5">
                    <span>👤</span>
                    <span>المستخدم المنفّذ</span>
                </div>
                @if ($auditLog->user)
                    <div class="font-bold text-slate-900 text-sm">{{ $auditLog->user->email }}</div>
                    <div class="text-slate-600">الدور: <span class="font-bold">{{ $auditLog->user->role->label() }}</span></div>
                    <div class="text-slate-400 font-mono text-[10px]">المعرف: {{ $auditLog->user->id }}</div>
                @else
                    <div class="text-slate-500 italic">نظام المنصة أو زائر غير مسجل الدخول</div>
                @endif
            </div>

            <!-- Target Entity -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                <div class="font-bold text-slate-500 flex items-center gap-1.5">
                    <span>🎯</span>
                    <span>الكيان المستهدف (Target Entity)</span>
                </div>
                @if ($auditLog->entity_type)
                    <div class="font-bold text-slate-900 text-sm">{{ $auditLog->entity_type }}</div>
                    <div class="text-slate-600 font-mono">المعرف: {{ $auditLog->entity_id ?: 'غير محدد' }}</div>
                @else
                    <div class="text-slate-500 italic">لا يوجد كيان محدد لهذا الحدث</div>
                @endif
            </div>

            <!-- IP and Client -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                <div class="font-bold text-slate-500 flex items-center gap-1.5">
                    <span>🌐</span>
                    <span>عنوان الشبكة (IP Address)</span>
                </div>
                <div class="font-mono text-sm font-bold text-slate-900">{{ $auditLog->ip_address ?: 'غير متوفر' }}</div>
            </div>

            <!-- Reason / Approval -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                <div class="font-bold text-slate-500 flex items-center gap-1.5">
                    <span>📝</span>
                    <span>السبب أو مبرر الإجراء (Reason)</span>
                </div>
                <div class="text-sm font-bold text-slate-800">
                    {{ $auditLog->reason ?: ($auditLog->payload_diff['rejection_reason'] ?? $auditLog->payload_diff['reason'] ?? 'لم يُحدد سبب إضافي') }}
                </div>
            </div>
        </div>

        <!-- User Agent -->
        @if ($auditLog->user_agent)
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-xs">
                <div class="font-bold text-slate-500 mb-1">بيانات المتصفح والنظام (User Agent):</div>
                <div class="font-mono text-[11px] text-slate-700 break-all">{{ $auditLog->user_agent }}</div>
            </div>
        @endif

        <!-- Payload Diff (Before / After Comparison) -->
        @if (!empty($auditLog->payload_diff))
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span>📊</span>
                    <span>بيانات وفروقات العملية (Payload Diff)</span>
                </h3>

                @if (isset($auditLog->payload_diff['old']) && isset($auditLog->payload_diff['new']))
                    <!-- Side-by-side Diff Table -->
                    <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                                <tr>
                                    <th class="py-2.5 px-4">الحقل المعدل</th>
                                    <th class="py-2.5 px-4 text-rose-700 bg-rose-50/50">القيمة السابقة (Before)</th>
                                    <th class="py-2.5 px-4 text-emerald-700 bg-emerald-50/50">القيمة الجديدة (After)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                @php
                                    $keys = array_unique(array_merge(
                                        array_keys($auditLog->payload_diff['old'] ?? []),
                                        array_keys($auditLog->payload_diff['new'] ?? [])
                                    ));
                                @endphp
                                @foreach ($keys as $k)
                                    @php
                                        $oldVal = $auditLog->payload_diff['old'][$k] ?? '-';
                                        $newVal = $auditLog->payload_diff['new'][$k] ?? '-';
                                        if (is_array($oldVal)) $oldVal = json_encode($oldVal, JSON_UNESCAPED_UNICODE);
                                        if (is_array($newVal)) $newVal = json_encode($newVal, JSON_UNESCAPED_UNICODE);
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $k }}</td>
                                        <td class="py-2.5 px-4 text-rose-800 bg-rose-50/30 break-all">{{ $oldVal }}</td>
                                        <td class="py-2.5 px-4 text-emerald-800 bg-emerald-50/30 break-all">{{ $newVal }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Key-Value Property Table -->
                    <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold">
                                <tr>
                                    <th class="py-2.5 px-4">المعلمة / الخاصية</th>
                                    <th class="py-2.5 px-4">القيمة المسجلة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                @foreach ($auditLog->payload_diff as $key => $val)
                                    @php
                                        if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                        elseif (is_bool($val)) $val = $val ? 'true' : 'false';
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $key }}</td>
                                        <td class="py-2.5 px-4 text-slate-700 break-all">{{ $val }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Raw JSON Inspection Accordion -->
                <details class="bg-slate-900 rounded-2xl p-4 text-white text-xs group">
                    <summary class="font-bold text-slate-300 cursor-pointer flex items-center justify-between select-none">
                        <span>عرض كود JSON الخام للحدث (Raw Audit Payload)</span>
                        <span class="group-open:rotate-180 transition text-slate-400">▼</span>
                    </summary>
                    <pre class="mt-3 p-3 bg-slate-950 rounded-xl overflow-x-auto text-[11px] font-mono text-emerald-400 leading-relaxed">{{ json_encode($auditLog->payload_diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </div>
        @endif
    </div>

    <!-- Immutability Guarantee Banner -->
    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 text-xs text-slate-600 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span>🛡️</span>
            <span>هذا السجل محمي برمجياً بنمط <strong>Append-Only</strong> ولا يتيح النظام أي مسار لتعديله أو حذفه.</span>
        </div>
        <span class="font-mono text-[10px] text-slate-400">AuditLog #{{ substr($auditLog->id, 0, 8) }}</span>
    </div>
</div>
@endsection
