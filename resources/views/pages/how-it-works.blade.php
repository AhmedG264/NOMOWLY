@extends('layouts.app')

@section('content')
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
            منهجية التشغيل والربط
        </span>
        <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
            كيف تعمل منصة نمولي؟
        </h1>
        <p class="text-slate-600 text-base leading-relaxed">
            منظومة متكاملة لربط أهداف العلامات التجارية بشركاء الأداء وصناع المحتوى، مع تتبع دقيق للنتائج وتسوية مالية دورية.
        </p>
    </div>

    <!-- Dual Tab / Flow Section -->
    <div x-data="{ activeTab: 'brands' }" class="space-y-10">
        <!-- Tab Buttons -->
        <div class="flex justify-center">
            <div class="inline-flex p-1.5 bg-slate-100 rounded-2xl border border-slate-200">
                <button @click="activeTab = 'brands'"
                        class="px-6 py-3 rounded-xl text-sm font-black transition cursor-pointer"
                        :class="activeTab === 'brands' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    🏢 رحلة العلامات التجارية والشركات
                </button>
                <button @click="activeTab = 'marketers'"
                        class="px-6 py-3 rounded-xl text-sm font-black transition cursor-pointer"
                        :class="activeTab === 'marketers' ? 'bg-white text-teal-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    🌟 رحلة صناع المحتوى وشركاء الأداء
                </button>
            </div>
        </div>

        <!-- Brands Flow Content -->
        <div x-show="activeTab === 'brands'" x-transition class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Step 1 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        01
                    </div>
                    <h3 class="font-black text-lg text-slate-900">حدد هدفك</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        حدد مؤشرات النجاح المطلوبة لعلامتك (زيادة مبيعات، اكتساب عملاء جدد، محتوى UGC) وعملة الحملة المعتمدة.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        02
                    </div>
                    <h3 class="font-black text-lg text-slate-900">أطلق حملتك</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        صمم شروط العرض والعمولة (نسبة من السلة أو مبلغا ثابتا للتحويل)، وحدد الأسواق الإقليمية المستهدفة.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        03
                    </div>
                    <h3 class="font-black text-lg text-slate-900">اكتشف الشركاء المناسبين</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        استعرض طلبات صناع المحتوى ومشتري الإعلانات، واعتمد الشركاء الذين يملكون الجمهور الأنسب لهوية علامتك.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        04
                    </div>
                    <h3 class="font-black text-lg text-slate-900">تتبع الأداء</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        راقب تدفق الزيارات والتحويلات المسجلة عبر الأكواد وروابط التتبع الذكية لحظياً في لوحة التحكم.
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        05
                    </div>
                    <h3 class="font-black text-lg text-slate-900">تحقق من النتائج</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        راجع العمليات والطلبات المؤكدة خلال دورة التحقق للتحقق من سلامتها قبل الاعتماد النهائي للمستحقات.
                    </p>
                </div>

                <!-- Step 6 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 font-black text-lg flex items-center justify-center">
                        06
                    </div>
                    <h3 class="font-black text-lg text-slate-900">وسع ما ينجح</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        وسع التعاون مع الشركاء الأكثر كفاءة وارفع سقف الحملات التي تحقق عائداً إيجابياً على الاستثمار.
                    </p>
                </div>
            </div>

            <div class="text-center pt-4">
                <a href="{{ route('register', ['role' => 'BRAND']) }}"
                   class="inline-block px-8 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-md transition">
                    ابدأ كعلامة تجارية الآن ←
                </a>
            </div>
        </div>

        <!-- Marketers Flow Content -->
        <div x-show="activeTab === 'marketers'" x-transition class="space-y-8" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Step 1 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        01
                    </div>
                    <h3 class="font-black text-lg text-slate-900">أنشئ ملفك</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        حدد تخصصك (صانع محتوى، مؤثر، UGC، مشتري إعلانات، ناشر) وقنوات التواصل والجمهور المستهدف.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        02
                    </div>
                    <h3 class="font-black text-lg text-slate-900">اكتشف الحملات المناسبة</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        تصفح الحملات المتاحة واختر ما يتوافق مع اهتمامات متابعيك وتوجهات محتواك وقيمك الإبداعية.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        03
                    </div>
                    <h3 class="font-black text-lg text-slate-900">قدّم أو انضم للحملات</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        احصل على أكواد الخصم الحصرية المعتمدة أو روابط الإسناد الذكية الخاصة بك لبدء الترويج.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        04
                    </div>
                    <h3 class="font-black text-lg text-slate-900">أنشئ المحتوى أو نفذ الحملة</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        شارك الكود أو الرابط في محتواك الإبداعي أو أدر حملاتك الإعلانية وفق أفضل الممارسات التسويقية.
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        05
                    </div>
                    <h3 class="font-black text-lg text-slate-900">تابع النتائج</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        شاهد مؤشرات التفاعل، النقرات، وحجم التحويلات والعمولات المستحقة بشفافية في لوحة التحكم.
                    </p>
                </div>

                <!-- Step 6 -->
                <div class="bg-white p-7 rounded-3xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 font-black text-lg flex items-center justify-center">
                        06
                    </div>
                    <h3 class="font-black text-lg text-slate-900">احصل على مستحقاتك</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                        تسوية مالية منتظمة وفق دورة الحملة بعملتك المحلية مباشرة إلى حسابك البنكي أو وسيلة الدفع المعتمدة.
                    </p>
                </div>
            </div>

            <div class="text-center pt-4">
                <a href="{{ route('register', ['role' => 'MARKETER']) }}"
                   class="inline-block px-8 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-sm shadow-md transition">
                    انضم كشريك أداء الآن ←
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
