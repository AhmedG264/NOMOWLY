<?php $__env->startSection('content'); ?>
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
            نموذج التعويض والأسعار
        </span>
        <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
            لا اشتراكات شهرية، ادفع فقط عند تحقيق مبيعات فعلية
        </h1>
        <p class="text-slate-600 text-base leading-relaxed">
            نموذج شراكة يربط الإنفاق التسويقي بالنتائج القابلة للقياس والتحويلات المعتمدة.
        </p>
    </div>

    <!-- Pricing Plans Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
        <!-- Standard Performance Plan -->
        <div class="bg-white rounded-3xl border-2 border-emerald-500/80 p-8 sm:p-10 shadow-lg relative flex flex-col justify-between">
            <div class="absolute -top-3.5 right-8 bg-emerald-600 text-white text-xs font-black px-3.5 py-1 rounded-full shadow-xs">
                نموذج التسويق بالأداء المباشر
            </div>

            <div class="space-y-6">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">نموذج الأداء (Performance Plan)</h3>
                    <p class="text-xs text-slate-500 mt-1">للعلامات التجارية والمتاجر الساعية لربط الميزانية بالمبيعات</p>
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="text-4xl sm:text-5xl font-black text-slate-900">0</span>
                    <span class="text-slate-500 font-bold text-sm">رسوم اشتراك ثابتة</span>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed font-medium">
                    تسجيل علامتك التجارية وإطلاق الحملة متاح دون اشتراكات مسبقة. تُحتسب العمولات فقط على التحويلات المكتملة والمستلمة.
                </p>

                <div class="pt-4 border-t border-slate-100 space-y-3 text-xs text-slate-700 font-bold">
                    <div class="flex items-center gap-2.5">
                        <span class="text-emerald-600">✓</span>
                        <span>إطلاق حملات تسويقية متعددة وفق أهداف مختلفة</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-emerald-600">✓</span>
                        <span>إتاحة الحملة للشركاء المؤهلين وصناع المحتوى</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-emerald-600">✓</span>
                        <span>توليد أكواد الخصم وروابط الإسناد الذكية</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-emerald-600">✓</span>
                        <span>إمكانية الربط مع منصات التجارة عبر Webhooks & API</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-emerald-600">✓</span>
                        <span>دورة فحص ومراجعة لحالات الإلغاء والمرتجعات</span>
                    </div>
                </div>
            </div>

            <div class="pt-8">
                <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
                   class="block text-center w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-md transition">
                    أطلق حملتك كعلامة تجارية ←
                </a>
            </div>
        </div>

        <!-- Enterprise / Scale Plan -->
        <div class="bg-slate-900 text-white rounded-3xl p-8 sm:p-10 shadow-lg relative flex flex-col justify-between">
            <div class="space-y-6">
                <div>
                    <h3 class="text-2xl font-black text-white">حلول المؤسسات والشركات الكبرى (Enterprise)</h3>
                    <p class="text-xs text-slate-400 mt-1">للشركات الإقليمية والعلامات التي تدير برامج شراكة واسعة النطاق</p>
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-white">برامج مخصصة</span>
                    <span class="text-slate-400 text-xs">وفق متطلبات المؤسسة</span>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed font-normal">
                    إدارة متقدمة للحملات وبرامج المؤثرين مع خيارات إسناد خاصة وفلاتر اعتماد صارمة للشركاء لحماية سمعة العلامة التجارية.
                </p>

                <div class="pt-4 border-t border-white/10 space-y-3 text-xs text-slate-300 font-medium">
                    <div class="flex items-center gap-2.5">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>إدارة مخصصة للبرامج والعلاقات مع الشركاء</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>تنسيق حملات المؤثرين وصناع المحتوى الحصريين</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>تكامل تقني مخصص للأنظمة المؤسسية</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>شروط تسوية مرنة وتقارير إسناد متقدمة</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>قنوات تواصل ودعم مباشر مخصصة</span>
                    </div>
                </div>
            </div>

            <div class="pt-8">
                <a href="mailto:hello@nomowly.com"
                   class="block text-center w-full py-3.5 rounded-xl bg-white text-slate-900 hover:bg-slate-100 font-black text-sm shadow-md transition">
                    تواصل معنا لحلول المؤسسات
                </a>
            </div>
        </div>
    </div>

    <!-- For Marketers: Performance-Driven -->
    <div class="bg-teal-50 border border-teal-200/80 rounded-3xl p-8 max-w-4xl mx-auto text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white text-teal-800 rounded-full text-xs font-black border border-teal-200">
            <span>🚀</span>
            <span>لصناع المحتوى والمسوقين ومشتري الإعلانات</span>
        </div>
        <h3 class="text-xl font-black text-slate-900">انضمام مباشر ومجاني لكافة مسارات الشركاء</h3>
        <p class="text-slate-600 text-xs sm:text-sm max-w-xl mx-auto leading-relaxed">
            لا توجد أي رسوم تسجيل أو اشتراك على الشركاء التسويقيين. تحصل على العوائد المحددة لكل حملة بعد مراجعة واعتماد التحويلات المحققة.
        </p>
        <div class="pt-2">
            <a href="<?php echo e(route('register', ['role' => 'MARKETER'])); ?>"
               class="inline-block px-6 py-2.5 bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs rounded-xl transition">
                سجل حسابك كشريك تسويقي الآن
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/pages/pricing.blade.php ENDPATH**/ ?>