@extends('layouts.app')

@section('content')
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
    <!-- Hero for Marketers -->
    <div class="text-center max-w-3xl mx-auto space-y-5">
        <span class="text-xs font-black text-teal-800 uppercase tracking-widest bg-teal-50 px-3.5 py-1.5 rounded-full border border-teal-200">
            لصناع المحتوى والمؤثرين ومشتري الإعلانات والناشرين
        </span>
        <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-[1.25]">
            حوّل التأثير والخبرة التسويقية إلى
            <span class="text-teal-600 block sm:inline">شراكات وعوائد مبنية على الأداء</span>
        </h1>
        <p class="text-slate-600 text-base sm:text-lg leading-relaxed">
            انضم إلى منظومة نمولي، واختر من بين حملات متنوعة في مصر والخليج العربي تتوافق مع اهتمامات جمهورك، مع تتبع دقيق وإسناد موثوق لكل عملية تحويل.
        </p>
        <div class="pt-2 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ route('register', ['role' => 'MARKETER']) }}"
               class="px-8 py-4 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-black text-base shadow-lg shadow-teal-600/20 transition">
                انضم كصانع محتوى / شريك تسويقي ←
            </a>
            <a href="{{ route('marketplace') }}"
               class="px-6 py-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 font-bold text-base border border-slate-200 transition">
                استكشف فرص الحملات بالسوق
            </a>
        </div>
    </div>

    <!-- The 4 Core Tracks / Specializations -->
    <div>
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900">
                منظومة متخصصة لمختلف مسارات صناعة التأثير والأداء
            </h2>
            <p class="text-slate-600 text-sm mt-2">
                سواء كنت صانع محتوى تبحث عن شراكات نوعية أو مشتري إعلانات تدير حملات Performance، توفر لك نمولي الأدوات والفرص المتوافقة مع أسلوبك.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Track 1: Creators & Influencers -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-7 shadow-xs hover:border-teal-300 transition space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-2xl font-black">
                    📱
                </div>
                <h3 class="text-base font-black text-slate-900">المؤثرون وصناع المحتوى</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    لمنشئي المحتوى على تيك توك، إنستغرام، سناب شات، ويوتيوب. شارك أكواد الخصم الحصرية مع جمهورك واربط عوائدك بالأثر الحقيقي لمحتواك.
                </p>
                <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                    <div>• أكواد خصم حصرية مخصصة</div>
                    <div>• توافق مع هوية واهتمامات المتابعين</div>
                    <div>• حرية إبداعية في تقديم المحتوى</div>
                </div>
            </div>

            <!-- Track 2: UGC Creators -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-7 shadow-xs hover:border-teal-300 transition space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-700 flex items-center justify-center text-2xl font-black">
                    🤳
                </div>
                <h3 class="text-base font-black text-slate-900">صناع محتوى UGC</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    لمنتجي الفيديوهات العفوية وتجارب المنتجات الموجهة للاستخدام الإعلاني مع العلامات التجارية الناشئة والإقليمية.
                </p>
                <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                    <div>• فرص تعاون لإنتاج مقاطع إعلانية</div>
                    <div>• عينات منتجات للتجربة والمراجعة</div>
                    <div>• حقوق استخدام واضحة ومحددة</div>
                </div>
            </div>

            <!-- Track 3: Media Buyers -->
            <div id="media-buyers" class="bg-white rounded-3xl border border-slate-200/90 p-7 shadow-xs hover:border-teal-300 transition space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-black">
                    📈
                </div>
                <h3 class="text-base font-black text-slate-900">مشترو الإعلانات (Media Buyers)</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    لمحترفي الحملات الممولة على ميتا، تيك توك، جوجل، وسناب شات. عروض أداء ونماذج تعويض ترتبط بنتائج المبيعات والتحويلات.
                </p>
                <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                    <div>• تتبع معلمات UTM ومسارات الإسناد</div>
                    <div>• عروض بهوامش ربحية متنوعة</div>
                    <div>• إمكانية التوسع في الحملات الناجحة</div>
                </div>
            </div>

            <!-- Track 4: Affiliates & Publishers -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-7 shadow-xs hover:border-teal-300 transition space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl font-black">
                    🌐
                </div>
                <h3 class="text-base font-black text-slate-900">المسوقون بالعمولة والناشرون</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    لأصحاب منصات المحتوى المتخصص وبوابات المقارنة ورواد توجيه الترافيك الرقمي للاستفادة من الروابط الذكية وفترات الكوكيز المرنة.
                </p>
                <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                    <div>• روابط تتبع ذكية مع دعم ملفات الارتباط</div>
                    <div>• تقارير تفصيلية لمعدلات النقر والتحويل</div>
                    <div>• شراكات مستمرة مع علامات موثوقة</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Marketer Experience Features -->
    <div class="bg-slate-900 rounded-3xl p-8 sm:p-12 text-white">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div class="space-y-6">
                <span class="text-xs font-black text-teal-400 uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">
                    بيئة عمل رقمية متكاملة
                </span>
                <h3 class="text-2xl sm:text-3xl font-black leading-tight text-white">
                    لوحة تحكم فورية لمتابعة التحويلات والمستحقات
                </h3>
                <p class="text-slate-300 text-sm leading-relaxed">
                    تمنحك نمولي رؤية واضحة لأداء حملاتك؛ تتبع النقرات، راقب التحويلات المنجزة، واطلع على العوائد المستحقة بكل شفافية.
                </p>
                <div class="space-y-3 text-sm text-slate-200">
                    <div class="flex items-center gap-3">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>تحديث فوري لبيانات التفاعل وعمليات الشراء المسجلة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>تسوية مستحقاتك بالعملات المحلية للأسواق المستهدفة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-teal-400 font-bold">✓</span>
                        <span>أدوات استخراج الروابط والأكواد بمرونة وسهولة</span>
                    </div>
                </div>
            </div>

            <!-- Mini Dashboard Graphic -->
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 text-xs">
                    <span class="font-bold text-slate-300">مؤشرات الأداء التقديرية</span>
                    <span class="text-emerald-400 font-black">متابعة لحظية</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-white/5 p-3 rounded-xl">
                        <span class="text-[11px] text-slate-400 block">إجمالي النقرات</span>
                        <span class="text-base font-bold text-white mt-1 block">12,420</span>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl">
                        <span class="text-[11px] text-slate-400 block">التحويلات المسجلة</span>
                        <span class="text-base font-bold text-white mt-1 block">534</span>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl">
                        <span class="text-[11px] text-slate-400 block">معدل التحويل</span>
                        <span class="text-base font-bold text-emerald-400 mt-1 block">4.3%</span>
                    </div>
                </div>
                <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-300 flex items-center justify-between">
                    <span>حالة الرصيد المعتمد:</span>
                    <span class="font-black text-white">تسوية منتظمة وفق دورة الحملة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Final CTA -->
    <div class="text-center space-y-4">
        <h3 class="text-2xl font-black text-slate-900">جاهز لاكتشاف حملات تتناسب مع جمهورك؟</h3>
        <p class="text-slate-500 text-sm max-w-md mx-auto">
            أنشئ ملفك كصانع محتوى أو شريك أداء، وتصفح الحملات المتاحة للبدء في بناء الشراكات.
        </p>
        <div class="pt-2">
            <a href="{{ route('register', ['role' => 'MARKETER']) }}"
               class="inline-block px-8 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-sm shadow-md transition">
                أنشئ حسابك كشريك تسويقي الآن ←
            </a>
        </div>
    </div>
</div>
@endsection
