<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <a href="<?php echo e(route('brand.campaigns.index')); ?>" class="text-xs font-bold text-slate-500 hover:text-emerald-700 transition">
                ← العودة إلى قائمة الحملات
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">
                طلبات الانضمام لحملة: <?php echo e($campaign->title); ?>

            </h1>
            <p class="text-xs text-slate-500 mt-1">
                مراجعة واعتماد صناع المحتوى والمسوقين وتوليد أكواد التتبع المخصصة.
            </p>
        </div>

        <a href="<?php echo e(route('marketplace.show', $campaign->slug)); ?>" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition">
            معاينة الحملة في السوق ↗
        </a>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Applications List -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900">
                المتقدمون (<?php echo e($applications->total()); ?>)
            </h2>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($applications->isEmpty()): ?>
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 text-2xl">
                    👥
                </div>
                <h3 class="text-base font-black text-slate-900">لا توجد طلبات انضمام لهذه الحملة حتى الآن</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    بمجرد أن يتقدم صناع المحتوى من سوق الحملات، ستظهر طلباتهم هنا لمراجعة قنواتهم واعتمادهم.
                </p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="p-6 space-y-5">
                        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-700 font-black text-lg">
                                    <?php echo e(mb_substr($app->marketerProfile->full_name, 0, 1)); ?>

                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-black text-slate-900"><?php echo e($app->marketerProfile->full_name); ?></h3>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black border <?php echo e($app->status->badgeClasses()); ?>">
                                            <?php echo e($app->status->label()); ?>

                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        التخصص: <span class="font-bold text-slate-700"><?php echo e($app->marketerProfile->primarySpecialization?->specialization?->label() ?? 'مسوق معتمد'); ?></span>
                                        • الدولة: <span class="font-bold text-slate-700"><?php echo e($app->marketerProfile->country ?? 'الشرق الأوسط'); ?></span>
                                        • تاريخ التقديم: <span class="font-bold text-slate-700"><?php echo e($app->applied_at->format('Y-m-d H:i')); ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->isApproved()): ?>
                                <div class="bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-xl text-xs space-y-1">
                                    <div class="text-emerald-900 font-bold">كود التتبع المعتمد:</div>
                                    <div class="font-mono font-black text-emerald-800 text-sm tracking-wider"><?php echo e($app->tracking_code); ?></div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <!-- Pitch Note & Channels -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->pitch_note || !empty($app->channel_urls)): ?>
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3 text-xs">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->pitch_note): ?>
                                    <div>
                                        <div class="font-bold text-slate-700 mb-0.5">مقترح وخطة الترويج (Pitch):</div>
                                        <p class="text-slate-600 leading-relaxed"><?php echo e($app->pitch_note); ?></p>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($app->channel_urls)): ?>
                                    <div>
                                        <div class="font-bold text-slate-700 mb-1">القنوات التسويقية المعلنة:</div>
                                        <div class="flex flex-wrap gap-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $app->channel_urls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <a href="<?php echo e($url); ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-teal-700 hover:underline font-mono text-[11px]">
                                                    <?php echo e($url); ?> ↗
                                                </a>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <!-- Action Form for Pending or Re-review -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->isPending()): ?>
                            <div class="bg-amber-50/50 rounded-2xl p-5 border border-amber-200/80 space-y-4">
                                <div class="text-xs font-black text-slate-900">اتخاذ القرار وتفعيل بيانات التتبع:</div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Approve Form -->
                                    <form method="POST" action="<?php echo e(route('brand.campaigns.applications.review', [$campaign, $app])); ?>" class="bg-white p-4 rounded-xl border border-emerald-200 space-y-3">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="approve">

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                كود الخصم والتتبع (اتركه فارغاً للتوليد التلقائي):
                                            </label>
                                            <input type="text" name="tracking_code" placeholder="مثال: SARA-AHMED-15" class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 p-2">
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                ملاحظة ترحيبية لصانع المحتوى (اختياري):
                                            </label>
                                            <input type="text" name="review_notes" placeholder="أهلاً بك، تم تفعيل كودك المخصص بنجاح" class="w-full text-xs rounded-lg border border-slate-300 p-2">
                                        </div>

                                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black transition cursor-pointer">
                                            ✓ اعتماد المسوق وتفعيل كود التتبع
                                        </button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" action="<?php echo e(route('brand.campaigns.applications.review', [$campaign, $app])); ?>" class="bg-white p-4 rounded-xl border border-rose-200 space-y-3">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="reject">

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                سبب الرفض (سيظهر للمسوق في حسابه):
                                            </label>
                                            <textarea name="review_notes" rows="3" placeholder="نعتذر، نبحث حالياً عن صناع محتوى متخصصين في فئة التجميل فقط..." class="w-full text-xs rounded-lg border border-slate-300 p-2"></textarea>
                                        </div>

                                        <button type="submit" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                            ✕ الاعتذار عن قبول الطلب
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-xs text-slate-500 flex items-center justify-between pt-2 border-t border-slate-100">
                                <div>
                                    تمت المراجعة في: <span class="font-bold"><?php echo e($app->reviewed_at?->format('Y-m-d H:i') ?? '-'); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($app->review_notes): ?>
                                        • ملاحظة: <span class="font-normal italic"><?php echo e($app->review_notes); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/brand/campaigns/applications.blade.php ENDPATH**/ ?>