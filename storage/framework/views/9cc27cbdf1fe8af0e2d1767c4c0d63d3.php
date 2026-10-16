<?php $__env->startSection('content'); ?>
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
    <!-- Breadcrumb & Status Alerts -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <a href="<?php echo e(route('home')); ?>" class="hover:text-emerald-700 transition">الرئيسية</a>
            <span>/</span>
            <a href="<?php echo e(route('marketplace')); ?>" class="hover:text-emerald-700 transition">سوق الحملات</a>
            <span>/</span>
            <span class="text-slate-900"><?php echo e($campaign->title); ?></span>
        </nav>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-black border <?php echo e($campaign->status->badgeClasses()); ?>">
                <?php echo e($campaign->status->label()); ?>

            </span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->badge): ?>
                <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                    ★ <?php echo e($campaign->badge); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 text-lg">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 font-bold space-y-1">
            <div class="font-black">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="list-disc list-inside text-xs font-normal">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($error); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Main Campaign Header Hero -->
    <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl border border-emerald-900/50">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-8 space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-400/30">
                        <?php echo e($campaign->category); ?>

                    </span>
                    <span class="text-slate-400 text-xs font-bold">العلامة التجارية:</span>
                    <span class="text-white font-black text-sm bg-white/10 px-3 py-0.5 rounded-lg border border-white/10">
                        <?php echo e($campaign->brandProfile?->company_name ?? 'علامة تجارية موثقة'); ?>

                    </span>
                </div>

                <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">
                    <?php echo e($campaign->title); ?>

                </h1>

                <p class="text-slate-300 text-sm sm:text-base leading-relaxed font-normal">
                    <?php echo e($campaign->description); ?>

                </p>

                <!-- Markets & Currencies -->
                <div class="flex flex-wrap items-center gap-4 pt-2 text-xs">
                    <div class="flex items-center gap-1.5 text-slate-300">
                        <span class="text-emerald-400 font-bold">📍 الأسواق المستهدفة:</span>
                        <span class="font-bold text-white">
                            <?php echo e(is_array($campaign->target_markets) ? implode(' • ', $campaign->target_markets) : $campaign->target_markets); ?>

                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-300">
                        <span class="text-teal-400 font-bold">💳 العملات المعتمدة:</span>
                        <span class="font-bold text-white">
                            <?php echo e(is_array($campaign->currencies) ? implode(' • ', $campaign->currencies) : $campaign->currencies); ?>

                        </span>
                    </div>
                </div>
            </div>

            <!-- Highlights KPI Card -->
            <div class="lg:col-span-4 bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 space-y-4">
                <div class="border-b border-white/15 pb-4">
                    <div class="text-xs text-slate-400 font-medium">قيمة العمولة / المقابل للأداء</div>
                    <div class="text-3xl font-black text-emerald-400 mt-1"><?php echo e($campaign->commission_value); ?></div>
                    <div class="text-xs text-slate-300 mt-1">
                        <?php echo e($campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->label() : $campaign->commission_type); ?>

                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="text-slate-400">فترة التتبع (الكوكيز)</div>
                        <div class="font-bold text-white text-sm mt-0.5"><?php echo e($campaign->cookie_duration_days); ?> يوماً</div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->avg_basket): ?>
                        <div>
                            <div class="text-slate-400">سلة الشراء التقديرية (تقدير المتجر)</div>
                            <div class="font-bold text-white text-sm mt-0.5"><?php echo e($campaign->avg_basket); ?></div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->conversion_rate): ?>
                        <div>
                            <div class="text-slate-400">معدل التحويل التقديري (تقدير المتجر)</div>
                            <div class="font-bold text-white text-sm mt-0.5"><?php echo e($campaign->conversion_rate); ?></div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div>
                        <div class="text-slate-400">طرق التتبع</div>
                        <div class="font-bold text-white text-xs mt-0.5">
                            <?php echo e(is_array($campaign->tracking_methods) ? implode(' + ', $campaign->tracking_methods) : $campaign->tracking_methods); ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content & Application Section (Two Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Brief, Requirements, Guidelines (8 Cols) -->
        <div class="lg:col-span-8 space-y-8">
            <!-- Campaign Brief -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
                <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="text-emerald-600">📋</span>
                    <span>ملخص الحملة والجمهور المستهدف (Campaign Brief)</span>
                </h2>
                <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line font-normal">
                    <?php echo e($campaign->brief); ?>

                </div>
            </div>

            <!-- Content Requirements -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->requirements): ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="text-teal-600">🎬</span>
                        <span>متطلبات المحتوى والتسليمات (Content Deliverables)</span>
                    </h2>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line font-normal">
                        <?php echo e($campaign->requirements); ?>

                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Brand Guidelines -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($campaign->brand_guidelines): ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="text-amber-600">⚠️</span>
                        <span>إرشادات الامتثال وشروط العلامة التجارية (Compliance Guidelines)</span>
                    </h2>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line font-normal">
                        <?php echo e($campaign->brand_guidelines); ?>

                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Commission & Payout Policy -->
            <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200 space-y-4">
                <h3 class="text-lg font-black text-slate-900">سياسة التحقق والمدفوعات في نمولي</h3>
                <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold">✓</span>
                        <span>يتم احتساب العمولات بنظام Attribution آلي ودقيق عبر أكواد التتبع المخصصة أو الروابط الذكية.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold">✓</span>
                        <span>فترة احتجاز المبيعات للتأكد من عدم الإرجاع والإلغاء هي 14 يوماً وفق معايير التجارة الإلكترونية الإقليمية.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold">✓</span>
                        <span>تحويل الأرباح متاح بالعملات المحلية للبلدان المدعومة بدون خصومات إضافية على حساب المسوق.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right: Action / Application Sidebar (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->isMarketer()): ?>
                    <!-- MARKETER LOGGED IN -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($myApplication): ?>
                        <!-- APPLICATION EXISTS -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($myApplication->isApproved()): ?>
                            <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-white rounded-3xl p-6 border-2 border-emerald-300 shadow-sm space-y-5">
                                <div class="flex items-center gap-2 text-emerald-800 font-black text-sm">
                                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span>
                                    <span>أنت شريك معتمد في هذه الحملة!</span>
                                </div>

                                <p class="text-xs text-slate-600 leading-relaxed">
                                    تهانينا! وافقت العلامة التجارية على انضمامك. استخدم بيانات التتبع أدناه لبدء الترويج واحتساب أرباحك:
                                </p>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($myApplication->tracking_code): ?>
                                    <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-xs space-y-2" x-data="{ copied: false }">
                                        <div class="text-[11px] font-bold text-slate-500">كود الخصم والتتبع الخاص بك:</div>
                                        <div class="flex items-center justify-between gap-2 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200">
                                            <span class="font-mono text-base font-black text-emerald-900 tracking-wider" id="couponCode">
                                                <?php echo e($myApplication->tracking_code); ?>

                                            </span>
                                            <button type="button"
                                                    @click="navigator.clipboard.writeText('<?php echo e($myApplication->tracking_code); ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="text-xs px-3 py-1 rounded-lg font-bold transition cursor-pointer"
                                                    :class="copied ? 'bg-emerald-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'">
                                                <span x-show="!copied">نسخ</span>
                                                <span x-show="copied" style="display: none;">تم النسخ ✓</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($myApplication->tracking_link): ?>
                                    <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-xs space-y-2" x-data="{ copied: false }">
                                        <div class="text-[11px] font-bold text-slate-500">رابط الإحالة الذكي:</div>
                                        <div class="flex items-center justify-between gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                                            <span class="font-mono text-xs text-slate-800 truncate" id="refLink">
                                                <?php echo e($myApplication->tracking_link); ?>

                                            </span>
                                            <button type="button"
                                                    @click="navigator.clipboard.writeText('<?php echo e($myApplication->tracking_link); ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="text-xs px-3 py-1 rounded-lg font-bold transition cursor-pointer"
                                                    :class="copied ? 'bg-emerald-700 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'">
                                                <span x-show="!copied">نسخ</span>
                                                <span x-show="copied" style="display: none;">تم النسخ ✓</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <div class="text-[11px] text-slate-500 pt-2 border-t border-emerald-200/60">
                                    تاريخ الاعتماد: <?php echo e($myApplication->reviewed_at?->format('Y-m-d') ?? $myApplication->updated_at->format('Y-m-d')); ?>

                                </div>
                            </div>
                        <?php elseif($myApplication->isPending()): ?>
                            <div class="bg-amber-50 rounded-3xl p-6 border-2 border-amber-300 shadow-sm space-y-4">
                                <div class="flex items-center gap-2 text-amber-900 font-black text-sm">
                                    <span>⏳</span>
                                    <span>طلبك قيد المراجعة</span>
                                </div>
                                <p class="text-xs text-slate-700 leading-relaxed">
                                    تم استلام طلب انضمامك إلى حملة «<?php echo e($campaign->title); ?>» بتاريخ <?php echo e($myApplication->applied_at->format('Y-m-d')); ?>. ستقوم العلامة التجارية بفحص ملفك وتفعيل كودك المخصص قريباً.
                                </p>
                                <a href="<?php echo e(route('marketer.campaigns.index')); ?>" class="block text-center text-xs font-black text-amber-900 bg-amber-200/60 hover:bg-amber-200 py-2.5 rounded-xl transition">
                                    متابعة جميع طلباتي
                                </a>
                            </div>
                        <?php elseif($myApplication->isRejected()): ?>
                            <div class="bg-rose-50 rounded-3xl p-6 border-2 border-rose-300 shadow-sm space-y-4">
                                <div class="flex items-center gap-2 text-rose-900 font-black text-sm">
                                    <span>✕</span>
                                    <span>تم الاعتذار عن قبول الطلب</span>
                                </div>
                                <p class="text-xs text-slate-700 leading-relaxed">
                                    <?php echo e($myApplication->review_notes ?? 'لم تتطابق المعايير مع متطلبات هذه الحملة حالياً.'); ?>

                                </p>
                                <a href="<?php echo e(route('marketplace')); ?>" class="block text-center text-xs font-black text-rose-900 bg-rose-200/60 hover:bg-rose-200 py-2.5 rounded-xl transition">
                                    تصفح حملات أخرى
                                </a>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php else: ?>
                        <!-- APPLICATION FORM -->
                        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">التقديم على الحملة</h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    أرسل مقترحك وخطة النشر للعلامة التجارية للحصول على كود الخصم ورابط التتبع.
                                </p>
                            </div>

                            <form method="POST" action="<?php echo e(route('marketer.campaigns.apply', $campaign)); ?>" class="space-y-4">
                                <?php echo csrf_field(); ?>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        كيف تخطط للترويج للحملة؟ (Pitch / Proposal)
                                    </label>
                                    <textarea name="pitch_note" rows="3" class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="مثال: سأنشر فيديو ريلز يوضح تجربة استخدام المنتج مع مراجعة حقيقية لجمهوري في السعودية ومصر..."></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        روابط قنواتك التسويقية أو حساباتك
                                    </label>
                                    <textarea name="channels" rows="2" class="w-full text-xs font-mono rounded-xl border border-slate-300 p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="https://tiktok.com/@youraccount&#10;https://instagram.com/youraccount"></textarea>
                                    <span class="text-[10px] text-slate-400 mt-1 block">ضع رابطاً في كل سطر</span>
                                </div>

                                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-sm transition cursor-pointer">
                                    إرسال طلب الانضمام للحملة
                                </button>
                            </form>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php elseif(auth()->user()->isBrand()): ?>
                    <!-- BRAND LOGGED IN -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->brandProfile?->id === $campaign->brand_profile_id): ?>
                        <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-sm space-y-4 border border-slate-800">
                            <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs">
                                <span>🏬</span>
                                <span>أنت صاحب هذه الحملة</span>
                            </div>
                            <h3 class="text-base font-black">إدارة الحملة والمتقدمين</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                يمكنك مراجعة طلبات صناع المحتوى وتعديل تفاصيل الحملة وتغيير حالتها في أي وقت.
                            </p>
                            <div class="space-y-2 pt-2">
                                <a href="<?php echo e(route('brand.campaigns.applications', $campaign)); ?>" class="block w-full py-2.5 text-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition">
                                    مراجعة طلبات الانضمام (<?php echo e($campaign->applications->count()); ?>)
                                </a>
                                <a href="<?php echo e(route('brand.campaigns.edit', $campaign)); ?>" class="block w-full py-2.5 text-center bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition border border-white/20">
                                    تعديل بيانات الحملة
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-3 text-center">
                            <span class="text-2xl">🏬</span>
                            <h4 class="text-sm font-black text-slate-900">أنت مسجل كعلامة تجارية</h4>
                            <p class="text-xs text-slate-600">
                                التقديم على الحملات مخصص لحسابات المسوقين وصناع المحتوى. يمكنك استعراض الحملة لمقارنة النماذج التسويقية.
                            </p>
                            <a href="<?php echo e(route('brand.campaigns.create')); ?>" class="inline-block mt-2 text-xs font-bold text-emerald-700 hover:underline">
                                إنشاء حملة خاصة بمتجرك ←
                            </a>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php elseif(auth()->user()->isSuperAdmin()): ?>
                    <!-- SUPER ADMIN -->
                    <div class="bg-purple-900 text-white rounded-3xl p-6 shadow-sm space-y-4 border border-purple-800">
                        <div class="flex items-center gap-2 text-purple-300 font-bold text-xs">
                            <span>🛡️</span>
                            <span>إدارة المنصة (Super Admin)</span>
                        </div>
                        <h3 class="text-base font-black">خيارات الإشراف على الحملة</h3>
                        <form method="POST" action="<?php echo e(route('admin.campaigns.status', $campaign)); ?>" class="space-y-3">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <select name="status" class="w-full text-xs bg-white text-slate-900 rounded-xl p-2.5 font-bold">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\CampaignStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($status->value); ?>" <?php echo e($campaign->status === $status ? 'selected' : ''); ?>>
                                        <?php echo e($status->label()); ?>

                                    </option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                            <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition">
                                تحديث حالة الحملة كمسؤول
                            </button>
                        </form>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php else: ?>
                <!-- GUEST CALL TO ACTION -->
                <div class="bg-gradient-to-br from-slate-900 to-emerald-950 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-emerald-900/60 space-y-5 text-center">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-xl">
                        🚀
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-black text-white">هل ترغب بالترويج لهذه الحملة؟</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            سجل حسابك كصانع محتوى أو مسوق أداء في نمولي للحصول على كود التتبع الحصري والبدء بالترويج وفق شروط الحملة.
                        </p>
                    </div>

                    <div class="space-y-2 pt-2">
                        <a href="<?php echo e(route('register', ['role' => 'marketer'])); ?>" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md transition">
                            إنشاء حساب مسوق / صانع محتوى
                        </a>
                        <a href="<?php echo e(route('login')); ?>" class="block w-full py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold border border-white/20 transition">
                            تسجيل الدخول لحسابك
                        </a>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Quick Specs Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">تفاصيل الدعم الفني</h4>
                <div class="space-y-2 text-xs text-slate-600">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span>معرف الحملة:</span>
                        <span class="font-mono text-slate-900 font-bold"><?php echo e(substr($campaign->id, 0, 8)); ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span>نوع التعويض:</span>
                        <span class="font-bold text-slate-900">
                            <?php echo e($campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->label() : $campaign->commission_type); ?>

                        </span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span>الشركاء المعتمدون:</span>
                        <span class="font-bold text-emerald-700"><?php echo e($campaign->approvedApplications->count()); ?> مسوق</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>جاهزية الإسناد:</span>
                        <span class="text-slate-700 font-bold">معرفات تتبع معتمدة (Attribution Ready)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/pages/campaign-detail.blade.php ENDPATH**/ ?>