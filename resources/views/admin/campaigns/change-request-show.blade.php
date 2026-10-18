@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.campaigns.change-requests.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-700 transition">
                ← العودة إلى قائمة طلبات التعديل
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">تدقيق ومراجعة طلب تعديل شروط الحملة</h1>
            <p class="text-xs text-slate-500 mt-1">الحملة: {{ $changeRequest->campaign?->title }} • العلامة: {{ $changeRequest->campaign?->brandProfile?->company_name }}</p>
        </div>

        <span class="px-3 py-1.5 rounded-xl text-xs font-black border {{ $changeRequest->status->badgeClasses() }}">
            {{ $changeRequest->status->label() }}
        </span>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="font-black">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="list-disc list-inside text-xs font-normal">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Request Meta Info -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">بيانات الطلب وسياقه التجاري</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block mb-0.5">مقدم الطلب:</span>
                <span class="font-black text-slate-900">{{ $changeRequest->requestedBy?->name }}</span>
                <span class="text-slate-400 block text-[11px]">({{ $changeRequest->requestedBy?->email }})</span>
            </div>

            <div>
                <span class="text-slate-500 block mb-0.5">نطاق تطبيق التعديل (Scope):</span>
                <span class="font-black text-indigo-700">{{ $changeRequest->scope->label() }}</span>
            </div>

            <div>
                <span class="text-slate-500 block mb-0.5">تاريخ التقديم:</span>
                <span class="font-bold text-slate-900">{{ $changeRequest->created_at->format('Y-m-d H:i') }}</span>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <span class="text-xs text-slate-500 font-bold block mb-1">سبب التعديل الموضح من العلامة:</span>
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-800 leading-relaxed font-medium">
                {{ $changeRequest->reason }}
            </div>
        </div>
    </div>

    <!-- Visual Diff (Side-by-Side Comparison) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
        <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
            <span>مقارنة الفروقات بين الشروط السارية والشروط المقترحة (Visual Diff)</span>
            <span class="text-xs text-slate-500 font-bold">من الإصدار v{{ $changeRequest->currentVersion?->version_number ?? 1 }} إلى v{{ ($changeRequest->currentVersion?->version_number ?? 1) + 1 }}</span>
        </h2>

        @if($changeRequest->diff_summary)
            <div class="divide-y divide-slate-100">
                @foreach($changeRequest->diff_summary as $field => $diff)
                    <div class="py-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                        <div class="text-xs font-black text-slate-800">
                            {{ $diff['label'] ?? $field }}
                        </div>

                        <!-- Old Value -->
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
                            <span class="text-[10px] text-rose-500 block font-bold mb-0.5">الشروط السارية الحالية:</span>
                            <span class="font-black line-through">{{ $diff['old'] }}</span>
                        </div>

                        <!-- New Value -->
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800">
                            <span class="text-[10px] text-emerald-600 block font-bold mb-0.5">الشروط المقترحة الجديدة:</span>
                            <span class="font-black">{{ $diff['new'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 bg-slate-50 rounded-2xl text-xs text-slate-500">
                لا توجد فروقات محددة في الملخص.
            </div>
        @endif
    </div>

    <!-- Reviewer Actions or Outcome -->
    @if($changeRequest->isPending())
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">قرار المراجعة والتدقيق الإداري</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Approve Form -->
                <form method="POST" action="{{ route('admin.campaigns.change-requests.approve', $changeRequest) }}" class="p-5 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-4">
                    @csrf
                    <div class="flex items-center gap-2 text-emerald-900 font-black text-sm">
                        <span>✓</span>
                        <span>اعتماد التعديل وترقية إصدار الحملة</span>
                    </div>
                    <p class="text-xs text-emerald-800 leading-relaxed">
                        سيؤدي الاعتماد إلى إنشاء الإصدار v{{ ($changeRequest->currentVersion?->version_number ?? 1) + 1 }} وترقية الشروط السارية على الحملة فوراً دون التأثير على التحويلات السابقة.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات الاعتماد (اختياري)</label>
                        <textarea name="reviewer_notes" rows="2" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white" placeholder="أدخل أي ملاحظات إدارية ترغب بتسجيلها في سجل التدقيق..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-sm transition cursor-pointer">
                        اعتماد طلب التغيير وترقية الإصدار
                    </button>
                </form>

                <!-- Reject Form -->
                <form method="POST" action="{{ route('admin.campaigns.change-requests.reject', $changeRequest) }}" class="p-5 bg-rose-50/50 rounded-2xl border border-rose-200 space-y-4">
                    @csrf
                    <div class="flex items-center gap-2 text-rose-900 font-black text-sm">
                        <span>✕</span>
                        <span>رفض طلب التعديل</span>
                    </div>
                    <p class="text-xs text-rose-800 leading-relaxed">
                        سيتم إخطار العلامة التجارية برفض الطلب، والإبقاء على الشروط السارية الحالية دون أي تعديل.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">سبب الرفض الإداري (إلزامي) *</label>
                        <textarea name="reviewer_notes" required rows="2" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white" placeholder="وضح أسباب الرفض بالتفصيل لمشاركتها مع العلامة التجارية..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-sm transition cursor-pointer">
                        رفض طلب التعديل
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- Completed Outcome Details -->
        <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200 text-xs space-y-3">
            <h3 class="font-black text-slate-900 text-sm">تفاصيل نتيجة المراجعة الإدارية</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <span class="text-slate-500 block">تمت المراجعة بواسطة:</span>
                    <span class="font-bold text-slate-900">{{ $changeRequest->reviewedBy?->name ?? 'المدير العام' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">تاريخ القرار:</span>
                    <span class="font-bold text-slate-900">{{ $changeRequest->reviewed_at?->format('Y-m-d H:i') }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">الحالة النهائية:</span>
                    <span class="font-black {{ $changeRequest->isApproved() ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $changeRequest->status->label() }}
                    </span>
                </div>
            </div>
            @if($changeRequest->reviewer_notes)
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-500 block font-bold mb-1">ملاحظات المراجع:</span>
                    <p class="text-slate-800 font-medium">{{ $changeRequest->reviewer_notes }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
