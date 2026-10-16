<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-teal-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold mb-3 border border-teal-400/30">
                <span>🚀</span>
                <span>لوحة تحكم المسوق (Marketer Workspace) • حملاتي وأكواد التتبع</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">أهلاً، <?php echo e(auth()->user()->marketerProfile?->full_name ?? auth()->user()->name); ?></h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                تتبع حالة طلباتك، وإدارة معرفات الإسناد (أكواد الخصم والروابط المخصصة) المعتمدة من العلامات التجارية.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('marketplace')); ?>" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs sm:text-sm font-black shadow-md transition flex items-center gap-2">
                <span>🔍</span>
                <span>تصفح سوق الحملات المتاحة</span>
            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Performance KPIs -->
    <div class="space-y-4">
        <!-- Application Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">إجمالي الحملات المتقدم إليها</div>
                <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e($stats['total']); ?></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">الحملات المعتمدة (النشطة)</div>
                <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo e($stats['approved']); ?></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">طلبات قيد المراجعة</div>
                <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e($stats['pending']); ?></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs text-slate-500 font-bold">طلبات تم الاعتذار عنها</div>
                <div class="text-2xl font-black text-slate-400 mt-1"><?php echo e($stats['rejected']); ?></div>
            </div>
        </div>

        <!-- Real Attribution Performance Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>النقرات المسجلة عبر روابطك</span>
                    <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">فعلي</span>
                </div>
                <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e(number_format($stats['total_clicks'] ?? 0)); ?></div>
                <div class="text-[11px] text-slate-400 mt-0.5 font-medium">زيارات مؤكدة عبر أكوادك الذكية</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>التحويلات والمبيعات المنسوبة</span>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-black">إسناد مؤكد</span>
                </div>
                <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo e(number_format($stats['total_conversions'] ?? 0)); ?></div>
                <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">عمليات شراء تمت بنجاح</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>العمولات المعتمدة المؤكدة</span>
                    <span class="text-[10px] bg-teal-50 text-teal-700 px-1.5 py-0.5 rounded font-bold">مستحقة</span>
                </div>
                <div class="text-2xl font-black text-teal-600 mt-1"><?php echo e(number_format($stats['approved_earnings'] ?? 0, 2)); ?> <span class="text-xs text-slate-500 font-normal">ر.س</span></div>
                <div class="text-[11px] text-teal-700/80 mt-0.5 font-medium">جاهزة للتسوية المالية</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                    <span>عمولات قيد المراجعة والتحقق</span>
                    <span class="text-[10px] bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">معلقة</span>
                </div>
                <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e(number_format($stats['pending_earnings'] ?? 0, 2)); ?> <span class="text-xs text-slate-500 font-normal">ر.س</span></div>
                <div class="text-[11px] text-amber-700/80 mt-0.5 font-medium">بانتهاء فترة المرتجعات</div>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-black text-slate-900">سجل الحملات وأكواد التتبع المخصصة</h2>

            <div class="flex items-center gap-2 text-xs">
                <a href="<?php echo e(route('marketer.campaigns.index')); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(!request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'); ?>">الكل</a>
                <a href="<?php echo e(route('marketer.campaigns.index', ['status' => 'APPROVED'])); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(request('status') === 'APPROVED' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'); ?>">المعتمدة فقط</a>
                <a href="<?php echo e(route('marketer.campaigns.index', ['status' => 'PENDING'])); ?>" class="px-3 py-1.5 rounded-lg font-bold <?php echo e(request('status') === 'PENDING' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700'); ?>">قيد المراجعة</a>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($applications->isEmpty()): ?>
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-600 text-2xl">
                    🎯
                </div>
                <h3 class="text-lg font-black text-slate-900">لم تقدم على أي حملة تسويقية بعد</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    استكشف مئات الحملات المتاحة لكبرى المتاجر والعلامات التجارية في السعودية ومصر والخليج وقدم طلبك الآن.
                </p>
                <a href="<?php echo e(route('marketplace')); ?>" class="inline-block px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                    استكشاف سوق الحملات الآن ←
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="p-6 hover:bg-slate-50/60 transition space-y-5">
                        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black border <?php echo e($app->status->badgeClasses()); ?>">
                                        <?php echo e($app->status->label()); ?>

                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600">
                                        <?php echo e($app->campaign->category); ?>

                                    </span>
                                    <span class="text-xs text-slate-400">العلامة:</span>
                                    <span class="text-xs font-black text-slate-700">
                                        <?php echo e($app->campaign->brandProfile?->company_name ?? 'متجر معتمد'); ?>

                                    </span>
                                </div>

                                <h3 class="text-base font-black text-slate-900">
                                    <a href="<?php echo e(route('marketplace.show', $app->campaign->slug)); ?>" class="hover:text-teal-700 transition">
                                        <?php echo e($app->campaign->title); ?>

                                    </a>
                                </h3>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                                    <span class="text-emerald-700 font-bold">العمولة: <?php echo e($app->campaign->commission_value); ?></span>
                                    <span>•</span>
                                    <span>تاريخ التقديم: <?php echo e($app->applied_at->format('Y-m-d')); ?></span>
                                    <span>•</span>
                                    <span>فترة الكوكيز: <?php echo e($app->campaign->cookie_duration_days); ?> يوماً</span>
                                </div>
                            </div>

                            <a href="<?php echo e(route('marketplace.show', $app->campaign->slug)); ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shrink-0">
                                عرض تفاصيل الحملة والشروط ↗
                            </a>
                        </div>

                        <!-- Approved Credentials Box -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->isApproved()): ?>
                            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-white rounded-2xl p-5 border border-emerald-200/80 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->tracking_code): ?>
                                    <div class="bg-white p-3.5 rounded-xl border border-emerald-200 space-y-1" x-data="{ copied: false }">
                                        <div class="text-[10px] font-bold text-slate-500">كود الخصم المعتمد للحملة:</div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-base font-black text-emerald-900 tracking-wider">
                                                <?php echo e($app->tracking_code); ?>

                                            </span>
                                            <button type="button"
                                                    @click="window.copyToClipboard('<?php echo e($app->tracking_code); ?>').then(() => { copied = true; setTimeout(() => copied = false, 2500); })" title="نسخ كود الخصم" aria-label="نسخ كود الخصم"
                                                    class="text-[11px] px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                                    :class="copied ? 'bg-emerald-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'">
                                                <span x-show="!copied">نسخ الكود</span>
                                                <span x-show="copied" style="display: none;">تم النسخ ✓</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->tracking_link): ?>
                                    <div class="bg-white p-3.5 rounded-xl border border-emerald-200 space-y-1" x-data="{ copied: false }">
                                        <div class="text-[10px] font-bold text-slate-500">رابط التتبع والإحالة الذكي:</div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-xs text-slate-700 truncate">
                                                <?php echo e($app->tracking_link); ?>

                                            </span>
                                            <button type="button"
                                                    @click="window.copyToClipboard('<?php echo e($app->tracking_link); ?>').then(() => { copied = true; setTimeout(() => copied = false, 2500); })" title="نسخ رابط التتبع" aria-label="نسخ رابط التتبع"
                                                    class="text-[11px] px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                                    :class="copied ? 'bg-emerald-700 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'">
                                                <span x-show="!copied">نسخ الرابط</span>
                                                <span x-show="copied" style="display: none;">تم النسخ ✓</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php elseif($app->isPending()): ?>
                            <div class="bg-amber-50 rounded-2xl p-4 border border-amber-200 text-xs text-amber-900 flex items-center gap-2">
                                <span class="font-bold">⏳</span>
                                <span>طلبك قيد مراجعة العلامة التجارية. سيتم إشعارك وتفعيل كودك المخصص فور الاعتماد.</span>
                            </div>
                        <?php elseif($app->isRejected()): ?>
                            <div class="bg-rose-50 rounded-2xl p-4 border border-rose-200 text-xs text-rose-900 flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-bold">✕ تم الاعتذار: </span>
                                    <span><?php echo e($app->review_notes ?? 'نعتذر، لم يتم قبول الطلب وفق متطلبات الحملة الحالية.'); ?></span>
                                </div>
                                <a href="<?php echo e(route('marketplace')); ?>" class="font-bold text-rose-800 underline shrink-0">تصفح بدائل أخرى</a>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <div class="p-4 border-t border-slate-100">
                <?php echo e($applications->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/marketer/campaigns/index.blade.php ENDPATH**/ ?>