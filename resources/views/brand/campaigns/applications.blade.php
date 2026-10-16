@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('brand.campaigns.index') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-700 transition">
                ← العودة إلى قائمة الحملات
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">
                طلبات الانضمام لحملة: {{ $campaign->title }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                مراجعة واعتماد صناع المحتوى والمسوقين وتوليد أكواد التتبع المخصصة.
            </p>
        </div>

        <a href="{{ route('marketplace.show', $campaign->slug) }}" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition">
            معاينة الحملة في السوق ↗
        </a>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Applications List -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">
                المتقدمون ({{ $applications->total() }})
            </h2>
        </div>

        @if($applications->isEmpty())
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 text-2xl">
                    👥
                </div>
                <h3 class="text-base font-black text-slate-900">لا توجد طلبات انضمام لهذه الحملة حتى الآن</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    بمجرد أن يتقدم صناع المحتوى من سوق الحملات، ستظهر طلباتهم هنا لمراجعة قنواتهم واعتمادهم.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($applications as $app)
                    <div class="p-6 space-y-5">
                        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-700 font-black text-lg">
                                    {{ mb_substr($app->marketerProfile->full_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-black text-slate-900">{{ $app->marketerProfile->full_name }}</h3>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black border {{ $app->status->badgeClasses() }}">
                                            {{ $app->status->label() }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        التخصص: <span class="font-bold text-slate-700">{{ $app->marketerProfile->primarySpecialization?->specialization?->label() ?? 'مسوق معتمد' }}</span>
                                        • الدولة: <span class="font-bold text-slate-700">{{ $app->marketerProfile->country ?? 'الشرق الأوسط' }}</span>
                                        • تاريخ التقديم: <span class="font-bold text-slate-700">{{ $app->applied_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($app->isApproved())
                                <div class="bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-xl text-xs space-y-1">
                                    <div class="text-emerald-900 font-bold">كود التتبع المعتمد:</div>
                                    <div class="font-mono font-black text-emerald-800 text-sm tracking-wider">{{ $app->tracking_code }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Pitch Note & Channels -->
                        @if($app->pitch_note || !empty($app->channel_urls))
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3 text-xs">
                                @if($app->pitch_note)
                                    <div>
                                        <div class="font-bold text-slate-700 mb-0.5">مقترح وخطة الترويج (Pitch):</div>
                                        <p class="text-slate-600 leading-relaxed">{{ $app->pitch_note }}</p>
                                    </div>
                                @endif

                                @if(!empty($app->channel_urls))
                                    <div>
                                        <div class="font-bold text-slate-700 mb-1">القنوات التسويقية المعلنة:</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($app->channel_urls as $url)
                                                <a href="{{ $url }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-teal-700 hover:underline font-mono text-[11px]">
                                                    {{ $url }} ↗
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Action Form for Pending or Re-review -->
                        @if($app->isPending())
                            <div class="bg-amber-50/50 rounded-2xl p-5 border border-amber-200/80 space-y-4">
                                <div class="text-xs font-black text-slate-900">اتخاذ القرار وتفعيل بيانات التتبع:</div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Approve Form -->
                                    <form method="POST" action="{{ route('brand.campaigns.applications.review', [$campaign, $app]) }}" class="bg-white p-4 rounded-xl border border-emerald-200 space-y-3">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                كود الخصم والتتبع (اتركه فارغاً للتوليد التلقائي):
                                            </label>
                                            <input type="text" name="tracking_code" placeholder="مثال: SARA-AHMED-15" class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 p-2">
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                ملاحظة ترحيبية لصانع المحتوى (اختياري):
                                            </label>
                                            <input type="text" name="review_notes" placeholder="أهلاً بك، تم تفعيل كودك المخصص بنجاح" class="w-full text-xs rounded-lg border border-slate-300 p-2">
                                        </div>

                                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black transition cursor-pointer">
                                            ✓ اعتماد المسوق وتفعيل كود التتبع
                                        </button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" action="{{ route('brand.campaigns.applications.review', [$campaign, $app]) }}" class="bg-white p-4 rounded-xl border border-rose-200 space-y-3">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                سبب الرفض (سيظهر للمسوق في حسابه):
                                            </label>
                                            <textarea name="review_notes" rows="3" placeholder="نعتذر، نبحث حالياً عن صناع محتوى متخصصين في فئة التجميل فقط..." class="w-full text-xs rounded-lg border border-slate-300 p-2"></textarea>
                                        </div>

                                        <button type="submit" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                            ✕ الاعتذار عن قبول الطلب
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-xs text-slate-500 flex items-center justify-between pt-2 border-t border-slate-100">
                                <div>
                                    تمت المراجعة في: <span class="font-bold">{{ $app->reviewed_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                    @if($app->review_notes)
                                        • ملاحظة: <span class="font-normal italic">{{ $app->review_notes }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
