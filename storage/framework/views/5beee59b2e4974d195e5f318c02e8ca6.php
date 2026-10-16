<?php $__env->startSection('content'); ?>
<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-purple-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold mb-3 border border-purple-400/30">
                <span>🛡️</span>
                <span>لوحة التحكم الإدارية العليا (Super Admin)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">إدارة حملات منصة نمولي</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                الإشراف العام على حملات العلامات التجارية، ومراقبة الالتزام، والتحكم في حالة النشر والتسويق.
            </p>
        </div>

        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs sm:text-sm font-bold border border-white/20 transition cursor-pointer">
                تسجيل الخروج
            </button>
        </form>
    </div>

    <!-- Status Alert -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 font-bold flex items-center gap-2">
            <span class="text-emerald-600 font-black">✓</span>
            <span><?php echo e(session('status')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- System KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي حملات المنصة</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo e($stats['total']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الحملات النشطة في السوق</div>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo e($stats['active']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">الحملات المتوقفة</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?php echo e($stats['paused']); ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-xs text-slate-500 font-bold">إجمالي طلبات الانضمام بالمنصة</div>
            <div class="text-2xl font-black text-purple-600 mt-1"><?php echo e($stats['total_applications']); ?></div>
        </div>
    </div>

    <!-- Campaigns Moderation Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-base font-black text-slate-900">جميع حملات المنصة</h2>

            <form method="GET" action="<?php echo e(route('admin.campaigns.index')); ?>" class="flex items-center gap-2">
                <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="بحث بالعنوان أو المتجر..." class="text-xs rounded-xl border border-slate-300 p-2 w-48">
                <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">بحث</button>
            </form>
        </div>

        <div class="divide-y divide-slate-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $camp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 hover:bg-slate-50/50 transition">
                    <div class="space-y-1.5 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black border <?php echo e($camp->status->badgeClasses()); ?>">
                                <?php echo e($camp->status->label()); ?>

                            </span>
                            <span class="text-xs font-bold text-slate-500">العلامة:</span>
                            <span class="text-xs font-black text-slate-800"><?php echo e($camp->brandProfile?->company_name ?? 'علامة غير معروفة'); ?></span>
                            <span class="text-xs text-slate-400">(<?php echo e($camp->brandProfile?->user?->email); ?>)</span>
                        </div>

                        <h3 class="text-base font-black text-slate-900">
                            <?php echo e($camp->title); ?>

                        </h3>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                            <span>العمولة: <strong class="text-emerald-700"><?php echo e($camp->commission_value); ?></strong></span>
                            <span>•</span>
                            <span>التصنيف: <?php echo e($camp->category); ?></span>
                            <span>•</span>
                            <span>المتقدمون: <strong class="text-purple-700"><?php echo e($camp->applications_count); ?></strong></span>
                            <span>•</span>
                            <span>المعتمدون: <strong class="text-emerald-700"><?php echo e($camp->approved_applications_count); ?></strong></span>
                        </div>
                    </div>

                    <!-- Moderation Status Change Form -->
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="<?php echo e(route('marketplace.show', $camp->slug)); ?>" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">
                            معاينة ↗
                        </a>

                        <form method="POST" action="<?php echo e(route('admin.campaigns.status', $camp)); ?>" class="flex items-center gap-1.5">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <select name="status" class="text-xs rounded-lg border border-slate-300 p-1.5 bg-white font-bold">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\CampaignStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($st->value); ?>" <?php echo e($camp->status === $st ? 'selected' : ''); ?>>
                                        <?php echo e($st->label()); ?>

                                    </option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                            <button type="submit" class="px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                تطبيق
                            </button>
                        </form>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>

        <div class="p-4 border-t border-slate-100">
            <?php echo e($campaigns->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/applet/resources/views/admin/campaigns/index.blade.php ENDPATH**/ ?>