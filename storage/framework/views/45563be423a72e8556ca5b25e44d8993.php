<?php $__env->startSection('content'); ?>
<div class="space-y-24 py-8">
    <!-- 1. HERO SECTION -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Hero Text -->
            <div class="lg:col-span-7 space-y-7 text-right">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/90 text-emerald-800 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>منصة عربية للتسويق بالأداء واقتصاد صناع المحتوى</span>
                </div>

                <div class="space-y-4">
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 leading-[1.2] tracking-tight">
                        حوّل التأثير إلى
                        <span class="text-transparent bg-clip-text bg-gradient-to-l from-emerald-600 via-teal-600 to-emerald-700">نمو حقيقي</span>
                    </h1>
                    <p class="text-lg sm:text-xl font-bold text-slate-800 leading-snug">
                        نمولي تجمع العلامات التجارية بصناع المحتوى والمسوقين وخبراء التسويق بالأداء لبناء حملات قابلة للقياس وتحقيق نتائج تجارية حقيقية.
                    </p>
                </div>

                <p class="text-base text-slate-600 leading-relaxed font-medium max-w-2xl">
                    من إطلاق حملات مع المؤثرين وصناع المحتوى، إلى التسويق بالعمولة وشراء الإعلانات، تمنح نمولي الشركات وشركاءها التسويقيين منظومة واحدة لاكتشاف الفرص، إدارة الحملات، تتبع النتائج، واحتساب العوائد.
                </p>

                <!-- Trio Action CTAs -->
                <div class="pt-2 flex flex-col sm:flex-row gap-3.5 sm:items-center flex-wrap">
                    <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
                       class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-base shadow-lg shadow-emerald-600/25 transition">
                        <span>أطلق حملتك</span>
                        <span aria-hidden="true">←</span>
                    </a>

                    <a href="<?php echo e(route('marketplace')); ?>"
                       class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 font-bold text-base border-2 border-slate-200 hover:border-slate-300 shadow-xs transition">
                        <span>اكتشف فرص الحملات</span>
                    </a>

                    <a href="<?php echo e(route('register', ['role' => 'MARKETER'])); ?>"
                       class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-base shadow-xs transition">
                        <span>انضم كصانع محتوى</span>
                    </a>
                </div>

                <!-- Strategic Ecosystem Flow Indicators -->
                <div class="pt-6 border-t border-slate-200/80">
                    <div class="text-xs font-bold text-slate-500 mb-3">مسار الحملات ونمو الأعمال في منظومة نمولي:</div>
                    <div class="flex items-center justify-between text-[11px] sm:text-xs font-extrabold text-slate-700 bg-slate-50 p-3 rounded-2xl border border-slate-200/80 overflow-x-auto gap-2">
                        <span class="text-emerald-700 shrink-0">العلامة التجارية</span>
                        <span class="text-slate-400 shrink-0">←</span>
                        <span class="shrink-0">الحملة</span>
                        <span class="text-slate-400 shrink-0">←</span>
                        <span class="text-teal-700 shrink-0">صانع المحتوى</span>
                        <span class="text-slate-400 shrink-0">←</span>
                        <span class="shrink-0">الجمهور</span>
                        <span class="text-slate-400 shrink-0">←</span>
                        <span class="shrink-0">التحويل</span>
                        <span class="text-slate-400 shrink-0">←</span>
                        <span class="text-emerald-700 shrink-0">نتائج تجارية</span>
                    </div>
                </div>
            </div>

            <!-- Hero Visual: Strategic Campaign Engine Mockup -->
            <div class="lg:col-span-5">
                <div class="relative">
                    <div class="absolute -inset-2 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 rounded-3xl blur-xl"></div>
                    <div class="relative bg-white rounded-3xl border border-slate-200/90 shadow-xl p-6 sm:p-7 space-y-6">
                        <!-- Header: Campaign Orchestration -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Campaign Architecture</span>
                                <h3 class="text-base font-black text-slate-900 mt-0.5">منظومة التسويق بالأداء والشراكات</h3>
                            </div>
                            <span class="text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full">
                                تشغيل نشط
                            </span>
                        </div>

                        <!-- 4 Strategic Pillars in Engine -->
                        <div class="space-y-3">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 font-black text-xs flex items-center justify-center">01</span>
                                    <div>
                                        <div class="text-xs font-black text-slate-800">الأهداف التجارية</div>
                                        <div class="text-[11px] text-slate-500">Sales · Leads · UGC · Growth</div>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60">محددة بدقة</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-teal-100 text-teal-800 font-black text-xs flex items-center justify-center">02</span>
                                    <div>
                                        <div class="text-xs font-black text-slate-800">شبكة المؤثرين والمسوقين</div>
                                        <div class="text-[11px] text-slate-500">Creators · Media Buyers · Publishers</div>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-teal-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60">تطابق الجمهور</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-800 font-black text-xs flex items-center justify-center">03</span>
                                    <div>
                                        <div class="text-xs font-black text-slate-800">الإسناد والتتبع المستقل</div>
                                        <div class="text-[11px] text-slate-500">Unique Codes · Smart Links · Webhooks</div>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-indigo-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60">تتبع مباشر</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 font-black text-xs flex items-center justify-center">04</span>
                                    <div>
                                        <div class="text-xs font-black text-slate-800">التسوية المالية بالعملة المحلية</div>
                                        <div class="text-[11px] text-slate-500">SAR · EGP · AED · KWD · QAR · BHD · OMR</div>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-amber-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60">حسب النتائج</span>
                            </div>
                        </div>

                        <!-- Trust Footnote -->
                        <div class="pt-2 text-center text-xs font-bold text-slate-500">
                            منظومة إقليمية تخدم الشركات ورواد التسويق في الأسواق العربية
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. WHO IS NOMOWLY FOR? (7 TARGET PERSONAS) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                منظومة متكاملة لجميع أطراف السوق
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-3 mb-3">
                لمن صُممت منصة نمولي؟
            </h2>
            <p class="text-slate-600 text-base leading-relaxed">
                تربط نمولي مختلف شرائح السوق التسويقي ضمن منصة موحدة تخدم أهداف النمو والربحية المتبادلة.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Persona 1: Enterprise & Brands -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-black">
                    🏢
                </div>
                <h3 class="font-black text-base text-slate-900">العلامات التجارية والشركات</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    شركات إقليمية وعلامات تجارية تبحث عن إطلاق برامج تسويق بالأداء والتوسع في أسواق جديدة بكفاءة تكلفة محكمة.
                </p>
                <div class="text-[11px] font-bold text-emerald-700 pt-1">
                    الهدف: خفض تكلفة الاستحواذ وتوسيع المبيعات
                </div>
            </div>

            <!-- Persona 2: Influencers -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-lg font-black">
                    ⭐
                </div>
                <h3 class="font-black text-base text-slate-900">المؤثرون (Influencers)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    أصحاب الجماهير الكبيرة على شبكات التواصل لبناء شراكات موثوقة مع علامات رائدة تتناسب مع هوية متابعيهم.
                </p>
                <div class="text-[11px] font-bold text-purple-700 pt-1">
                    الهدف: شراكات نوعية متوافقة مع المتابعين
                </div>
            </div>

            <!-- Persona 3: Content Creators -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg font-black">
                    🎬
                </div>
                <h3 class="font-black text-base text-slate-900">صناع المحتوى (Creators)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    مبدعو المحتوى الرقمي المتخصص في المراجعات، التجارب الواقعية، وتثقيف المستهلك لتحويل المحتوى إلى دخل مستدام.
                </p>
                <div class="text-[11px] font-bold text-teal-700 pt-1">
                    الهدف: عوائد مستمرة مبنية على التأثير الحقيقي
                </div>
            </div>

            <!-- Persona 4: UGC Creators -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-pink-50 text-pink-700 flex items-center justify-center text-lg font-black">
                    📱
                </div>
                <h3 class="font-black text-base text-slate-900">صناع محتوى UGC</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    متخصصو إنتاج المقاطع العفوية والمقنعة لاستخدامها في الحملات الإعلانية ومحتوى العلامات التجارية.
                </p>
                <div class="text-[11px] font-bold text-pink-700 pt-1">
                    الهدف: فرص إنتاج مستمرة مع علامات كبرى
                </div>
            </div>

            <!-- Persona 5: Media Buyers -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-lg font-black">
                    📊
                </div>
                <h3 class="font-black text-base text-slate-900">مشترو الإعلانات (Media Buyers)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    محترفو إدارة الحملات الممولة على تيك توك، سناب شات، ميتا، وجوجل الباحثون عن عروض ذات هوامش ربحية عالية.
                </p>
                <div class="text-[11px] font-bold text-blue-700 pt-1">
                    الهدف: عروض أداء عالية التحويل وبيانات واضحة
                </div>
            </div>

            <!-- Persona 6: Affiliate Marketers -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg font-black">
                    🔗
                </div>
                <h3 class="font-black text-base text-slate-900">المسوقون بالعمولة (Affiliates)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    خبراء توجيه الترافيك وتوليد الطلبات عبر استراتيجيات التسويق الرقمي وروابط الإسناد والأكواد المخصصة.
                </p>
                <div class="text-[11px] font-bold text-amber-700 pt-1">
                    الهدف: عمولات تنافسية ومتابعة تحويلات دقيقة
                </div>
            </div>

            <!-- Persona 7: Publishers -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition space-y-3 sm:col-span-2 lg:col-span-2">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg font-black">
                    📰
                </div>
                <h3 class="font-black text-base text-slate-900">الناشرون والمواقع المتخصصة (Publishers)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    منصات المحتوى المتخصص، بوابات المقارنة، والمدونات الرائدة الساعية لتحقيق أقصى عائد من زوارها عبر شراكات نوعية مع علامات تجارية موثوقة.
                </p>
                <div class="text-[11px] font-bold text-indigo-700 pt-1">
                    الهدف: تسييل الزيارات المتخصصة بشراكات تجارية طويلة المدى
                </div>
            </div>
        </div>
    </section>

    <!-- 3. CREATOR ECONOMY SECTION -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-950 to-emerald-950 rounded-3xl p-8 sm:p-14 text-white border border-emerald-900/40 shadow-2xl space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/20 px-3.5 py-1.5 rounded-full border border-emerald-400/30">
                    اقتصاد جديد لصناع المحتوى
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight">
                    لم يعد جمهورك مجرد أرقام
                </h2>
                <p class="text-slate-300 text-base sm:text-lg leading-relaxed font-medium">
                    حوّل تأثيرك ومحتواك إلى شراكات تجارية وفرص دخل مبنية على النتائج.
                </p>
            </div>

            <!-- 6 Visual Creator Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Category 1 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🌟</span>
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">الفئة الأولى</span>
                    </div>
                    <h3 class="text-lg font-black text-white">المؤثرون</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        شارك مع علامات تجارية تبحث عن جمهورك وتأثيرك.
                    </p>
                </div>

                <!-- Category 2 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🎥</span>
                        <span class="text-xs font-bold text-teal-400 bg-teal-500/10 px-2.5 py-1 rounded-full border border-teal-500/20">الفئة الثانية</span>
                    </div>
                    <h3 class="text-lg font-black text-white">صناع المحتوى</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        حوّل المحتوى الذي تصنعه إلى فرص تجارية قابلة للنمو.
                    </p>
                </div>

                <!-- Category 3 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🤳</span>
                        <span class="text-xs font-bold text-pink-400 bg-pink-500/10 px-2.5 py-1 rounded-full border border-pink-500/20">الفئة الثالثة</span>
                    </div>
                    <h3 class="text-lg font-black text-white">UGC Creators</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        أنشئ محتوى للعلامات التجارية واحصل على فرص تعاون جديدة.
                    </p>
                </div>

                <!-- Category 4 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🤝</span>
                        <span class="text-xs font-bold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20">الفئة الرابعة</span>
                    </div>
                    <h3 class="text-lg font-black text-white">المسوقون بالعمولة</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        اكتشف عروضًا وحملات مناسبة لجمهورك وحقق عمولة مقابل النتائج.
                    </p>
                </div>

                <!-- Category 5 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">📈</span>
                        <span class="text-xs font-bold text-blue-400 bg-blue-500/10 px-2.5 py-1 rounded-full border border-blue-500/20">الفئة الخامسة</span>
                    </div>
                    <h3 class="text-lg font-black text-white">Media Buyers</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        أدر حملات Performance ببيانات أوضح ونماذج تعويض مرتبطة بالنتائج.
                    </p>
                </div>

                <!-- Category 6 -->
                <div class="bg-white/5 backdrop-blur-xs p-6 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">📰</span>
                        <span class="text-xs font-bold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">الفئة السادسة</span>
                    </div>
                    <h3 class="text-lg font-black text-white">Publishers</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium">
                        حوّل جمهور المواقع والمنصات المتخصصة إلى قناة نمو للعلامات التجارية.
                    </p>
                </div>
            </div>

            <div class="pt-4 text-center">
                <a href="<?php echo e(route('register', ['role' => 'MARKETER'])); ?>"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-base transition">
                    <span>انضم كصانع محتوى أو شريك تسويقي</span>
                    <span aria-hidden="true">←</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. MAJOR ENTERPRISE & BRAND SECTION -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-8 sm:p-12 space-y-12">
            <div class="max-w-3xl space-y-4">
                <span class="text-xs font-black text-slate-700 uppercase tracking-widest bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200">
                    حلول التسويق بالأداء للشركات
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900">
                    من العلامات الناشئة إلى الشركات الكبرى
                </h2>
                <p class="text-slate-600 text-base sm:text-lg leading-relaxed font-medium">
                    سواء كنت علامة تجارية ناشئة تبحث عن أول عملائك، أو شركة إقليمية تدير ميزانيات تسويقية كبيرة، تساعدك نمولي على بناء برامج تسويق بالأداء وشراكات مع صناع التأثير وفق أهدافك التجارية.
                </p>
            </div>

            <!-- Target Sectors & Industries -->
            <div class="space-y-4">
                <div class="text-xs font-black text-slate-500 uppercase tracking-wider">
                    قطاعات الأعمال المستهدفة للحملات التسويقية:
                </div>
                <div class="flex flex-wrap gap-2.5">
                    <?php
                        $sectors = [
                            'Fashion' => 'الأزياء والموضة',
                            'Beauty' => 'التجميل والعناية',
                            'Technology' => 'التقنية والبرمجيات',
                            'Electronics' => 'الإلكترونيات والأجهزة',
                            'Travel' => 'السياحة والسفر',
                            'Hospitality' => 'الضيافة والفنادق',
                            'Fintech' => 'التقنية المالية والخدمات المصرفية',
                            'Education' => 'التعليم والتدريب',
                            'Automotive' => 'السيارات ومستلزماتها',
                            'Real Estate' => 'التطوير العقاري',
                            'Healthcare' => 'الرعاية الصحية',
                            'SaaS' => 'برمجيات الاشتراكات (SaaS)',
                            'Retail' => 'تجارة التجزئة',
                            'Food & Beverage' => 'الأغذية والمشروبات',
                        ];
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $en => $ar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/80 text-xs font-bold text-slate-700 transition">
                            <span class="text-slate-900"><?php echo e($ar); ?></span>
                            <span class="text-slate-400 font-medium text-[10px] mr-1">(<?php echo e($en); ?>)</span>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            <!-- Business Size Scalability -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-100">
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100">
                    <span class="text-xs font-black text-emerald-700 block">الشركات الناشئة (Startups)</span>
                    <p class="text-xs text-slate-600 mt-1">اختبار قنوات الاستحواذ والتحقق من الجدوى التسويقية بتكلفة مرتبطة بالنتائج.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100">
                    <span class="text-xs font-black text-emerald-700 block">المتاجر الآخذة في النمو</span>
                    <p class="text-xs text-slate-600 mt-1">توسيع المبيعات اليومية عبر ربط متجرك بشبكة من صناع المحتوى الملتزمين بالنتائج.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100">
                    <span class="text-xs font-black text-emerald-700 block">العلامات الإقليمية</span>
                    <p class="text-xs text-slate-600 mt-1">دخول أسواق عربية متعددة في وقت واحد بحملات محلية مخصصة لكل دولة.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100">
                    <span class="text-xs font-black text-emerald-700 block">الشركات الكبرى (Enterprise)</span>
                    <p class="text-xs text-slate-600 mt-1">إدارة برامج أداء مخصصة ومصفوفات إسناد متعددة مع حماية سمعة العلامة التجارية.</p>
                </div>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row gap-3">
                <a href="<?php echo e(route('for-brands')); ?>"
                   class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition">
                    <span>تعرف على حلول العلامات التجارية</span>
                    <span aria-hidden="true">←</span>
                </a>
                <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
                   class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-sm border border-emerald-200 transition">
                    <span>سجل شركتك أو متجرك</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 5. ENTERPRISE MARKETING OBJECTIVES -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                أهداف تسويقية متعددة
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-3 mb-2">
                حدد هدف حملتك، ودع الشركاء يحققونه
            </h2>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                لا تنحصر حملات الأداء في شكل واحد؛ تصمم نمولي برامج تتكامل مع مختلف المراحل في مسار الشراء والتأثير.
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🛍️</span>
                <h3 class="font-black text-sm text-slate-900">زيادة المبيعات</h3>
                <p class="text-[11px] text-slate-500">حملات CPO مباشرة مرتبطة بالطلبات المكتملة</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🎯</span>
                <h3 class="font-black text-sm text-slate-900">اكتساب عملاء جدد</h3>
                <p class="text-[11px] text-slate-500">الوصول لشرائح جماهيرية جديدة عبر المؤثرين</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🚀</span>
                <h3 class="font-black text-sm text-slate-900">إطلاق منتجات جديدة</h3>
                <p class="text-[11px] text-slate-500">تغطية مكثفة وتجارب واقعية للمنتجات عند التدشين</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🌟</span>
                <h3 class="font-black text-sm text-slate-900">حملات المؤثرين</h3>
                <p class="text-[11px] text-slate-500">تأثير اجتماعي موجه مع تتبع دقيق للمردود</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">📱</span>
                <h3 class="font-black text-sm text-slate-900">محتوى UGC أصيل</h3>
                <p class="text-[11px] text-slate-500">محتوى مرئي عالي المصداقية يعزز ثقة المشترين</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🔗</span>
                <h3 class="font-black text-sm text-slate-900">برامج الأفلييت</h3>
                <p class="text-[11px] text-slate-500">بناء شبكة ترويج بالعمولة خاصة بعلامتك</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">📋</span>
                <h3 class="font-black text-sm text-slate-900">توليد العملاء المحتملين</h3>
                <p class="text-[11px] text-slate-500">Lead Generation لقطاعات الخدمات والتعليم والعقارات</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">📣</span>
                <h3 class="font-black text-sm text-slate-900">الوعي بالعلامة</h3>
                <p class="text-[11px] text-slate-500">Brand Awareness مقترن بمؤشرات أداء قابلة للقياس</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">📊</span>
                <h3 class="font-black text-sm text-slate-900">Performance Marketing</h3>
                <p class="text-[11px] text-slate-500">شراء إعلانات رقمية موجهة بأعلى عائد إنفاق</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 text-center space-y-2 hover:border-emerald-300 transition">
                <span class="text-2xl">🌍</span>
                <h3 class="font-black text-sm text-slate-900">التوسع الإقليمي</h3>
                <p class="text-[11px] text-slate-500">اختراق أسواق عربية جديدة دون تكاليف تشغيلية مسبقة</p>
            </div>
        </div>
    </section>

    <!-- 6. CAMPAIGN TYPES OVERVIEW -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-slate-900 text-white rounded-3xl p-8 sm:p-12 space-y-8">
            <div class="max-w-3xl space-y-3">
                <span class="text-xs font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/20 px-3.5 py-1.5 rounded-full border border-emerald-400/30">
                    أنماط الحملات في المنظومة
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">
                    خيارات متعددة لإطلاق الحملات وإدارتها
                </h2>
                <p class="text-slate-300 text-sm leading-relaxed">
                    توفر المنظومة نماذج حملات تناسب استراتيجية علامتك وطبيعة المحتوى المقدم من الشركاء.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-white/5 border border-white/10 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-black text-sm text-white">حملات المبيعات والأفلييت</h3>
                        <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md">متاح حالياً</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        دفع عمولة محددة أو نسبة من قيمة السلة مقابل كل طلب مؤكد يتم شحنه للعميل.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-white/5 border border-white/10 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-black text-sm text-white">حملات المؤثرين وصناع المحتوى</h3>
                        <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md">متاح حالياً</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        أكواد خصم حصرية مخصصة باسم المؤثر مع تتبع لحظي لتفاعلات المتابعين وطلباتهم.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-white/5 border border-white/10 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-black text-sm text-white">حملات محتوى UGC</h3>
                        <span class="text-[10px] font-bold text-teal-400 bg-teal-500/10 px-2 py-0.5 rounded-md">متاح بالطلب</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        تكليف صناع المحتوى بإنتاج مقاطع إعلانية وتجارب استخدام موجهة لحملات المنصات.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-white/5 border border-white/10 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-black text-sm text-white">حملات Lead Generation</h3>
                        <span class="text-[10px] font-bold text-slate-400 bg-white/10 px-2 py-0.5 rounded-md">قريباً</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        احتساب العائد مقابل العملاء المحتملين المؤهلين لقطاعات الخدمات والاشتراكات.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. MARKETPLACE PREVIEW (REAL DATABASE / SHOWCASE DATA WITH STRATEGIC HIERARCHY) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
            <div class="max-w-2xl">
                <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                    سوق الحملات النشطة
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-2">
                    اكتشف حملات تستحق أن تكون جزءًا من استراتيجيتك
                </h2>
                <p class="text-slate-600 text-sm mt-1">
                    تصفح فرص التسويق المتاحة من العلامات التجارية، واختر الحملات التي تتناسب مع جمهورك ومجالك وطريقة عملك.
                </p>
            </div>

            <a href="<?php echo e(route('marketplace')); ?>"
               class="inline-flex items-center gap-2 text-sm font-black text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-4 py-2.5 rounded-xl border border-emerald-200 transition">
                <span>استكشف سوق الحملات الكامل (<?php echo e(count($campaigns)); ?>+)</span>
                <span aria-hidden="true">←</span>
            </a>
        </div>

        <!-- Strategic Campaign Cards (Hierarchy: Brand -> Campaign -> Objective -> Audience -> Channel -> Compensation) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $cId = is_object($campaign) ? ($campaign->slug ?? $campaign->id) : ($campaign['slug'] ?? $campaign['id']);
                    $cTitle = is_object($campaign) ? $campaign->title : $campaign['title'];
                    $cBrand = is_object($campaign) ? ($campaign->brandProfile?->company_name ?? 'علامة تجارية موثقة') : $campaign['brand_name'];
                    $cCategory = is_object($campaign) ? $campaign->category : $campaign['category'];
                    $cDescription = is_object($campaign) ? $campaign->description : $campaign['description'];
                    $cCommission = is_object($campaign) ? $campaign->commission_value : $campaign['commission'];
                    $cCommissionType = is_object($campaign) ? ($campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->label() : $campaign->commission_type) : $campaign['commission_type'];
                    $cMarkets = is_object($campaign) ? (is_array($campaign->target_markets) ? $campaign->target_markets : [$campaign->target_markets]) : $campaign['target_markets'];
                    $cTracking = is_object($campaign) ? (is_array($campaign->tracking_methods) ? $campaign->tracking_methods : [$campaign->tracking_methods]) : $campaign['tracking_methods'];
                ?>
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 hover:shadow-md transition p-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <!-- Top Metadata: Brand Name & Category Badge -->
                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 block uppercase tracking-wider">العلامة التجارية</span>
                                <span class="font-black text-slate-900 text-sm"><?php echo e($cBrand); ?></span>
                            </div>
                            <span class="font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100 text-xs">
                                <?php echo e($cCategory); ?>

                            </span>
                        </div>

                        <!-- Campaign Title & Objective -->
                        <div>
                            <h3 class="font-black text-base text-slate-900 leading-snug">
                                <a href="<?php echo e(route('marketplace.show', $cId)); ?>" class="hover:text-emerald-700 transition">
                                    <?php echo e($cTitle); ?>

                                </a>
                            </h3>
                            <p class="text-slate-600 text-xs leading-relaxed mt-1 line-clamp-2">
                                <?php echo e($cDescription); ?>

                            </p>
                        </div>

                        <!-- Objective & Audience Specification -->
                        <div class="bg-slate-50 rounded-2xl p-3.5 space-y-2 border border-slate-100 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">الهدف التسويقي:</span>
                                <span class="font-bold text-slate-800"><?php echo e($cCommissionType); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">الأسواق المستهدفة:</span>
                                <span class="font-bold text-slate-800"><?php echo e(implode(' · ', (array) $cMarkets)); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">قنوات التتبع:</span>
                                <span class="font-bold text-slate-700"><?php echo e(implode(' · ', (array) $cTracking)); ?></span>
                            </div>
                        </div>

                        <!-- Compensation & Financial Model -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-100 text-xs">
                            <span class="text-slate-600 font-medium">نموذج العائد:</span>
                            <span class="font-black text-emerald-700 text-sm"><?php echo e($cCommission); ?></span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="pt-5">
                        <a href="<?php echo e(route('marketplace.show', $cId)); ?>"
                           class="block text-center w-full py-2.5 rounded-xl bg-slate-900 hover:bg-emerald-600 text-white font-bold text-xs transition">
                            عرض تفاصيل الحملة والتقديم ←
                        </a>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </section>

    <!-- 8. HOW BRANDS GROW WITH NOMOWLY (6 OPERATING STEPS) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                منهجية نمو الشركات
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-3 mb-3">
                كيف تنمو العلامات التجارية مع نمولي؟
            </h2>
            <p class="text-slate-600 text-base leading-relaxed">
                ابدأ بحملة واضحة، اعمل مع الشركاء المناسبين، قِس النتائج، ثم وسّع القنوات والحملات التي تحقق أفضل أداء.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">01</span>
                <h3 class="font-black text-sm text-slate-900">حدد هدفك</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    حدد النتائج المطلوبة (مبيعات، عملاء، محتوى) وحدد ميزانية العائد المناسبة.
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">02</span>
                <h3 class="font-black text-sm text-slate-900">أطلق حملتك</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    أدرج شروط الحملة، المنتجات أو الخدمات، والمواد التسويقية الإرشادية.
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">03</span>
                <h3 class="font-black text-sm text-slate-900">اكتشف الشركاء</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    استقبل طلبات صناع المحتوى والمسوقين واعتمد الشركاء الأنسب لهوية علامتك.
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">04</span>
                <h3 class="font-black text-sm text-slate-900">تتبع الأداء</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    راقب حركة الزيارات والنقرات والتحويلات عبر لوحة تحكم فورية موثوقة.
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">05</span>
                <h3 class="font-black text-sm text-slate-900">تحقق من النتائج</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    راجع التحويلات والطلبات المستلمة قبل اعتماد العمولات والمستحقات.
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">06</span>
                <h3 class="font-black text-sm text-slate-900">وسع ما ينجح</h3>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    ضاعف التعاون مع الشركاء الأكثر كفاءة ووسع نطاق الحملة لأسواق إقليمية إضافية.
                </p>
            </div>
        </div>
    </section>

    <!-- 9. HOW CREATORS GROW WITH NOMOWLY (6 CREATOR STEPS) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-emerald-950/20 rounded-3xl p-8 sm:p-12 border border-emerald-200/70 space-y-10">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-black text-teal-800 uppercase tracking-widest bg-teal-50 px-3.5 py-1.5 rounded-full border border-teal-200">
                    رحلة صناع المحتوى والمسوقين
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900">
                    حوّل جمهورك إلى فرصة نمو
                </h2>
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                    مسار عمل واضح ومنظم يتيح لك التركيز على إنتاج المحتوى وبناء التأثير مع حماية حقوقك المالية.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">01</span>
                    <h3 class="font-black text-sm text-slate-900">أنشئ ملفك</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        حدد تخصصك وقنواتك وجمهورك المستهدف في المنصة.
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">02</span>
                    <h3 class="font-black text-sm text-slate-900">اكتشف الحملات</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        تصفح الحملات المتوافقة مع اهتمامات متابعيك وقيم محتواك.
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">03</span>
                    <h3 class="font-black text-sm text-slate-900">قدّم أو انضم</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        احصل على روابط الإسناد الذكية أو أكواد الخصم الحصرية المعتمدة.
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">04</span>
                    <h3 class="font-black text-sm text-slate-900">أنشئ المحتوى</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        انشر محتواك الإبداعي أو أدر حملاتك الممولة وفق أفضل الممارسات.
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">05</span>
                    <h3 class="font-black text-sm text-slate-900">تابع النتائج</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        شاهد مؤشرات التحويل ومبالغ العمولات المستحقة في لوحة التحكم.
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs space-y-2">
                    <span class="text-xs font-black text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">06</span>
                    <h3 class="font-black text-sm text-slate-900">احصل على مستحقاتك</h3>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        استلم عوائدك بانتظام وبشفافية بالعملة المحلية المحددة لكل حملة.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 10. VALUE PROPOSITION: 4 STRATEGIC PILLARS (REPLACED GENERIC CLAIMS) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                الركائز التشغيلية
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-3 mb-3">
                أسس منظومة نمولي للتسويق بالأداء
            </h2>
            <p class="text-slate-600 text-base leading-relaxed">
                معايير مبنية على الشفافية والكفاءة لتأسيس شراكات تجارية طويلة الأمد.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Pillar 1: Performance -->
            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-black">
                    🎯
                </div>
                <h3 class="font-black text-lg text-slate-900">الأداء (Performance)</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    اربط الإنفاق التسويقي بالنتائج التي يمكن قياسها.
                </p>
            </div>

            <!-- Pillar 2: Creator Partnerships -->
            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-xl font-black">
                    👥
                </div>
                <h3 class="font-black text-lg text-slate-900">شراكات صناع المحتوى (Creator Partnerships)</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    ابنِ شبكة من صناع المحتوى والشركاء الذين يتناسبون مع جمهور علامتك.
                </p>
            </div>

            <!-- Pillar 3: Attribution -->
            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xl font-black">
                    ⚡
                </div>
                <h3 class="font-black text-lg text-slate-900">الإسناد (Attribution)</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    تتبع التحويلات والنتائج من خلال روابط وأكواد وآليات الإسناد المتاحة.
                </p>
            </div>

            <!-- Pillar 4: Scale -->
            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-xs hover:border-emerald-300 transition space-y-3">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl font-black">
                    📈
                </div>
                <h3 class="font-black text-lg text-slate-900">التوسع والنمو (Scale)</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    اكتشف القنوات والشراكات التي تحقق نتائج ووسعها.
                </p>
            </div>
        </div>
    </section>

    <!-- 11. REGIONAL COVERAGE (NEUTRAL ARAB REGION COVERAGE) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-slate-900 text-white rounded-3xl p-8 sm:p-12 space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/20 px-3.5 py-1.5 rounded-full border border-emerald-400/30">
                    تغطية إقليمية متكاملة
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-white">
                    فرص تسويقية عبر الأسواق العربية
                </h2>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    تدعم نمولي الحملات والشراكات في مختلف الأسواق العربية بعملات محلية صريحة دون فرض عملة موحدة أو قيود إقليمية ضيقة.
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇪🇬</span>
                    <h3 class="font-bold text-sm text-white">مصر</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">EGP</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇸🇦</span>
                    <h3 class="font-bold text-sm text-white">السعودية</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">SAR</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇦🇪</span>
                    <h3 class="font-bold text-sm text-white">الإمارات</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">AED</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇰🇼</span>
                    <h3 class="font-bold text-sm text-white">الكويت</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">KWD</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇶🇦</span>
                    <h3 class="font-bold text-sm text-white">قطر</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">QAR</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇧🇭</span>
                    <h3 class="font-bold text-sm text-white">البحرين</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">BHD</span>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/10 space-y-1">
                    <span class="text-xl">🇴🇲</span>
                    <h3 class="font-bold text-sm text-white">عمان</h3>
                    <span class="text-[11px] text-emerald-400 font-bold block">OMR</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 12. FAQ SECTION (PROFESSIONAL ARABIC COPY) -->
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-xs font-black text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200">
                وضوح وشفافية
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-3 mb-2">
                الأسئلة الشائعة حول المنظومة
            </h2>
            <p class="text-slate-500 text-sm">إجابات مهنية حول تشغيل الحملات وإدارة الشراكات ومتابعة الأداء</p>
        </div>

        <div class="space-y-4">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <h3 class="font-black text-base text-slate-900">كيف ترتبط ميزانية الحملة بالنتائج التجارية؟</h3>
                <p class="text-sm text-slate-600 leading-relaxed font-normal">
                    تحدد العلامة التجارية نموذج التعويض المناسب لأهدافها (مثل عمولة نسبة مئوية من السلة أو مبلغا ثابتا لكل تحويل مكتمل). يتم التحقق من صحة التحويلات عبر آليات الإسناد قبل اعتماد المستحقات، مما يضمن كفاءة الإنفاق التسويقي.
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <h3 class="font-black text-base text-slate-900">ما هي آليات التتبع والإسناد المعتمدة؟</h3>
                <p class="text-sm text-slate-600 leading-relaxed font-normal">
                    تعتمد المنصة على مزيج من أكواد الخصم الحصرية المعرفة لكل شريك تسويقي وروابط التتبع الذكية، إلى جانب الربط المباشر عبر Webhooks مع منصات التجارة والأنظمة الداخلية لضمان تسجيل التحويل بدقة.
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <h3 class="font-black text-base text-slate-900">كيف تتم تسوية المستحقات المالية؟</h3>
                <p class="text-sm text-slate-600 leading-relaxed font-normal">
                    تتم التسويات المالية وفق عملة الحملة المحددة بشكل مباشر دون رسوم خفية أو تحويلات قسرية، مع إصدار كشوف حساب تفصيلية توضح كل عملية تحويل وحالتها التشغيلية.
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                <h3 class="font-black text-base text-slate-900">هل يمكن للشركات الكبرى إدارة برامج خاصة؟</h3>
                <p class="text-sm text-slate-600 leading-relaxed font-normal">
                    نعم، تتيح نمولي حلولاً مخصصة للشركات والعلامات الإقليمية الكبرى لإطلاق حملات مغلقة أو خاصة، وتحديد شروط اعتماد دقيقة للشركاء المؤهلين للتوافق مع معايير العلامة التجارية.
                </p>
            </div>
        </div>
    </section>

    <!-- 13. FINAL HIGH-IMPACT ENTERPRISE CTA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-emerald-700 via-teal-800 to-slate-900 rounded-3xl p-8 sm:p-14 text-white text-center relative overflow-hidden shadow-xl">
            <div class="relative z-10 max-w-3xl mx-auto space-y-6">
                <h2 class="text-3xl sm:text-5xl font-black text-white leading-tight">
                    جاهز لبناء شراكات تسويقية تحقق نمواً ملموساً؟
                </h2>
                <p class="text-slate-200 text-base sm:text-lg leading-relaxed font-medium">
                    انضم إلى منظومة نمولي اليوم. أطلق حملتك كعلامة تجارية، أو ابدأ اكتشاف الحملات وتحقيق العوائد كصانع محتوى وشريك أداء.
                </p>

                <div class="pt-2 flex flex-col sm:flex-row justify-center gap-4">
                    <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
                       class="px-8 py-4 rounded-2xl bg-white text-slate-950 hover:bg-slate-100 font-black text-base shadow-md transition">
                        أطلق حملتك كعلامة تجارية
                    </a>
                    <a href="<?php echo e(route('register', ['role' => 'MARKETER'])); ?>"
                       class="px-8 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-base border border-emerald-400/40 shadow-md transition">
                        انضم كصانع محتوى أو شريك أداء
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/welcome.blade.php ENDPATH**/ ?>