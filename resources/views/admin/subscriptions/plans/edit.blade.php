@extends('layouts.app')

@section('content')
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8" dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.subscriptions.plans.index') }}" class="text-xs font-bold text-purple-700 hover:text-purple-900 transition flex items-center gap-1 mb-2">
                <span>←</span>
                <span>العودة لقائمة خطط الاشتراكات</span>
            </a>
            <h1 class="text-2xl font-black text-slate-900">تعديل خطة الاشتراك: {{ $plan->name_ar }}</h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-0.5">التعديل هنا يُحدث شروط الباقة للمشتركين المستقبليين مع حفظ الشروط التعاقدية للمشتركين الحاليين.</p>
        </div>
    </div>

    <!-- Error Alert -->
    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-xs sm:text-sm text-rose-800 space-y-1">
            <span class="font-black block">يرجى تصحيح الأخطاء التالية:</span>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.subscriptions.plans.update', $plan->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-6 shadow-xs">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">1. البيانات الأساسية للخطة</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم الخطة (بالعربية) *</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $plan->name_ar) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم الخطة (بالإنجليزية)</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $plan->name_en) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 focus:ring-1 focus:ring-purple-600 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دورة الفوترة *</label>
                    <select name="billing_cycle" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none">
                        @foreach ($billingCycles as $cycle)
                            <option value="{{ $cycle->value }}" {{ old('billing_cycle', $plan->billing_cycle->value) === $cycle->value ? 'selected' : '' }}>
                                {{ $cycle->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">مدة الاشتراك بالـ (أيام) *</label>
                    <input type="number" name="duration_in_days" value="{{ old('duration_in_days', $plan->duration_in_days) }}" min="1" max="3650" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الحالة *</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none">
                        <option value="ACTIVE" {{ old('status', $plan->status->value) === 'ACTIVE' ? 'selected' : '' }}>نشطة (متاحة للاشتراك)</option>
                        <option value="INACTIVE" {{ old('status', $plan->status->value) === 'INACTIVE' ? 'selected' : '' }}>معطلة مؤقتاً</option>
                        <option value="ARCHIVED" {{ old('status', $plan->status->value) === 'ARCHIVED' ? 'selected' : '' }}>مؤرشفة</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">وصف الخطة التسويقي</label>
                <textarea name="description_ar" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none">{{ old('description_ar', $plan->description_ar) }}</textarea>
            </div>
        </div>

        <!-- Multi-Currency Pricing -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-6 shadow-xs">
            <div>
                <h2 class="text-base font-black text-slate-900">2. أسعار الخطة لكل عملة</h2>
                <p class="text-xs text-slate-500 mt-0.5">يمكنك تحديث سعر العملة أو تركه فارغاً لتعطيل إتاحة الخطة بتلك العملة.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($currencies as $curr)
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50">
                        <label class="block text-xs font-mono font-bold text-purple-900 mb-1">{{ $curr }}</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" name="prices[{{ $curr }}]" value="{{ old("prices.{$curr}", $priceMap[$curr] ?? '') }}" placeholder="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none font-mono">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Entitlements & Features -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-6 shadow-xs">
            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3">3. الصلاحيات والمزايا والحدود</h2>

            <div class="space-y-4">
                <label class="flex items-start gap-3 p-4 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                    <input type="checkbox" name="allows_premium_campaigns" value="1" {{ old('allows_premium_campaigns', $plan->allows_premium_campaigns) ? 'checked' : '' }} class="mt-1 w-4 h-4 text-purple-600 rounded">
                    <div>
                        <span class="block text-sm font-black text-slate-900">تتيح التقديم على الحملات المميزة والحصرية (Premium Access)</span>
                        <span class="block text-xs text-slate-500 mt-0.5">تمكين المشترك في هذه الباقة من الانضمام للحملات التي تتطلب رتبة اشتراك مميزة.</span>
                    </div>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الحد الأقصى للتقديم على الحملات المميزة شهرياً</label>
                        <input type="number" name="max_premium_campaigns_per_month" value="{{ old('max_premium_campaigns_per_month', $plan->max_premium_campaigns_per_month) }}" min="1" placeholder="اتركه فارغاً إذا كان غير محدود" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none font-mono">
                        <span class="text-[11px] text-slate-400 mt-0.5 block">فارغ = عدد غير محدود من الحملات المميزة</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الحد الأقصى للمشاركات النشطة المتزامنة</label>
                        <input type="number" name="max_active_applications" value="{{ old('max_active_applications', $plan->max_active_applications) }}" min="1" placeholder="اتركه فارغاً إذا كان غير محدود" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none font-mono">
                        <span class="text-[11px] text-slate-400 mt-0.5 block">فارغ = غير مقيد بعدد طلبات محدد</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">مزايا الخطة التسويقية (كل ميزة في سطر مستقل)</label>
                    <textarea name="features" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-purple-600 outline-none font-sans leading-relaxed">{{ old('features', is_array($plan->features) ? implode("\n", $plan->features) : '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition cursor-pointer">
                تحديث وحفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection
