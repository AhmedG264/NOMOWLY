<?php $__env->startSection('content'); ?>
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
    <!-- Hero for Brands -->
    <div class="text-center max-w-3xl mx-auto space-y-5">
        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
            للعلامات التجارية والمتاجر والشركات
        </span>
        <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-[1.25]">
            نمِّ مبيعاتك وأعمالك من خلال
            <span class="text-emerald-600 block sm:inline">قنوات تسويق قابلة للقياس</span>
        </h1>
        <p class="text-slate-600 text-base sm:text-lg leading-relaxed">
            تمنحك نمولي إمكانية بناء شبكة من صناع المحتوى والمؤثرين ومشتري الإعلانات المحترفين عبر أسواق المنطقة، مع ربط ميزانيتك بالنتائج والأداء الفعلي.
        </p>
        <div class="pt-2 flex flex-col sm:flex-row justify-center gap-3">
            <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
               class="px-8 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-base shadow-lg shadow-emerald-600/20 transition">
                أطلق حملتك كعلامة تجارية ←
            </a>
            <a href="<?php echo e(route('how-it-works')); ?>"
               class="px-6 py-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 font-bold text-base border border-slate-200 transition">
                كيفية العمل وآليات الإسناد
            </a>
        </div>
    </div>

    <!-- The Problem vs The Nomowly Solution -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Old Traditional Way -->
        <div class="bg-rose-50/50 rounded-3xl p-8 border border-rose-100 space-y-4">
            <div class="flex items-center gap-2 text-rose-700 font-black text-sm">
                <span>❌</span>
                <span>تحديات الإنفاق الإعلاني التقليدي</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">مخاطر الهدر الإعلاني وصعوبة القياس</h3>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <span class="text-rose-500 font-bold">•</span>
                    <span>ارتفاع مستمر في تكاليف الظهور والنقر (CPMs) دون ضمان مردود تجاري ملموس.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-rose-500 font-bold">•</span>
                    <span>تعقيدات الإسناد وتتبع مسارات التحويل بعد تحديثات الخصوصية الرقمية.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-rose-500 font-bold">•</span>
                    <span>صعوبة إدارة عقود المؤثرين وتوحيد معايير تقييم الأثر المباشر لحملاتهم.</span>
                </li>
            </ul>
        </div>

        <!-- The Nomowly Advantage -->
        <div class="bg-emerald-50/60 rounded-3xl p-8 border border-emerald-200/80 space-y-4">
            <div class="flex items-center gap-2 text-emerald-800 font-black text-sm">
                <span>✅</span>
                <span>حلول نمولي للتسويق بالأداء</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">كفاءة تشغيلية ونمو مبني على الأثر</h3>
            <ul class="space-y-3 text-sm text-slate-700">
                <li class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>ربط العمولات والمستحقات بالنتائج المؤكدة والتحويلات المعتمدة.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>أكواد خصم مخصصة وروابط إسناد ذكية لمتابعة دقيقة لكل شريك تسويقي.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>الوصول السريع إلى صناع محتوى ومؤثرين ومشتري إعلانات مؤهلين في الأسواق المستهدفة.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Integrations & Platforms -->
    <div class="bg-white rounded-3xl border border-slate-200 p-8 sm:p-12 text-center space-y-6">
        <h3 class="text-2xl font-black text-slate-900">تكامل تقني مع أنظمة التجارة ومنصات الأعمال</h3>
        <p class="text-slate-600 text-sm max-w-2xl mx-auto">
            اربط مبيعاتك عبر منصات التجارة الإلكترونية الشائعة أو عبر واجهة برمجية مباشرة (Webhooks & API) لتسجيل الأحداث والتحويلات لحظياً.
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto pt-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                <span class="font-black text-slate-800 text-base">منصة سلة (Salla)</span>
                <span class="text-xs text-emerald-600 mt-1">تكامل مباشر للطلبات</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                <span class="font-black text-slate-800 text-base">منصة زد (Zid)</span>
                <span class="text-xs text-emerald-600 mt-1">مزامنة الأكواد والمبيعات</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                <span class="font-black text-slate-800 text-base">شوبيفاي (Shopify)</span>
                <span class="text-xs text-emerald-600 mt-1">تتبع التحويلات والإسناد</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                <span class="font-black text-slate-800 text-base">واجهات API مخصصة</span>
                <span class="text-xs text-emerald-600 mt-1">ربط مباشر للأنظمة الخاصة</span>
            </div>
        </div>
    </div>

    <!-- Protection & Quality Assurance -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="text-2xl mb-4">🛡️</div>
            <h4 class="font-black text-base text-slate-900 mb-2">تدقيق التحويلات والحد من التكرار</h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                آليات تدقيق للتحقق من سلامة الطلبات وإسنادها للشريك الصحيح ومنع تكرار الاحتساب.
            </p>
        </div>

        <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="text-2xl mb-4">⏳</div>
            <h4 class="font-black text-base text-slate-900 mb-2">فترات تسوية مرنة</h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                إمكانية مراجعة حالة الطلبات والمرتجعات خلال دورة التحقق قبل الاعتماد النهائي للمستحقات.
            </p>
        </div>

        <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="text-2xl mb-4">📊</div>
            <h4 class="font-black text-base text-slate-900 mb-2">تقارير وتحليلات أداء تفصيلية</h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                تعرف على الشركاء الأكثر تحقيقاً للأثر وقنوات الترويج الأفضل عائداً على ميزانية علامتك.
            </p>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-slate-900 rounded-3xl p-8 sm:p-12 text-white text-center space-y-5">
        <h3 class="text-2xl sm:text-4xl font-black">جاهز لتوسيع قنوات نمو علامتك التجارية؟</h3>
        <p class="text-slate-300 text-sm max-w-xl mx-auto">
            سجل علامتك التجارية في نمولي، وحدد أهداف حملتك وشروط العائد، وابدأ باستقبال طلبات الشركاء ومراجعة النتائج.
        </p>
        <div class="pt-2">
            <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
               class="px-8 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm shadow-md transition">
                سجل علامتك التجارية الآن ←
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/pages/for-brands.blade.php ENDPATH**/ ?>