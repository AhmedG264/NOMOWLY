@extends('layouts.app')

@section('title', 'إعدادات قواعد كشف الاحتيال - الإدارة العامة')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-500 mb-1">
                <a href="{{ route('admin.conversions.index') }}" class="hover:text-purple-700">تدقيق التحويلات</a>
                <span>/</span>
                <span class="text-slate-800">قواعد كشف الاحتيال</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">إعدادات قواعد كشف الاحتيال ونقاط المخاطر</h1>
            <p class="text-sm text-slate-500 mt-1">ضبط محددات السرعة، كثافة النقرات، ونسب الاسترداد للتحكم بدقة الكشف الآلي</p>
        </div>

        <div>
            <a href="{{ route('admin.conversions.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition shadow-xs">
                ← العودة إلى قائمة التحويلات
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Rules Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-slate-900">القواعد الخوارزمية الفعالة</h2>
                <p class="text-xs text-slate-500 mt-0.5">تُطبّق هذه القواعد آلياً عند استقبال كل تحويل مبيعات جديد عبر واجهة برمجة التطبيقات (API)</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                عتبة الاشتباه الافتراضية: 40 نقطة
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($rules as $rule)
                <div class="p-6 hover:bg-slate-50/50 transition" x-data="{ editing: false }">
                    <form method="POST" action="{{ route('admin.conversions.rules.update', $rule->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1 md:max-w-xl">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-sm text-slate-900">{{ $rule->name_ar }}</span>
                                    <span class="font-mono text-[11px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                        {{ $rule->rule_key }}
                                    </span>
                                    @if($rule->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            مفعلة
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            معطلة
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">{{ $rule->description }}</p>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-slate-400">قيمة العتبة (Threshold)</label>
                                    <input type="text" name="threshold_value" value="{{ $rule->threshold_value }}" class="text-xs font-mono font-bold rounded-xl border border-slate-200 p-2 w-28 text-center focus:border-purple-600 focus:outline-hidden">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-slate-400">وزن المخاطرة (Weight)</label>
                                    <input type="number" name="risk_weight" value="{{ $rule->risk_weight }}" min="1" max="100" class="text-xs font-bold rounded-xl border border-slate-200 p-2 w-24 text-center focus:border-purple-600 focus:outline-hidden">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-slate-400">الحالة</label>
                                    <select name="is_active" class="text-xs font-bold rounded-xl border border-slate-200 p-2 focus:border-purple-600 focus:outline-hidden">
                                        <option value="1" @selected($rule->is_active)>مفعلة</option>
                                        <option value="0" @selected(!$rule->is_active)>معطلة</option>
                                    </select>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-purple-900 text-white hover:bg-purple-800 transition cursor-pointer">
                                        حفظ التعديل
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Security & Privacy Principles Card -->
    <div class="bg-gradient-to-r from-purple-900 to-indigo-900 rounded-3xl p-6 text-white space-y-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">🛡️</span>
            <h3 class="font-black text-sm">مبادئ حماية الخصوصية ومكافحة الاحتيال في المنصة</h3>
        </div>
        <ul class="text-xs text-purple-100 space-y-1.5 list-disc list-inside">
            <li><strong>تشفير البصمات:</strong> عناوين IP وبيانات User-Agent تُخزّن مشفرة ومملحة (Hashed & Salted) دون تخزين عناوين صريحة حفاظاً على خصوصية الزوار.</li>
            <li><strong>الفصل بين الاشتباه والرفض:</strong> التحويل المشبوه لا يُرفض تلقائياً من مؤشر ضعيف؛ بل يُعلّم للمراجعة والتدقيق الإداري لحماية عمولات المسوقين الحقيقيين.</li>
            <li><strong>المعاملات المالية المزدوجة:</strong> لا يتم ترحيل أي عمولة مالياً في دفتر الأستاذ إلا بعد التحقق الأمني واعتماد التحويل صراحة.</li>
        </ul>
    </div>

</div>
@endsection
