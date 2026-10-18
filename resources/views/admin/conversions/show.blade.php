@extends('layouts.app')

@section('title', 'تفاصيل التحويل والتدقيق الأمني #' . $conversion->external_order_id . ' - الإدارة العامة')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-500 mb-1">
                <a href="{{ route('admin.conversions.index') }}" class="hover:text-purple-700">تدقيق التحويلات</a>
                <span>/</span>
                <span class="text-slate-800">طلب #{{ $conversion->external_order_id }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900">التحويل #{{ $conversion->external_order_id }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black border {{ $conversion->status->badgeClasses() }}">
                    {{ $conversion->status->label() }}
                </span>
                @if($conversion->is_suspicious)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-rose-600 text-white animate-pulse">
                        <span>🚨</span>
                        <span>شبهة احتيال نشطة</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.conversions.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition">
                ← العودة للقائمة
            </a>

            @if($conversion->status === \App\Enums\ConversionStatus::PENDING)
                <!-- Quick Approve -->
                <form method="POST" action="{{ route('admin.conversions.approve', $conversion->id) }}" onsubmit="return confirm('هل أنت متأكد من اعتماد هذا التحويل وترحيل العمولة مالياً في دفتر الأستاذ؟')">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-black bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-xs cursor-pointer">
                        اعتماد وتسوية العمولة
                    </button>
                </form>

                <!-- Quick Reject -->
                <button type="button" @click="$dispatch('open-reject-modal')" class="px-4 py-2.5 rounded-xl text-xs font-black bg-rose-600 text-white hover:bg-rose-700 transition shadow-xs cursor-pointer">
                    رفض التحويل
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-bold flex items-center gap-2">
            <span>⛔</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Details & Fraud Analysis -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Fraud & Integrity Analysis Card -->
            <div class="bg-white rounded-3xl p-6 border {{ $conversion->is_suspicious ? 'border-rose-300 shadow-md ring-1 ring-rose-200' : 'border-slate-200 shadow-xs' }}">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🛡️</span>
                        <div>
                            <h2 class="text-base font-black text-slate-900">تحليل الاحتيال ومؤشرات السلامة التقنية</h2>
                            <p class="text-xs text-slate-500">تقييم سلامة الإسناد ونمط السلوك عبر القواعد الخوارزمية</p>
                        </div>
                    </div>

                    <div class="text-left">
                        <div class="text-xs font-bold text-slate-500">درجة الخطورة المحسوبة</div>
                        @php
                            $score = $conversion->fraud_risk_score ?? 0;
                            $scoreColor = $score >= 60 ? 'text-rose-600' : ($score >= 30 ? 'text-amber-600' : 'text-emerald-600');
                        @endphp
                        <div class="text-2xl font-black {{ $scoreColor }}">{{ $score }} <span class="text-xs text-slate-400">/ 100</span></div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                        @php
                            $barBg = $score >= 60 ? 'bg-rose-500' : ($score >= 30 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="{{ $barBg }} h-2.5 rounded-full transition-all duration-500" style="width: {{ max(4, min(100, $score)) }}%"></div>
                    </div>
                </div>

                <!-- Triggered Signals List -->
                <div class="mt-6 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700">إشارات ومؤشرات الفحص الأمني المرصودة:</h3>

                    @if(!empty($conversion->fraud_signals))
                        <div class="grid grid-cols-1 gap-2.5">
                            @foreach($conversion->fraud_signals as $signal)
                                <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200 text-xs text-slate-800 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <div class="font-bold text-rose-900 flex items-center gap-1.5">
                                            <span>⚠️</span>
                                            <span>{{ $signal['name_ar'] ?? $signal['rule_key'] }}</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-rose-200/80 text-rose-900 font-bold text-[10px]">
                                            +{{ $signal['weight'] }} نقطة مخاطرة
                                        </span>
                                    </div>
                                    @if(!empty($signal['evidence']))
                                        <div class="p-2 rounded-xl bg-white/80 border border-rose-100 font-mono text-[11px] text-slate-600 space-y-0.5">
                                            @foreach($signal['evidence'] as $k => $val)
                                                <div><span class="text-slate-400">{{ $k }}:</span> <span class="font-bold">{{ is_array($val) ? json_encode($val) : $val }}</span></div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                            <span>✅</span>
                            <span>لم يتم رصد أي إشارات اشتباه احتيال في هذا التحويل. كافة فحوصات السرعة وكثافة النقرات طبيعية.</span>
                        </div>
                    @endif
                </div>

                <!-- Investigation Status & Notes -->
                @if($conversion->investigated_at)
                    <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="font-bold">سجل التحقيق الإداري:</span>
                            <span>{{ $conversion->investigated_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="text-slate-800">
                            <strong>المشرف المحقق:</strong> {{ $conversion->investigatedBy?->name ?? 'مشرف النظام' }}
                        </div>
                        <div class="text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 font-mono">
                            {{ $conversion->investigation_notes }}
                        </div>
                    </div>
                @endif

                <!-- Investigation Form -->
                @if($conversion->is_suspicious || $conversion->status === \App\Enums\ConversionStatus::PENDING)
                    <div class="mt-6 pt-5 border-t border-slate-100" x-data="{ decision: 'CLEAR_SUSPICION' }">
                        <h4 class="text-xs font-bold text-slate-800 mb-3">اتخاذ قرار تحقيق أمني وإداري:</h4>
                        <form method="POST" action="{{ route('admin.conversions.investigate', $conversion->id) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-bold"
                                       :class="decision === 'CLEAR_SUSPICION' ? 'bg-emerald-50 border-emerald-400 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                    <input type="radio" name="decision" value="CLEAR_SUSPICION" x-model="decision" class="hidden">
                                    <span>🟢</span>
                                    <span>تبرئة الشبهة واعتبار التحويل نظامياً</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-bold"
                                       :class="decision === 'CONFIRM_FRAUD_REJECT' ? 'bg-rose-50 border-rose-400 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                    <input type="radio" name="decision" value="CONFIRM_FRAUD_REJECT" x-model="decision" class="hidden">
                                    <span>🔴</span>
                                    <span>تأكيد الشبهة ورفض التحويل كاحتيال</span>
                                </label>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">ملاحظات وأدلة التحقيق الإداري (إلزامي للتوثيق في سجل التدقيق)</label>
                                <textarea name="notes" rows="2" required placeholder="أدخل أسباب القرار وملاحظات التدقيق التقني..." class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden"></textarea>
                            </div>

                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-purple-900 text-white hover:bg-purple-800 transition cursor-pointer">
                                حفظ قرار التحقيق وتحديث السجل
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Attribution & Click Deep-Dive Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <span class="text-xl">🔗</span>
                    <div>
                        <h2 class="text-base font-black text-slate-900">سلسلة الإسناد ونقرة الإحالة</h2>
                        <p class="text-xs text-slate-500">تفاصيل جلسة التتبع ونموذج الإسناد الأخير (Last-Touch Attribution)</p>
                    </div>
                </div>

                @if($conversion->attributionClick)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">معرف الزائر الأول (Privacy Token)</span>
                            <span class="font-mono font-bold text-slate-800 block break-all">{{ $conversion->attributionClick->visitor_id }}</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">معرف الجلسة (Session ID)</span>
                            <span class="font-mono font-bold text-slate-800 block break-all">{{ $conversion->attributionClick->session_id }}</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">بصمة عنوان IP (مشفّر بدون كشف بيانات شخصية)</span>
                            <span class="font-mono text-slate-600 block break-all">{{ $conversion->attributionClick->ip_hash }}</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">بصمة المتصفح (User-Agent Hash)</span>
                            <span class="font-mono text-slate-600 block break-all">{{ $conversion->attributionClick->user_agent_hash }}</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">نوع الجهاز والدولة</span>
                            <span class="font-bold text-slate-800">{{ $conversion->attributionClick->device_type }} | {{ $conversion->attributionClick->country ?? 'SA' }}</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="text-slate-400 block">توقيت النقرة مقابل توقيت التحويل</span>
                            <span class="font-bold text-slate-800">
                                النقرة: {{ $conversion->attributionClick->created_at->format('H:i:s Y-m-d') }}
                                <br>
                                التحويل: {{ $conversion->occurred_at ? $conversion->occurred_at->format('H:i:s Y-m-d') : $conversion->created_at->format('H:i:s Y-m-d') }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold">
                        تم إسناد هذا التحويل مباشرة عبر إدخال كود الخصم الترويجي عند نقطة الدفع دون تسجيل نقرة تتبع سابقة.
                    </div>
                @endif
            </div>

            <!-- Commission Terms Snapshot (Immutable Contract) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <span class="text-xl">📜</span>
                    <div>
                        <h2 class="text-base font-black text-slate-900">لقطة الشروط التعاقدية للعمولة (Terms Snapshot)</h2>
                        <p class="text-xs text-slate-500">لقطة تعاقدية مجمدة ومحمية من التعديل تضمن حق المسوق والعلامة التجارية</p>
                    </div>
                </div>

                @if($conversion->commission_terms_snapshot)
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 block">إصدار الشروط</span>
                            <span class="font-bold text-slate-900">v{{ $conversion->commission_terms_snapshot['version_number'] ?? 1 }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 block">نموذج العمولة</span>
                            <span class="font-bold text-slate-900">{{ $conversion->commission_terms_snapshot['commission_type'] ?? 'PERCENTAGE_CPO' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 block">نسبة / قيمة الشرط</span>
                            <span class="font-bold text-slate-900">{{ $conversion->commission_terms_snapshot['commission_value'] ?? '—' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 block">نافذة الكوكيز</span>
                            <span class="font-bold text-slate-900">{{ $conversion->commission_terms_snapshot['cookie_duration_days'] ?? 30 }} يوم</span>
                        </div>
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic">لا توجد لقطة شروط مخزنة لهذا التحويل التاريخي</div>
                @endif
            </div>

            <!-- Audit Trail Timeline -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <span class="text-xl">📋</span>
                    <div>
                        <h2 class="text-base font-black text-slate-900">سجل العمليات والتدقيق (Audit Trail)</h2>
                        <p class="text-xs text-slate-500">سلسلة التدقيق الزمنية الكاملة لكافة انتقالات الحالة</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($auditLogs as $log)
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                            <div class="w-2 h-2 rounded-full bg-purple-600 mt-1.5 shrink-0"></div>
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900">{{ $log->action->label() }}</span>
                                    <span class="text-[11px] text-slate-400 font-mono">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                                </div>
                                <div class="text-slate-600 text-[11px]">
                                    بواسطة: {{ $log->user?->name ?? 'النظام الآلي (System Engine)' }}
                                    @if($log->reason)
                                        | السبب: {{ $log->reason }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 italic text-center py-4">لا توجد سجلات تدقيق مسجلة</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Column 3: Summary, Campaign & Ledger Settlement -->
        <div class="space-y-6">

            <!-- Conversion Overview Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4 text-xs">
                <h3 class="font-black text-slate-900 text-sm pb-2 border-b border-slate-100">ملخص العملية المالية</h3>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-500">رقم الطلب الخارجي:</span>
                        <span class="font-mono font-bold text-slate-900">#{{ $conversion->external_order_id }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">نوع التحويل:</span>
                        <span class="font-bold text-slate-900">{{ $conversion->conversion_type ?? 'ORDER' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">قيمة الطلب الكلية (GMV):</span>
                        <span class="font-bold text-slate-900 text-sm">{{ number_format($conversion->order_value, 2) }} {{ $conversion->currency }}</span>
                    </div>

                    <div class="flex justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-bold">عمولة المسوق المحسوبة:</span>
                        <span class="font-black text-emerald-700 text-base">{{ number_format($conversion->commission_value, 2) }} {{ $conversion->currency }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">تاريخ حدوث التحويل:</span>
                        <span class="text-slate-800">{{ $conversion->occurred_at ? $conversion->occurred_at->format('Y-m-d H:i') : $conversion->created_at->format('Y-m-d H:i') }}</span>
                    </div>

                    @if($conversion->verified_at)
                        <div class="flex justify-between">
                            <span class="text-slate-500">تاريخ الاعتماد/المراجعة:</span>
                            <span class="text-slate-800">{{ $conversion->verified_at->format('Y-m-d H:i') }}</span>
                        </div>
                    @endif

                    @if($conversion->rejection_reason)
                        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] space-y-1">
                            <span class="font-bold block">سبب الرفض / الإلغاء:</span>
                            <span>{{ $conversion->rejection_reason }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Double-Entry Ledger Settlement Status -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="text-base">💼</span>
                    <h3 class="font-black text-slate-900 text-sm">التسوية المحاسبية (Double-Entry Ledger)</h3>
                </div>

                @if($conversion->settlement)
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-1.5 text-emerald-700 font-bold">
                            <span>✅</span>
                            <span>تم ترحيل العمولة بنجاح في دفتر الأستاذ</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1 font-mono text-[11px]">
                            <div>معرف التسوية: #{{ Str::limit($conversion->settlement->id, 8) }}</div>
                            <div>معاملة القيد: #{{ Str::limit($conversion->settlement->ledger_transaction_id, 8) }}</div>
                            <div>المبلغ المرحل: {{ number_format($conversion->settlement->commission_amount, 2) }} {{ $conversion->settlement->currency }}</div>
                            <div>تاريخ التسوية: {{ $conversion->settlement->settled_at->format('Y-m-d H:i') }}</div>
                        </div>

                        @if($conversion->settlement->isReversed())
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1">
                                <span class="font-bold block">تم عكس القيد المحاسبي (Reversal)</span>
                                <div>تاريخ العكس: {{ $conversion->settlement->reversed_at?->format('Y-m-d H:i') }}</div>
                                <div>سبب العكس: {{ $conversion->settlement->reversal_reason }}</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 text-center space-y-1">
                        <div>لم يتم ترحيل قيد محاسبي بعد</div>
                        <div class="text-[11px] text-slate-400">يتم إنشاء القيد تلقائياً عند اعتماد التحويل بشرط كفاية رصيد الضمان المحجوز</div>
                    </div>
                @endif
            </div>

            <!-- Campaign & Brand Information -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-3 text-xs">
                <h3 class="font-black text-slate-900 text-sm pb-2 border-b border-slate-100">بيانات الحملة والعلامة التجارية</h3>

                <div class="space-y-2">
                    <div>
                        <span class="text-slate-400 block">اسم الحملة:</span>
                        <a href="{{ route('admin.campaigns.index') }}" class="font-bold text-purple-900 hover:underline">{{ $conversion->campaign?->title }}</a>
                    </div>
                    <div>
                        <span class="text-slate-400 block">العلامة التجارية:</span>
                        <span class="font-bold text-slate-800">{{ $conversion->campaign?->brandProfile?->company_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">المسوق المعتمد:</span>
                        <span class="font-bold text-slate-800">{{ $conversion->campaignApplication?->marketerProfile?->user?->name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">رمز التتبع:</span>
                        <span class="font-mono font-bold text-purple-700">{{ $conversion->campaignApplication?->tracking_code ?? '—' }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Rejection Modal -->
    <div x-data="{ open: false }"
         @open-reject-modal.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-200" @click.outside="open = false">
            <h3 class="text-base font-black text-slate-900">رفض تحويل مبيعات</h3>
            <p class="text-xs text-slate-500">يرجى توثيق سبب الرفض لحفظ حقوق كافة الأطراف وإشعار العلامة التجارية والمسوق في سجل التدقيق.</p>

            <form method="POST" action="{{ route('admin.conversions.reject', $conversion->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">سبب الرفض (إلزامي)</label>
                    <textarea name="reason" rows="3" required placeholder="مثال: تم إرجاع الطلب، بيانات ترويجية وهمية، إلخ..." class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-purple-600 focus:outline-hidden"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        إلغاء
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-rose-600 text-white hover:bg-rose-700">
                        تأكيد الرفض
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
