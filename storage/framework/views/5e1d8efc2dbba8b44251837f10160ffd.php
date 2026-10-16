<?php $__env->startSection('content'); ?>
<div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <span class="text-xs font-black text-emerald-800 bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200/80">
            سوق الحملات والشراكات التسويقية
        </span>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            استكشف حملات الأداء المتاحة للشراكة
        </h1>
        <p class="text-sm text-slate-600 leading-relaxed font-normal">
            اكتشف فرص الحملات التسويقية في المنطقة — دليل حي يجمع حملات العلامات التجارية والمتاجر في السعودية، مصر، والإمارات. ابحث عن الحملة المناسبة لجمهورك وقدم طلبك للبدء في جني العمولات الموثقة.
        </p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs space-y-6">
        <!-- Search bar -->
        <form method="GET" action="<?php echo e(route('marketplace')); ?>" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-grow">
                <input type="text"
                       name="q"
                       value="<?php echo e($search ?? ''); ?>"
                       placeholder="ابحث باسم العلامة التجارية، نوع الحملة، أو المجال..."
                       class="w-full px-4 py-3.5 pr-11 rounded-2xl border border-slate-200 text-sm font-medium focus:outline-hidden focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                <span class="absolute right-4 top-4 text-slate-400">🔍</span>
            </div>

            <button type="submit"
                    class="px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition cursor-pointer">
                بحث في الحملات
            </button>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($search) || ($selectedCategory ?? 'all') !== 'all' || ($selectedCountry ?? 'all') !== 'all'): ?>
                <a href="<?php echo e(route('marketplace')); ?>"
                   class="px-5 py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm text-center transition">
                    إلغاء التصفية
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </form>

        <!-- Category Filter Tabs -->
        <div class="space-y-3">
            <div>
                <div class="text-xs font-bold text-slate-500 mb-2">القطاعات والمجالات:</div>
                <div class="flex flex-wrap gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('marketplace', array_merge(request()->query(), ['category' => $cat['slug']]))); ?>"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition <?php echo e(($selectedCategory ?? 'all') === $cat['slug'] ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                            <?php echo e($cat['name']); ?>

                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            <!-- Country Filter Tabs -->
            <div class="pt-2 border-t border-slate-100">
                <div class="text-xs font-bold text-slate-500 mb-2">الدولة والسوق المستهدف:</div>
                <div class="flex flex-wrap gap-2">
                    <a href="<?php echo e(route('marketplace', array_merge(request()->query(), ['country' => 'all']))); ?>"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?php echo e(($selectedCountry ?? 'all') === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'); ?>">
                        جميع الأسواق
                    </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['السعودية', 'مصر', 'الإمارات', 'الكويت']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('marketplace', array_merge(request()->query(), ['country' => $country]))); ?>"
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?php echo e(($selectedCountry ?? 'all') === $country ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'); ?>">
                            <?php echo e($country); ?>

                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns Count & Info -->
    <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
        <span>عرض <strong><?php echo e(count($campaigns)); ?></strong> حملة متوافقة مع شروط البحث</span>
        <span>تحديث مستمر للفرص والشراكات</span>
    </div>

    <!-- Campaigns Grid -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($campaigns) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $cId = is_object($campaign) ? ($campaign->slug ?? $campaign->id) : ($campaign['slug'] ?? $campaign['id']);
                    $cTitle = is_object($campaign) ? $campaign->title : $campaign['title'];
                    $cBrand = is_object($campaign) ? ($campaign->brandProfile?->company_name ?? 'علامة موثقة') : $campaign['brand_name'];
                    $cCategory = is_object($campaign) ? $campaign->category : $campaign['category'];
                    $cDescription = is_object($campaign) ? $campaign->description : $campaign['description'];
                    $cCommission = is_object($campaign) ? $campaign->commission_value : $campaign['commission'];
                    $cCommissionType = is_object($campaign) ? ($campaign->commission_type instanceof \App\Enums\CampaignCommissionType ? $campaign->commission_type->label() : $campaign->commission_type) : $campaign['commission_type'];
                    $cMarkets = is_object($campaign) ? (is_array($campaign->target_markets) ? $campaign->target_markets : [$campaign->target_markets]) : $campaign['target_markets'];
                    $cTracking = is_object($campaign) ? (is_array($campaign->tracking_methods) ? $campaign->tracking_methods : [$campaign->tracking_methods]) : $campaign['tracking_methods'];
                    $cCookieDays = is_object($campaign) ? $campaign->cookie_duration_days : $campaign['cookie_days'];
                ?>
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs hover:border-emerald-300 hover:shadow-md transition p-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <!-- Top Metadata: Brand & Category -->
                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 block uppercase tracking-wider">العلامة التجارية</span>
                                <span class="font-black text-slate-900 text-sm"><?php echo e($cBrand); ?></span>
                            </div>
                            <span class="font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100 text-xs">
                                <?php echo e($cCategory); ?>

                            </span>
                        </div>

                        <!-- Campaign Title & Description -->
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

                        <!-- Highlights Box -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">الهدف والنموذج:</span>
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
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">فترة إسناد الكوكيز:</span>
                                <span class="font-bold text-slate-700"><?php echo e($cCookieDays); ?> يوماً</span>
                            </div>
                        </div>

                        <!-- Compensation -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 text-xs">
                            <span class="text-slate-600 font-medium">العائد المرتبط بالنتيجة:</span>
                            <span class="font-black text-emerald-700 text-sm"><?php echo e($cCommission); ?></span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="pt-5">
                        <a href="<?php echo e(route('marketplace.show', $cId)); ?>"
                           class="block text-center w-full py-2.5 rounded-xl bg-slate-900 hover:bg-emerald-600 text-white font-bold text-xs transition">
                            عرض تفاصيل الحملة والتقديم ←
                        </a>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_object($campaigns) && method_exists($campaigns, 'links')): ?>
            <div class="pt-4">
                <?php echo e($campaigns->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php else: ?>
        <div class="text-center py-16 bg-white rounded-3xl border border-slate-200 p-8">
            <span class="text-4xl block mb-3">🔍</span>
            <h3 class="text-lg font-black text-slate-900 mb-2">لم يتم العثور على حملات مطابقة</h3>
            <p class="text-sm text-slate-500 mb-6">جرب تعديل كلمات البحث أو اختيار مجال مختلف من القائمة أعلاه.</p>
            <a href="<?php echo e(route('marketplace')); ?>" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs">
                عرض جميع الحملات
            </a>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Marketplace Callout for Brands -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-3xl p-8 sm:p-10 text-white flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 text-center md:text-right">
            <h3 class="text-xl font-black">هل تمتلك متجراً أو علامة تجارية وترغب بإطلاق حملتك؟</h3>
            <p class="text-xs text-slate-400 max-w-xl">
                انضم إلى نمولي اليوم، وحدد شروط الأداء والعمولات، وافتح الباب أمام شبكة معتمدة من المسوقين وصناع المحتوى بدون أي مخاطرة مالية مسبقة.
            </p>
        </div>
        <a href="<?php echo e(route('register', ['role' => 'BRAND'])); ?>"
           class="px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shrink-0 transition">
            سجل علامتك التجارية وأنشئ حملتك
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/pages/marketplace.blade.php ENDPATH**/ ?>