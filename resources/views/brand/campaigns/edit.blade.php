@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('brand.campaigns.index') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-700 transition">
                ← العودة إلى قائمة الحملات
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">تعديل الحملة: {{ $campaign->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('brand.campaigns.terms', $campaign) }}" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-black transition flex items-center gap-1.5">
                <span>📜</span>
                <span>سجل إصدارات الشروط (v{{ $campaign->currentTermsVersion?->version_number ?? 1 }})</span>
            </a>
            <a href="{{ route('marketplace.show', $campaign->slug) }}" target="_blank" class="text-xs font-bold text-emerald-700 hover:underline px-3 py-2 bg-emerald-50 rounded-xl border border-emerald-200">
                معاينة في السوق ↗
            </a>
        </div>
    </div>

    @if ($campaign->isTermsLocked())
        <div class="rounded-3xl bg-amber-50 border-2 border-amber-300 p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <span class="text-2xl p-2 bg-amber-200/60 rounded-2xl">🔒</span>
                <div>
                    <h3 class="text-sm font-black text-amber-900">تم تثبيت الشروط التعاقدية والمالية للحملة (Terms Locked)</h3>
                    <p class="text-xs text-amber-800/90 mt-1 leading-relaxed">
                        نظراً لبدء انضمام صناع المحتوى أو تسجيل نقرات ترويجية، تم تثبيت شروط العمولات وفترة الكوكيز لمنع أي تعديل تعسفي بأثر رجعي وحماية حقوق الشركاء.
                    </p>
                </div>
            </div>
            <a href="{{ route('brand.campaigns.change-requests.create', $campaign) }}" class="shrink-0 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black shadow-sm transition flex items-center gap-2">
                <span>📝</span>
                <span>تقديم طلب تغيير شروط</span>
            </a>
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

    <form method="POST" action="{{ route('brand.campaigns.update', $campaign) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Status Controller -->
        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-xs font-bold text-slate-700">حالة الحملة الحالية:</div>
                <div class="text-xs text-slate-500 mt-0.5">يمكنك إيقاف الحملة مؤقتاً أو أرشفة العرض.</div>
            </div>
            <div class="w-48">
                <select name="status" class="w-full text-xs font-bold rounded-xl border border-slate-300 p-2.5 bg-white">
                    @foreach(\App\Enums\CampaignStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ old('status', $campaign->status->value) === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 1. Basic Info -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">1. البيانات الأساسية للحملة</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان الحملة التسويقية *</label>
                    <input type="text" name="title" value="{{ old('title', $campaign->title) }}" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رابط المتجر أو صفحة الهبوط المباشرة (Destination URL)</label>
                    <input type="url" name="destination_url" value="{{ old('destination_url', $campaign->destination_url) }}" placeholder="https://yourstore.com/summer-collection" class="w-full text-sm font-mono rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-slate-400 mt-1">الرابط الذي سيتم توجيه العملاء إليه بعد تسجيل النقرة التتبعية وحفظ كوكيز الإسناد.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">تصنيف الحملة *</label>
                        <select name="category" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @foreach(['أزياء وموضة', 'عطور وتجميل', 'إلكترونيات', 'صحة ورشاقة', 'مستلزمات منزل', 'أطفال وعائلة', 'خدمات وتطبيقات'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', $campaign->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شارة تسويقية (اختياري)</label>
                        <input type="text" name="badge" value="{{ old('badge', $campaign->badge) }}" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الوصف المختصر *</label>
                    <textarea name="description" rows="2" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $campaign->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ملخص الحملة الكامل (Campaign Brief) *</label>
                    <textarea name="brief" rows="4" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('brief', $campaign->brief) }}</textarea>
                </div>
            </div>
        </div>

        <!-- 2. Performance Terms & Compensation -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-base font-black text-slate-900">2. هيكل العمولات وشروط الأداء</h2>
                @if($campaign->isTermsLocked())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-black">
                        <span>🔒</span>
                        <span>شروط مثبتة وغير قابلة للتعديل المباشر</span>
                    </span>
                @endif
            </div>

            <div class="space-y-4">
                @if($campaign->isTermsLocked())
                    <!-- Locked Display with Hidden Inputs -->
                    <input type="hidden" name="commission_type" value="{{ $campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->value : $campaign->commission_type }}">
                    <input type="hidden" name="commission_value" value="{{ $campaign->commission_value }}">
                    <input type="hidden" name="cookie_duration_days" value="{{ $campaign->cookie_duration_days }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <span class="block text-xs font-bold text-slate-500 mb-1">نوع نموذج التعويض (مثبت)</span>
                            <div class="text-sm font-black text-slate-800 flex items-center justify-between">
                                <span>{{ $campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->label() : $campaign->commission_type }}</span>
                                <span class="text-slate-400 text-xs">🔒 قيد تعاقدي</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <span class="block text-xs font-bold text-slate-500 mb-1">قيمة العمولة أو المقابل (مثبت)</span>
                            <div class="text-sm font-black text-emerald-700 flex items-center justify-between">
                                <span>{{ $campaign->commission_value }}</span>
                                <span class="text-slate-400 text-xs">🔒 قيد تعاقدي</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <span class="block text-xs font-bold text-slate-500 mb-1">مدة الكوكيز (الأيام) (مثبت)</span>
                            <div class="text-sm font-black text-slate-800 flex items-center justify-between">
                                <span>{{ $campaign->cookie_duration_days }} يوماً</span>
                                <span class="text-slate-400 text-xs">🔒</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">سلة الشراء التقديرية (تقدير المتجر)</label>
                            <input type="text" name="avg_basket" value="{{ old('avg_basket', $campaign->avg_basket) }}" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="تقدير إرشادي من المتجر">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">معدل التحويل التقديري (تقدير المتجر)</label>
                            <input type="text" name="conversion_rate" value="{{ old('conversion_rate', $campaign->conversion_rate) }}" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="تقدير إرشادي من المتجر">
                        </div>
                    </div>
                @else
                    <!-- Unlocked / Editable Form -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">نوع نموذج التعويض *</label>
                            <select name="commission_type" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @foreach(\App\Enums\CampaignCommissionType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('commission_type', $campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->value : $campaign->commission_type) === $type->value ? 'selected' : '' }}>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">قيمة العمولة أو المقابل *</label>
                            <input type="text" name="commission_value" value="{{ old('commission_value', $campaign->commission_value) }}" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">مدة الكوكيز بالأيام *</label>
                            <input type="number" name="cookie_duration_days" value="{{ old('cookie_duration_days', $campaign->cookie_duration_days) }}" min="1" max="365" required class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">سلة الشراء التقديرية (تقدير المتجر)</label>
                            <input type="text" name="avg_basket" value="{{ old('avg_basket', $campaign->avg_basket) }}" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="تقدير إرشادي من المتجر">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">معدل التحويل التقديري (تقدير المتجر)</label>
                            <input type="text" name="conversion_rate" value="{{ old('conversion_rate', $campaign->conversion_rate) }}" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="تقدير إرشادي من المتجر">
                        </div>
                    </div>
                @endif

                <!-- Tracking Methods -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">طرق التتبع المعتمدة *</label>
                    @php
                        $currentTracking = is_array($campaign->tracking_methods) ? $campaign->tracking_methods : [];
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="كود خصم حصري" {{ in_array('كود خصم حصري', old('tracking_methods', $currentTracking)) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">كود خصم حصري</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="رابط تتبع ذكي" {{ in_array('رابط تتبع ذكي', old('tracking_methods', $currentTracking)) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">رابط تتبع ذكي</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tracking_methods[]" value="إحالة مباشرة" {{ in_array('إحالة مباشرة', old('tracking_methods', $currentTracking)) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">إحالة مباشرة</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Target Markets & Currencies -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">3. النطاق الجغرافي والعملات</h2>

            @php
                $currentMarkets = is_array($campaign->target_markets) ? $campaign->target_markets : [];
                $currentCurrencies = is_array($campaign->currencies) ? $campaign->currencies : [];
            @endphp
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">الأسواق المستهدفة *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach(['السعودية', 'مصر', 'الإمارات', 'الكويت', 'قطر', 'عمان', 'البحرين'] as $country)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="target_markets[]" value="{{ $country }}" {{ in_array($country, old('target_markets', $currentMarkets)) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-slate-800">{{ $country }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">العملات المعتمدة *</label>
                    @if($campaign->isTermsLocked())
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($currentCurrencies as $curr)
                                <input type="hidden" name="currencies[]" value="{{ $curr }}">
                                <span class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-800 flex items-center gap-1.5">
                                    <span>🔒</span>
                                    <span>{{ $curr }}</span>
                                </span>
                            @endforeach
                            <span class="text-xs text-amber-700 font-bold mr-2">(مثبتة ضمن الشروط التعاقدية)</span>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach(['SAR', 'EGP', 'AED', 'KWD', 'QAR', 'OMR', 'BHD'] as $curr)
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="currencies[]" value="{{ $curr }}" {{ in_array($curr, old('currencies', $currentCurrencies)) ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-slate-800">{{ $curr }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Guidelines & Deliverables -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">4. متطلبات المحتوى وشروط الامتثال</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">متطلبات المحتوى والتسليمات</label>
                    <textarea name="requirements" rows="3" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('requirements', $campaign->requirements) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">إرشادات الامتثال والمحظورات</label>
                    <textarea name="brand_guidelines" rows="3" class="w-full text-sm rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('brand_guidelines', $campaign->brand_guidelines) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('brand.campaigns.index') }}" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                حفظ التعديلات ←
            </button>
        </div>
    </form>
</div>
@endsection
